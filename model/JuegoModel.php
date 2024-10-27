<?php

class JuegoModel
{

    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }


    public function empezar()
    {
        return $this->getPreguntaRandom();
    }

    public function getRespuestasDePreguntaTest($id)
    {
        $result =  $this->getPreguntaPorIdTest($id);
        if ($result["success"]) {
            return $result["data"]["respuestas"];
        } else return false;
    }

    public function getRespuestasDePregunta($id)
    {
        $q = "SELECT *
              FROM respuesta r
              JOIN pregunta p ON p.id = r.id
              WHERE p.id = :id";
        $params = [
            ["columna" => "id", "valor" => $id]
        ];
        $result = $this->database->query($q, 'MULTIPLE', $params);
        if ($result["success"]) {
            return $result["data"];
        }
        return $result;
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

    public function getPreguntaPorId($id)
    {
        $q = "SELECT *
              FROM pregunta
              WHERE id = :id";
        $params = [
            ["columna" => "id", "valor" => $id, "tipo" => "int"]
        ];
        $result = $this->database->query($q, 'SINGLE', $params);
        if ($result["success"]) {
            return $result["data"];
        }
        return $result;
    }

    private function getPreguntaRandom()
    {
        $preguntasDB = $this->getPreguntas();
        $indicePreguntaRandom = array_rand($preguntasDB, 1);
        return $preguntasDB[$indicePreguntaRandom];
    }

    private function getPreguntas()
    {
        $juego = [];

// Pregunta 1
        $juego[] = [
            "pregunta" => ["id" => 0, "pregunta" => "¿Cuál es el primer nombre de Messi?"],
            "respuestas" => [
                ["id" => 0, "respuesta" => "Lionel", "esCorrecta" => true, "preguntaId" => 0],
                ["id" => 1, "respuesta" => "Andres", "esCorrecta" => false, "preguntaId" => 0],
                ["id" => 2, "respuesta" => "Natalia", "esCorrecta" => false, "preguntaId" => 0],
                ["id" => 3, "respuesta" => "Diego", "esCorrecta" => false, "preguntaId" => 0],
            ]
        ];

// Pregunta 2
        $juego[] = [
            "pregunta" => ["id" => 1, "pregunta" => "¿En qué país se encuentra la Torre Eiffel?"],
            "respuestas" => [
                ["id" => 0, "respuesta" => "Francia", "esCorrecta" => true, "preguntaId" => 1],
                ["id" => 1, "respuesta" => "España", "esCorrecta" => false, "preguntaId" => 1],
                ["id" => 2, "respuesta" => "Italia", "esCorrecta" => false, "preguntaId" => 1],
                ["id" => 3, "respuesta" => "Alemania", "esCorrecta" => false, "preguntaId" => 1],
            ]
        ];

// Pregunta 3
        $juego[] = [
            "pregunta" => ["id" => 2, "pregunta" => "¿Cuál es el planeta más cercano al sol?"],
            "respuestas" => [
                ["id" => 0, "respuesta" => "Mercurio", "esCorrecta" => true, "preguntaId" => 2],
                ["id" => 1, "respuesta" => "Venus", "esCorrecta" => false, "preguntaId" => 2],
                ["id" => 2, "respuesta" => "Tierra", "esCorrecta" => false, "preguntaId" => 2],
                ["id" => 3, "respuesta" => "Marte", "esCorrecta" => false, "preguntaId" => 2],
            ]
        ];

// Pregunta 4
        $juego[] = [
            "pregunta" => ["id" => 3, "pregunta" => "¿Cuál es el océano más grande del mundo?"],
            "respuestas" => [
                ["id" => 0, "respuesta" => "Océano Pacífico", "esCorrecta" => true, "preguntaId" => 3],
                ["id" => 1, "respuesta" => "Océano Atlántico", "esCorrecta" => false, "preguntaId" => 3],
                ["id" => 2, "respuesta" => "Océano Índico", "esCorrecta" => false, "preguntaId" => 3],
                ["id" => 3, "respuesta" => "Océano Ártico", "esCorrecta" => false, "preguntaId" => 3],
            ]
        ];

// Pregunta 5
        $juego[] = [
            "pregunta" => ["id" => 4, "pregunta" => "¿Qué color se obtiene al mezclar azul y amarillo?"],
            "respuestas" => [
                ["id" => 0, "respuesta" => "Verde", "esCorrecta" => true, "preguntaId" => 4],
                ["id" => 1, "respuesta" => "Rojo", "esCorrecta" => false, "preguntaId" => 4],
                ["id" => 2, "respuesta" => "Naranja", "esCorrecta" => false, "preguntaId" => 4],
                ["id" => 3, "respuesta" => "Morado", "esCorrecta" => false, "preguntaId" => 4],
            ]
        ];

 // Pregunta 6
        $juego[] = [
            "pregunta" => ["id" => 3, "pregunta" => "¿Cuál es el animal terrestre más grande?"],
            "respuestas" => [
                ["id" => 0, "respuesta" => "Elefante", "esCorrecta" => true, "preguntaId" => 5],
                ["id" => 1, "respuesta" => "Jirafa", "esCorrecta" => false, "preguntaId" => 5],
                ["id" => 2, "respuesta" => "Hipopótamo", "esCorrecta" => false, "preguntaId" => 5],
                ["id" => 3, "respuesta" => "León", "esCorrecta" => false, "preguntaId" => 5],
            ]
        ];

        // Pregunta 7
        $juego[] = [
            "pregunta" => ["id" => 2, "pregunta" => "¿Cuántos continentes existen en la Tierra?"],
            "respuestas" => [
                ["id" => 0, "respuesta" => "5", "esCorrecta" => false, "preguntaId" => 6],
                ["id" => 1, "respuesta" => "6", "esCorrecta" => false, "preguntaId" => 6],
                ["id" => 2, "respuesta" => "7", "esCorrecta" => true, "preguntaId" => 6],
                ["id" => 3, "respuesta" => "8", "esCorrecta" => false, "preguntaId" => 6],
            ]
        ];

        // Pregunta 8
        $juego[] = [
            "pregunta" => ["id" => 4, "pregunta" => "¿Qué gas es esencial para la respiración humana?"],
            "respuestas" => [
                ["id" => 0, "respuesta" => "Oxígeno", "esCorrecta" => true, "preguntaId" => 7],
                ["id" => 1, "respuesta" => "Nitrógeno", "esCorrecta" => false, "preguntaId" => 7],
                ["id" => 2, "respuesta" => "Helio", "esCorrecta" => false, "preguntaId" => 7],
                ["id" => 3, "respuesta" => "Dióxido de carbono", "esCorrecta" => false, "preguntaId" => 7],
            ]
        ];


        //Pregunta 9
        $juego[] = [
            "pregunta" => ["id" => 5, "pregunta" => "¿Qué instrumento mide la temperatura?"],
            "respuestas" => [
                ["id" => 0, "respuesta" => "Barómetro", "esCorrecta" => false, "preguntaId" => 8],
                ["id" => 1, "respuesta" => "Termómetro", "esCorrecta" => true, "preguntaId" => 8],
                ["id" => 2, "respuesta" => "Anemómetro", "esCorrecta" => false, "preguntaId" => 8],
                ["id" => 3, "respuesta" => "Higrómetro", "esCorrecta" => false, "preguntaId" => 8],
            ]
        ];


        return $juego;
    }

}