<?php

require __DIR__ . '/../../../vendor/autoload.php';

Tester\Environment::setup();
date_default_timezone_set('Europe/Prague');

$configurator = new Nette\Configurator;

$configurator->enableDebugger(__DIR__ . '/log');
$configurator->setTempDirectory(__DIR__ . '/temp');

$configurator->createRobotLoader()
    ->addDirectory(__DIR__ . '/../app')
    ->addDirectory(__DIR__ . '/../libs')
    ->register();

$configurator->addConfig(__DIR__ . '/../app/config/config.neon');
/*$configurator->addConfig(__DIR__ . '/../app/config/parameters.neon');
$configurator->addConfig(__DIR__ . '/../app/config/services.neon');*/
$configurator->addConfig(__DIR__ . '/../app/config/config.local.neon');

$container = $configurator->createContainer();

return $container;