<?php
namespace App\controllers;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class Oficinista extends Persona{

    protected $container;

    const RECURSO = 'oficinista';
    const ROL = 2;

    /*public function __construct(ContainerInterface $c){
        $this->container=$c;
    }*/

    function create(Request $request, Response $response, $args) {
        $body = json_decode($request->getBody(), 1);
        
        $status = $this->createP(self::RECURSO, self::ROL, $body);

        return $response->withStatus($status);
    }

    function read(Request $request, Response $response, $args) {
        if(isset($args['id'])){
            $resp = $this->readP(self::RECURSO, $args['id']);
        }else{
            $resp = $this->readP(self::RECURSO);
        }
        
        $response->getBody()->write(json_encode($resp['resp']));
        return $response
                ->withHeader('Content-type', 'Application/json')
                ->withStatus($resp['status']);
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
            $query->bindValue(":$key", $value, PDO::PARAM_STR);
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