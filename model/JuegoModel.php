<?php

class JuegoModel
{

    private $database;
    const NIVEL_BAJO = "BAJO", NIVEL_MEDIO = "MEDIO", NIVEL_ALTO = "ALTO";

    public function __construct($database)
    {
        $this->database = $database;
    }

    private function buscarPreguntasParaUsuarioPorNivel($idUsuario) {
        $nivelUsuario = $this->obtenerNivelDeUsuario($idUsuario);
        $resultado = $this->saberMinMaxSegunNivelDeUsuario($nivelUsuario);
        $minimo = $resultado["minimo"];
        $maximo = $resultado["maximo"];
        unset($resultado);
        $preguntas = $this->obtenerPreguntasParaUsuarioPorNivel($idUsuario, $minimo, $maximo);
        if (!empty($preguntas)) {
            return $preguntas;
        } else return false;
    }

    private function obtenerNivelDeUsuario($idUsuario): ?string
    {
        $respondidas = $this->obtenerCantidadRespondidasPorUsuario($idUsuario);
        if ($respondidas < 10) {
            return self::NIVEL_MEDIO;
        }
        $acertadas = $this->obtenerCantidadAcertadasPorUsuario($idUsuario);
        $nivelUsuario = $acertadas / $respondidas;

        return match (true) {
            $nivelUsuario <= 0.3 => self::NIVEL_BAJO,   // De 0 a 0.3
            $nivelUsuario <= 0.7 => self::NIVEL_MEDIO,  // De 0.3 a 0.7
            $nivelUsuario <= 1.0 => self::NIVEL_ALTO,   // De 0.7 a 1
            default => null
        };
    }

    /**
     * @throws Exception
     */
    public function actualizarRespondidas($idUsuario, $idPregunta)
    {
        try {
            $this->database->beginTransaction();
            $this->actualizarRespondidasUsuario($idUsuario);
            $this->actualizarRespondidasPregunta($idPregunta);
            $this->database->commit();
        } catch (Exception $e) {
            $this->database->rollBack();
            throw new Exception($e->getMessage());
        }
    }

    public function actualizarAcertadas($idUsuario, $idPregunta)
    {
        $this->actualizarAcertadasUsuario($idUsuario);
        $this->actualizarAcertadasPregunta($idPregunta);
    }

    public function empezar($idUsuario, $idPregunta = false): array
    {
        // Anti f5 y para que no responda lo que no le dí
        if ($idPregunta) {
            $data["pregunta"] = $this->obtenerPreguntaAprobadaPorId($idPregunta);
        } else {
            // Si empieza
            $pregunta = $this->getPreguntaRandom($idUsuario);
            if ($pregunta == null) {
                $data["mensaje"] = "No hay preguntas disponibles";
                $data["url"] = "home";
                $data["boton"] = "Volver al inicio";
                return $data;
            } else {
                $data["pregunta"] = $pregunta;
                $data["id_pregunta"] = $pregunta["id"];
                $data["id_partida"] = $this->insertPartida($idUsuario);
                unset($pregunta);
            }
        }

        $data["respuestas"] = $this->obtenerRespuestasDePregunta($data["pregunta"]["id"]);
        shuffle($data["respuestas"]); // delegar despues
        return $data;
    }

    public function aprobarPregunta($idPregunta)
    {
        if ($idPregunta == null) return false;
        return $this->updatePreguntaAprobada($idPregunta);
    }

    public function desactivarPregunta($idPregunta)
    {
        if ($idPregunta == null) return false;
        return $this->updatePreguntaDesactivar($idPregunta);
    }

    public function rechazarPregunta($idPregunta)
    {
        if ($idPregunta == null) return false;
        return $this->updatePreguntaRechazar($idPregunta);
    }

    private function hayMasPreguntasParaUsuarioPorNivel($idUsuario)
    {
        $result = $this->buscarPreguntasParaUsuarioPorNivel($idUsuario);
        if (count($result["data"]) > 0) {
            return true;
        }
        return false;
    }

