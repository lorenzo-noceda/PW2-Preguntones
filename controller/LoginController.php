<?php

class LoginController
{
    private $usuarioModelo;
    private $presenter;

    public function __construct($usuarioModelo, $presenter)
    {
        $this->usuarioModelo = $usuarioModelo;
        $this->presenter = $presenter;
    }

    public function list() {
        $this->presenter->show("login", []);
    }

    public function validar()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $user = $_POST['username'];
            $pass = $_POST['password'];

            $usuario = $this->usuarioModelo->getUsuarioPorUsername($user);

            echo "POST";
            var_dump($usuario);

//            if ($usuario && password_verify($pass, $usuario['password'])) {
//                session_start();
//                $_SESSION['usuario'] = $usuario;
//                $this->presenter->show("home", []);
//            } else {
//                $error = 'Credenciales incorrectas';
//                require('view/loginView.mustache');
//            }
        } else {
            echo "No entré";
//            require('view/loginView.mustache');
        }
    }
}