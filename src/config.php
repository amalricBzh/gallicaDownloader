<?php

return [
    'settings' => [
        'displayErrorDetails' => true,
        'addContentLengthHeader' => false,  // Fait par le serveur web
        
        // Monolog
        'logger' => [
            'name' => 'error',
            'path' => __DIR__ . '/../data/logs/error.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
        'logger' => [
            'name' => 'main',
            'path' => __DIR__ . '/../data/logs/main.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
        
        // Renderer
        'renderer' => [
            'templatePath' => __DIR__ . '/../templates/'
        ],
        
        // Divers
        'configFile' => __DIR__ . '/../data/config.json',
        'projectsPath' => __DIR__.'/../data/downloads/',
        'projectsConfig'  => __DIR__.'/../data/downloads/config.json',
        'gallicaBaseUrl' => "http://gallica.bnf.fr/iiif/ark:/12148",
    ]
];