    private function hayMasPreguntasParaUsuario($idUsuario): bool
    {
        $result = $this->obtenerPreguntasParaUsuario($idUsuario);
        if (count($result["data"]) > 0) {
            return true;
        }
        return false;
    }

    public function crearSugerencia($texto, $idCategoria, $respuestas)
    {
        $result = $this->insertarPregunta($texto, $idCategoria);
        if ($result) {
            $ultimoId = $this->database->getUltimoIdGenerado();
            $convertidas = $this->convertirRespuestas($respuestas, $ultimoId);
            foreach ($convertidas as $respuesta) {
                $salioBien = $this->insertRespuestaDePregunta(
                    $respuesta["texto"],
                    $respuesta["id_pregunta"],
                    $respuesta["esCorrecta"]
                );
                if (!$salioBien) {
                    return false;
                }
            }
        }
        return true;
    }


    public function reportar($idUsuario, $idPregunta, $stringTexto)
    {
        $preguntaBuscada = $this->obtenerPreguntaPorId($idPregunta);
        $resultado = null;
        if (!empty($preguntaBuscada)) {
            $resultado = $this->insertReporte((int)$idUsuario, $idPregunta, $stringTexto);
            if ($resultado) {
                $resultado = $this->updateEstadoPregunta($idPregunta);
                if ($resultado) {
                    return true;
                }
            }
        }
        return false;
    }

    private function getPreguntaRandom($idUsuario)
    {
        // Obtener preguntas no respondidas en el nivel del jugador
        $result = $this->obtenerPreguntasNoRespondidasDelUsuarioPorNivel($idUsuario);
        if (empty($result)) {
            return null;
        } else {
            $indicePreguntaRandom = array_rand($result);
            return $result[$indicePreguntaRandom];
        }

    }

    private function obtenerNivelDePregunta($idPregunta)
    {
        $respondidas = $this->obtenerCantidadRespondidasPorPregunta($idPregunta);
        if ($respondidas < 10) {
            return self::NIVEL_MEDIO;
        }
        $acertadas = $this->obtenerCantidadAcertadasPorPregunta($idPregunta);
        $nivelPregunta = $acertadas / $respondidas;
        switch ($nivelPregunta) {
            case $nivelPregunta < 0.33:
                return self::NIVEL_BAJO;
            case $nivelPregunta < 0.66:
                return self::NIVEL_MEDIO;
            default:
                return self::NIVEL_ALTO;
        }
    }

    public function guardarRespuesta($idUsuario, $idPregunta, $idPartida, $state)
    {
        $result = $this->insertRespuestaDePartida($idUsuario, $idPregunta, $state);

        if ($state && $result) {
            // correcta y salio bien el insert
            $result = $this->updatePartida($idUsuario, $idPartida, 10);
        } else if (!$state && $result) {
            // incorrecta y salio bien el insert
            $result = $this->updatePartida($idUsuario, $idPartida, 0);
        } else {
            // aca no debería entrar
        }
        return $result;
    }

    public function obtenerNumeroDeJugadores()
    {
        return $this->obtenerCantidadDeJugadores()["jugadores"];
    }

    /** Calcula cuantas respondió el usuario de todas las preguntas.
     * @param $idUsuario
     * @return int
     */
    public function getRespondidasDeUsuario($idUsuario): int
    {
        $noRespondidas = $this->obtenerPreguntasNoRespondidasDelUsuarioPorNivel($idUsuario);
        return $this->getCantidadPreguntasBD() - count($noRespondidas);
    }

    public function actualizarPregunta($data)
    {
        $result = $this->updatePregunta(
            $data["preguntaTexto"],
            $data["idCategoria"],
            $data["idPregunta"],
            $data["idEstado"]
        );

        if ($result) {
            $contador = $this->actualizarRespuestasDePregunta(
                $data["idPregunta"],
                $data["respuestas"],
                $data["nroRespuestaCorrecta"]);
            if ($contador == 4) {
                return true;
            } else {
                return false;
            }
        }
        return false;
    }

