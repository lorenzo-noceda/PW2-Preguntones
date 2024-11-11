<?php

class JuegoModel
{

    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }


    public function empezar($idUsuario): array
    {
        if(isset($_SESSION["id_pregunta"])){
            $data["pregunta"] = $this->getPreguntaPorId($_SESSION["id_pregunta"]);
        } else {
            $data["pregunta"] = $this->getPreguntaRandom($idUsuario);
            $data["id_pregunta"] = $data["pregunta"]["id"];
        }

        $data["respuestas"] = $this->getRespuestasDePregunta($data["pregunta"]["id"]);
        shuffle($data["respuestas"]); // delegar despues

        if(!isset($_SESSION["id_partida"])){
            $idPartida = $this->insertPartida($idUsuario);
            $data["id_partida"] = $idPartida;
        }

        return $data;
    }


    private function getPreguntaRandom($idUsuario)
    {
        // Functiona OK
        $preguntasDB = $this->obtenerPreguntasNoRespondidasDelUsuario($idUsuario);
        $indicePreguntaRandom = array_rand($preguntasDB);
        return $preguntasDB[$indicePreguntaRandom];
    }


    public function guardarResultados()
    {

    }

    /** Calcula cuantas respondió el usuario de todas las preguntas.
     * @param $idUsuario
     * @return int
     */
    public function getRespondidasDeUsuario($idUsuario): int
    {
        $noRespondidas = $this->obtenerPreguntasNoRespondidasDelUsuario($idUsuario);
        return $this->getCantidadPreguntasBD() - count($noRespondidas);
    }

    public function guardarRespondida()
    {

    }

    // Métodos para desarrollo

    public function getUsuariosTest () {
        $q = "SELECT u.* FROM usuario u
              JOIN jugador j ON u.id = j.id";
        $result = $this->database->query($q, 'MULTIPLE', []);
        if ($result["success"]) {
            return $result["data"];
        }
        return $result;
    }

    public function resetPartidasDelUsuario ($idUsuario) {
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

    // CONSULTAS A LA BASE DE DATOS

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

    public function getPartidas () {
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

    public function getRanking () {
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

    public function guardarRespuesta($idUsuario, $idPregunta, $idPartida,$state)
    {
        $result = $this->insertRespuesta($idUsuario, $idPregunta, $state);

        if ($state && $result) {
            // correcta y salio bien el insert
            $result = $this->updatePartida($idUsuario, $idPartida, 10);
        } else if (!$state && $result) {
            // incorrecta y salio bien el insert
            $result = $this->updatePartida($idUsuario, $idPartida, 0);
        } else {
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
                    texto as pregunta_str, 
                    id, id_categoria, id_estado
              FROM pregunta
              WHERE id = :id";

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

    private function obtenerPreguntasNoRespondidasDelUsuario($idUsuario)
    {
        $q = "SELECT p.id as id, p.texto as pregunta_str
              FROM pregunta p 
              LEFT JOIN usuario_pregunta up 
                  ON p.id = up.pregunta_id AND up.usuario_id = :id
              WHERE up.pregunta_id IS NULL";
        $params = [
            ["columna" => "id", "valor" => $idUsuario]
        ];
        $result = $this->database->query($q, 'MULTIPLE', $params);
        if ($result["success"] && !empty($result["data"])) {
            return $result["data"];
        } elseif($result["success"]){
            return $this->resetearPreguntasRespondidasDelUsuario($idUsuario);
        }
        return $result;
    }

    private function resetearPreguntasRespondidasDelUsuario($idUsuario)
    {
        $this->eliminarPreguntasRespondidasDelUsuario($idUsuario);
        return $this->obtenerPreguntasNoRespondidasDelUsuario($idUsuario);
    }

    private function eliminarPreguntasRespondidasDelUsuario($idUsuario)
    {
        $q = "DELETE FROM usuario_pregunta
              WHERE usuario_id = :id";

        $params = [
            ["columna" => "id", "valor" => $idUsuario]
        ];

        return $this->database->query($q, 'DELETE', $params);
    }

    // helpers de clase
    private function verVariable($data): void
    {
        echo '<pre>' . print_r($data, true) . '</pre>';
    }

}