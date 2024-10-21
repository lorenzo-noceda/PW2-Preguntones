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

    public function list()
    {
        $data = [
            'formTitle' => 'Iniciar sesión',
            'formAction' => '/PW2-Preguntones/login/validar',
            'submitButtonText' => 'Ingresar',
            "mensaje" => $_SESSION["success"]?? null,
            "error" => $_SESSION["error"] ?? null,
        ];
        unset($_SESSION["error"]);
        $this->presenter->show("login", $data);
    }

    public function validar()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $user = $_POST['username'];
            $pass = $_POST['password'];

            $usuario = $this->usuarioModelo->getUsuarioPorUsername($user);
            if ($usuario && password_verify($pass, $usuario['password'])) {
                $_SESSION['usuario'] = $usuario;
                $this->redireccionar("home");
            } else {
                $_SESSION["error"] = "Credenciales incorrectas";
                $this->redireccionar("login");
            }
        } else {
            echo "No entré";
//            require('view/loginView.mustache');
        }
    }

    /**
     * @param $ruta
     * @return void
     */
    private function redireccionar($ruta)
    {
        header("Location: /PW2-preguntones/$ruta");
        exit();
    }
}