    private function actualizarRespuestasDePregunta(
        int   $idPregunta,
        array $respuestasCrudo,
        int   $nroRespuestaCorrecta): int
    {
        $result = $this->eliminarRespuestasDePregunta($idPregunta);

        $respuestas = [];
        $counter = 0;
        // crear el "objeto"
        foreach ($respuestasCrudo as $r) {
            $respuestas[] = [
                "texto" => $r,
                "esCorrecta" => false,
                "marcador" => $counter++
            ];
        }
        unset($r);

        $contadorInsertsCorrectos = 0;

        if ($result) {
            // asignarle true a la que va
            foreach ($respuestas as &$r) {
                if ($r["marcador"] == $nroRespuestaCorrecta) {
                    $r["esCorrecta"] = true;
                    break;
                }
            }
            unset($r);

            foreach ($respuestas as $r) {
                $ok = $this->insertRespuestaDePregunta(
                    $r["texto"], $idPregunta, $r["esCorrecta"]);
                if ($ok) {
                    $contadorInsertsCorrectos++;
                }
            }
            unset($r);

        }
        return $contadorInsertsCorrectos;
    }

    // Métodos para desarrollo

    public function resetPartidasDelUsuario($idUsuario)
    {
        $q = "DELETE FROM partida
              WHERE jugador_id = :idUsuario";
        $params = [
            ["columna" => "idUsuario", "valor" => $idUsuario]
        ];
        $result = $this->database->query($q, 'DELETE', $params);
        return $result["success"];
    }

    public function resetUsuario_Pregunta($idUsuario)
    {
        $q = "DELETE FROM usuario_pregunta
              WHERE usuario_id = :idUsuario";
        $params = [
            ["columna" => "idUsuario", "valor" => $idUsuario]
        ];
        $result = $this->database->query($q, 'DELETE', $params);
        return $result["success"];
    }

    public function resetReportes()
    {
        $q = "TRUNCATE TABLE reporte";
        $result = $this->database->query($q, 'UPDATE', []);
        if ($result["success"]) {
            $q = "UPDATE pregunta
                  SET id_estado = 1
                  WHERE id != 999";
            return $this->database->query($q, 'UPDATE', [])["success"];
        }
        return false;
    }

    // CONSULTAS A LA BASE DE DATOS

    public function getCategoriasMenosLaDePregunta($idPregunta)
    {
        $q = "SELECT c.* FROM categoria c
              JOIN pregunta p ON p.id_categoria != c.id
              WHERE p.id = :idPregunta";
        $params = [
            ["columna" => "idPregunta", "valor" => (int)$idPregunta]
        ];
        $result = $this->database->query($q, 'MULTIPLE', $params);
        if ($result["success"]) {
            return $result["data"];
        }
        return $result["success"];
    }

    public function obtenerEstadosMenosElDePregunta($idPregunta)
    {
        $q = "SELECT e.* FROM estado e
              JOIN pregunta p ON p.id_estado != e.id
              WHERE p.id = :idPregunta";
        $params = [
            ["columna" => "idPregunta", "valor" => (int)$idPregunta]
        ];
        $result = $this->database->query($q, 'MULTIPLE', $params);
        if ($result["success"]) {
            return $result["data"];
        }
        return $result["success"];
    }

    public function getReportesDePreguntaConId($idPregunta)
    {
        $q = "SELECT r.* ,
              p.texto AS pregunta
              FROM pregunta AS p
              JOIN reporte r ON p.id = r.id_pregunta
              WHERE p.id = :idPregunta";
        $params = [
            ["columna" => "idPregunta", "valor" => (int)$idPregunta]
        ];
        $result = $this->database->query($q, "MULTIPLE", $params);
        if ($result["success"]) {
            return $result["data"];
        }
        return $result;
    }

