<?php

class UsuarioModel
{
    private $database;
    private $mailPresenter;
    private $qrGenerator;
    const urlParaQR = 'https://localhost/PW2-Preguntones/perfil/verPerfilUsuario?id=';

    public function __construct($database, $mailPresenter, $qrGenerator)
    {
        $this->database = $database;
        $this->mailPresenter = $mailPresenter;
        $this->qrGenerator = $qrGenerator;
    }

    public function buscarUsuarioPorId($idUsuario, $conPartidas)
    {
        $usuarioBuscado = $this->getUsuarioPorId($idUsuario);
        $urlParaGenerarQr = self::urlParaQR . $usuarioBuscado["id"];
        $usuarioBuscado["qr"] =
            $this->qrGenerator::getQrCodeParaImg($urlParaGenerarQr);
        if ($conPartidas) {
            $usuarioBuscado["partidas"] = $this->getPartidasDelUsuario($idUsuario);
            $usuarioBuscado["puntajeAcumulado"] = $this->getPuntajeAcumuladoDeUnUsuario($idUsuario)["puntaje_acumulado"];
        }
        return $usuarioBuscado;
    }


    public function guardarJugador($usuario)
    {
        $id = $this->getUltimoIdGenerado();

        $emailUsuario = $usuario['email'];
        $nombreUsuario = $usuario['nombre'];

        $query = "
                INSERT INTO jugador
                    (id, verificado)
                VALUES 
                    (:id, :verificado)";
        $params = [
            ["columna" => "id", "valor" => $id],
            ["columna" => "verificado", "valor" => 0],
        ];

        $this->enviarMailValidacion($emailUsuario, $nombreUsuario);

        return $this->database->query($query, 'INSERT', $params);
    }

