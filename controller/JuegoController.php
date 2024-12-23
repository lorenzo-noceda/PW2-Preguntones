<?php

use JetBrains\PhpStorm\NoReturn;

class JuegoController
{

    private JuegoModel $model;
    private $presenter;

    public function __construct($model, $presenter)
    {
        $this->model = $model;
        $this->presenter = $presenter;
    }

    public function empezar()
    {
        // Validar usuario en sesión y validación de correo.
        $usuarioActual = $this->validarUsuario();

        // Validar contador correctas y puntaje.
        $_SESSION["contadorCorrectas"] = $_SESSION["contadorCorrectas"] ?? 0;
        $_SESSION["puntaje"] = $_SESSION["puntaje"] ?? 0;

        if(!isset($_SESSION["id_pregunta"])){
            $_SESSION["tiempo_inicio"] = time();
        }

        $data = $this->model->empezar($usuarioActual["id"], $_SESSION["id_pregunta"] ?? null);

        if (isset($data["mensaje"])) {
            $this->presenter->show("mensajeProcesoCorrecto", $data);
            return;
        }

        $_SESSION["id_pregunta"] = $data["id_pregunta"] ?? $_SESSION["id_pregunta"];
        $_SESSION["id_partida"] = $data["id_partida"] ?? $_SESSION["id_partida"];
        // Guardar para verla como resultado (malo/bueno)
        $_SESSION["pregunta"] = $data["pregunta"];

        $musica = $_SESSION['musica'];
        $tiempoLimite = 20 + $_SESSION["tiempo_inicio"] - time();

        $data +=
            ["nombre" => $usuarioActual["nombre"],
             "id_usuario" => $usuarioActual["id"],
             "tiempo_limite" => $tiempoLimite,
             "tiempo_inicio" => time(),
                "musica" =>$musica ];
        $this->presenter->show("juego", $data);
    }

    public function reportar()
    {
        $this->validarUsuario();
        $idPregunta = $_GET["id"];
        $data["pregunta"] = $this->model->obtenerPreguntaPorId($idPregunta);
        $this->presenter->show("reportePregunta", $data);
    }

