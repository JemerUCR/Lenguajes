<?php
namespace App\controllers;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use PDO;

class Artefacto {

    protected $container;

    public function __construct(ContainerInterface $c){
        $this->container=$c;
    }

    function create  (Request $request, Response $response, $args){

        $body = json_decode($request->getBody(),1);

        $sql = "INSERT INTO artefacto (";
        $values = "VALUES (";
        foreach($body as $key => $value){
            $sql .= $key . ', ';
            //$values .= ':' . $key . ', ';
            $values .= ":$key, ";
        }
        $values = substr($values, 0, -2) . ');';
        $sql = substr($sql, 0, -2) . ') ' . $values;

        $data = [];
        foreach($body as $key => $value){
            $data[$key] = filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);
        }
    
        $con = $this->container->get('bd');
        $con->beginTransaction();

        try {
            $query = $con->prepare($sql);
            $query->execute($data);
            $sql = "INSERT INTO usuario (idUsuario, rol, passw) VALUES (:idUsuario, :rol, :passw);";
            
            $passw = password_hash($data['idCliente'], PASSWORD_BCRYPT, ['cost' => 10] );
        
           // die($passw);
           $query = $con->prepare($sql);
    
           $query->bindValue(":idUsuario", $data['idCliente']);
           $query->bindValue(":rol", 3, PDO::PARAM_INT);
           $query->bindValue(":passw", $passw);
           
           $query->execute();
           $con->commit();
           $status = 201;

        } catch (\PDOException $e) {
            //throw $th;
            echo($e->getCode() . '<br>');
            echo($e->getMessage());
            $status = $e->getCode() == 23000 ? 409 : 500;
            $con->rollback();
        }
        //$status = $query->rowCount() > 0 ? 201 : 409;
        $query = null;
        $con = null;
        return $response->withStatus($status);
    }

    function read  (Request $request, Response $response, $args){
        $sql = "SELECT * FROM artefacto ";
        if(isset($args['id'])){
          $sql .= "WHERE id= :id";
        }

        $con = $this->container->get('bd');

        $query = $con->prepare($sql);
        if(isset($args['id'])){
            $query->execute(['id'=> $args['id']]);
        }else{
            $query->execute();
        }

        $res = $query->fetchAll();

        $status = $query->rowCount() > 0 ? 200 : 204;

        $query = null;
        $con = null;

        $response->getBody()->write(json_encode($res));

        return $response
            ->withHeader('Content-type','Application/json')
            ->withStatus($status);
    } 
    
    function update  (Request $request, Response $response, $args){
        $body = json_decode($request->getBody());

        if (isset($body->idCliente)) {
            unset($body->idCliente);
        }
      
        $sql = "UPDATE artefacto SET ";
        foreach ($body as $key => $value) {
            $sql .= "$key = :$key, ";
        }

        $sql = substr($sql, 0, -2);
        $sql .= " WHERE id = :id;";

        $con = $this->container->get('bd');
        $query = $con->prepare($sql);

        foreach ($body as $key => $value) {
            $query->bindValue(":$key", $value,PDO::PARAM_STR);
        }

        $query->bindValue(':id', $args['id'], PDO::PARAM_INT);

        $query->execute();

        $status = $query->rowCount() > 0 ? 200 : 204; //Conflicto

        $query = null;
        $con = null;

        return $response->withStatus($status);
    }

    function buscar(Request $request, Response $response, $args) {
        $id = $args['id'];
        
        $sql = "SELECT * FROM artefacto WHERE id = $id";

      //  $con = $this->conte->get('bd');
        $con = $this->container->get('bd');
        
        $query = $con->prepare($sql);

        $query->execute();
        //$res = $query->fetchAll(PDO::FETCH_ASSOC);
        $res = $query->fetchAll();
        $status = $query->rowCount() > 0 ? 200 : 204;
        $query = null;
        $con = null;
        
        $response->getBody()->write(json_encode($res));
        return $response
                ->withHeader('Content-type', 'Application/json')
                ->withStatus($status);
    }
    
    function delete  (Request $request, Response $response, $args){
       
        $sql= "DELETE FROM artefacto WHERE id = :id";

        $con = $this->container->get('bd');

        $query = $con->prepare($sql);
        $query->bindValue(':id', $args['id'], PDO::PARAM_INT);
        $query->execute();
    
        $status = $query->rowCount() > 0 ? 200 : 204; //204 sin ningun error
       
        $query = null;
        $con = null;
        #$response->getBody()->write(json_encode($res));
        return $response->withStatus($status);   
    } 

    function filtrar(Request $request, Response $response, $args){

        $datos = $request->getQueryParams();

        $sql = "SELECT * FROM artefacto WHERE ";
        foreach ($datos as $key => $value) {
            $sql .= "$key LIKE :$key AND ";
        }
        $sql = rtrim($sql, 'AND ') . ';';

        $con = $this->container->get('bd');
        $query = $con->prepare($sql);
        foreach ($datos as $key => $value) {
            $query->bindValue(":$key","%$value%",PDO::PARAM_STR);
        }

        $query->execute();
        $res = $query->fetchAll();
        $status = $query->rowCount() > 0 ? 200 : 204;

        $query = null;
        $con = null;
        $response->getBody()->write(json_encode($res));

        return $response
            ->withHeader('Content-type', 'Application/json')
            ->withStatus($status);
    }
}