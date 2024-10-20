<?php

class LoginController
{
private $usuarioModelo;

public function __construct($usuarioModelo){
    $this->usuarioModelo = $usuarioModelo;
}
    public function login()
    {
        if ('REQUEST_METHOD' == 'POST') {
            $user = $_POST['username'];
            $pass = $_POST['password'];


            $usuario = $this->usuarioModelo->getUsuarioPorUsername($user);

            if ($usuario && password_verify($pass, $usuario['password'])) {
                session_start();
                $_SESSION['usuario'] = $usuario;
                header('Location: index.php');
                exit();
            } else {
                $error = 'Credenciales incorrectas';
                require('view/loginView.mustache');
            }
        } else {
            require('view/loginView.mustache');
        }
    }
}