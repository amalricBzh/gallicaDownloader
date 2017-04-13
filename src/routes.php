<?php

// Home
$app->get('/', Controller\ProjetController::class . ':index')->setName('home');

// Projets
$app->get('/projet', Controller\ProjetController::class . ':index')->setName('projet');
$app->get('/projet/nouveau', Controller\ProjetController::class . ':nouveau')->setName('projetNouveau');
$app->post('/projet/nouveau', Controller\ProjetController::class . ':nouveauPost');
$app->get('/projet/options', Controller\ProjetController::class . ':options')->setName('projetOptions');
$app->post('/projet/options', Controller\ProjetController::class . ':optionsPost');
// Téléchargements
$app->get('/download', Controller\DownloadController::class . ':index')->setName('download');
$app->post('/download/next', Controller\DownloadController::class . ':getNext')->setName('downloadNext');
// Upload GoogleDrive
$app->get('/googleDrive', Controller\GoogleDriveController::class . ':index')->setName('googleDrive');
$app->get('/gallicaDownloader/googleDrive/oAuth', Controller\GoogleDriveController::class . ':oAuth')->setName('googleDriveOAuth');
$app->get('/googleDrive/upload', Controller\GoogleDriveController::class . ':upload')->setName('upload');
$app->post('/googleDrive/next', Controller\GoogleDriveController::class . ':getnext')->setName('uploadNext');