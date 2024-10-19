<?php

class Database
{
    private $conexion;
    private $stmt = null;

    public function __construct($host, $username, $password, $database)
    {
        $this->iniciarConexion($host, $username, $password, $database);
    }

    private function iniciarConexion($host, $username, $password, $database)
    {
        $dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";
        try {
            $this->conexion = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } catch (PDOException $e) {
            throw new Exception("Error en la conexión: " . $e->getMessage());
        }
    }

    public function query($query, $mode, $params = [])
    {
        try {
            $this->stmt = $this->conexion->prepare($query); // Prepara la consulta

            // Vincula los parámetros a la consulta
            foreach ($params as $columna => $valor) {
                $this->stmt->bindValue(":{$columna}", $valor);
            }

            $success = $this->ejecutarConsulta(); // Ejecuta la consulta

            $response = ["success" => $success];
            if ($success && in_array($mode, ['SINGLE', 'MULTIPLE'])) {
                // Obtiene los resultados según el modo solicitado
                $response['data'] = ($mode === "SINGLE") ? $this->stmt->fetch(PDO::FETCH_ASSOC) : $this->stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            return $response;
        } catch (PDOException $e) {
            throw new Exception("Error en la ejecución de la consulta: " . $e->getMessage());
        }
    }

    private function ejecutarConsulta()
    {
        return $this->stmt ? $this->stmt->execute() : false;
    }

    public function cerrarConexion()
    {
        $this->conexion = null;
    }

    public function __destruct()
    {
        $this->cerrarConexion();
    }
}