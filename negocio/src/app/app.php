<?php

use Slim\Factory\AppFactory;
//use DI\Container;

require __DIR__ . '/../../vendor/autoload.php';

//$container = new Container();

// Set container to create App with on AppFactory
//AppFactory::setContainer($container);

$app = AppFactory::create();

$app->addRoutingMiddleware();

//require 'config.php';
require_once 'routes.php';

$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$app->run();