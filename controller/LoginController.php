<?php

use JetBrains\PhpStorm\NoReturn;
class LoginController
{

    private UsuarioModel $usuarioModelo;
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

    public function cerrarSesion()
    {
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
            //$usuario["password"] == $pass
            //password_verify($pass, $usuario['password']) usar en segundo if cuando este hasheadas todas
            if ($usuario != null && password_verify($pass, $usuario['password'])) {
                // Determinamos el rol basado en los IDs devueltos por la consulta
                if (!is_null($usuario['administrador_id'])) {
                    $rol = 'admin';
                } elseif (!is_null($usuario['editor_id'])) {
                    $rol = 'editor';
                } elseif (!is_null($usuario['jugador_id'])) {
                    $rol = 'jugador';
                } else {
                    $rol = 'sin_rol'; // Maneja el caso de usuarios sin rol asignado, si aplica
                }

                // Guardamos el usuario en sesion
                $_SESSION['usuario'] = $usuario;
                $_SESSION['rol'] = $rol;
                $_SESSION['musica'] = $usuario['musica'];

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