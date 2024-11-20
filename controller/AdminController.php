<?php

class AdminController
{

    private JuegoModel $juegoModel;
    private UsuarioModel $usuarioModel;
    private MustachePresenter $presenter;
    private GraficosModel $graficosModel;

    public function __construct($juegoModel, $usuarioModel, $presenter, $graficosModel)
    {
        $this->juegoModel = $juegoModel;
        $this->usuarioModel = $usuarioModel;
        $this->presenter = $presenter;
        $this->graficosModel = $graficosModel;
    }


    public function list(): void
    {
        $usuarioActual = $this->validarUsuario();

        // sacar esto despues si no se necesita
        $cantidadDePartidas = $this->juegoModel->getPartidasDelUsuario($usuarioActual["id"]);
        if (empty($cantidadDePartidas)) {
            $cantidadDePartidas = 0;
        } else {
            $cantidadDePartidas = count($cantidadDePartidas);
        }

        $_SESSION["usuarios"] = $this->juegoModel->getUsuariosTest();

        $datosBD = $this->usuarioModel->obtenerUsuariosPorSexo();
        $graficoTortaUsuariosSexo = $this->graficosModel->generarGraficoDeTortaPorSexos($datosBD);

        $data = [
            "texto" => "Hola mundo",
            "todas" => $this->juegoModel->getCantidadPreguntasBD(),
            "correctas" => $this->juegoModel->obtenerRespondidasMalasBuenas($usuarioActual["id"])["correctas"]["correctas"],
            "malas" => $this->juegoModel->obtenerRespondidasMalasBuenas($usuarioActual["id"])["incorrectas"]["incorrectas"],
            "partidas" => $cantidadDePartidas,
            "usuarios" => $_SESSION["usuarios"],
            "partidasJugadas" => $this->juegoModel->getPartidas(),
            "ranking" => $this->juegoModel->getRanking(),
            "tortaSexo" => $graficoTortaUsuariosSexo,
            "jugadoresTotales" => $this->juegoModel->obtenerNumeroDeJugadores(),
            "porcentajeAciertos" => $this->obtenerPorcentajeAciertos($usuarioActual),
            "acertadas" => $usuarioActual["cantidad_acertadas"],
            "respondidas" => $usuarioActual["cantidad_respondidas"],
        ];
        $this->presenter->show("admin", $data);
    }

    public function usuariosPorSexo () {
        $datosBD = $this->usuarioModel->obtenerUsuariosPorSexo();
        $graficoTortaUsuariosSexo = $this->graficosModel->generarGraficoDeTortaPorSexos($datosBD);

        $data = [
            "usuariosPorSexoGrafico" => $graficoTortaUsuariosSexo
        ];
        $this->presenter->show("adminGraficosUsuariosPorSexo", $data);
    }

    private function obtenerPorcentajeAciertos($usuario)
    {
        if ($usuario["cantidad_respondidas"] == 0) return 0;
        return round(($usuario["cantidad_acertadas"] / $usuario["cantidad_respondidas"]) * 100, 2);
    }

    public function preguntas()
    {
        $data["preguntas"] = $this->juegoModel->getPreguntas();
        $this->presenter->show("adminPreguntas", $data);
    }

    public function reportadas()
    {
        $data["reportadas"] = $this->juegoModel->getReportes();
        $this->presenter->show("adminReportes", $data);
    }

