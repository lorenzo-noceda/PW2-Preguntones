<?php

class RegistroController
{
 private $usuarioModel;
 private $presenter;

 public function __construct($usuarioModel, $presenter){
     $this->usuarioModel = $usuarioModel;
     $this->presenter = $presenter;
 }

 public function list () {
     $this->presenter->show("registro", []);
 }

 // Registrar
 public function registrar(){
     if($_SERVER['REQUEST_METHOD'] === 'POST'){
        $usuario = [
         'nombre' => $_POST['nombre'],
         'apellido' => $_POST['apellido'],
         'username' => $_POST['username'],
         'email' => $_POST['email'],
         'password' => $_POST['password'],
         'anio_nacimiento' => $_POST['anio_nacimiento'],
         'id_sexo' => $_POST['id_sexo'],
         'id_ciudad' => $_POST['id_ciudad']
         ];

        $resultadoUsuario = $this->registroModel->registrar($usuario);

        if(!$resultadoUsuario['success']){
            $error = RegistroModel::ERROR_REGISTRO_USUARIO;
            require 'view/registroView.mustache';
            return;
        }

        $idUsuario = $this->registroModel->getUltimoIdGenerado();

        $resultadoJugador = $this->registroModel->registrarJugador($idUsuario);

        if(!$resultadoJugador['success']){
            $error = RegistroModel::ERROR_REGISTRO_JUGADOR;
            require 'view/registroView.mustache';
            return;
        }

        $success = RegistroModel::SUCCESS_REGISTRO;
        require 'view/registro.success.mustache';
     }else{
         require 'view/registroView.mustache';
     }
 }
}