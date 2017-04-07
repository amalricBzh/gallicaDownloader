<?php

namespace Service;

class ProjetService
{
    protected $config ;
    protected $projects = null ;
    
    public function __construct($config)
    {
        $this->config = $config ;
        
        // Init par défaut
        $this->projects = [
            'nbProjects' => 0,
            'projects' => []
        ];
        // Le fichier existe-t-il ? Si oui, on le charge, sinon, on écrit le fichier par défaut
        if (file_exists($this->config['projectsConfig'])){
            $this->projects = $this->read();
        } else {
            $this->write();
        }
    }
    
    public function get($id = null)
    {
        
        // Si on a demandé un projet spécifique
        if ($id !== null) {
            if (isset($this->projects['projects'][$id])){
                return $this->projects['projects'][$id] ;
            }
            // On a demandé un projet spécifique mais qui n'a pas été trouvé
            return null ;
        }
        // On retourne tous les projets
        return $this->projects['projects'];
    }
    
    public function update($project)
    {
        $project['nbDownloaded'] = count($project['downloaded']);
        $this->projects['projects'][$project['id']] = $project ;
        $this->projects['nbProjects'] = count($this->projects['projects']);
        $this->write();
    }
    
    public function write()
    {
        @file_put_contents($this->config['projectsConfig'], json_encode($this->projects,  JSON_PRETTY_PRINT));
    }
    
    public function read()
    {
        return json_decode(file_get_contents($this->config['projectsConfig']), true);
    }
}