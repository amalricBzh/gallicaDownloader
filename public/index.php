<?php

require '../vendor/autoload.php';

session_start();

// Autoloader
spl_autoload_register(function ($classname) {
	require ("../src/" . $classname . ".php");
});

// Create the application
$settings = require __DIR__ . '/../src/config.php';
$app = new \Slim\App($settings);

// DI Container
require __DIR__ . '/../src/dependencies.php';

// Middlewares
require __DIR__ . '/../src/middleware.php';

// Routes
require __DIR__ . '/../src/routes.php';

// Start
$app->run();
