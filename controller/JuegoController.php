<?php

class JuegoController
{

    private $model;
    private $presenter;

    public function __construct($model, $presenter)
    {
        $this->model = $model;
        $this->presenter = $presenter;
    }

    public function list(): void
    {
        $usuarioActual = $this->validarUsuario();
        $this->validarActivacion($usuarioActual);
        $juego = $this->model->empezar();
        $juego["pregunta"] = ["id" => 0, "pregunta" => "¿Cual es el primer nombre de Messi?"];
        $juego["respuestas"] = [
            ["id" => 0, "respuesta" => "Lionel", "esCorrecta" => true],
            ["id" => 1, "respuesta" => "Andres", "esCorrecta" => false],
            ["id" => 2, "respuesta" => "Natalia", "esCorrecta" => false],
            ["id" => 3, "respuesta" => "Diego", "esCorrecta" => false],
        ];
        $_SESSION["respuestas"] = $juego["respuestas"];
        $_SESSION["correcta"] = $juego["respuestas"][0];
        $data = [
            "nombre" => $usuarioActual["nombre"],
            "id_usuario" => $usuarioActual["id"],
            "pregunta" => $juego["pregunta"],
            "respuestas" => $juego["respuestas"],
        ];
        $this->presenter->show("juego", $data);
    }

    public function validarRespuesta()
    {
        $respuestaElegidaId = $_POST["respuesta_id"] ?? null;
        $estado = "respondiste mal";
        foreach ($_SESSION["respuestas"] as $respuesta) {
            if ($respuesta["id"] == (int)$respuestaElegidaId
                && $respuesta["esCorrecta"]) {
                $estado = "respondiste bien";
            }
        }
        echo '<br>' . $estado;
    }

    private function validarUsuario()
    {
        $usuarioActual = $_SESSION["usuario"] ?? null;
        if ($usuarioActual == null) {
            $this->redireccionar("login");
        }
        return $usuarioActual;
    }

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