    public function getReportes()
    {
        $q = "SELECT r.* ,
              p.texto AS pregunta,
              COUNT(*) AS cantidad_reportes
              FROM reporte AS r
              JOIN pregunta p ON p.id = r.id_pregunta
              GROUP BY r.id_pregunta";
        $result = $this->database->query($q, "MULTIPLE", []);
        if ($result["success"]) {
            return $result["data"];
        }
        return $result;
    }

    public function getSugeridas()
    {
        $q = "SELECT * FROM pregunta
              WHERE id_estado = 1";
        $result = $this->database->query($q, "MULTIPLE", []);
        if ($result["success"]) {
            return $result["data"];
        }
        return $result;
    }

    public function getPreguntas()
    {
        $q = "SELECT p.*, 
              e.descripcion AS estado,
              e.color as estadoColor,
              c.descripcion AS categoria,
              c.color AS color
              FROM pregunta p
              JOIN estado e ON p.id_estado = e.id
              JOIN categoria c ON c.id = p.id_categoria";
        $result = $this->database->query($q, "MULTIPLE", []);
        if ($result["success"]) {
            return $result["data"];
        }
        return $result;
    }

    public function getCategorias()
    {
        $q = "SELECT *
              FROM categoria";
        $result = $this->database->query($q, 'MULTIPLE', []);
        if ($result["success"]) {
            return $result["data"];
        }
        return $result;
    }

    public function getPartidas()
    {
        $q = "SELECT p.id, u.username as jugador_id, p.puntaje
              FROM partida p
              JOIN usuario u ON p.jugador_id = u.id";
        $result = $this->database->query($q, 'MULTIPLE', []);
        if ($result["success"]) {
            return $result["data"];
        } else {
            return $result;
        }
    }

    public function getRanking()
    {
        $q = "SELECT 
                    u.username, 
                    u.id AS usuario_id,
                    MAX(p.puntaje) AS puntaje_maximo
              FROM partida p
              JOIN usuario u ON p.jugador_id = u.id
              GROUP BY p.jugador_id
              ORDER BY puntaje_maximo DESC
              LIMIT 10";
        $result = $this->database->query($q, 'MULTIPLE', []);
        if ($result["success"]) {
            return $result["data"];
        } else {
            return $result;
        }
    }

    /** Obtener partidas de un usuario por id.
     * @param $idUsuario
     * @return mixed
     */
    public function getPartidasDelUsuario($idUsuario): mixed
    {
        $q = "SELECT * FROM partida
              WHERE jugador_id = :id";
        $params = [
            ["columna" => "id", "valor" => $idUsuario]
        ];
        $result = $this->database->query($q, 'MULTIPLE', $params);
        if ($result["success"]) {
            return $result["data"];
        }
        return $result;
    }

    /** Obtener estados de pregunta (aprobada, pendiente, etc)
     * @return mixed
     */
    public function getEstados(): mixed
    {
        $q = "SELECT *
              FROM estado";
        $result = $this->database->query($q, 'MULTIPLE', []);
        if ($result["success"]) {
            return $result["data"];
        }
        return $result;
    }

    // refactorizar querys :TODO
    public function obtenerRespondidasMalasBuenas($idUsuario)
    {
        $q = "SELECT COUNT(respondida_correctamente) as correctas
              FROM usuario_pregunta
              WHERE usuario_id = :idUsuario AND respondida_correctamente = true";
        $params = [
            ["columna" => "idUsuario", "valor" => $idUsuario]
        ];
        $result = $this->database->query($q, 'SINGLE', $params);
        $data["correctas"] = $result["data"];

        $q = "SELECT COUNT(respondida_correctamente) as incorrectas
              FROM usuario_pregunta
              WHERE usuario_id = :idUsuario AND respondida_correctamente = false";
        $params = [
            ["columna" => "idUsuario", "valor" => $idUsuario]
        ];
        $result = $this->database->query($q, 'SINGLE', $params);
        $data["incorrectas"] = $result["data"];

        if ($result["success"]) {
            return $data;
        }
        return $result;
    }

