<?php

class RegistroController
{
    private $usuarioModel;
    private $presenter;

    public function __construct($usuarioModel, $presenter)
    {
        $this->usuarioModel = $usuarioModel;
        $this->presenter = $presenter;
    }

    public function list()
    {
        $_SESSION["sexo"] = $this->usuarioModel->getSexos();

        $data = [
            "sexo" => $_SESSION["sexo"],
            "error" => $_SESSION["error"] ?? null,
        ];

        unset($_SESSION["error"]);
        $this->presenter->show("registro", $data);
    }


    // Registrar
    public function registrar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Obtener información del formulario
            $usuario = $this->obtenerDatosDelUsuario();

            // Validar campos (contraseñas, correo y username)
            $result = $this->usuarioModel->verificarCampos($usuario);

            // Manejar error de validación
            if (!$result["success"]) {
                $_SESSION["error"] = $result["message"];
                $this->redireccionar("registro");
            }

            // Registrar usuario
            $resultadoUsuario = $this->usuarioModel->registrarUsuario($usuario);

            // Manejar error de registro
            if (!$resultadoUsuario['success']) {
                $_SESSION["error"] = $resultadoUsuario["message"];
                $this->redireccionar("registro");
            }

            // Asignar el ID generado al array $usuario
            $usuario['id'] = $resultadoUsuario["lastId"];

            // Guardamos jugador
            $resultJugador = $this->usuarioModel->guardarJugador($usuario);

            // Manejar error al guardar jugador
            if ($resultJugador["success"]) {
                $_SESSION["correoParaValidar"] = $usuario['email'];
                $_SESSION["usuario"] = $this->usuarioModel->getUsuarioPorCorreo($usuario['email']);
                $_SESSION["id_usuario"] = $usuario['id'];
                $this->redireccionar("registro/validarCorreo");
            } else {
                $_SESSION["error"] = $resultJugador["message"];
                $this->redireccionar("registro");
            }
        } else {
            $this->redireccionar("registro");
        }
    }

    public function validarJugador()
    {
        $id = $_POST["id_usuario"];
        $result = $this->usuarioModel->validarJugador($id);
        if ($result["success"]) {
            $_SESSION['usuario']["verificado"] = true;
            $this->redireccionar("home");
        } else {
            $data = ["error" => "Algo salió mal"];
            $this->presenter->show("error", $data);
        }
    }

    public function validarCorreo()
    {
        $data = [
            "correoParaValidar" => $_SESSION["correoParaValidar"] ?? "nada crack.",
            "id_usuario" => $_SESSION["id_usuario"],
            "mensaje" => "Revisa tu correo electrónico y valída el mismo."
        ];
        $this->presenter->show("validacionCorreo", $data);
    }

    private function obtenerDatosDelUsuario(): array
    {
        return [
            'nombre' => $_POST['nombre'],
            'apellido' => $_POST['apellido'],
            'username' => $_POST['username'],
            'email' => $_POST['email'],
            'password' => $_POST['password'],
            'anio_nacimiento' => $_POST['anio_nacimiento'],
            'id_sexo' => $_POST['id_sexo'],
            'latitud' => $_POST['latitud'],
            'longitud' => $_POST['longitud'],
            "confirmPassword" => $_POST['confirmPassword'],
        ];
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