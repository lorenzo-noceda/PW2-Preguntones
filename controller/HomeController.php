<?php

class HomeController
{

    private $juegoModel;
    private $usuarioModel;
    private $presenter;

    public function __construct($juegoModel, $usuarioModel, $presenter)
    {
        $this->juegoModel = $juegoModel;
        $this->usuarioModel = $usuarioModel;
        $this->presenter = $presenter;
    }

    public function list(): void
    {
        $usuarioActual = $_SESSION["usuario"] ?? null;
        $rol = $_SESSION["rol"] ?? null;

        if ($usuarioActual == null) {
            $this->redireccionar("login");
        } else {
            if (!$usuarioActual["verificado"]) {
                $data = [
                    "mensaje" => "Verifica tu correo por favor.",
                    "correo" => $usuarioActual["email"],
                    "id_usuario" => $usuarioActual["id"]
                ];
                $this->presenter->show("validacionCorreo", $data);
            } else {
                // Procesar roles específicos
                $esJugador = $rol === 'jugador';
                $esEditor = $rol === 'editor';
                $esAdmin = $rol === 'admin';

                $data = [
                    "nombre" => $usuarioActual["nombre"],
                    "id_usuario" => $usuarioActual["id"],
                    "esJugador" => $esJugador,
                    "esEditor" => $esEditor,
                    "esAdmin" => $esAdmin
                ];
                $this->presenter->show("home", $data);
            }
        }
    }

    public function ranking()
    {
        $data["ranking"] = $this->usuarioModel->getRanking();
        $this->presenter->show("ranking", $data);
    }

    public function sugerir(): void
    {
        $this->validarUsuario();
        $data["categorias"] = $this->juegoModel->getCategorias();
        $this->presenter->show("sugerirPregunta", $data);
    }

    public function enviarSugerencia(): void
    {
        $usuarioActual = $this->validarUsuario();
        if ($_SERVER["REQUEST_METHOD"] === 'POST' && $usuarioActual) {
            $pregunta = $_POST["pregunta"];
            $categoria = $_POST["categoria"];
            $respuestas = $this->obtenerRespuestasFormulario();
            $result = $this->juegoModel->crearSugerencia($pregunta, $categoria, $respuestas);
            if ($result) {
                $this->presenter->show("mensajeSugerenciaRealizada");
            } else {
                $data["error"] = "Ups! ocurrió un error. Inténtalo de nuevo más tarde";
                $this->presenter->show("error", $data);
            }
        } else {
            $this->redireccionar("login");
        }
    }

    private function obtenerRespuestasFormulario()
    {
        return [
            ['texto' => $_POST['respuesta1'], "esCorrecta" => false],
            ['texto' => $_POST['respuesta2'], "esCorrecta" => false],
            ['texto' => $_POST['respuesta3'], "esCorrecta" => false],
            ['texto' => $_POST['respuestaCorrecta'], "esCorrecta" => true],
        ];
    }

    private function validarUsuario(): array
    {
        $usuarioActual = $_SESSION["usuario"] ?? null;
        if ($usuarioActual == null) {
            $this->redireccionar("login");
        }
        $this->validarActivacion($usuarioActual);
        return $usuarioActual;
    }

    /**
     * Valida que el usuario este verificado sino hace uso de <code>header</code> para redireccionar y que valide su correo.
     * @param $usuario
     * @return void
     */
    private function validarActivacion($usuario): void
    {
        if (!$usuario["verificado"]) {
            $_SESSION["correoParaValidar"] = $usuario["email"];
            $_SESSION["id_usuario"] = $usuario["id"];
            $this->redireccionar("registro/validarCorreo");
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