<?php
namespace App\controllers;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use PDO;

class Cliente {

    protected $container;

    public function __construct(ContainerInterface $c){
        $this->container=$c;
    }

    function create(Request $request, Response $response, $args) {
        $body = json_decode($request->getBody(), 1);
        /*
        $sql = "INSERT INTO cliente (idCliente, nombre, apellido1, apellido2, telefono,"
                . "celular, direccion, correo)"
                . "VALUES (:idCliente, :nombre, :apellido1, :apellido2, :telefono, :celular, :direccion, "
                .":correo);";
        */

        $sql = "INSERT INTO cliente (";
        $values = "VALUES (";

        foreach($body as $key => $value){ //$body['namobre'] = "Chris"
            $sql .= $key .', ';
            $values .= ':'.$key.', ';
        }
        $values = substr($values, 0, -2).');';
        
        $sql = substr($sql, 0, -2).') '.$values;

        //Para que no se pueda hacer inyeccion de dependencias
        /*
        $query->bindParam(':idCliente', $body->idCliente, PDO::PARAM_STR);
        $query->bindParam(':nombre', $body->nombre);
        $query->bindParam(':apellido1', $body->apellido1);
        $query->bindParam(':apellido2', $body->apellido2);
        $query->bindParam(':telefono', $body->telefono);
        $query->bindParam(':celular', $body->celular);
        $query->bindParam(':direccion', $body->direccion);
        $query->bindParam(':correo', $body->correo);
        */
        
        $data = [];
        foreach($body as $key => $value){
            $data[$key] = filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);
        }

        $con = $this->container->get('bd');
        $con->beginTransaction(); //Transacciones

        try{
            $query = $con->prepare($sql);
            $query->execute($data);
            $sql = "INSERT INTO usuario (idUsuario, rol, passw) VALUES (:idUsuario, :rol, :passw);";
            
            $passw = password_hash($data['idCliente'], PASSWORD_BCRYPT, ['cost' => 10]);
            
            $query = $con->prepare($sql);
            $query->bindValue(":idUsuario", $data['idCliente']);
            $query->bindValue(":rol", 3, PDO::PARAM_INT);
            $query->bindValue(":passw", $passw);
            $query->execute();
            $con->commit();
            $status = 201;
        }catch(\PDOException $e){
            //echo ($e->getCode()).'<br>';
            //echo ($e->getMessage());
            $status = $e->getCode() == 23000 ? 409 : 500;
            $con->rollback();
        }

        //$response->getBody()->write(json_encode($body));

        //$status = $query->rowCount() > 0 ? 201 : 409    ;

        $query = null;
        
        $con = null;

        return $response->withStatus($status);
    }
    function read(Request $request, Response $response, $args) {
        $sql = "SELECT * FROM cliente ";

        if(isset($args['id'])){
            $sql .= "WHERE id = :id"; //cuestion de inyeccion
        }

        $con = $this->container->get('bd');
        
        $query = $con->prepare($sql);

        if(isset($args['id'])){
            $query->execute(['id' => $args['id']]);
        }else{
            $query->execute();
        }
        
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
    
    function update  (Request $request, Response $response, $args){
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
            $query->bindValue(":$key", $value,PDO::PARAM_STR);
        }

        $query->bindValue(':id', $args['id'], PDO::PARAM_INT);

        $query->execute();


        $status = $query->rowCount() > 0 ? 200 : 204; //Conflicto

        $query = null;
        $con = null;

        return $response->withStatus($status);   
    }

    function delete  (Request $request, Response $response, $args){
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

    function filtrar(Request $request, Response $response, $args){
        $datos = $request->getQueryParams();
        $sql = "SELECT * FROM cliente WHERE ";
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