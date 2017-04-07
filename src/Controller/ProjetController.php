<?php

namespace Controller ;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class ProjetController
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
        if (file_exists($this->config['projectsConfig'])) {
            $projects = file_get_contents($this->config['projectsConfig']);
        }

        // render view
        $res = $this->renderer->render($response, 'projet/index.phtml', [
            'projets' => $this->projets->get(),
            'messages' => $this->flash->getMessages(),
        ]);
        return $res;
    }
    
    public function nouveau(Request $request, Response $response, $args)
    {
        $id = '';
        // Si id dans la query, on le récupère
        $qp = $request->getQueryParams() ;
        if (isset($qp['id'])) {
            $id = $qp['id'];
        }
        // Si id dans la session, on le récupère
        if (isset($this->flash->getmessage('id')[0])) {
            $id = $this->flash->getmessage('id')[0] ;
        }
        $qp = $request->getQueryParams() ;
        // render view
        $res = $this->renderer->render($response, 'projet/nouveau.phtml', [
            'messages' => $this->flash->getMessages(),
            'id' => $id
        ]);
        return $res;
    }
    
    public function nouveauPost(Request $request, Response $response, $args)
    {
        $id = '';
        if (isset($request->getParsedBody()['identifiant'])){
            $id = $request->getParsedBody()['identifiant'];
        }
        // Si id a moins de 5 caractères, ça le fait pas
        if (strlen ($id) <5 ) {
            $this->flash->addMessage('error', "L'identifiant ne semble pas valide (trop court).");
            $this->flash->addMessage('id', $id);
            return $response->withStatus(302)->withHeader('Location', '/projet/nouveau');
        }
        // Si on au une url Gallica
        $baseUrl = 'http://gallica.bnf.fr/ark:/12148/';
        if (strncmp($baseUrl, $id, strlen($baseUrl)) === 0) {
            preg_match('@^(?:'. $baseUrl . ')?([a-z0-9]+)@',$id, $matches);
            if (isset($matches[1])){
                $id = $matches[1];
            }
        }
        // On ne garde que les caractères alphanum de l'id
        $id = preg_replace('/[^\da-z]/i', '', $id);
        
        $resource = $this->gd->info($id);
        // Si erreur
        if (isset($resource['error'])) {
            $this->flash->addMessage('error', $resource['error']);
            $this->flash->addMessage('id', $id);
            return $response->withStatus(302)->withHeader('Location', '/projet/nouveau');
        }
        
        $resource['options'] = [
            'nbImagesLigne' => 6,
            'premierePage' => 1,
            'dernierePage' => count($resource['todo']),
            'suffixe' => '',
        ];
        $resource['source'] = [
            'x' => 0,
            'y' => 0,
            'w' => 0,
            'h' => 0,
        ];
        $resource['destination'] = [
            'maxW' => $resource['w']['mean'] - $resource['w']['ecart'],
            'maxH' => $resource['h']['mean'] -  $resource['h']['ecart'],
        ];
        $resource['downloaded'] = [];
        
        // Mettre le nouveau projet dans la config
        $this->projets->update($resource);
        $this->flash->addMessage('succes', "Le projet a été créé sans erreur.");
        
        $url = $this->router->pathFor('projetOptions', [], ['id' =>  $resource['id']]);
        return $response->withStatus(302)->withHeader('Location', $url);
    }
    
    public function options(Request $request, Response $response, $args)
    {
        $qp = $request->getQueryParams() ;
        if (!isset($qp['id'])) {
            $this->flash->addMessage('error', "Pas d'identifiant trouvé.");
            $url = $this->router->pathFor('home');
            return $response->withStatus(302)->withHeader('Location', $url);
        }
        $id = $qp['id'] ;

        $projet = $this->projets->get($id);
        // render view
        $res = $this->renderer->render($response, 'projet/options.phtml', [
            'id' => $id,
            'statsW' => $projet['w'],
            'statsH' => $projet['h'],
            'nbVues' => $projet['nbVues'],
            'options' => $projet['options'],
            'source' => $projet['source'],
            'destination' => $projet['destination'],
            'messages' => $this->flash->getMessages()
        ]);
        return $res;
    }
    
    public function optionsPost(Request $request, Response $response, $args)
    {
        $params = $request->getParsedBody();
        
        if (!isset ($params['identifiant'])) {
            $this->flash->addMessage('error', "Pas d'identifiant trouvé.");
            $url = $this->router->pathFor('home');
            return $response->withStatus(302)->withHeader('Location', $url);
        }
        // On charge le projet
        $id = $params['identifiant'] ;
        $projet = $this->projets->get($id);
        // On merge le formulaire (valeurs converties en entier) avec les données actuelles
        $projet['options'] = array_merge($projet['options'], array_map('intval', $params['options']));
        $projet['options']['suffixe'] = $params['suffixe'];
        $projet['source'] = array_merge($projet['source'], array_map('intval', $params['source']));
        $projet['destination'] = array_merge($projet['destination'], array_map('intval', $params['destination']));
        
        $this->projets->update($projet);
        $this->flash->addMessageNow('succes', "Les options du projet ont été correctement enregistrées.");
        // render view
        $res = $this->renderer->render($response, 'projet/options.phtml', [
            'id' => $id,
            'statsW' => $projet['w'],
            'statsH' => $projet['h'],
            'nbVues' => $projet['nbVues'],
            'options' => $projet['options'],
            'source' => $projet['source'],
            'destination' => $projet['destination'],
            'messages' => $this->flash->getMessages()
        ]);
        return $res;
    }

}