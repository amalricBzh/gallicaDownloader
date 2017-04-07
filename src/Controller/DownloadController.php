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
    // GallicaDownloader
    protected $gd ;

    public function __construct($c)
    {
        $this->projets = $c['projets'];
        $this->logger = $c['logger'];
        $this->renderer = $c['renderer'];
        $this->flash = $c['flash'];
        $this->gd = $c['gallicaDownloader'] ;
        $this->router = $c['router'];
    }
    
    public function index(Request $request, Response $response, $args)
    {
        $id = '';
        // Si id dans la query, on le récupère
        $qp = $request->getQueryParams() ;
        if (isset($qp['id'])) {
            $id = $qp['id'];
        }
        $projet = $this->projets->get($id);
        
        if ($projet === null) {
            $this->flash->addMessage('error', "Le projet $id n'a pas été trouvé.");
            $this->flash->addMessage('id', $id);
            return $response->withStatus(302)->withHeader('Location', '/');
        }
        
        // render view
        $res = $this->renderer->render($response, 'download/index.phtml', [
            'projet' => $projet,
            'messages' => $this->flash->getMessages(),
        ]);
        return $res;
    }
    
    public function getNext(Request $request, Response $response, $args)
    {
        $params = $request->getParsedBody();
        if (!isset ($params['id'])) {
            return $this->jsonResponseError($response, "Pas d'identifiant trouvé dans la requête.");
        }
        // On charge le projet
        $id = $params['id'] ;
        $projet = $this->projets->get($id);
        if ($projet === null) {
            return $this->jsonResponseError($response, "Aucun projet trouvé avec l'identifiant $id.");
        }
        // Get first todo image
        $tmpArray = array_reverse($projet['todo']);
        $image = array_pop($tmpArray);
        unset($tmpArray);
        $options = [
            'id' => $id,
            'suffixe' => $projet['options']['suffixe']
        ];
        // Téléchargement de l'image
        $result = $this->gd->download($image, $projet['source'], $projet['destination'], $options);
        
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