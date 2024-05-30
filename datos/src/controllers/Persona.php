<?php
namespace App\controllers;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use PDO;

class Persona {
    protected $container;

    public function __construct(ContainerInterface $c){
        $this->container=$c;
    }

    function createP($recurso, $rol, $datos) {
        $sql = "INSERT INTO recurso (";
        $values = "VALUES (";

        foreach($datos as $key => $value){ //$body['namobre'] = "Chris"
            $sql .= $key .', ';
            $values .= ':'.$key.', ';
        }
        $values = substr($values, 0, -2).');';
        
        $sql = substr($sql, 0, -2).') '.$values;
        
        $data = [];
        foreach($datos as $key => $value){
            $data[$key] = filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);
        }

        $con = $this->container->get('bd');
        $con->beginTransaction(); //Transacciones

        try{
            $query = $con->prepare($sql);
            $query->execute($data);
            $id = $datos['idUsuario'];
            $sql = "INSERT INTO usuario (idUsuario, correo, rol, passw) VALUES (:idUsuario, :correo, :rol, :passw);";
            
            $passw = password_hash($data['idUsuario'], PASSWORD_BCRYPT, ['cost' => 10]);
            
            $query = $con->prepare($sql);
            //$query->bindValue(":idUsuario", $data['idCliente']);
           
            $query->bindValue(":idUsuario", $id, PDO::PARAM_STR);
            $query->bindValue(":correo", $datos['correo'], PDO::PARAM_STR);
            $query->bindValue(":rol", $rol, PDO::PARAM_INT);
            $query->bindValue(":passw", $passw);
            $query->execute();
            
            $con->commit();
            $status = 201;
        }catch(\PDOException $e){
            $status = $e->getCode() == 23000 ? 409 : 500;
            $con->rollback();
        }
        $query = null;
        $con = null;
        
        return $status;
    }

    function readP($recurso, $id = "null") {
        $sql = "SELECT * FROM $recurso ";

        if($id != null){
            $sql .= "WHERE id = :id"; //cuestion de inyeccion
        }
        
        $con = $this->container->get('bd');
        $query = $con->prepare($sql);

        if($id != null){
            $query->execute(['id' => $id]);
        }else{
            $query->execute();
        }
        
        $resp['resp'] = $query->fetchAll();
        $resp['status'] = $query->rowCount() > 0 ? 200 : 204;
        
        $query = null;
        $con = null;
       
        return $resp;
    }
    
    function updateP(Request $request, Response $response, $args){
        $body = json_decode($request->getBody());

        if (isset($body->idCliente)) {
            unset($body->idCliente);
        }

      
        $sql = "UPDATE cliente SET ";
        foreach ($body as $key => $value) {
            $sql .= "$key = :$key, ";
        }

        $sql = substr($sql, 0, -2);
        $sql .= " WHERE id = :id;";


        $con = $this->container->get('bd');
        $query = $con->prepare($sql);


        foreach ($body as $key => $value) {
            $query->bindValue(":$key", $value, PDO::PARAM_STR);
        }

        $query->bindValue(':id', $args['id'], PDO::PARAM_INT);

        $query->execute();


        $status = $query->rowCount() > 0 ? 200 : 204; //Conflicto

        $query = null;
        $con = null;

        return $response->withStatus($status);   
    }

    function deleteP(Request $request, Response $response, $args){
        $sql= "DELETE FROM cliente WHERE id = :id";
        $con = $this->container->get('bd');
        $query = $con->prepare($sql);
        $query->bindValue(':id', $args['id'], PDO::PARAM_INT);
        $query->execute();
    
        $status = $query->rowCount() > 0 ? 200 : 204; #204 no hubo ningun error
        $query = null;
        $con = null;
        #$response->getBody()->write(json_encode($res));
        return $response->withStatus($status);
    }

    function filtrarP(Request $request, Response $response, $args){
        $datos = $request->getQueryParams();
        $sql = "SELECT * FROM cliente WHERE ";
        foreach ($datos as $key => $value) {
            $sql .= "$key LIKE :$key AND ";
        }
        $sql = rtrim($sql, 'AND ') . ';';

        $con = $this->container->get('bd');
        $query = $con->prepare($sql);
        foreach ($datos as $key => $value) {
            //$query->bindValue(":$key","%$value%",PDO::PARAM_STR);
            $query->bindValue(":$key", "%$value%");
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

    /*function buscar(Request $request, Response $response, $args) {
        $id = $args['id'];
        
        $sql = "SELECT * FROM cliente WHERE id = $id";

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
    }*/
}