    public function enviarReporte()
    {
        $usuarioActual = $this->validarUsuario();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $idPregunta = $_POST['id'];
            $stringQueja = $_POST['queja'];
            $result = $this->model->reportar($usuarioActual["id"], $idPregunta, $stringQueja);
            if ($result) {
                $data["mensaje"] = "¡Reporte enviado correctamente!";
                $data["boton"] = "Ir al inicio";
                $data["url"] = "home";
                $this->presenter->show("mensajeProcesoCorrecto", $data);
            } else {
                echo "no reportada";
            }
        }
    }



    // Método de validación
    // Guarda puntaje y contador
    // Guarda como fue respondida la pregunta // in progress...
    // TODO: sacar if's del controlador y mandarlos al modelo

    public function validarRespuesta(){
        if(!isset($_SESSION["id_partida"])){
            $this->redireccionar("home");
        }

        if(!isset($_SESSION["id_pregunta"])){
            $this->redireccionar("juego/empezar");
        }

        unset($_SESSION["id_pregunta"]);
        // Valido ingreso por $_POST
        $parametros = $this->validarPreguntaRespuestaRecibidas();

        // Asigno parametros obtenidos
        $preguntaId = $parametros["pregunta_id"];
        $respuestaElegidaId = $parametros["respuesta_id"];
        $idUsuario = $_SESSION["usuario"]["id"];
        $idPartida = $_SESSION["id_partida"];

        $pregunta = $this->model->obtenerPreguntaPorId($preguntaId);

        $tiempoLimite = $_SESSION["tiempo_inicio"] + 120;

        if(time() > $tiempoLimite){
            $this->model->guardarRespuesta($idUsuario, $pregunta["id"], $idPartida, 0);
            unset($_SESSION["id_partida"]);
            $this->tiempoPreguntaCumplido();
            return;
        }

        $respuestas = $this->model->obtenerRespuestasDePregunta($pregunta["id"]);
        $respuesta = $this->validarRespuestaUsuario($respuestas, $respuestaElegidaId);

        $_SESSION["correcta_str"] = $respuesta["respuestaCorrecta_str"] ?? null;
        $_SESSION["incorrecta_str"] = $respuesta["respuestaIncorrecta_str"] ?? null;

        $error = $this->model->actualizarRespondidas($idUsuario, $preguntaId);
        if($error){
            $this->empezar();
        }

        if($respuesta["respondioBien"] === false){
            $this->model->guardarRespuesta($idUsuario, $pregunta["id"], $idPartida, 0);
            unset($_SESSION["id_partida"]);
            $this->finalizarJuego();
            return;
        }

        $_SESSION["contadorCorrectas"] = $_SESSION["contadorCorrectas"] + 1;
        $_SESSION["puntaje"] += 10;
        $this->model->actualizarAcertadas($idUsuario, $preguntaId);
        $result = $this->model->guardarRespuesta(
            $idUsuario, $preguntaId, $idPartida, 1
        );

        if ($result) {
            $_SESSION["empezada"] = true;
            $data = [
                "pregunta" => $pregunta["pregunta_str"],
                "correctas" => $_SESSION["contadorCorrectas"],
                "puntaje" => $_SESSION["puntaje"],
                "id" => $pregunta["id"],
                "respuesta_texto" => $respuesta["respuestaCorrecta_str"],
            ];
            $this->presenter->show("despuesDePregunta", $data);
        } else {
            echo "error";
        }

    }

    // Termina juego en caso de responder mal o quedar fuera de tiempo
    private function finalizarJuego(): void
    {
        $data = [
            "puntaje" => $_SESSION["puntaje"],
            "pregunta" => $_SESSION["pregunta"]["pregunta_str"],
            "id" => $_SESSION["pregunta"]["id"],
            "respuestaCorrecta" => htmlspecialchars($_SESSION["correcta_str"]),
            "respuestaElegida" => htmlspecialchars($_SESSION["incorrecta_str"])
        ];

        unset($_SESSION["pregunta"]);
        unset($_SESSION["contadorCorrectas"]);
        unset($_SESSION["puntaje"]);

        $this->presenter->show("resultadoPartida", $data);
    }

    private function tiempoPreguntaCumplido(): void {
        $data = [
            "puntaje" => $_SESSION["puntaje"],
            "pregunta" => $_SESSION["pregunta"]["pregunta_str"]
        ];

        unset($_SESSION["pregunta"]);
        unset($_SESSION["contadorCorrectas"]);
        unset($_SESSION["puntaje"]);

        $this->presenter->show("tiempoPreguntaCumplido", $data);
    }

    // Métodos solo para desarrollo
    public function resetPartidasJugadas(){
        $idUsuario = $_SESSION["usuario"]["id"];
        $this->model->resetPartidasDelUsuario($idUsuario);
        $this->redireccionar("admin");
    }

    public function resetRespondidasDelUsuario(){
        $idUsuario = $_SESSION["usuario"]["id"];
        $this->model->resetUsuario_Pregunta($idUsuario);
        $this->redireccionar("admin");
    }

    /** Valida si la respuesta es de la pregunta y si la respuesta es correcta.
     * @param $respuestas
     * @param $idRespuestaDada
     * @return bool
     */

    private function validarRespuestaUsuario($respuestas, $idRespuestaDada): array{
        $respuesta = [
            "respondioBien" => false,
            "respuestaIncorrecta_str" => null
        ];
        foreach ($respuestas as $r) {
            $esCorrecta = (bool) $r["esCorrecta"];
            $idRespuesta = $r["respuesta_id"];

            if($idRespuesta == $idRespuestaDada){
                if($esCorrecta){
                    $respuesta["respondioBien"] = true;
                    $respuesta["respuestaCorrecta_str"] = $r["respuesta_str"];
                    break;
                } else {
                    $respuesta["respuestaIncorrecta_str"] = $r["respuesta_str"];
                }
            }
            // Captura la correcta en el caso que el usuario responda erroneamente
            if($esCorrecta){
                $respuesta["respuestaCorrecta_str"] = $r["respuesta_str"];
            }
        }
        return $respuesta;
    }

    public function probandoDeGonza()
    {
        $data = [
            "categorias" => $this->model->getCategorias(),
            "estados" => $this->model->getEstados(),
            "preguntas" => $this->model->getPreguntas(),
            "respuestas" => $this->model->getRespuestas()
        ];
        $this->presenter->show("vistaDePruebas", $data);
    }

    /**
     * Valida que haya un usuario en sesión, *LoginController* se encarga de realizar el guardado en sesión.
     * @return mixed|null Retorna <code>usuario</code> si esta en sesión sino redirección hacia _login_.
     */
    private
    function validarUsuario(): mixed
    {
        $usuarioActual = $_SESSION["usuario"] ?? null;
        if ($usuarioActual == null) {
            $this->redireccionar("login");
        }
        if (!$usuarioActual["verificado"]) {
            $_SESSION["correoParaValidar"] = $usuarioActual["email"];
            $_SESSION["id_usuario"] = $usuarioActual["id"];
            $this->redireccionar("registro/validarCorreo");
        }
        return $usuarioActual;
    }

    /**
     * Valida que el usuario este verificado sino hace uso de <code>header</code> para redireccionar y que valide su correo.
     * @param $usuario
     * @return void
     */
    private
    function validarActivacion($usuario): void
    {
        if (!$usuario["verificado"]) {
            $_SESSION["correoParaValidar"] = $usuario["email"];
            $_SESSION["id_usuario"] = $usuario["id"];
            $this->redireccionar("registro/validarCorreo");
        }
    }

    /**
     * Valida si los parametros (pregunta_id y respuesta_id) estan establecidos luego de haber respondido. Sino usa <code>header</code> para redireccionar por error.
     * @return array
     */
    private function validarPreguntaRespuestaRecibidas(): array
    {
        $params = isset($_POST["pregunta_id"]) &&
            isset($_POST["respuesta_id"]);
        if (!$params) {
            $_SESSION["error"] = "Ocurrió un error.";
            $this->redireccionar("home");
        } else {
            return
                [
                    "pregunta_id" => $_POST["pregunta_id"],
                    "respuesta_id" => $_POST["respuesta_id"],
                ];
        }
    }

    // Helpers de clase

    /**
     * @param $ruta
     * @return void
     */
    #[
        NoReturn] private function redireccionar($ruta): void
    {
        header("Location: /PW2-preguntones/$ruta");
        exit();
    }

    private function verVariable($data): void
    {
        echo '<pre>' . print_r($data, true) . '</pre>';
    }

}