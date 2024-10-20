<?php

class RegistroModel
{
    const ERROR_REGISTRO_USUARIO = 'No se pudo registrar al usuario';
    const ERROR_REGISTRO_JUGADOR = 'No se pudo registrar al jugador';
    const SUCCESS_REGISTRO = 'Jugador registrado correctamente';
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function registrarUsuario($usuario){
        $usuario['password'] = password_hash($usuario['password'], PASSWORD_DEFAULT);
        return $this->insertarUsuario($usuario);
    }

    public function registrarJugador($usuario){
        return $this->insertarUsuario($usuario);
    }

    public function getUltimoIdGenerado(){
        return $this->database->ultimoIdGenerado();
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