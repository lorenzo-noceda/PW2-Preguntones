<?php

use JetBrains\PhpStorm\NoReturn;

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
            "mensaje" => $_SESSION["success"] ?? null,
            "error" => $_SESSION["error"] ?? null,
        ];
        unset($_SESSION["error"]);
        $this->presenter->show("login", $data);
    }

    public function cerrarSesion() {
        unset($_SESSION["usuario"]);
        $this->redireccionar("login");
    }

    public function validar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $user = $_POST['username'];
            $pass = $_POST['password'];

            // Obtener usuario de la DB
            $usuario = $this->usuarioModelo->getUsuarioPorUsername($user);

            //password_verify($pass, $usuario['password']) usar en segundo if cuando este hasheadas todas
            if ($usuario != null && ($usuario["password"] == $pass)) {
                // Guardamos usuario en sesión
                $_SESSION['usuario'] = $usuario;
                // A casita perro
                $this->redireccionar("home");
            } else {
                // No se encontró usuario
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
    #[NoReturn] private function redireccionar($ruta): void
    {
        header("Location: /PW2-preguntones/$ruta");
        exit();
    }
}