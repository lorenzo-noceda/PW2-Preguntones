<?php

class UsuarioModel
{
    private $database;
//    const string ERROR_REGISTRO_USUARIO = 'No se pudo registrar al usuario';
//    const string ERROR_REGISTRO_JUGADOR = 'No se pudo registrar al jugador';
//    const string SUCCESS_REGISTRO = 'Jugador registrado correctamente';

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function guardarJugador(){
        $id = $this->getUltimoIdGenerado();
        $query = "
                INSERT INTO jugador
                    (id, verificado)
                VALUES 
                    (:id, :verificado)";
        $params = [
            ["columna" => "id", "valor" => $id],
            ["columna" => "verificado", "valor" => false],
        ];
        return $this->database->query($query, 'INSERT', $params);
    }

    public function getVerificacionDeUsuario ($idusuario) {
        if ($idusuario != null) {
            $q = "
            SELECT verificado 
            FROM jugador
            WHERE id = :id";
            $params = [
                ["columna" => "id", "valor" => $idusuario],
            ];
            $result = $this->database->query($q, "SINGLE", $params);
            if ($result["success"]) {
                return $result["data"];
            } else {
                return [];
            }
        } else {
            return [];
            // Manejar que pasa si llega null
        }
    }

    public function validarJugador ($id) {
        $query = "
                UPDATE jugador
                SET verificado = :verificado
                WHERE id = :id";
        $params = [
            ["columna" => "id", "valor" => $id],
            ["columna" => "verificado", "valor" => true],
        ];
        return $this->database->query($query, 'UPDATE', $params);
    }

    public function getUltimoIdGenerado(){
        return $this->database->getUltimoIdGenerado();
    }

    public function getSexos() {
        $q = 'SELECT * FROM sexo';
        $result = $this->database->query($q, "MULTIPLE", []);
        if ($result["success"]) {
            return $result["data"];
        } else {
            return [];
        }
    }

    public function registrarUsuario($usuario)
    {
        $usuario['password'] = password_hash($usuario['password'], PASSWORD_DEFAULT);
        return $this->guardarUsuario($usuario);
    }

    private function guardarUsuario($usuario)
    {
        $query = "
                INSERT INTO usuario
                    (nombre, apellido, username, email, password, anio_nacimiento, id_sexo, id_ciudad)
                VALUES 
                    (:nombre, :apellido, :username, :email, :password, :anio_nacimiento, :id_sexo, :id_ciudad)";
        $params = [
            ["columna" => "nombre", "valor" => $usuario['nombre']],
            ["columna" => "apellido", "valor" => $usuario['apellido']],
            ["columna" => "username", "valor" => $usuario['username']],
            ["columna" => "email", "valor" => $usuario['email']],
            ["columna" => "password", "valor" => $usuario['password']],
            ["columna" => "anio_nacimiento", "valor" => $usuario['anio_nacimiento']],
            ["columna" => "id_sexo", "valor" => $usuario['id_sexo']],
            ["columna" => "id_ciudad", "valor" => $usuario['id_ciudad']],
            ["columna" => "anio_nacimiento", "valor" => $usuario['nombre']],
        ];

        return $this->database->query($query, 'INSERT', $params);
    }

    private function insertarJugador($idUsuario)
    {
        $query = 'INSERT INTO jugador(id) VALUES(:id)';
        $params = [
            ["columna" => "id", "valor" => $idUsuario]
        ];
        return $this->database->query($query, 'INSERT', $params);
    }

    public function getUsuarioPorId($id)
    {
        if ($id != null) {
            $q = "
            SELECT u.*, c.nombre AS ciudad, p.nombre AS pais 
            FROM usuario u
            JOIN ciudad c on u.id_ciudad = c.id
            JOIN pais p on c.id_pais = p.id
            WHERE u.id = :id";
            $params = [
                ["columna" => "id", "valor" => $id],
            ];
            $result = $this->database->query($q, "SINGLE", $params);
            if ($result["success"]) {
                return $result["data"];
            } else {
                return [];
            }
        } else {
            return [];
            // Manejar que pasa si llega null
        }
    }

    public function getUsuarioPorUsername($username)
    {
        $query = 'SELECT * FROM usuario WHERE username = :username';
        $params = [
            ["columna" => "username", "valor" => $username],
        ];
        $result = $this->database->query($query, "SINGLE", $params);
        if ($result["success"]) {
            return $result["data"];
        } else {
            return null;
        }
    }

}