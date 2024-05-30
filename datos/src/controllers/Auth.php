<?php
namespace App\controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use Firebase\JWT\JWT;
use PDO;

class Auth {

    private $container;

    public function __construct(ContainerInterface $c){
        $this->container = $c;
    }
    
    private function autenticar($usuario, $passw){
        
        $sql = "SELECT * FROM usuario WHERE idUsuario = :idUsuario";

        $con = $this->container->get('bd');

        $query = $con->prepare($sql);

        $query->bindValue(':idUsuario', $usuario, PDO::PARAM_INT);
        $query->execute();
        $datos = $query->fetch();

        if($datos && password_verify($passw, $datos->passw)){
            $retorno = ["rol" => $datos->rol];
            $recurso = match($datos->rol){
                1 => "administrador",
                2 => "oficinista",
                3 => "tecnico",
                4 => "cliente"
            };

            $sql = "UPDATE usuario SET ultimoAcceso = CURDATE() WHERE idUsuario = :idUsuario";
            $query = $con->prepare($sql);
            $query->bindValue(":idUsuario", $datos->idUsuario);
            $query->execute();

            $sql = "SELECT nombre FROM $recurso WHERE id = :id";
            $query = $con->prepare($sql);
            $query->bindValue(":id", $datos->idUsuario);
            $query->execute();
            $datos = $query->fetch();
            $retorno["nombre"] = $datos->nombre;
            
        }
        $query = null;
        $con = null;
        
        return isset($retorno) ? $retorno : null;
    }


    private function generarToken(string $idUsuario, int $rol, string $nombre){
        $key = $this->container->get("key"); 
        $payload = [
            'iss' => $_SERVER['SERVER_NAME'],
            'iat' => time(),
            'exp' => time() + 1000,
            'sub' => $idUsuario,
            'rol' => $rol,
            'nom' => $nombre
        ];
        $token = JWT::encode($payload, $key, 'HS256');

        return $token;
    }

    public function iniciar(Request $request, Response $response, $args){
        $body = json_decode($request->getBody());

        if($datos = $this->autenticar($body->usuario, $body->passw)){
            $token = $this->generarToken($body->usuario, $datos['rol'], $datos['nombre']);
            $response->getBody()->write(json_encode($token));
            $status = 200;
        }else{
            $status = 401;
        }

        //$response->getBody()->write(json_encode($token));

        return $response->withHeader('Content-type', 'Application/json')
            ->withStatus($status);
    }
}