    public function enviarMailValidacion($emailUsuario, $nombreUsuario)
    {
        $this->mailPresenter->setRecipient($emailUsuario, $nombreUsuario);
        $this->mailPresenter->setSubject('Confirmación de registro');
        $this->mailPresenter->setBody("<h1>Usuario registrado!</h1><br><a href='http://localhost/PW2-preguntones/registro/validarCorreo'>Cliquea aquí para validar tu correo</a>");

        try {
            if ($this->mailPresenter->sendEmail()) {
                echo 'El correo ha sido enviado';
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function getVerificacionDeUsuario($idusuario)
    {
        if ($idusuario != null) {
            $q = "
            SELECT verificado 
            FROM jugador
            WHERE id = :id";
            $params = [
                ["columna" => "id", "valor" => $idusuario],
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

    public function validarJugador($id)
    {
        $query = "
                UPDATE jugador
                SET verificado = :verificado
                WHERE id = :id";
        $params = [
            ["columna" => "id", "valor" => $id],
            ["columna" => "verificado", "valor" => true],
        ];
        return $this->database->query($query, 'UPDATE', $params);
    }

    public function getUltimoIdGenerado()
    {
        return $this->database->getUltimoIdGenerado();
    }




    // Consultas a la base de datos

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

    /** Obtener la suma del puntaje obtenido en todas sus partidas jugadas.
     * @param $idUsuario
     * @return mixed
     */
    public function getPuntajeAcumuladoDeUnUsuario($idUsuario): mixed
    {
        $q = "SELECT SUM(puntaje) AS puntaje_acumulado 
              FROM partida
              WHERE jugador_id = :id";
        $params = [
            ["columna" => "id", "valor" => $idUsuario]
        ];
        $result = $this->database->query($q, 'SINGLE', $params);
        if ($result["success"]) {
            return $result["data"];
        }
        return $result;
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

    public function getSexos()
    {
        $q = 'SELECT * FROM sexo';
        $result = $this->database->query($q, "MULTIPLE", []);
        if ($result["success"]) {
            return $result["data"];
        } else {
            return [];
        }
    }

    public function getSexosMenosElDelUsuario($sexoUsuario)
    {
        $query = "SELECT * FROM sexo
                 WHERE nombre != :sexo";
        $params = [
            ["columna" => "sexo", "valor" => $sexoUsuario]
        ];
        $result = $this->database->query($query, "MULTIPLE", $params);
        if ($result["success"]) {
            return $result["data"];
        } else {
            return [];
        }
    }

    public function registrarUsuario($usuario)
    {
        $usuario['password'] = password_hash($usuario['password'], PASSWORD_DEFAULT);
        return $this->guardarUsuario($usuario);
    }

    private function guardarUsuario($usuario)
    {
        $query = "
                INSERT INTO usuario
                    (nombre, apellido, username, email, password, anio_nacimiento, id_sexo, id_ciudad)
                VALUES 
                    (:nombre, :apellido, :username, :email, :password, :anio_nacimiento, :id_sexo, :id_ciudad)";
        $params = [
            ["columna" => "nombre", "valor" => $usuario['nombre']],
            ["columna" => "apellido", "valor" => $usuario['apellido']],
            ["columna" => "username", "valor" => $usuario['username']],
            ["columna" => "email", "valor" => $usuario['email']],
            ["columna" => "password", "valor" => $usuario['password']],
            ["columna" => "anio_nacimiento", "valor" => $usuario['anio_nacimiento']],
            ["columna" => "id_sexo", "valor" => $usuario['id_sexo']],
            ["columna" => "id_ciudad", "valor" => $usuario['id_ciudad']],
        ];

        $result = $this->database->query($query, 'INSERT', $params);
        if ($result["success"]) {
            $result["lastId"] = $this->database->getUltimoIdGenerado();
        }
        return $result;
    }

    private function insertarJugador($idUsuario)
    {
        $query = 'INSERT INTO jugador(id) VALUES(:id)';
        $params = [
            ["columna" => "id", "valor" => $idUsuario]
        ];
        return $this->database->query($query, 'INSERT', $params);
    }


    public function getUsuarioPorId($id)
    {
        if ($id != null) {
            $q = "
            SELECT u.id,
                   u.nombre,
                   u.apellido,
                   u.username,
                   u.email,
                   u.anio_nacimiento,
                   u.foto_perfil,
                   u.fecha_registro,
                   c.nombre AS ciudad, 
                   p.nombre AS pais,
                   s.id AS sexoId, s.nombre AS sexoNombre 
            FROM usuario u
            JOIN sexo s ON u.id_sexo = s.id
            JOIN ciudad c ON u.id_ciudad = c.id
            JOIN pais p ON c.id_pais = p.id
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

    public function getUsuarioPorUsername($username)
    {
        $query = 'SELECT u.*, j.verificado 
                  FROM usuario u 
                  JOIN jugador j on u.id = j.id
                  WHERE u.username = :username';
        $params = [
            ["columna" => "username", "valor" => $username],
        ];
        $result = $this->database->query($query, "SINGLE", $params);
        if ($result["success"]) {
            return $result["data"];
        } else {
            return null;
        }
    }

    public function getUsuarioPorCorreo($correo)
    {
        $query = 'SELECT * FROM usuario WHERE email = :email';
        $params = [
            ["columna" => "email", "valor" => $correo],
        ];
        $result = $this->database->query($query, "SINGLE", $params);
        if ($result["success"]) {
            return $result["data"];
        } else {
            return null;
        }
    }

    /**
     * @param $usuario
     * @return array
     */
    public function verificarCampos($usuario): array
    {
        $existeCorreo = $this->existeYaElCorreo($usuario["email"]);
        $existeNombreDeUsuario = $this->existeYaElUsuario($usuario["username"]);
        $error = "";
        $data = [];

        if ($existeCorreo) {
            $error = "Ya existe ese correo, elige otro.";
        }
        if ($existeNombreDeUsuario) {
            $error = "Ya existe nombre de usuario, elige otro.";
        }
        if ($usuario["password"] !== $usuario["confirmPassword"]) {
            $error = "Las contraseñas son diferentes.";
        }

        $data["message"] = $error;
        $data["success"] = empty($error);
        return $data;
    }

    public function existeYaElUsuario($username): bool
    {
        return $this->getUsuarioPorUsername($username) != null;
    }

    public function existeYaElCorreo($correo): bool
    {
        return $this->getUsuarioPorCorreo($correo) != null;
    }


}