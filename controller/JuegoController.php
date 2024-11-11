<?php

use JetBrains\PhpStorm\NoReturn;

class JuegoController
{

    private $model;
    private $presenter;

    public function __construct($model, $presenter)
    {
        $this->model = $model;
        $this->presenter = $presenter;
    }

    /*
     con 1 metodo de tipo acción empezamos el juego
     mientras valla todo bien lo dejamos en el metodo partida
     cuando termina ejecutamos metetodo de acción


     empezar () -> prepara partida
     partida () -> renderiza a medida que va avanzando y guarda
     finalizar () -> corta ejecución por -> abandono, pierde
     */


    public function empezar()
    {
        // Validar usuario en sesión y validación de correo.
        $usuarioActual = $this->validarUsuario();
        $this->validarActivacion($usuarioActual);

        if (!isset($_SESSION["contadorCorrectas"])) {
            $_SESSION["contadorCorrectas"] = 0;
            $_SESSION["puntaje"] = 0;
        }
      
        $data = $this->model->empezar($usuarioActual["id"]);

        if(isset($data["id_pregunta"])){
            $_SESSION["id_pregunta"] = $data["id_pregunta"];

        }

        if(isset($data["id_partida"])){
            $_SESSION["id_partida"] = $data["id_partida"];
        }

        $data = [
            "nombre" => $usuarioActual["nombre"],
            "id_usuario" => $usuarioActual["id"],
            "pregunta" => $data["pregunta"],
            "respuestas" => $data["respuestas"],
        ];

        $this->presenter->show("juego", $data);
    }


    // Método para controlar y usar en desarrollo
    public function status(): void
    {
        $usuarioActual = $this->validarUsuario();
        $this->validarActivacion($usuarioActual);

        $cantidadDePartidas = $this->model->getPartidasDelUsuario($usuarioActual["id"]);
        if (empty($cantidadDePartidas)) {
            $cantidadDePartidas = 0;
        } else {
            $cantidadDePartidas = count($cantidadDePartidas);
        }

        $_SESSION["usuarios"] = $this->model->getUsuariosTest();

        $data = [
            "texto" => "Hola mundo",
            "respondidas" => $this->model->getRespondidasDeUsuario($usuarioActual["id"]),
            "todas" => $this->model->getCantidadPreguntasBD(),
            "correctas" => $this->model->obtenerRespondidasMalasBuenas($usuarioActual["id"])["correctas"]["correctas"],
            "malas" => $this->model->obtenerRespondidasMalasBuenas($usuarioActual["id"])["incorrectas"]["incorrectas"],
            "partidas" => $cantidadDePartidas,
            "usuarios" => $_SESSION["usuarios"],
            "partidasJugadas" => $this->model->getPartidas(),
            "ranking" => $this->model->getRanking()
        ];
        $this->presenter->show("admin", $data);
    }

    public function cambiarPerfil()
    {
        $id = $_GET["id"];
        $usuarios = $_SESSION["usuarios"];
        foreach ($usuarios as $usuario) {
            if ($usuario["id"] == $id) {
                $usuario["verificado"] = true;
                $_SESSION["usuario"] = $usuario;
                unset($_SESSION["usuarios"]);
                $this->redireccionar("home");
            }
        }
    }

    public function reportar()
    {
        $usuarioActual = $this->validarUsuario();
        $this->validarActivacion($usuarioActual);
        $idPreguntaReporte = $_GET["id"];
        $data["pregunta"] = $this->model->getPreguntaPorId($idPreguntaReporte);
        $this->presenter->show("reportePregunta", $data);
    }