    private function updatePartida($idUsuario, $idPartida, $puntos)
    {
        $puntos = (int)$puntos;
        $q = "UPDATE partida
              SET puntaje = puntaje + :puntaje
              WHERE jugador_id = :id AND id = :partidaId";
        $params = [
            ["columna" => "id", "valor" => $idUsuario],
            ["columna" => "puntaje", "valor" => $puntos],
            ["columna" => "partidaId", "valor" => $idPartida],
        ];
        $result = $this->database->query($q, 'UPDATE', $params);
        return $result["success"];
    }

    public function insertRespuestaDePartida($idUsuario, $idPregunta, $state)
    {
        $q = "INSERT INTO usuario_pregunta 
              (usuario_id, pregunta_id, respondida_correctamente) 
              VALUES (:idUsuario, :idPregunta, :respondioBien)";
        $params = [
            ["columna" => "idUsuario", "valor" => $idUsuario],
            ["columna" => "idPregunta", "valor" => $idPregunta],
            ["columna" => "respondioBien", "valor" => $state]
        ];
        $result = $this->database->query($q, 'INSERT', $params);
        return $result["success"];
    }


    /** Obtener pregunta de la base de datos por id.
     * @param $id
     * @return mixed
     */
    public function obtenerPreguntaPorId($id)
    {
        $q = "SELECT 
              p.texto as pregunta_str, 
              p.id, 
              p.id_categoria, 
              p.id_estado, 
              c.descripcion as categoria,
              e.id as id_estado,
              e.descripcion as estado
              FROM pregunta p 
              JOIN categoria c on p.id_categoria = c.id
              JOIN estado e on p.id_estado = e.id
              WHERE p.id = :id";
        $params = [
            ["columna" => "id", "valor" => $id]
        ];
        $result = $this->database->query($q, 'SINGLE', $params);
        if ($result["success"]) {
            return $result["data"];
        }
        return $result;
    }

    public function obtenerRespuestasDePregunta($id)
    {
        $q = "SELECT 
                    texto as respuesta_str, 
                    id as respuesta_id,
                    esCorrecta
              FROM respuesta
              WHERE id_pregunta = :id";
        $params = [
            ["columna" => "id", "valor" => $id]
        ];
        $result = $this->database->query($q, 'MULTIPLE', $params);
        if ($result["success"]) {
            return $result["data"];
        }
        return $result;
    }

    public function getCantidadPreguntasBD()
    {
        $q = "SELECT COUNT(*) AS total_registros FROM pregunta";
        $result = $this->database->query($q, 'SINGLE', []);
        if ($result["success"]) {
            return $result["data"]["total_registros"];
        }
        return $result;
    }

    private function obtenerPreguntasNoRespondidasDelUsuarioPorNivel($idUsuario)
    {
        $preguntasDelNivelDelUsuario = $this->buscarPreguntasParaUsuarioPorNivel($idUsuario);

        // hay preguntas de su nivel que todavía no respondió
        if (!empty($preguntasDelNivelDelUsuario)) {
            return $preguntasDelNivelDelUsuario;
        } else {
            $result = $this->resetearPreguntasRespondidasPorUsuario($idUsuario);
            if ($result) {
                return $this->buscarPreguntasParaUsuarioPorNivel($idUsuario);
            } else return false;
        }
    }

    private function obtenerPreguntasNoRespondidasDelUsuario($idUsuario)
    {
        if (!$this->hayMasPreguntasParaUsuario($idUsuario)) {
            $this->resetearPreguntasRespondidasPorUsuario($idUsuario);
        }

        $q = "SELECT p.id as id, p.texto as pregunta_str
              FROM pregunta p 
              LEFT JOIN usuario_pregunta up 
                  ON p.id = up.pregunta_id AND up.usuario_id = :id
              JOIN estado e
                  ON p.id_estado = e.id AND e.id = 2
              WHERE up.pregunta_id IS NULL
              ORDER BY RAND()";
        $params = [
            ["columna" => "id", "valor" => $idUsuario]
        ];
        return $this->database->query($q, 'MULTIPLE', $params);
    }

