<?php
namespace App\controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
//use Psr\Container\ContainerInterface;
use Firebase\JWT\JWT;
use PDO;

class Auth {

    private $container;

    /*public function __construct(ContainerInterface $c){
        $this->container = $c;
    }*/
    
    private function generarToken(string $idUsuario, int $rol, string $nombre){ //REVISAR******
        $key = $_ENV["KEY"];
        $payload = [
            'iss' => $_SERVER['SERVER_NAME'],
            'iat' => time(),
            'exp' => time() + 60,
            'sub' => $idUsuario,
            'rol' => $rol,
            'nom' => $nombre
        ];
        $token = JWT::encode($payload, $key, 'HS256');

        return $token;
    }

    public function iniciar(Request $request, Response $response, $args){
        $body = $request->getBody();
        $ch = curl_init();
        //http://web-datos/auth/iniciar
        curl_setopt($ch, CURLOPT_URL, "http://web-datos/auth/iniciar"); //esta no es, verificar y cambiarlo en cliente negocio
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        $resp = curl_exec($ch);        
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if($resp){
            $response->getBody()->write(json_encode($resp));
        }
        
        //return ['token' => $resp, 'status' => $status];  

        return $response->withHeader('Content-type', 'Application/json')
            ->withStatus($status);
    }
}