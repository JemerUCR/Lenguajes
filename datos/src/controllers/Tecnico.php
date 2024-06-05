<?php
namespace App\controllers;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use PDO;

class Tecnico extends Persona{

    protected $container;

    const RECURSO = 'tecnico';
    const ROL = 3; // Ajusta el rol según corresponda

    public function __construct(ContainerInterface $c){
        $this->container = $c;
    }

    function create(Request $request, Response $response, $args) {
        $body = json_decode($request->getBody(), true);
    
        // Validar que el cuerpo de la solicitud se haya decodificado correctamente
        if (json_last_error() !== JSON_ERROR_NONE) {
            $response->getBody()->write(json_encode(['error' => 'Invalid JSON format']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    
        try {
            // Crear conexión a la base de datos
            $con = $this->container->get('bd');
    
            // Preparar la consulta SQL de inserción
            $sql = "INSERT INTO tecnico (idUsuario, nombre, apellido1, apellido2, telefono, celular, direccion, correo, especialidad, fechaIngreso) 
                    VALUES (:idUsuario, :nombre, :apellido1, :apellido2, :telefono, :celular, :direccion, :correo, :especialidad, :fechaIngreso)";
    
            $query = $con->prepare($sql);
    
            // Bind de los parámetros
            $query->bindParam(':idUsuario', $body['idUsuario']);
            $query->bindParam(':nombre', $body['nombre']);
            $query->bindParam(':apellido1', $body['apellido1']);
            $query->bindParam(':apellido2', $body['apellido2']);
            $query->bindParam(':telefono', $body['telefono']);
            $query->bindParam(':celular', $body['celular']);
            $query->bindParam(':direccion', $body['direccion']);
            $query->bindParam(':correo', $body['correo']);
            $query->bindParam(':especialidad', $body['especialidad']);
            $query->bindParam(':fechaIngreso', $body['fechaIngreso']);
    
            // Ejecutar la consulta
            $query->execute();
    
            // Verificar si la inserción fue exitosa
            if ($query->rowCount() > 0) {
                $response->getBody()->write(json_encode(['message' => 'Tecnico created successfully']));
                return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
            } else {
                $response->getBody()->write(json_encode(['error' => 'Failed to create Tecnico']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Internal Server Error', 'details' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
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
    
    function update(Request $request, Response $response, $args){
        $body = json_decode($request->getBody(), true);
    
        if (isset($body['idTecnico'])) {
            unset($body['idTecnico']);
        }
    
        $sql = "UPDATE tecnico SET ";
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
    
        if (isset($body['fechaIngreso'])) {
            $query->bindParam(':fechaIngreso', $body['fechaIngreso']);
        }
    
        $query->execute();
    
        $status = $query->rowCount() > 0 ? 200 : 204;
    
        $query = null;
        $con = null;
    
        return $response->withStatus($status);
    }
    

    function delete(Request $request, Response $response, $args){
        $sql= "DELETE FROM tecnico WHERE id = :id";
        $con = $this->container->get('bd');
        $query = $con->prepare($sql);
        $query->bindValue(':id', $args['id'], PDO::PARAM_INT);
        $query->execute();
    
        $status = $query->rowCount() > 0 ? 200 : 204;
        $query = null;
        $con = null;
        return $response->withStatus($status);
    }

    function filtrar(Request $request, Response $response, $args){
        $datos = $request->getQueryParams();
        $sql = "SELECT * FROM tecnico WHERE ";
        foreach ($datos as $key => $value) {
            $sql .= "$key LIKE :$key AND ";
        }
        $sql = rtrim($sql, 'AND ') . ';';

        $con = $this->container->get('bd');
        $query = $con->prepare($sql);
        foreach ($datos as $key => $value) {
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
}