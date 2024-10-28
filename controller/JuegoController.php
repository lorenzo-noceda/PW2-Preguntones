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

        $juego = $this->model->empezar($usuarioActual["id"]);

        $data = [
            "nombre" => $usuarioActual["nombre"],
            "id_usuario" => $usuarioActual["id"],
            "pregunta" => $juego["pregunta"],
            "respuestas" => $juego["respuestas"],
        ];

        $this->presenter->show("juego", $data);
    }

    // Método por defecto
    public function partida(): void
    {
        $usuarioActual = $this->validarUsuario();
        $this->validarActivacion($usuarioActual);

        if (!isset($_SESSION["contadorCorrectas"])) {
            $_SESSION["contadorCorrectas"] = 0;
            $_SESSION["puntaje"] = 0;
        }

        $juego = $this->model->empezar($usuarioActual["id"]);

        $data = [
            "nombre" => $usuarioActual["nombre"],
            "id_usuario" => $usuarioActual["id"],
            "pregunta" => $juego["pregunta"],
            "respuestas" => $juego["respuestas"],
        ];

        $this->presenter->show("juego", $data);
    }

    // Método para controlar y usar en desarrollo
    public function status(): void
    {
        $usuarioActual = $this->validarUsuario();
        $this->validarActivacion($usuarioActual);
        $data = [
            "texto" => "Hola mundo",
            "respondidas" => $this->model->getRespondidasDeUsuario($usuarioActual["id"]),
            "todas" => $this->model->getCantidadPreguntasBD()
        ];
        $this->presenter->show("admin", $data);
    }

    private function validarRespuestaUsuario($arrayRespuestas, $idRespuestaDada): bool
    {
        $this->verVariable($arrayRespuestas);
        if ($arrayRespuestas) {
            foreach ($arrayRespuestas as $r) {
                if ((int)$r["respuesta_id"] == (int)$idRespuestaDada
                    && $r["esCorrecta"]) {
                    return true;
                }
            }
        }
        return false;
    }

    // Método de validación
    // Guarda puntaje y contador
    // Guarda como fue respondida la pregunta // in progress...
    public function validarRespuesta()
    {
        // Valido ingreso por $_POST
        $parametros = $this->validarPreguntaRespuestaRecibidas();
        // Asigno parametros obtenidos
        $preguntaId = $parametros["pregunta_id"];
        $respuestaElegidaId = $parametros["respuesta_id"];

        $maximasCorrectas = 5;

        $pregunta = $this->model->getPreguntaPorId($preguntaId);
        $respuestas = $this->model->getRespuestasDePregunta($pregunta["id"]);

        $estado = $this->validarRespuestaUsuario($respuestas, $respuestaElegidaId);

        echo $estado ? "bien" : "mal";

        if ($estado) {
            // Si responde bien, contador y puntaje actualizado
            // Si llegó al máximo contador, corta
            $_SESSION["contadorCorrectas"]++;
            $_SESSION["puntaje"] += 10;
            if ($_SESSION["contadorCorrectas"] == $maximasCorrectas) {
                $this->model->guardarResultados();
                $this->finalizarJuego();
                return;
            }
        } else {
            // Si responde mal se termina
            $this->finalizarJuego();
            return;
        }

        // Flujo por si responde bien y todavía no llegó al máximo contador
        // $this->redireccionar("juego");
        $data = [
            "pregunta" => $pregunta["data"]["pregunta"],
            "correctas" => $_SESSION["contadorCorrectas"],
            "puntaje" => $_SESSION["puntaje"],

        ];
        $this->presenter->show("despuesDePregunta", $data);
    }

    public function reportar()
    {
        $idPreguntaReporte = $_GET["id"];
        $data["id"] = $idPreguntaReporte;
        $this->presenter->show("reportePregunta", $data);
    }

    public function probandoDeGonza()
    {
        $data = [
            "categorias" => $this->model->getCategorias(),
            "estados" => $this->model->getEstados(),
            "preguntas" => $this->model->getPreguntas1(),
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
        $params = isset($_POST["pregunta_id"]) && isset($_POST["respuesta_id"]);
        if (!$params) {
            $_SESSION["error"] = "Ocurrió un error.";
            $this->redireccionar("home");
        } else {
            return
                [
                    "pregunta_id" => $_POST["pregunta_id"],
                    "respuesta_id" => $_POST["respuesta_id"]
                ];
        }
    }


    private function finalizarJuego(): void
    {
        $data = [
            "puntaje" => $_SESSION["puntaje"],
        ];

        // Reinicia el puntaje y el contador para la próxima partida
        unset($_SESSION["contadorCorrectas"]);
        unset($_SESSION["puntaje"]);

        if ($data["puntaje"] >= 50) {
            $this->presenter->show("resultadoPartida", $data);
        } else {
            $this->presenter->show("respuestaPregunta", $data);
        }

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