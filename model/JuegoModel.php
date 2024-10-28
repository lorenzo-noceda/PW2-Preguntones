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
        $data["pregunta"] = $this->getPreguntaRandom($idUsuario);
        $data["respuestas"] = $this->getRespuestasDePregunta($data["pregunta"]["id"]);
        return $data;
    }


    public function getEstados()
    {
        $q = "SELECT *
              FROM estado";
        $result = $this->database->query($q, 'MULTIPLE', []);
        if ($result["success"]) {
            return $result["data"];
        }
        return $result;
    }

    public function getPreguntas1()
    {
        $q = "SELECT *
              FROM pregunta";
        $result = $this->database->query($q, 'MULTIPLE', []);
        if ($result["success"]) {
            return $result["data"];
        }
        return $result;
    }

    public function getRespuestas()
    {
        $q = "SELECT *
              FROM respuesta";
        $result = $this->database->query($q, 'MULTIPLE', []);
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

    public function getRespuestasDePreguntaTest($id)
    {
        $result = $this->getPreguntaPorIdTest($id);
        if ($result["success"]) {
            return $result["data"]["respuestas"];
        } else return false;
    }


    // Metodo para probar mientras no este la DB establecida
    public function getPreguntaPorIdTest($id)
    {
        $data = [];
        $data["success"] = false;
        $data["data"] = null;
        foreach ($this->getPreguntas() as $pregunta) {
            if ((int)$pregunta["pregunta"]["id"] == (int)$id) {
                $data["data"] = $pregunta;
                break;
            }
        }
        if ($data["data"] != null) {
            $data["success"] = true;
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

    // CONSULTAS A LA BASE DE DATOS

    public function getPreguntaPorId($id)
    {
        $q = "SELECT *
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
            ["columna" => "id", "valor" => (int)$id]
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
        if ($result["success"]) {
            return $result["data"];
        }
        return $result;
    }

    // helpers de clase
    private function verVariable($data): void
    {
        echo '<pre>' . print_r($data, true) . '</pre>';
    }

}