    /** Crear nueva partida.
     * @param $idUsuario
     * @return mixed
     */
    public function insertPartida($idUsuario): mixed
    {
        $q = "INSERT INTO partida (jugador_id, puntaje) VALUES (:id,0)";
        $params = [
            ["columna" => "id", "valor" => $idUsuario]
        ];
        $result = $this->database->query($q, 'INSERT', $params);
        if ($result["success"]) {
            return $this->database->getUltimoIdGenerado();
        } else return false;
    }

    public function insertReporte($idUsuario, $idPregunta, $texto)
    {
        $q = "INSERT INTO reporte 
              (texto, id_usuario, id_pregunta) 
              VALUES (:texto, :idUsuario, :idPregunta)";
        $params = [
            ["columna" => "texto", "valor" => $texto],
            ["columna" => "idUsuario", "valor" => $idUsuario],
            ["columna" => "idPregunta", "valor" => $idPregunta]
        ];
        $result = $this->database->query($q, 'INSERT', $params);
        return $result["success"];
    }

    private function updateEstadoPregunta($idPregunta)
    {
        $q = "UPDATE pregunta
              SET id_estado = 4
              WHERE id = :idPregunta";
        $params = [
            ["columna" => "idPregunta", "valor" => $idPregunta]
        ];
        $result = $this->database->query($q, 'UPDATE', $params);
        return $result["success"];
    }


    private function actualizarRespondidasUsuario($idUsuario)
    {
        $q = "UPDATE usuario SET cantidad_respondidas = cantidad_respondidas+1
               WHERE id = :id";
        $params = [
            ["columna" => "id", "valor" => $idUsuario]
        ];
        return $this->database->query($q, 'UPDATE', $params);
    }

    private function actualizarRespondidasPregunta($idPregunta)
    {
        $q = "UPDATE pregunta SET cantidad_respondidas = cantidad_respondidas+1
               WHERE id = :id";
        $params = [
            ["columna" => "id", "valor" => $idPregunta]
        ];
        return $this->database->query($q, 'UPDATE', $params);
    }


    private function actualizarAcertadasUsuario($idUsuario)
    {
        $q = "UPDATE usuario SET cantidad_acertadas = cantidad_acertadas+1
               WHERE id = :id";
        $params = [
            ["columna" => "id", "valor" => $idUsuario]
        ];
        return $this->database->query($q, 'UPDATE', $params);
    }

    private function actualizarAcertadasPregunta($idPregunta)
    {
        $q = "UPDATE pregunta SET cantidad_acertadas = cantidad_acertadas+1
               WHERE id = :id";
        $params = [
            ["columna" => "id", "valor" => $idPregunta]
        ];
        return $this->database->query($q, 'UPDATE', $params);
    }

    private function obtenerCantidadDeJugadores()
    {
        $q = "SELECT COUNT(*) AS jugadores FROM jugador";
        $result = $this->database->query($q, "SINGLE", []);
        if ($result["success"]) {
            return $result["data"];
        }
        return $result["success"];
    }

    private function obtenerCantidadRespondidasPorUsuario($idUsuario)
    {
        $q = "SELECT cantidad_respondidas FROM usuario 
               WHERE id = :id";
        $params = [
            ["columna" => "id", "valor" => $idUsuario]
        ];
        $resultado = $this->database->query($q, 'SINGLE', $params);
        return $resultado["data"]["cantidad_respondidas"] ?? 0;
    }

    private function obtenerCantidadAcertadasPorUsuario($idUsuario)
    {
        $q = "SELECT cantidad_acertadas FROM usuario 
               WHERE id = :id";
        $params = [
            ["columna" => "id", "valor" => $idUsuario]
        ];
        $resultado = $this->database->query($q, 'SINGLE', $params);
        return $resultado["data"]["cantidad_acertadas"] ?? 0;
    }

