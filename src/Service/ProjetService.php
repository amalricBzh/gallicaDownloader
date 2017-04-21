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
            'nb' => 0,
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
    
    public function update(&$project)
    {
        $project['downloaded']['size'] = 0 ;
        foreach ($project['downloaded']['images'] as $image) {
            $project['downloaded']['size'] += $image['filesize'];
        }
        $project['todo']['nb'] = count($project['todo']['images']);
        $project['downloaded']['nb'] = count($project['downloaded']['images']);
        $project['googleDrive']['nb'] = count($project['googleDrive']['images']);
        $this->projects['projects'][$project['id']] = $project ;
        $this->write();
        return $project ;
    }
    
    public function delete($projectId)
    {
        unset($this->projects['projects'][$projectId]) ;
        $this->rmDir($this->config['projectsPath'].'/' .$projectId);
        $this->write();
    }
    
    public function write()
    {
	    if (!is_dir($this->config['projectsPath'])) {
		    mkdir($this->config['projectsPath'], 0777, true);
	    }
        $this->projects['nb'] = count($this->projects['projects']);
        file_put_contents($this->config['projectsConfig'], json_encode($this->projects,  JSON_PRETTY_PRINT));
    }
    
    public function read()
    {
        return json_decode(file_get_contents($this->config['projectsConfig']), true);
    }
    
    protected function rmDir($dir) {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            echo " *** $file ";
          (is_dir("$dir/$file")) ? $this->rmDir("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }
}
