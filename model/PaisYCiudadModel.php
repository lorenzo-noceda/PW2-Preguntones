<?php

class PaisYCiudadModel
{

    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }


    public function getPaises(){
        $query= "SELECT * from pais";
        $result = $this->database->query($query, "MULTIPLE", []);

        if ($result["success"]) {
            return $result["data"];
        } else {
            return [];
        }
    }

    public function getCiudades($idPais)
    {
        $query= "SELECT c.* from ciudad c
                    join pais p on c.id_pais = p.id 
                    where c.id_pais = $idPais";
        $result = $this->database->query($query, "MULTIPLE", []);
        if ($result["success"]) {
            return $result["data"];
        } else {
            return [];
        }

    }
}