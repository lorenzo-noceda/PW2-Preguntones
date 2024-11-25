<?php
require_once __DIR__ . '/../vendor/fpdf186/fpdf.php';

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
        $this->validarAdministrador(); // Asegura que el usuario es administrador.
        $usuarioActual = $this->validarUsuario(); // Obtiene los datos del usuario actual.


        // Calcula la cantidad de partidas jugadas por el usuario actual.
        // sacar esto despues si no se necesita
        $cantidadDePartidas = $this->juegoModel->getPartidasDelUsuario($usuarioActual["id"]);
        $cantidadDePartidas = empty($cantidadDePartidas) ? 0 : count($cantidadDePartidas);

        $data = [
            "texto" => "Hola mundo",
            "todas" => $this->juegoModel->getCantidadPreguntasBD(),
            "correctas" => $this->juegoModel->obtenerRespondidasMalasBuenas($usuarioActual["id"])["correctas"]["correctas"],
            "malas" => $this->juegoModel->obtenerRespondidasMalasBuenas($usuarioActual["id"])["incorrectas"]["incorrectas"],
            "partidas" => $cantidadDePartidas,
            "partidasJugadas" => $this->juegoModel->getPartidas(),
            "ranking" => $this->juegoModel->getRanking(),
            "jugadoresTotales" => $this->juegoModel->obtenerNumeroDeJugadores(),
            "porcentajeAciertos" => $this->obtenerPorcentajeAciertos($usuarioActual),
            "acertadas" => $usuarioActual["cantidad_acertadas"],
            "respondidas" => $usuarioActual["cantidad_respondidas"],
        ];

        // Renderiza la vista de administrador con los datos.
        $this->presenter->show("admin", $data);
    }


    /**
     * @throws Exception
     */
    public function usuariosPorAtributo()
    {
        $tiempo = $_GET["time"] ?? 360;
       try {
                $result = $this->getGraficosPorAtributo($tiempo);
                $data["userPorSexo"] = $result["userPorSexo"];
                $data["userPorPais"] = $result["userPorPais"];
                $data["userPorEdad"] = $result["userPorEdad"];
                $data["texto"] = $tiempo == 99999 ? "De todos los tiempos" : "Visualizando últimos $tiempo días.";
                $this->presenter->show("adminGraficosUsuariosPorPaisSexoEdad", $data);
            } catch (Exception $ex) {
                $data["error"] = "¡Ups! No se pudo cargar los graficos.";
                $this->presenter->show("error", $data);
        }

    }

    public function general()
    {
        $this->graficosModel->reset();
        try {
            $result = $this->getGraficosGenerales();
            $data["usuariosPorTiempo"] = $result["usuariosPorTiempo"];
            $data["partidasPorTiempo"] = $result["partidasPorTiempo"];
            $data["preguntasEstado"] = $result["preguntasEstado"];
            $data["usuariosNuevos"] = $result["usuariosNuevos"];
            $data["usuariosAciertos"] = $result["usuariosAciertos"];
            $this->presenter->show("adminGraficosGeneral", $data);
        } catch (Exception $ex) {
            $data["error"] = "¡Ups! No se pudo cargar los graficos.";
            $this->presenter->show("error", $data);
        }
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
            $estados = $this->juegoModel->obtenerEstadosMenosElDePregunta($idPregunta);

            $respuestas = $this->juegoModel->obtenerRespuestasDePregunta($idPregunta);
            $pregunta = $this->juegoModel->obtenerPreguntaPorId($idPregunta);

            $idAumentado = 1;
            foreach ($respuestas as &$respuesta) {
                $respuesta["idAumentado"] = $idAumentado++;
                $respuesta["checked"] = $respuesta["esCorrecta"] ? "checked" : "";
            }

            $data = [
                "pregunta" => $pregunta,
                "respuestas" => $respuestas,
                "categorias" => $categorias,
                "estados" => $estados
            ];

            unset($respuesta);
            $this->presenter->show("adminEditarPregunta", $data);
        }

    }

    public function actualizar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = $this->getFormularioDataActualizarPregunta();
            $result = $this->juegoModel->actualizarPregunta($data);

            if (!empty($result)) {
                $d["mensaje"] = "Pregunta actualizada correctamente.";
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
            "idEstado" => $_POST["estado"],
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

    public function generarPDF(): void
    {
        // Validar que el usuario es administrador.
        $this->validarAdministrador();

        // Obtener datos dinámicos, como las partidas jugadas o ranking.
        $partidasJugadas = $this->juegoModel->getPartidas();
        $ranking = $this->juegoModel->getRanking();

        // Generar gráfico de usuarios por sexo
        $datosPorSexo = $this->usuarioModel->obtenerUsuariosPorSexo(); // Debes implementar este método para obtener los datos
        $graficosModel = new GraficosModel(new Graficador());
        $rutaGraficoSexo = $graficosModel->generarGraficoDeTortaPorSexos($datosPorSexo);

        // Inicializar FPDF
        $pdf = new FPDF;
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);

        // Título del PDF
        $pdf->Cell(0, 10, 'Reporte de Administración', 0, 1, 'C');
        $pdf->Ln(10);

        // Agregar tabla de Partidas Jugadas
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Partidas Jugadas', 0, 1);
        $pdf->SetFont('Arial', '', 10);
        foreach ($partidasJugadas as $partida) {
            $pdf->Cell(50, 10, "ID Partida: " . $partida['id'], 1);
            $pdf->Cell(50, 10, "ID Usuario: " . $partida['jugador_id'], 1);
            $pdf->Cell(50, 10, "Puntaje: " . $partida['puntaje'], 1);
            $pdf->Ln();
        }
        $pdf->Ln(10);

        // Agregar tabla de Ranking
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Ranking', 0, 1);
        $pdf->SetFont('Arial', '', 10);
        foreach ($ranking as $rank) {
            $pdf->Cell(50, 10, "Usuario: " . $rank['username'], 1);
            $pdf->Cell(50, 10, "Puntaje Máximo: " . $rank['puntaje_maximo'], 1);
            $pdf->Ln();
        }
        $pdf->Ln(10);

        // Agregar gráfico de usuarios por sexo
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Gráfico de Usuarios por Sexo', 0, 1);
        $pdf->Image($rutaGraficoSexo, 10, $pdf->GetY(), 100); // Ajusta la posición y tamaño de la imagen
        $pdf->Ln(50);

        // Salida del PDF al navegador
        $pdf->Output('I', 'Reporte_Admin.pdf');
    }


    /**
     * Valida que haya un usuario en sesión, *LoginController* se encarga de realizar el guardado en sesión.
     * @return mixed|null Retorna <code>usuario</code> si esta en sesión sino redirección hacia _login_.
     */
    private function validarUsuario(): mixed
    {
        $usuarioActual = $_SESSION["usuario"] ?? null;
        $usuarioActual = $this->usuarioModel->getUsuarioPorId($usuarioActual["id"]);

        if ($usuarioActual === null) {
            $this->redireccionar("login");
            exit();
        }

        $this->validarActivacion($usuarioActual);

        return $usuarioActual; // Retorna el usuario actual si todo está bien.
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


    private function validarAdministrador(): void
    {
        $usuarioActual = $_SESSION["usuario"] ?? null;

        // Si no hay usuario logueado o el usuario no es administrador, redirige.
        if ($usuarioActual === null || !$this->usuarioModel->esAdmin($usuarioActual["id"])) {
            error_log("Acceso denegado: Usuario actual " . print_r($usuarioActual, true)); // Log para depuración.
            $this->redireccionar("home");
        }
    }

    /**
     * @throws Exception
     */
    private function getGraficosPorAtributo($tiempo): array
    {
        $this->graficosModel->reset();
        $datosBD = $this->usuarioModel->obtenerUsuariosPorSexo($tiempo);
        if (empty($datosBD)) throw new Exception("No se pudo obtener los datos.");
        $graficoTortaUsuariosSexo = $this->graficosModel->generarGraficoDeTorta(
            "Usuarios por sexo",
            array_column($datosBD, "cantidad"),
            ["blue", "red", "yellow"],
            array_column($datosBD, "nombre"),
            true
        );
        $data["userPorSexo"] = $graficoTortaUsuariosSexo;

        $datosBD = $this->usuarioModel->obtenerUsuariosPorEdad($tiempo);
        if (empty($datosBD)) throw new Exception("No se pudo obtener los datos.");
        $graficoUsuariosPorEdad = $this->graficosModel->generarGraficoDeTorta(
            "Usuarios por edad",
            array_column($datosBD, "cantidad"),
            ["blue", "red", "yellow"],
            array_column($datosBD, "grupo_etario"),
            true
        );
        $data["userPorEdad"] = $graficoUsuariosPorEdad;

        $datosBD = $this->usuarioModel->obtenerUsuariosPorPais($tiempo);
        if (empty($datosBD)) throw new Exception("No se pudo obtener los datos.");
        $graficoUsuariosPorEdad = $this->graficosModel->generarGraficoDeBarras(
            "Usuarios por pais",
            "Paises",
            "Usuarios",
            array_column($datosBD, "cantidad"),
            array_column($datosBD, "pais"),
        );
        $data["userPorPais"] = $graficoUsuariosPorEdad;
        return $data;
    }

    public function getGraficosGenerales () {
        $datosBD = $this->usuarioModel->obtenerCantidadDeUsuariosPorTiempo();
        $graficoUsuarios = $this->graficosModel->generarGraficoDeBarras(
            "Usuarios",
            "Tiempo",
            "Registrados",
            array_column($datosBD, "cantidad_usuarios"),
            array_column($datosBD, "periodo"),
        );
        $data["usuariosPorTiempo"] = $graficoUsuarios;

        $datosBD = $this->usuarioModel->obtenerCantidadDePartidasPorTiempo();
        $graficoPartidas = $this->graficosModel->generarGraficoDeBarras(
            "Partidas",
            "Tiempo",
            "Jugadas",
            array_column($datosBD, "cantidad_partidas"),
            array_column($datosBD, "periodo"),
        );
        $data["partidasPorTiempo"] = $graficoPartidas;

        $datosBD = $this->usuarioModel->obtenerCantidadDePreguntasHabilitadasYNoHabilitadas();
        $graficoPreguntas = $this->graficosModel->generarGraficoDeTorta(
            "Preguntas del juego",
            array_column($datosBD, "cantidad"),
            ["green", "gray", "orange", "red", "blue"],
            array_column($datosBD, "estado"),
            true
        );
        $data["preguntasEstado"] = $graficoPreguntas;

        $datosBD = $this->usuarioModel->obtenerUsuariosNuevosYViejos();
        $usuariosNuevos = $this->graficosModel->generarGraficoDeTorta(
            "Usuarios nuevos (7 días)",
            array_column($datosBD, "cantidad"),
            ["blue", "red"],
            array_column($datosBD, "categoria"),
            true
        );
        $data["usuariosNuevos"] = $usuariosNuevos;

        $datosBD = $this->usuarioModel->obtenerAciertosYErroresDeUsuarios();
        $usuariosAciertos = $this->graficosModel->generarGraficoDeTorta(
            "Aciertos y errores",
            array_column($datosBD, "cantidad"),
            ["blue", "red"],
            array_column($datosBD, "nombre"),
            true
        );
        $data["usuariosAciertos"] = $usuariosAciertos;
        return $data;

    }

    private function verVariable($data): void
    {
        echo '<pre class="text-white">' . print_r($data, true) . '</pre>';
    }


}