    private function obtenerCantidadRespondidasPorPregunta($idPregunta)
    {
        $q = "SELECT cantidad_respondidas FROM pregunta 
               WHERE id = :id";
        $params = [
            ["columna" => "id", "valor" => $idPregunta]
        ];
        $resultado = $this->database->query($q, 'SINGLE', $params);
        return $resultado["data"]["cantidad_respondidas"] ?? 0;
    }

    private function obtenerCantidadAcertadasPorPregunta($idPregunta)
    {
        $q = "SELECT cantidad_acertadas FROM pregunta 
               WHERE id = :id";
        $params = [
            ["columna" => "id", "valor" => $idPregunta]
        ];
        $resultado = $this->database->query($q, 'SINGLE', $params);
        return $resultado["data"]["cantidad_acertadas"] ?? 0;
    }

    private function obtenerPreguntasParaUsuarioPorNivel($idUsuario, $minimo, $maximo)
    {
        $q = "SELECT p.id as id, p.texto as pregunta_str, p.cantidad_acertadas, p.cantidad_respondidas
              FROM pregunta p 
              LEFT JOIN usuario_pregunta up 
                  ON p.id = up.pregunta_id AND up.usuario_id = :id
              JOIN estado e
                  ON p.id_estado = e.id AND e.id = 2
              WHERE up.pregunta_id IS NULL
              AND p.cantidad_respondidas >= 0 -- antes 5
              AND (
                  p.cantidad_acertadas/p.cantidad_respondidas BETWEEN :minimo AND :maximo 
                      OR p.cantidad_respondidas = 0
                  )
              ORDER BY RAND()";

        $params = [
            ["columna" => "id", "valor" => $idUsuario],
            ["columna" => "minimo", "valor" => $minimo],
            ["columna" => "maximo", "valor" => $maximo]
        ];

        $result = $this->database->query($q, 'MULTIPLE', $params);

        if ($result["success"]) {
            return $result["data"];
        } else return $result["success"];
    }

    private function obtenerPreguntasParaUsuario($idUsuario)
    {
        $q = "SELECT p.id as id, p.texto as pregunta_str
              FROM pregunta p 
              LEFT JOIN usuario_pregunta up 
                  ON p.id = up.pregunta_id AND up.usuario_id = :id
              WHERE up.pregunta_id IS NULL
              ORDER BY RAND()";
        $params = [
            ["columna" => "id", "valor" => $idUsuario],
        ];
        return $this->database->query($q, 'MULTIPLE', $params);
    }

    /** Insertar pregunta en la base de datos.
     * @param $texto string texto pregunta
     * @param $idCategoria
     * @return mixed
     */
    private function insertarPregunta($texto, $idCategoria): mixed
    {
        $q = " INSERT INTO pregunta(texto, id_categoria, id_estado)
                   VALUES (:texto, :id_categoria, :id_estado)";
        $params = [
            ["columna" => "texto", "valor" => $texto],
            ["columna" => "id_categoria", "valor" => $idCategoria],
            ["columna" => "id_estado", "valor" => 1]
        ];
        $result = $this->database->query($q, "INSERT", $params);
        return $result["success"];
    }

    private function insertRespuestaDePregunta(
        $texto, $idPregunta, $esCorrecta = false)
    {
        $q = "INSERT INTO respuesta (texto, id_pregunta, esCorrecta)
              VALUES (:texto, :id_pregunta, :esCorrecta)";
        $params = [
            ["columna" => "texto", "valor" => $texto],
            ["columna" => "id_pregunta", "valor" => $idPregunta],
            ["columna" => "esCorrecta", "valor" => $esCorrecta],
        ];
        $result = $this->database->query($q, "INSERT", $params);
        return $result["success"];
    }

    /** Cambiar estado de pregunta a APROBADA
     * @param $idPregunta
     * @return mixed
     */
    private function updatePreguntaAprobada($idPregunta)
    {
        $q = "UPDATE pregunta
              SET id_estado = 2 
              WHERE id = :idPregunta";
        $params = [
            ["columna" => "idPregunta", "valor" => $idPregunta]
        ];
        $result = $this->database->query($q, "UPDATE", $params);
        return $result["success"];
    }

