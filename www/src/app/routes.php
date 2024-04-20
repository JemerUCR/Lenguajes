<?php
namespace App\controllers;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// require __DIR__ . '/../controllers/Cliente.php';

$app->post('/cliente', Cliente::class . ':create');

$app->get('/cliente/{id}', Cliente::class . ':buscar');
$app->get('/cliente', Cliente::class . ':read');

$app->put('/cliente/{id}', Cliente::class . ':update');

$app->delete('/cliente/{id}', Cliente::class . ':delete');


$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Hello world!");
    return $response;
});

$app->get('/nombre/{nom}', function (Request $request, Response $response, $args) {
    $nombre = $args['nom'];
    $response->getBody()->write("Hola desde $nombre");
    return $response;
});