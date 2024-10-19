<?php

class UsuarioModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function getUsuarioPorId($id) {
        if ($id != null) {
            $q = "
            SELECT u.*, c.nombre AS ciudad, p.nombre AS pais 
            FROM usuario u
            JOIN ciudad c on u.id_ciudad = c.id
            JOIN pais p on c.id_pais = p.id
            WHERE u.id = :id";
            $params = [
                ["columna" => "id", "valor" => $id],
            ];
            $result = $this->database->query($q, "SINGLE", $params);
            if ($result["success"]) {
                return $result["data"];
            } else {
                return [];
            }
        } else {
            return [];
            // Manejar que pasa si llega null
        }
    }

}