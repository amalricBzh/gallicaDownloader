<?php

namespace Controller ;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class GoogleDriveController
{
    protected $logger ;
    protected $renderer ;
    protected $config ;
    protected $projets ;
    protected $flash ;
    protected $router ;
    protected $client ;

    public function __construct($c)
    {
        $this->projets = $c['projets'];
        $this->logger = $c['logger'];
        $this->renderer = $c['renderer'];
        $this->flash = $c['flash'];
        $this->config = $c['settings'] ;
        $this->router = $c['router'];
        
        // Request auth
        $this->client = new \Google_Client();
        $this->client->setAuthConfig($this->config['googleDrive']['jsonSecret']);
        $this->client->setAccessType("offline");        // offline access
        $this->client->setIncludeGrantedScopes(true);   // incremental auth
        $this->client->addScope(\Google_Service_Drive::DRIVE_FILE);
        $this->client->setRedirectUri('http://' . 'localhost' . '/gallicaDownloader/googleDrive/oAuth');
    }
    
    public function index(Request $request, Response $response, $args)
    {
        $projetId = '';
        // Si id dans la query, on le récupère
        $queryParams = $request->getQueryParams() ;
        if (isset($queryParams['id'])) {
            $projetId = $queryParams['id'];
        }
        // Enregistrement de l'ID en session
        $session = new \RKA\Session();
        $session->set('projetId', $projetId);
        
        $auth_url = $this->client->createAuthUrl();
        return $response->withStatus(302)->withHeader('Location', $auth_url. '&state='.$projetId);
        
    }
    
    public function oAuth(Request $request, Response $response, $args)
    {
        // Si id dans la query, on le récupère
        $queryParams = $request->getQueryParams() ;
        
        if (isset($queryParams['error'])) {
            $this->flash->addMessage('error', "Une erreur est survenue lors de la connexion au drive : " .$queryParams['error']);
            return $response->withStatus(302)->withHeader('Location', '/');
        }
        // Exchange authorization code for refresh and access tokens
        $this->client->authenticate($queryParams['code']);
        $accessToken = $this->client->getAccessToken();
        return $response->withStatus(302)->withHeader('Location', 'http://gd.localhost/googleDrive/upload?token=' . serialize($accessToken));
    }
    
    public function upload(Request $request, Response $response, $args)
    {
        // Get session Id et projet
        $session = new \RKA\Session();
        $projetId = $session->get('projetId', '') ;
        $projet = $this->projets->get($projetId);
       
        if ($projet === null) {
            $this->flash->addMessage('error', "Le projet $projetId n'a pas été trouvé.");
            $this->flash->addMessage('id', $projetId);
            return $response->withStatus(302)->withHeader('Location', '/');
        }
        
        // AccessToken passé en paramètre
        $queryParams = $request->getQueryParams() ;
        $accessToken = 'Unknown';
        if (isset($queryParams['token'])) {
            $accessToken = unserialize($queryParams['token']);
        }
        // Set accessToken
        $this->client->setAccessToken($accessToken);
        $service = new \Google_Service_Drive($this->client);
        
        // Répertoire GallicaDownloader (avec création s'il n'existe pas)
        $gallicaDownloaderId = $this->getDirectoryId($service, 'GallicaDownloader', 'root');
        // Répertoire du projet (avec création si nécessaire)
        $folderId = $this->getDirectoryId($service, $projetId, $gallicaDownloaderId);
         
        // render view
        return $this->renderer->render($response, 'googleDrive/upload.phtml', [
            'projet' => $projet,
            'messages' => $this->flash->getMessages(),
            'accessToken' => base64_encode(serialize($accessToken)),
            'folderId' => $folderId
        ]);
    }
    
