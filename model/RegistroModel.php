<?php

class RegistroModel
{
    private $database;
    const ERROR_REGISTRO_USUARIO = 'No se pudo registrar al usuario';
    const ERROR_REGISTRO_JUGADOR = 'No se pudo registrar al jugador';
    const SUCCESS_REGISTRO = 'Jugador registrado correctamente';

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function registrarJugador($usuario) {
        $usuario['password'] = password_hash($usuario['password'], PASSWORD_DEFAULT);
        $resultado = $this->insertarUsuario($usuario);

        if(!$resultado['success']){
            return ['success' => false, 'message' => self::ERROR_REGISTRO_USUARIO];
        }

        $idUsuario = $this->database->getUltimoIdGenerado();
        $resultado = $this->insertarJugador($idUsuario);

        if(!$resultado['success']){
            return ['success' => false, 'message' => self::ERROR_REGISTRO_JUGADOR];
        }

        return ['success' => true, 'message' => self::SUCCESS_REGISTRO];
    }

    private function insertarUsuario($usuario){
        $query="INSERT INTO usuario
            (nombre, apellido, username, email, password, anio_nacimiento, id_sexo, id_ciudad)
            VALUES (:nombre, :apellido, :username, :email, :password, :anio_nacimiento, :id_sexo, :id_ciudad)";
        $params=[
            'nombre' => $usuario['nombre'],
            'apellido' => $usuario['apellido'],
            'username' => $usuario['username'],
            'email' => $usuario['email'],
            'password' => $usuario['password'],
            'anio_nacimiento' => $usuario['anio_nacimiento'],
            'id_sexo' => $usuario['id_sexo'],
            'id_ciudad' => $usuario['id_ciudad']
        ];

        return $this->database->query($query,'',$params);
    }

    private function insertarJugador($idUsuario){
        $query = 'INSERT INTO jugador(id) VALUES(:id)';
        $params = ['id' => $idUsuario];

        return $this->database->query($query,'',$params);
    }

}