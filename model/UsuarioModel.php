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
        $queryJugador = "
        INSERT INTO jugador (id)
        VALUES (:id)";

        $paramsJugador = [
            ["columna" => "id", "valor" => $usuario['id']]
        ];

        $resultJugador = $this->database->query($queryJugador, 'INSERT', $paramsJugador);

        if ($resultJugador["success"]) {
            // Opcional: Actualizar estado de verificado
            $this->actualizarEstadoVerificado($usuario['id']);
            return ['success' => true, 'message' => 'Jugador registrado con éxito.'];
        }

        return ['success' => false, 'message' => 'Error al registrar el jugador.'];
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
        FROM usuario
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
            UPDATE usuario
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
            (nombre, apellido, username, email, password, anio_nacimiento, id_sexo, latitud, longitud, verificado)
        VALUES 
            (:nombre, :apellido, :username, :email, :password, :anio_nacimiento, :id_sexo, :latitud, :longitud, :verificado)";

        $params = [
            ["columna" => "nombre", "valor" => $usuario['nombre']],
            ["columna" => "apellido", "valor" => $usuario['apellido']],
            ["columna" => "username", "valor" => $usuario['username']],
            ["columna" => "email", "valor" => $usuario['email']],
            ["columna" => "password", "valor" => $usuario['password']],
            ["columna" => "anio_nacimiento", "valor" => $usuario['anio_nacimiento']],
            ["columna" => "id_sexo", "valor" => $usuario['id_sexo']],
            ["columna" => "latitud", "valor" => $usuario['latitud']],
            ["columna" => "longitud", "valor" => $usuario['longitud']],
            ["columna" => "verificado", "valor" => 0]
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
                   u.latitud,          
                   u.longitud, 
                   s.id AS sexoId, s.nombre AS sexoNombre ,
                   u.verificado,
                   u.cantidad_respondidas,
                   u.cantidad_acertadas,
                   u.musica
            FROM usuario u
            JOIN sexo s ON u.id_sexo = s.id
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
        $query = '
SELECT u.*, 
       j.id AS jugador_id, 
       e.id AS editor_id, 
       a.id AS administrador_id 
    FROM usuario u
    LEFT JOIN jugador j ON u.id = j.id
    LEFT JOIN editor e ON u.id = e.id
    LEFT JOIN administrador a ON u.id = a.id
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

    public function subirRespuesta($respuesta){
        $queryRespuesta="
        INSERT INTO respuesta(texto,id_pregunta, esCorrecta)
        values(:texto, :id_pregunta, :esCorrecta)";

        $params = [
            ["columna" => "texto", "valor" => $respuesta["texto"]],
            ["columna" => "id_pregunta", "valor" => $respuesta["id_pregunta"]],
            ["columna" => "esCorrecta", "valor" => $respuesta["esCorrecta"]]
        ];
        return $this->database->query($queryRespuesta, 'INSERT', $params);
    }

    public function guardarSugerencia($texto, $id_categoria,$respuestas): bool
    {
        $query = "
        INSERT INTO pregunta(texto, id_categoria, id_estado)
        VALUES (:texto, :id_categoria, :id_estado)";
        $params = [
            ["columna" => "texto", "valor" => $texto],
            ["columna" => "id_categoria", "valor" => $id_categoria],
            ["columna" => "id_estado", "valor" => 1]
        ];
        $result = $this->database->query($query, 'INSERT', $params);

        $ultimoId = $this->database->getUltimoIdGenerado();

        $queryRespuesta="
        INSERT INTO respuesta(respuestas,ultimoId, esCorrecta)
        values(:respuestas, :ultimoId, :esCorrecta)";

        $respuesta1=["texto"=>$respuestas["incorrecta1"], "id_pregunta"=>$ultimoId, "esCorrecta"=>0];
        $respuesta2=["texto"=>$respuestas["incorrecta2"], "id_pregunta"=>$ultimoId, "esCorrecta"=>0];
        $respuesta3=["texto"=>$respuestas["incorrecta3"], "id_pregunta"=>$ultimoId, "esCorrecta"=>0];
        $respuesta4=["texto"=>$respuestas["correcta"], "id_pregunta"=>$ultimoId, "esCorrecta"=>1];

        $this->subirRespuesta($respuesta1);
        $this->subirRespuesta($respuesta2);
        $this->subirRespuesta($respuesta3);
        $this->subirRespuesta($respuesta4);

        return $result["success"];
    }

    public function esAdmin(int $idUsuario): bool
    {
        $query = "SELECT COUNT(*) AS total FROM administrador WHERE id = :id";
        $params = [
            ["columna" => "id", "valor" => $idUsuario]
        ];
        $result = $this->database->query($query, "SINGLE", $params);

        return $result['success'] && $result['data']['total'] > 0;
    }


    public function esEditor($idUsuario) {
        $query = "SELECT COUNT(*) AS total FROM editor WHERE id = :id";
        $params = [
            ["columna" => "id", "valor" => $idUsuario]
        ];
        $result = $this->database->query($query, "SINGLE", $params);

        return $result['success'] && $result['data']['total'] > 0;
    }


    public function esJugador($idUsuario) {
        $query = "SELECT COUNT(*) AS total FROM jugador WHERE id = :id";
        $params = [
            ["columna" => "id", "valor" => $idUsuario]
        ];
        $result = $this->database->query($query, "SINGLE", $params);

        return $result['success'] && $result['data']['total'] > 0;
    }

    private function actualizarEstadoVerificado($idUsuario)
    {
        $query = "UPDATE usuario
                  SET verificado = :verificado
                  WHERE id = :id";

        $params = [
            ["columna" => "verificado", "valor" => 1],
            ["columna" => "id", "valor" => $idUsuario]
        ];

        return $this->database->query($query, 'UPDATE', $params);
    }

    public function obtenerUsuariosPorSexo () {
        $q = "SELECT COUNT(s.nombre) as cantidad, s.nombre
              FROM USUARIO u JOIN sexo s ON u.id_sexo = s.id
              GROUP BY s.nombre";
        $result = $this->database->query($q, "MULTIPLE");
        if ($result["success"]) {
            return $result["data"];
        }
        return $result["success"];
    }

    public function actualizarUsuario($idUsuario, $campos){
        foreach ($campos as $clave => $valor) {
            $q = "UPDATE usuario SET $clave = :valor WHERE id = :id";
            var_dump($q);
            $params = [
                ["columna" => $clave, "valor" => $valor],
                ["columna" => "id" , "valor" => $idUsuario]
            ];
            var_dump($params);
            $this->database->query($q, 'UPDATE', $params);
        }
    }

    public function actualizarMusica($idUsuario, $activarMusica){

        $q = "UPDATE usuario
              SET musica = :musica 
              WHERE id = :id";

        $params = [
            ["columna" => "musica", "valor" => $activarMusica],
             ["columna" => "id" , "valor" => $idUsuario]
        ];
        return $this->database->query($q, 'UPDATE', $params);
    }
}