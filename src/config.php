<?php

return [
    'settings' => [
        'displayErrorDetails' => true,
        'addContentLengthHeader' => false,  // Fait par le serveur web
        
        // Monolog
        'errorLogger' => [
            'name' => 'error',
            'path' => __DIR__ . '/../data/logs/error.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
        'mainLogger' => [
            'name' => 'main',
            'path' => __DIR__ . '/../data/logs/main.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
        
        // Renderer
        'renderer' => [
            'templatePath' => __DIR__ . '/../templates/'
        ],

        // google Drive
        'googleDrive' => [
            'jsonSecret' => __DIR__ . '/../data/auth/client_id.json',
        ],
        
        // Divers
        'configFile' => __DIR__ . '/../data/config.json',
        'projectsPath' => __DIR__.'/../data/downloads/',
        'projectsConfig'  => __DIR__.'/../data/downloads/config.json',
        'gallicaBaseUrl' => "http://gallica.bnf.fr/iiif/ark:/12148",
    ]
];
