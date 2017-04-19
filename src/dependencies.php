<?php

$container = $app->getContainer();

// Logger : Monolog
$container['mainLogger'] = function (\Slim\Container $c) {
    $config = $c->get('settings')['mainLogger'] ;
    $logger = new \Monolog\Logger($config['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());   // Créée un id unique pour l'instance du Logger
    $fileHandler = new \Monolog\Handler\StreamHandler($config['path'], $config['level']);
    $logger->pushHandler($fileHandler);
    return $logger;
};
$container['errorLogger'] = function (\Slim\Container $c) {
	$config = $c->get('settings')['errorLogger'] ;
	$logger = new \Monolog\Logger($config['name']);
	$logger->pushProcessor(new Monolog\Processor\UidProcessor());   // Créée un id unique pour l'instance du Logger
	$fileHandler = new \Monolog\Handler\StreamHandler($config['path'], $config['level']);
	$logger->pushHandler($fileHandler);
	return $logger;
};

// Flash messages
$container['flash'] = function () {
    return new \Slim\Flash\Messages();
};

// Vues
$container['renderer'] = function(\Slim\Container $container) {
    $config = $container->get('settings')['renderer'] ;
    $renderer = new \Slim\Views\PhpRenderer($config['templatePath']);
    return $renderer;
};

// Csrf
$container['csrf'] = function (/*\Slim\Container $container*/) {
    $guard = new \Slim\Csrf\Guard();
    // callback en cas d'erreur
    $guard->setFailureCallable(function ($request, $response, $next) {
        // Cette callback met l'attribut csrfStatus à false
        $request = $request->withAttribute("csrfStatus", false);
        return $next($request, $response);
    });
    return $guard;
};


// Projet service
$container['projets'] = function(\Slim\Container $container) {
    return new \Service\ProjetService($container['settings']);
};

// Gallica Downloader service
$container['gallicaDownloader'] = function(\Slim\Container $container) {
    return new \Service\GallicaDownloaderService($container['settings']);
};

// Unités service
$container['unitesService'] = function(\Slim\Container $container) {
    return new \Service\UnitesService($container['settings']);
};



