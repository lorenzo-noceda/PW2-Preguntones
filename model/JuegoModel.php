<?php

class JuegoModel
{

    private $database;
    CONST NIVEL_BAJO = "BAJO", NIVEL_MEDIO = "MEDIO", NIVEL_ALTO = "ALTO";

    public function __construct($database)
    {
        $this->database = $database;
    }


    public function empezar($idUsuario, $hayPregunta = false): array
    {
        if($hayPregunta){
            $data["pregunta"] = $this->getPreguntaPorId($hayPregunta);
        } else {
            $data["pregunta"] = $this->getPreguntaRandom($idUsuario);
            $data["id_pregunta"] = $data["pregunta"]["id"];
            $data["id_partida"] = $this->insertPartida($idUsuario);
        }

        $data["respuestas"] = $this->getRespuestasDePregunta($data["pregunta"]["id"]);
        shuffle($data["respuestas"]); // delegar despues
        return $data;
    }
  

    public function reportar($idUsuario, $idPregunta, $stringTexto)
    {
        $preguntaBuscada = $this->getPreguntaPorId($idPregunta);
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
        // Functiona OK
        $preguntasDB = $this->obtenerPreguntasNoRespondidasDelUsuarioPorNivel($idUsuario);
        $indicePreguntaRandom = array_rand($preguntasDB);
        return $preguntasDB[$indicePreguntaRandom];
    }

    public function guardarRespuesta($idUsuario, $idPregunta, $idPartida, $state)
    {
        $result = $this->insertRespuesta($idUsuario, $idPregunta, $state);

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

    private function resetearPreguntasRespondidasPorUsuario($idUsuario)
    {
        $q = "DELETE FROM usuario_pregunta
              WHERE usuario_id = :id";

        $params = [
            ["columna" => "id", "valor" => $idUsuario]
        ];

        return $this->database->query($q, 'DELETE', $params);
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
            $data["idPregunta"]
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

    // NUEVO PARA ACTUALIZAR PREGUNTA

    private function updatePregunta(string $texto, int $idCategoria, int $idPregunta)
    {
        $q = "UPDATE pregunta 
              SET texto = :texto,
                  id_categoria = :categoria,
                  id_estado = 2
             WHERE id = :id";
        $params = [
            ["columna" => "texto", "valor" => $texto],
            ["columna" => "categoria", "valor" => $idCategoria],
            ["columna" => "id", "valor" => $idPregunta],
        ];
        $result = $this->database->query($q, "UPDATE", $params);
        return $result["success"];
    }

    private function eliminarRespuestasDePregunta($idPregunta)
    {
        $q = "DELETE FROM respuesta WHERE id_pregunta = :id";
        $params = [
            ["columna" => "id", "valor" => $idPregunta],
        ];
        $result = $this->database->query($q, "DELETE", $params);
        return $result["success"];
    }

    private function insertRespuestasDePregunta(
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

    private function actualizarRespuestasDePregunta(
        int $idPregunta,
        array $respuestasCrudo,
        int $nroRespuestaCorrecta): int
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
                $ok = $this->insertRespuestasDePregunta(
                    $r["texto"], $idPregunta, $r["esCorrecta"]);
                if ($ok) {
                    $contadorInsertsCorrectos++;
                }
            }
            unset($r);

        }
        return $contadorInsertsCorrectos;
    }


    // FIN DE LO NUEVO

    // Métodos para desarrollo

    public function getUsuariosTest()
    {
        $q = "SELECT u.* FROM usuario u
              JOIN jugador j ON u.id = j.id";
        $result = $this->database->query($q, 'MULTIPLE', []);
        if ($result["success"]) {
            return $result["data"];
        }
        return $result;
    }

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
    // Getters, inserts, updates, deletes
    // getCategoriasMenosLaDePregunta(idPregunta)
    // getReporteConId(id)
    // getReportes()
    // getSugeridas()
    // getPreguntas() todas
    // getCategorias()
    // getPartidas()
    // getRanking()
    // getPartidasDelUsuario(id)
    // getEstados()
    // obtenerRespondidasMalasBuenas(id)
    // tienePreguntas(id)
    // getPreguntaPorId(id)
    // getRespuestasDePregunta(id)
    // getCantidadPreguntasBD()
    // obtenerPreguntasNoRespondidasDelUsuario(id)
    // insertPartida(id)
    // insertRespuesta(idPreg,idUser,State)
    // insertReporte(iduser,idPreg,texto)
    // updatePartida(idUsuario,idPartida,puntos)
    // updateEstadoPregunta(idPregunta)
    // desactivarPregunta(idPregunta)
    // eliminarPreguntasRespondidasDelUsuario(id)

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
              JOIN categoria c ON c.id = p.id_categoria;";
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