    public function getNext(Request $request, Response $response)
    {
        $params = $request->getParsedBody();
        if (!isset ($params['id'])) {
            return $this->jsonResponseError($response, "Pas d'identifiant trouvé dans la requête.");
        }
        if (!isset ($params['accessToken'])) {
            return $this->jsonResponseError($response, "Pas de Token trouvé dans la requête.");
        }
        if (!isset ($params['folderId'])) {
            return $this->jsonResponseError($response, "Pas de FolderId parent trouvé dans la requête.");
        }
        // On charge le projet
	    $projetId = $params['id'] ;
        $projet = $this->projets->get($projetId);
        if ($projet === null) {
            return $this->jsonResponseError($response, "Aucun projet trouvé avec l'identifiant $projetId.");
        }
        // Il y a au moins une image à télécharger
        if (count($projet['downloaded']) === 0) {
            return $this->jsonResponseError($response, "Toutes les images ont été envoyées sur GoogleDrive !");
        }

        // Set accessToken from request
        $this->client->setAccessToken(unserialize(base64_decode($params['accessToken'])));
        $service = new \Google_Service_Drive($this->client);
        
        // Get first todo image
        $tmpArray = array_reverse($projet['downloaded']);
        $image = array_pop($tmpArray);
        unset($tmpArray);
        
        // Envoi sur le Drive
        $source = $this->config['projectsPath'] . $projet['id'] .'/images/' . $image['filename'] ;
        $result = $this->sendImageToDrive($service, $image['filename'], $params['folderId'], $source);
        
        if (isset($result['id'])){
            // Mise à jour de l'image
            $image['filename'] = $result['name'];
            // On met l'image dans les downloaded et on l'enlève des todo.
            $projet['googleDrive'][$image['page']] = $image;
            unset($projet['downloaded'][$image['page']]);
            // Supprimer aussi l'image du disque
            
            // Sauvegarde du projet
            $this->projets->update($projet);
            return $this->jsonResponse($response, [
                'result' => 'success',
                'message' => "Image ${image['page']} téléchargée sur GoogleDrive.",
                'nbDownloaded' => count($projet['downloaded']),
                'nbGoogleDrive' => count($projet['googleDrive']),
                'filename' => $result['filename']
            ]);
        }
        
        return $this->jsonResponseError($response, "Erreur lors du transfert de l'image ". $image['filename'] . "sur GoogleDrive.");
    }
    
    protected function jsonResponseError($response, $errorMessage)
    {
        return $this->jsonResponse($response, [
            'result' => 'error',
            'message' => $errorMessage
        ]);
    }
    
    protected function jsonResponse($response, $json)
    {
        $body = $response->getBody();
        $body->write(json_encode($json));
        
        return $response->withHeader('Content-Type','application/json')->withBody($body);
    }
    
    protected function getDirectoryId($service, $directory, $parentId)
    {
        // Ask Google drive for directory
        $response = $service->files->listFiles(array(
            'q' => "mimeType='application/vnd.google-apps.folder' and name='$directory' and trashed=false and '$parentId' in parents",
            'spaces' => 'drive',
            'fields' => 'files(id)',
        ));
        // Si une réponse, on renvoie son id (ou l'id de la première ressource trouvée)
        if (count($response) >0){
            return $response[0]->id;
        }
        // Sinon, on créé le répertoire
        $metadata = new \Google_Service_Drive_DriveFile([
            'name' => $directory,
            'parents' => [$parentId],
            'mimeType' => 'application/vnd.google-apps.folder'
        ]);        
        $folder = $service->files->create($metadata, [
            'fields' => 'id'
        ]);
        
        return $folder->id ;
    }
    
    protected function sendImageToDrive($service, $filename, $parentId, $source)
    {
        // On vérifie que l'image existe
        $response = $service->files->listFiles(array(
            'q' => "mimeType='image/jpeg' and name='$filename' and trashed=false and '$parentId' in parents",
            'spaces' => 'drive',
            'fields' => 'files(id)',
        ));
        // Si une réponse, on la met à jour
        if (count($response) >0){
            
            $file = new \Google_Service_Drive_DriveFile([
                'fileId' => $response[0]->id,
                'name' => $filename,
                'mimeType' => 'image/jpeg',
            ]);

            $result = $service->files->update(
                $response[0]->id,
                $file,
                [
                  'data' => file_get_contents($source),
                  'uploadType' => 'multipart'
                ]
            );
            return $result;
        }
       
        // C'est une création
        $file = new \Google_Service_Drive_DriveFile([
            'name' => $filename,
            'mimeType' => 'image/jpeg',
            'parents' => [$parentId]
        ]);
        $result = $service->files->create(
            $file,
            [
              'data' => file_get_contents($source),
              'uploadType' => 'multipart'
            ]
        );
        return $result;
    }
    
  
}