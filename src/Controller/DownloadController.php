<?php

namespace Controller ;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class DownloadController
{
    protected $logger ;
    protected $renderer ;
    protected $config ;
    protected $projets ;
    protected $flash ;
    protected $router ;
    protected $gallicaDownloader ;

    public function __construct($config)
    {
        $this->projets = $config['projets'];
        $this->logger = $config['logger'];
        $this->renderer = $config['renderer'];
        $this->flash = $config['flash'];
        $this->gallicaDownloader = $config['gallicaDownloader'] ;
        $this->router = $config['router'];
    }
    
    public function index(Request $request, Response $response)
    {
        $projetId = '';
        // Si id dans la query, on le récupère
        $params = $request->getQueryParams() ;
        if (isset($params['id'])) {
	        $projetId = $params['id'];
        }
        $projet = $this->projets->get($projetId);
        
        if ($projet === null) {
            $this->flash->addMessage('error', "Le projet $projetId n'a pas été trouvé.");
            $this->flash->addMessage('id', $projetId);
            return $response->withStatus(302)->withHeader('Location', '/');
        }
        
        // render view
        $res = $this->renderer->render($response, 'download/index.phtml', [
            'projet' => $projet,
            'messages' => $this->flash->getMessages(),
        ]);
        return $res;
    }
    
    public function getNext(Request $request, Response $response)
    {
        $params = $request->getParsedBody();
        if (!isset ($params['id'])) {
            return $this->jsonResponseError($response, "Pas d'identifiant trouvé dans la requête.");
        }
        // On charge le projet
	    $projetId = $params['id'] ;
        $projet = $this->projets->get($projetId);
        if ($projet === null) {
            return $this->jsonResponseError($response, "Aucun projet trouvé avec l'identifiant $projetId.");
        }
        // Get first todo image
        $tmpArray = array_reverse($projet['todo']);
        $image = array_pop($tmpArray);
        unset($tmpArray);
        $options = [
            'id' => $projetId,
            'suffixe' => $projet['options']['suffixe']
        ];
        // Téléchargement de l'image
        $result = $this->gallicaDownloader->download($image, $projet['source'], $projet['destination'], $options);
        
        if ($result['result'] === 'success'){
            // Mise à jour de l'image
            $image['filename'] = $result['filename'];
            // On met l'image dans les downloaded et on l'enlève des todo.
            $projet['downloaded'][$image['page']] = $image;
            unset($projet['todo'][$image['page']]);
            // Sauvegarde du projet
            $this->projets->update($projet);
            return $this->jsonResponse($response, [
                'result' => 'success',
                'message' => "Image ${image['page']} téléchargée.",
                'nbTodo' => count($projet['todo']),
                'nbDownloaded' => count($projet['downloaded']),
                'filename' => $result['filename']
            ]);
        }
        
        var_dump($result);
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

}