    public function reporte()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $idPregunta = $_GET['id'];
            $result = $this->juegoModel->getReportesDePreguntaConId($idPregunta);
            $data["reporte"] = $result;
            $data["id_pregunta"] = $idPregunta;
            $data["pregunta_texto"] = htmlspecialchars($result[0]["pregunta"]);
            if (!empty($result)) {
                $this->presenter->show("detalleReporte", $data);
            } else {
                echo "error";
            }
        } else echo "error devuelta";
    }

    // Ver todas las "sugeridas" (pendientes)
    public function sugeridas()
    {
        $usuarioActual = $this->validarUsuario();
        $data["sugeridas"] = $this->juegoModel->getSugeridas();
        $this->presenter->show("adminSugeridas", $data);
    }

    // Ver una sugeria en específico
    public function verSugerida()
    {
        $idSugerida = $_GET["id"];
        $sugeridaCompleta = [
            "pregunta" => $this->juegoModel->obtenerPreguntaPorId($idSugerida),
            "respuestas" => $this->juegoModel->obtenerRespuestasDePregunta($idSugerida)
        ];
        $data["pregunta"] = $sugeridaCompleta["pregunta"];
        $data["respuestas"] = $sugeridaCompleta["respuestas"];
        $this->presenter->show("adminVerSugerida", $data);
    }

    // Accions de preguntas sugeridas (activar, desactivar, rechazar)
    public function aprobar()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $idPregunta = $_GET["id"];
            $result = $this->juegoModel->aprobarPregunta($idPregunta);
            if ($result) {
                $data["mensaje"] = "¡Pregunta aprobada correctamente!";
                $data["url"] = "admin";
                $data["boton"] = "Volver a administración";
                $this->presenter->show("mensajeProcesoCorrecto", $data);
            } else {
                $data["error"] = "¡Ups! No se pudo aprobar la pregunta.";
                $this->presenter->show("error", $data);
            }
        } else {
            $this->redireccionar("home");
        }
    }

    public function rechazar()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $idPregunta = $_GET['id'];
            $result = $this->juegoModel->rechazarPregunta($idPregunta);
            if ($result) {
                $data["mensaje"] = "Pregunta rechazada correctamente.";
                $data["boton"] = "Volver a administración";
                $data["url"] = "admin";
                $this->presenter->show("mensajeProcesoCorrecto", $data);
            } else {
                $data["error"] = "¡Ups! No se pudo rechazar la pregunta.";
                $this->presenter->show("error", $data);
            };
        } else {
            $this->redireccionar("home");
        }
    }

    public function desactivar()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $idPregunta = $_GET['id'];
            $result = $this->juegoModel->desactivarPregunta($idPregunta);
            if ($result) {
                $data["mensaje"] = "Pregunta desactivada correctamente.";
                $data["boton"] = "Volver a administración";
                $data["url"] = "admin";
                $this->presenter->show("mensajeProcesoCorrecto", $data);
            } else {
                $data["error"] = "¡Ups! No se pudo desactivar la pregunta.";
                $this->presenter->show("error", $data);
            };
        } else {
            $this->redireccionar("home");
        }
    }

    public function editar()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $idPregunta = $_GET['id'];
            $categorias = $this->juegoModel->getCategoriasMenosLaDePregunta($idPregunta);

            $respuestas = $this->juegoModel->getRespuestasDePregunta($idPregunta);
            $pregunta = $this->juegoModel->getPreguntaPorId($idPregunta);

            $idAumentado = 1;
            foreach ($respuestas as &$respuesta) {
                $respuesta["idAumentado"] = $idAumentado++;
                $respuesta["checked"] = $respuesta["esCorrecta"] ? "checked" : "";
            }
            unset($respuesta);

            $data["respuestas"] = $respuestas;
            $data["pregunta"] = $pregunta;
            $data["categorias"] = $categorias;

            $this->presenter->show("adminEditarPregunta", $data);
        }

    }

    public function actualizar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = $this->getFormularioDataActualizarPregunta();
            $result = $this->juegoModel->actualizarPregunta($data);

            if (!empty($result)) {
                $d["mensaje"] = "Actualizado correctamente.";
                $d["url"] = "admin/preguntas";
                $d["boton"] = "Volver a administración";
                $this->presenter->show("mensajeProcesoCorrecto", $d);
            } else {
                $d["mensaje"] = "Ups! No ocurrió un error.";
                $d["url"] = "/PW2-Preguntones/admin/preguntas";
                $d["boton"] = "Volver a administración";
                $this->presenter->show("mensajeProcesoEroneo", $d);
            }
        }
    }

    private function getFormularioDataActualizarPregunta(): array
    {
        return [
            "preguntaTexto" => $_POST["pregunta"],
            "idPregunta" => $_POST["preguntaId"],
            "respuestas" => $_POST["respuestas"],
            "nroRespuestaCorrecta" => (int)$_POST["correcta"] - 1,
            "idCategoria" => $_POST["categoria"],
        ];
    }

    public function resetReportes()
    {
        $this->juegoModel->resetReportes();
        $this->redireccionar("admin");
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

    /**
     * Valida que haya un usuario en sesión, *LoginController* se encarga de realizar el guardado en sesión.
     * @return mixed|null Retorna <code>usuario</code> si esta en sesión sino redirección hacia _login_.
     */
    private function validarUsuario(): mixed
    {
        $usuarioActual = $_SESSION["usuario"] ?? null;
        $usuarioActual = $this->usuarioModel->getUsuarioPorId($usuarioActual["id"]);
        $_SESSION["usuario"] = $usuarioActual;
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

    private function verVariable($data): void
    {
        echo '<pre class="text-white">' . print_r($data, true) . '</pre>';
    }

}
