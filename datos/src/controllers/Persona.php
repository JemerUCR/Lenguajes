<?php
namespace App\controllers;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class Persona {
    protected $container;

    public function __construct(ContainerInterface $c){
        $this->container=$c;
    }

    protected function createP($recurso, $rol, $datos) {
        $sql = "INSERT INTO $recurso (";
        $values = "VALUES (";
    
        foreach ($datos as $key => $value) {
            $sql .= $key . ', ';
            $values .= ':' . $key . ', ';
        }
        $values = substr($values, 0, -2) . ');';
        $sql = substr($sql, 0, -2) . ') ' . $values;
    
        $con = $this->container->get('bd');
        $con->beginTransaction(); // Iniciar la transacción
    
        try {
            error_log("SQL de inserción en recurso: " . $sql);
            error_log("Datos de inserción en recurso: " . json_encode($datos));
    
            $query = $con->prepare($sql);
            $query->execute($datos);
    
            $id = $datos['idUsuario'];
            $sql = "INSERT INTO usuario (idUsuario, correo, rol, passw) VALUES (:idUsuario, :correo, :rol, :passw);";
    
            $passw = password_hash($datos['idUsuario'], PASSWORD_BCRYPT, ['cost' => 10]);
    
            error_log("SQL de inserción en usuario: " . $sql);
            error_log("Datos de inserción en usuario: idUsuario=$id, correo=" . $datos['correo'] . ", rol=$rol, passw=$passw");
    
            $query = $con->prepare($sql);
            $query->bindValue(":idUsuario", $id, PDO::PARAM_STR);
            $query->bindValue(":correo", $datos['correo'], PDO::PARAM_STR);
            $query->bindValue(":rol", $rol, PDO::PARAM_INT);
            $query->bindValue(":passw", $passw);
            $query->execute();
    
            $con->commit();
            $status = 201;
        } catch (\PDOException $e) {
            $status = $e->getCode() == 23000 ? 409 : 500;
            $errorData = [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString()
            ];
            error_log(json_encode($errorData)); // Registrar el error
            $con->rollback();
        }
    
        $query = null;
        $con = null;
    
        return $status;
    }
    

    protected function readP($recurso, $id = null) {
        try {
            if ($id) {
                $sql = "SELECT * FROM $recurso WHERE id = :id";
                $query = $this->container->get('bd')->prepare($sql);
                $query->bindParam(':id', $id, PDO::PARAM_INT);
                $query->execute();
                $result = $query->fetch(PDO::FETCH_ASSOC);
                $status = $result ? 200 : 404; // 404 si no se encuentra el recurso
                $resp = ['resp' => $result];
            } else {
                $sql = "SELECT * FROM $recurso";
                $query = $this->container->get('bd')->prepare($sql);
                $query->execute();
                $result = $query->fetchAll(PDO::FETCH_ASSOC);
                $status = $result ? 200 : 404; // 404 si no se encuentra el recurso
                $resp = ['resp' => $result];
            }
        } catch (\PDOException $e) {
            $status = 500;
            $resp = ['resp' => ['error' => $e->getMessage()]];
        }
    
        return ['resp' => $resp, 'status' => $status];
    }
    
    function updateP($recurso, $datos, $id) {
        $sql = "UPDATE $recurso SET ";
        foreach ($datos as $key => $value) {
            $sql .= "$key = :$key, ";
        }
        $sql = substr($sql, 0, -2);
        $sql .= " WHERE id = :id;";
        $con = $this->container->get('bd');
        $query = $con->prepare($sql);
        foreach ($datos as $key => $value) {
            $query->bindValue(":$key", $value, PDO::PARAM_STR);
        }
        $query->bindValue(':id', $id, PDO::PARAM_INT);
        $query->execute();
        $status = $query->rowCount() > 0 ? 200 : 204;
        $query = null;
        $con = null;
        return $status;
    }

    function deleteP($recurso, $id) {
        $sql = "DELETE FROM $recurso WHERE id = :id";
        $con = $this->container->get('bd');
        $query = $con->prepare($sql);
        $query->bindValue(':id', $id, PDO::PARAM_INT);
        $query->execute();
        $status = $query->rowCount() > 0 ? 200 : 204;
        $query = null;
        $con = null;
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