    public function insertRespuesta($idUsuario, $idPregunta, $state)
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


    public function getPreguntaPorId($id)
    {
        $q = "SELECT 
              p.texto as pregunta_str, 
              p.id, p.id_categoria, p.id_estado, c.descripcion as categoria
              FROM pregunta p 
              JOIN categoria c on p.id_categoria = c.id
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

    public function getRespuestasDePregunta($id)
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
        $result = $this->buscarPreguntasParaUsuarioPorNivel($idUsuario);
        // hay preguntas de su nivel que todavía no respondió
        if ($result["success"] && !empty($result["data"])) {
            return $result["data"];
        } elseif ($result["success"]) {
            if($this->hayMasPreguntasParaUsuarioPorNivel($idUsuario)){
                // hay preguntas de su nivel pero ya las respondió
                $this->resetearPreguntasRespondidasPorUsuario($idUsuario);
                return $this->obtenerPreguntasNoRespondidasDelUsuarioPorNivel($idUsuario);
            }
            // no hay mas preguntas de su nivel, hay que darle preguntas no respondidas
            $result = $this->obtenerPreguntasNoRespondidasDelUsuario($idUsuario);
        }
        return $result["data"];
    }

    private function obtenerPreguntasNoRespondidasDelUsuario($idUsuario){
        if(!$this->hayMasPreguntasParaUsuario($idUsuario)){
            $this->resetearPreguntasRespondidasPorUsuario($idUsuario);
        }

        $q = "SELECT p.id as id, p.texto as pregunta_str
              FROM pregunta p 
              LEFT JOIN usuario_pregunta up 
                  ON p.id = up.pregunta_id AND up.usuario_id = :id
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

    public function desactivarPregunta($idPregunta)
    {
        $q = "UPDATE pregunta
              SET id_estado = 5
              WHERE id = :id";
        $params = [
            ["columna" => "id", "valor" => $idPregunta, "tipo" => "int"],
        ];
        $result = $this->database->query($q, "UPDATE", $params);
        return $result["success"];
    }

    /**
     * @throws Exception
     */
    public function actualizarRespondidas($idUsuario, $idPregunta){
        try {
            $this->database->beginTransaction();
            $this->actualizarRespondidasUsuario($idUsuario);
            $this->actualizarRespondidasPregunta($idPregunta);
            $this->database->commit();
        } catch(Exception $e){
            $this->database->rollBack();
            throw new Exception($e->getMessage());
        }
    }

    private function actualizarRespondidasUsuario($idUsuario){
        $q = "UPDATE usuario SET cantidad_respondidas = cantidad_respondidas+1
               WHERE id = :id";
        $params = [
            ["columna" => "id", "valor" => $idUsuario]
        ];
        return $this->database->query($q, 'UPDATE', $params);
    }

    private function actualizarRespondidasPregunta($idPregunta){
        $q = "UPDATE pregunta SET cantidad_respondidas = cantidad_respondidas+1
               WHERE id = :id";
        $params = [
            ["columna" => "id", "valor" => $idPregunta]
        ];
        return $this->database->query($q, 'UPDATE', $params);
    }
    public function actualizarAcertadas($idUsuario, $idPregunta){
        $this->actualizarAcertadasUsuario($idUsuario);
        $this->actualizarAcertadasPregunta($idPregunta);
    }

    private function actualizarAcertadasUsuario($idUsuario){
        $q = "UPDATE usuario SET cantidad_acertadas = cantidad_acertadas+1
               WHERE id = :id";
        $params = [
            ["columna" => "id", "valor" => $idUsuario]
        ];
        return $this->database->query($q, 'UPDATE', $params);
    }

    private function actualizarAcertadasPregunta($idPregunta){
        $q = "UPDATE pregunta SET cantidad_acertadas = cantidad_acertadas+1
               WHERE id = :id";
        $params = [
            ["columna" => "id", "valor" => $idPregunta]
        ];
        return $this->database->query($q, 'UPDATE', $params);
    }

    // helpers de clase
    private function verVariable($data): void
    {
        echo '<pre>' . print_r($data, true) . '</pre>';
    }

    private function obtenerNivelDeUsuario($idUsuario){
        $respondidas = $this->obtenerCantidadRespondidasPorUsuario($idUsuario);
        if($respondidas < 10) {
            return self::NIVEL_MEDIO;
        }
        $acertadas = $this->obtenerCantidadAcertadasPorUsuario($idUsuario);
        $nivelUsuario = $acertadas / $respondidas;
        switch ($nivelUsuario) {
            CASE $nivelUsuario < 0.3:
                return self::NIVEL_BAJO;
            CASE $nivelUsuario < 0.71:
                return self::NIVEL_MEDIO;
            DEFAULT:
                return self::NIVEL_ALTO;
        }
    }

    private function obtenerNivelDePregunta($idPregunta){
        $respondidas = $this->obtenerCantidadRespondidasPorPregunta($idPregunta);
        if($respondidas < 10) {
            return self::NIVEL_MEDIO;
        }
        $acertadas = $this->obtenerCantidadAcertadasPorPregunta($idPregunta);
        $nivelPregunta = $acertadas / $respondidas;
        switch ($nivelPregunta) {
            CASE $nivelPregunta < 0.33:
                return self::NIVEL_BAJO;
            CASE $nivelPregunta < 0.66:
                return self::NIVEL_MEDIO;
            DEFAULT:
                return self::NIVEL_ALTO;
        }
    }

    private function obtenerCantidadRespondidasPorUsuario($idUsuario){
        $q = "SELECT cantidad_respondidas FROM usuario 
               WHERE id = :id";
        $params = [
            ["columna" => "id", "valor" => $idUsuario]
        ];
        $resultado = $this->database->query($q, 'SINGLE', $params);
        return $resultado["data"]["cantidad_respondidas"] ?? 0;
    }

    private function obtenerCantidadAcertadasPorUsuario($idUsuario){
        $q = "SELECT cantidad_acertadas FROM usuario 
               WHERE id = :id";
        $params = [
            ["columna" => "id", "valor" => $idUsuario]
        ];
        $resultado = $this->database->query($q, 'SINGLE', $params);
        return $resultado["data"]["cantidad_acertadas"] ?? 0;
    }

    private function obtenerCantidadRespondidasPorPregunta($idPregunta){
        $q = "SELECT cantidad_respondidas FROM pregunta 
               WHERE id = :id";
        $params = [
            ["columna" => "id", "valor" => $idPregunta]
        ];
        $resultado = $this->database->query($q, 'SINGLE', $params);
        return $resultado["data"]["cantidad_respondidas"] ?? 0;
    }

    private function obtenerCantidadAcertadasPorPregunta($idPregunta){
        $q = "SELECT cantidad_acertadas FROM pregunta 
               WHERE id = :id";
        $params = [
            ["columna" => "id", "valor" => $idPregunta]
        ];
        $resultado = $this->database->query($q, 'SINGLE', $params);
        return $resultado["data"]["cantidad_acertadas"] ?? 0;
    }

    private function buscarPreguntasParaUsuarioPorNivel($idUsuario){
        $nivelUsuario = $this->obtenerNivelDeUsuario($idUsuario);
        switch ($nivelUsuario) {
            CASE $nivelUsuario === 'ALTO':
                $minimo = 0; $maximo = 0.29;
                break;
            CASE $nivelUsuario === 'MEDIO':
                $minimo = 0.3; $maximo = 0.7;
                break;
            DEFAULT:
                $minimo = 0.71; $maximo = 1;
        }

        $q = "SELECT p.id as id, p.texto as pregunta_str
              FROM pregunta p 
              LEFT JOIN usuario_pregunta up 
                  ON p.id = up.pregunta_id AND up.usuario_id = :id
              WHERE up.pregunta_id IS NULL
              AND p.cantidad_respondidas >= 5
              AND p.cantidad_acertadas/p.cantidad_respondidas BETWEEN :minimo AND :maximo
              ORDER BY RAND()";

        $params = [
            ["columna" => "id", "valor" => $idUsuario],
            ["columna" => "minimo", "valor" => $minimo],
            ["columna" => "maximo", "valor" => $maximo]
        ];

        return $this->database->query($q, 'MULTIPLE', $params);
    }

    private function hayMasPreguntasParaUsuarioPorNivel($idUsuario){
        $result = $this->buscarPreguntasParaUsuarioPorNivel($idUsuario);
        if(count($result["data"]) > 0) {
            return true;
        }
        return false;
    }

    private function hayMasPreguntasParaUsuario($idUsuario){
        $result = $this->buscarPreguntasParaUsuario($idUsuario);
        if(count($result["data"]) > 0) {
            return true;
        }
        return false;
    }

    private function buscarPreguntasParaUsuario($idUsuario){
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

}