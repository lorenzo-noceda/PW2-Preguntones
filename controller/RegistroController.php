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
        $_SESSION["paises"] = $this->paisesModel->getPaises();
        $_SESSION["sexo"] = $this->usuarioModel->getSexos();

        $data = [
            'paises' => $_SESSION["paises"],
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
            var_dump($result);

            // Manejar error de validación
            if (!$result["success"]) {
                $_SESSION["error"] = $result["message"];
                $this->redireccionar("registro");
            }

            // Registrar usuario
            $resultadoUsuario = $this->usuarioModel->registrarUsuario($usuario);
            var_dump($resultadoUsuario["success"]);

            // Manejar error de registro
            if (!$resultadoUsuario['success']) {
                $_SESSION["error"] = $resultadoUsuario["message"];
                $this->redireccionar("registro");
            }

            // Guardamos jugador
            $resultJugador = $this->usuarioModel->guardarJugador($usuario);
            var_dump($resultJugador);

            // Manejar error
            if ($resultJugador["success"]) {
                $_SESSION["correoParaValidar"] = $usuario['email'];
                $_SESSION["usuario"] = $this->usuarioModel->getUsuarioPorCorreo($usuario['email']);
                $_SESSION["id_usuario"] = $resultadoUsuario["lastId"];
                $this->redireccionar("registro/validarCorreo");
            } else {
                $_SESSION["error"] = $resultJugador["message"];
                $this->redireccionar("registro");
            }
        } else $this->redireccionar("registro");
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
        ];
        $this->presenter->show("validacionCorreo", $data);
    }


    public function getCiudades()
    {
        $id = $_GET["id"];
        $variablesCiudades = $this->paisesModel->getCiudades($id);
        echo json_encode($variablesCiudades);
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
            'id_ciudad' => $_POST['id_ciudad'],
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