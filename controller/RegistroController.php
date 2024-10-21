<?php

class RegistroController
{
    private $usuarioModel;
    private $presenter;

    private $paisesModel;

    public function __construct($usuarioModel, $presenter, $paisesModel)
    {
        $this->usuarioModel = $usuarioModel;
        $this->presenter = $presenter;
        $this->paisesModel = $paisesModel;
    }

    public function list()
    {
        $data = [
            'formTitle' => 'Registro de Usuario',
            'formAction' => '/PW2-Preguntones/registro/registrar',
            'submitButtonText' => 'Registrar',
            'loginLink' => 'view/login',
            'paises' => $this->paisesModel->getPaises(),
            "sexo" => $this->usuarioModel->getSexos(),
        ];
        $this->presenter->show("registro", $data);
    }

    // Registrar
    public function registrar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

            $resultadoUsuario = $this->usuarioModel->registrarUsuario($usuario);

            if (!$resultadoUsuario['success']) {
//                $data["error"] = RegistroModel::ERROR_REGISTRO_USUARIO;
                $data["error"] = "Algo salio mal.";
//            require 'view/registroView.mustache';
                $this->presenter->show("registro", $data["error"]);
                return;
            }

//            $idUsuario = $this->usuarioModel->getUltimoIdGenerado();

            $_SESSION["success"] = "Te registraste bien, ahora logueate capo.";
            header("Location: /PW2-Preguntones/login");
            exit();
//            $this->presenter->show("home", $data["success"]);
//            require 'view/registro.success.mustache';
        } else {
            require 'view/registroView.mustache';

        }
    }

    public function getCiudades()
    {

        $id = $_GET["id"];
        $variablesCiudades = $this->paisesModel->getCiudades($id);
        echo json_encode($variablesCiudades);
    }
}