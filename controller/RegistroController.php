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
    public function registrar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Obtener información del formulario
            $usuario = $this->obtenerDatosDelUsuario();

            // Registrar usuario
            $resultadoUsuario = $this->usuarioModel->registrarUsuario($usuario);

            // Manejar error
            if (!$resultadoUsuario['success']) {
                $data["error"] = "Algo salio mal.";
                $this->presenter->show("registro", $data["error"]);
                return;
            }

            // Guardamos jugador
            $result = $this->usuarioModel->guardarJugador();

            // Manejar error
            if ($result["success"]) {
                $_SESSION["correoParaValidar"] = $usuario['email'];
                $_SESSION["usuario"] = $usuario;
                $this->redireccionar("registro/validarCorreo");
            } else {
                $this->presenter->show("error", []);
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
            "correoParaValidar" => $_SESSION["correoParaValidar"],
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
            'id_ciudad' => $_POST['id_ciudad']
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