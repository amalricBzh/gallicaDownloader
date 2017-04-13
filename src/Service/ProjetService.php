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
        // Le fichier existe-t-il ? Si non, on écrit le fichier par défaut
        if (!file_exists($this->config['projectsConfig'])){
	        $this->write();
        }
	    $this->projects = $this->read();
    }
    
    public function get($projetId = null)
    {
        // Si on a demandé un projet spécifique
        if ($projetId !== null) {
            if (isset($this->projects['projects'][$projetId])){
                return $this->projects['projects'][$projetId] ;
            }
            // On a demandé un projet spécifique mais qui n'a pas été trouvé
            return null ;
        }
        // On retourne tous les projets
        return $this->projects['projects'];
    }
    
    public function update($project)
    {
        $project['nbTodo'] = count($project['todo']);
        $project['nbDownloaded'] = count($project['downloaded']);
        $project['nbGoogleDrive'] = count($project['googleDrive']);
        $this->projects['projects'][$project['id']] = $project ;
        $this->projects['nbProjects'] = count($this->projects['projects']);
        $this->write();
    }
    
    public function write()
    {
	    if (!is_dir($this->config['projectsPath'])) {
		    mkdir($this->config['projectsPath'], 0777, true);
	    }
        file_put_contents($this->config['projectsConfig'], json_encode($this->projects,  JSON_PRETTY_PRINT));
    }
    
    public function read()
    {
        return json_decode(file_get_contents($this->config['projectsConfig']), true);
    }
}