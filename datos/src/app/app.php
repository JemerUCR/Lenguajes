<?php
use Slim\Factory\AppFactory;
use DI\Container;
//use Dotenv;

require __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable('/var/www/html');
$dotenv->load();

$container = new Container();

// Set container to create App with on AppFactory
AppFactory::setContainer($container);

$app = AppFactory::create();

$app->addRoutingMiddleware();

require 'config.php';

/*$app->add(new Tuupola\Middleware\JwtAuthentication([
    "secure"=> false,
    "path" => ["/cliente", "/artefacto"],
    "ignore" => ["/cliente/read"],
    "secret" => $container->get('key'),
    "algorithm" => ["amce" => 'HS256']
]));*/

require 'conexion.php';
require_once 'routes.php';

$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$app->run();