    /** Resetea todas las preguntas que respondió
     * @param $idUsuario
     * @return array
     */
    private function resetearPreguntasRespondidasPorUsuario($idUsuario): bool
    {
        $this->verVariable("Reseteo las que respondió.");

        $q = "DELETE FROM usuario_pregunta
              WHERE usuario_id = :id";
        $params = [
            ["columna" => "id", "valor" => $idUsuario]
        ];

        $result = $this->database->query($q, 'DELETE', $params);
        return $result["success"];
    }

    /** Actualizar pregunta (texto, categoria, estado)
     * @param string $texto
     * @param int $idCategoria
     * @param int $idPregunta
     * @return mixed
     */
    private function updatePregunta(string $texto, int $idCategoria, int $idPregunta, int $idEstado): mixed
    {
        $q = "UPDATE pregunta 
              SET texto = :texto,
                  id_categoria = :categoria,
                  id_estado = :estado
             WHERE id = :id";
        $params = [
            ["columna" => "texto", "valor" => $texto],
            ["columna" => "categoria", "valor" => $idCategoria],
            ["columna" => "id", "valor" => $idPregunta],
            ["columna" => "estado", "valor" => $idEstado],
        ];
        $result = $this->database->query($q, "UPDATE", $params);
        return $result["success"];
    }

    /** Elimina las respuestas asociadas a una pregunta
     * @param $idPregunta
     * @return mixed
     */
    private function eliminarRespuestasDePregunta($idPregunta)
    {
        $q = "DELETE FROM respuesta WHERE id_pregunta = :id";
        $params = [
            ["columna" => "id", "valor" => $idPregunta],
        ];
        $result = $this->database->query($q, "DELETE", $params);
        return $result["success"];
    }

    /** Cambiar estado de pregunta a DESACTIVADA
     * @param $idPregunta
     * @return mixed
     */
    private function updatePreguntaDesactivar($idPregunta)
    {
        $q = "UPDATE pregunta
              SET id_estado = 5
              WHERE id = :id";
        $params = [
            ["columna" => "id", "valor" => $idPregunta],
        ];
        $result = $this->database->query($q, "UPDATE", $params);
        return $result["success"];
    }

    private function updatePreguntaRechazar($idPregunta)
    {
        $q = "UPDATE pregunta
              SET id_estado = 3
              WHERE id = :id";
        $params = [
            ["columna" => "id", "valor" => $idPregunta],
        ];
        $result = $this->database->query($q, "UPDATE", $params);
        return $result["success"];
    }

    private function obtenerPreguntaAprobadaPorId($idPregunta)
    {
        $q = "SELECT 
              p.id as id,
              p.texto as pregunta_str
              FROM pregunta p 
              JOIN estado e ON e.id = p.id_estado AND e.id = 2
              WHERE p.id = :idPregunta";
        $params = [
            ["columna" => "idPregunta", "valor" => $idPregunta]
        ];
        $result = $this->database->query($q, "SINGLE", $params);
        if ($result["success"]) {
            return $result["data"];
        } else return $result["success"];
    }

    private function convertirRespuestas($respuestas, $idPregunta): array
    {
        $result = [];
        foreach ($respuestas as $r) {
            $r["id_pregunta"] = $idPregunta;
            $result[] = $r;
        }
        return $result;
    }

    private function saberMinMaxSegunNivelDeUsuario($nivel): array
    {
        switch ($nivel) {
            case $nivel === 'ALTO':
                $minimo = 0;
                $maximo = 0.29;
                break;
            case $nivel === 'MEDIO':
                $minimo = 0.3;
                $maximo = 0.7;
                break;
            default:
                $minimo = 0.71;
                $maximo = 1;
        }
        return ["minimo" => $minimo, "maximo" => $maximo];
    }

    private function verVariable($data): void
    {
        echo '<pre>' . print_r($data, true) . '</pre>';
    }

}