    public function enviarReporte()
    {
        $usuarioActual = $this->validarUsuario();
        $this->validarActivacion($usuarioActual);
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $idPregunta = $_POST['id'];
            $stringQueja = $_POST['queja'];
            $result = $this->model->reportar(
                $usuarioActual["id"],
                $idPregunta,
                $stringQueja);

            if ($result) {
                echo "reportada cheto pa";
            } else {
                echo "no reportada";
            }
        }
    }



    // Método de validación
    // Guarda puntaje y contador
    // Guarda como fue respondida la pregunta // in progress...
    // TODO: sacar if's del controlador y mandarlos al modelo
    public function validarRespuesta()
    {
        // Valido ingreso por $_POST
        $parametros = $this->validarPreguntaRespuestaRecibidas();
        // Asigno parametros obtenidos
        $preguntaId = $parametros["pregunta_id"];
        $respuestaElegidaId = $parametros["respuesta_id"];
        $idUsuario = $_SESSION["usuario"]["id"];
        $idPartida = $_SESSION["idPartida"];

        $pregunta = $this->model->getPreguntaPorId($preguntaId);
        $respuestas = $this->model->getRespuestasDePregunta($pregunta["id"]);

        $estado = $this->validarRespuestaUsuario($respuestas, $respuestaElegidaId);
        unset($_SESSION["id_pregunta"]);

        if (!empty($estado)) {
            // Si responde bien, contador y puntaje actualizado
            // Si llegó al máximo contador, corta
            $_SESSION["contadorCorrectas"] = $_SESSION["contadorCorrectas"] + 1;
            $_SESSION["puntaje"] += 10;
        } else {
            // Si responde mal se termina
            $this->model->guardarRespuesta(
                $idUsuario, $pregunta["id"], $idPartida, 0
            );
            unset($_SESSION["id_partida"]);
            $this->finalizarJuego();
            return;
        }


        // Flujo por si responde bien y todavía no llegó al máximo contador
        // $this->redireccionar("juego");
        $result = $this->model->guardarRespuesta(
            $idUsuario, $preguntaId, $idPartida, 1
        );

        if ($result) {
            $data = [
                "pregunta" => $pregunta["pregunta_str"],
                "correctas" => $_SESSION["contadorCorrectas"],
                "puntaje" => $_SESSION["puntaje"],
                "id" => $pregunta["id"],
                "respuesta_texto" => $estado,
            ];
            $this->presenter->show("despuesDePregunta", $data);
        } else {
            echo "error";
        }

    }

    // Métodos solo para desarrollo
    public function resetPartidasJugadas()
    {
        $idUsuario = $_SESSION["usuario"]["id"];
        $this->model->resetPartidasDelUsuario($idUsuario);
        $this->redireccionar("juego/status");
    }

    public function resetRespondidasDelUsuario()
    {
        $idUsuario = $_SESSION["usuario"]["id"];
        $this->model->resetUsuario_Pregunta($idUsuario);
        $this->redireccionar("juego/status");
    }

    /** Valida si la respuesta es de la pregunta y si la respuesta es correcta.
     * @param $arrayRespuestas
     * @param $idRespuestaDada
     * @return bool
     */
    private function validarRespuestaUsuario($arrayRespuestas, $idRespuestaDada): mixed
    {
        if ($arrayRespuestas) {
            foreach ($arrayRespuestas as $r) {
                if ((int)$r["respuesta_id"] == (int)$idRespuestaDada
                    && $r["esCorrecta"]) {
                    return $r["respuesta_str"];
                }
            }
        }
        return false;
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


    private function finalizarJuego(): void
    {
        $data = [
            "puntaje" => $_SESSION["puntaje"],
        ];

        unset($_SESSION["contadorCorrectas"]);
        unset($_SESSION["puntaje"]);

        $this->presenter->show("resultadoPartida", $data);
    }

    // Helpers de clase

    /**
     * @param $ruta
     * @return void
     */
    #[NoReturn] private function redireccionar($ruta): void
    {
        header("Location: /PW2-preguntones/$ruta");
        exit();
    }

    private function verVariable($data): void
    {
        echo '<pre>' . print_r($data, true) . '</pre>';
    }

}