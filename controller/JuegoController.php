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
        $data = [
            "nombre" => $usuarioActual["nombre"],
            "id_usuario" => $usuarioActual["id"],
            "pregunta" => $juego["pregunta"],
            "respuestas" => $juego["respuestas"],
        ];

        $this->presenter->show("juego", $data);
    }


    private function verVariable($data): void
    {
        echo '<pre>' . print_r($data, true) . '</pre>';
    }

    public function validarRespuesta()
    {
        $this->validarPreguntaRespuestaRecibidas();
        $preguntaId = $_POST["pregunta_id"] ?? null;
        $respuestaElegidaId = $_POST["respuesta_id"] ?? null;
        $estado = false;

        $pregunta = $this->model->getPreguntaPorIdTest($preguntaId);
        // $this->verVariable($pregunta);

        $respuestas = $this->model->getRespuestasDePreguntaTest($pregunta["data"]["pregunta"]["id"]);

        if ($respuestas && $pregunta["success"]) {
            foreach ($respuestas as $r) {
                if ((int)$r["id"] == (int)$respuestaElegidaId
                    && $r["esCorrecta"]) {
                    $estado = true;
                    break;
                }
            }
        }

        $data["estado"] = $estado;
        $this->presenter->show("respuestaPregunta", $data);
    }

    public function reportar () {
        $idPreguntaReporte = $_GET["id"];
        $data["id"] = $idPreguntaReporte;
        $this->presenter->show("reportePregunta", $data);
    }

    /**
     * Valida que haya un usuario en sesión, *LoginController* se encarga de realizar el guardado en sesión.
     * @return mixed|null Retorna <code>usuario</code> si esta en sesión sino redirección hacia _login_.
     */
    private function validarUsuario(): mixed
    {
        $usuarioActual = $_SESSION["usuario"] ?? null;
        if ($usuarioActual == null) {
            $this->redireccionar("login");
        }
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
     * Valida si los parametros (pregunta_id y respuesta_id) estan establecidos luego de haber respondido. Sino usa <code>header</code> para redireccionar por error.
     * @return void
     */
    private function validarPreguntaRespuestaRecibidas() : void {
        $params = isset($_POST["pregunta_id"]) && isset($_POST["respuesta_id"]);
        if (!$params) {
            $_SESSION["error"] = "Ocurrió un error.";
            $this->redireccionar("home");
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