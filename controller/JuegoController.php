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

        if(!isset($_SESSION["contadorCorrectas"])){
            $_SESSION["contadorCorrectas"] = 0;
            $_SESSION["puntaje"]=0;
        }

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
        $maximasCorrectas=5;

        $pregunta = $this->model->getPreguntaPorIdTest($preguntaId);
        // $this->verVariable($pregunta);

        $respuestas = $this->model->getRespuestasDePreguntaTest($pregunta["data"]["pregunta"]["id"]);

        if ($respuestas && $pregunta["success"]) {
            foreach ($respuestas as $r) {
                if ((int)$r["id"] == (int)$respuestaElegidaId
                    && $r["esCorrecta"]){
                    $estado = true;
                    break;
                }
            }
        }
        if($estado){
            $_SESSION["contadorCorrectas"] ++;
            $_SESSION["puntaje"] +=10;
            if($_SESSION["contadorCorrectas"] == $maximasCorrectas) {
                $this->finalizarJuego();
                return;
            }

            }else{
            $this->finalizarJuego();
            return;
        }

        $this->redireccionar("juego");
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

    private function finalizarJuego()
    {
        $data = [
            "puntaje" => $_SESSION["puntaje"],
        ];

        // Reinicia el puntaje y el contador para la próxima partida
        unset($_SESSION["contadorCorrectas"]);
        unset($_SESSION["puntaje"]);

        if($data["puntaje"] >=50 ){
            $this->presenter->show("resultadoPartida", $data);
        }else{
            $this->presenter->show("respuestaPregunta", $data);
        }

    }


}