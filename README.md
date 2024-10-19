# Programación web 2 - Preguntones

```php
<?php
class Database
{

    private $conexion;
    private $host;
    private $username;
    private $password;
    private $database;

    private $stmt = null;

    public function __construct($host, $username, $password, $database)
    {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
    }

    /**
     * Iniciar la conexión.
     */
    public function iniciarConexion(): void
    {
        $p = "mysql:host={$this->host};dbname={$this->database}";
        $this->conexion = new PDO($p, $this->username, $this->password) or die("Error en la conexion");
    }

    /**
     * Obtener la conexión de la base de datos.
     * @return mixed
     */
    public function getConexion()
    {
        return $this->conexion;
    }

    /**
     * Realiza FetchAll asociativo sobre la consulta realizada.
     * @return array con los resultados si es que se obtuvieron. Caso contrario array vacio.
     */
    public function fetchAllAssoc(): array
    {
        if ($this->stmt) {
            return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    public function fetchSingleAssoc(): array
    {
        if ($this->stmt) {
            $result = $this->stmt->fetch(PDO::FETCH_ASSOC);
            if ($result !== false) {
                return $result;
            }
        }
        return [];
    }

    /**
     * Cierra conexión con la base de datos.
     * @return void
     */
    public function cerrarSesion(): void
    {
        $this->conexion = null;
    }

    /**
     * Prepara consulta para ejecutar
     * @return array
     */
    public function query($query, $mode, $params = []): array
    {
        $response = [];
        $this->iniciarConexion();
        $this->stmt = $this->conexion->prepare($query);
        if (count($params) > 0) {
            foreach ($params as $param) {
                $this->stmt->bindParam(":{$param["columna"]}", $param["valor"]);
            }
        }
        if ($this->ejecutarConsulta()) {
            if ($mode == "ASSOC") {
                $response["data"] = $this->fetchAllAssoc();
            } elseif ($mode == "SINGLE") {
                $response["data"] = $this->fetchSingleAssoc();
            }
            // DELETE, UPDATE, INSERT solo bool
            $response["success"] = true;
        } else {
            $response["success"] = false;
        }
        $this->cerrarSesion();
        return $response;
    }

    /**
     * Ejecuta consulta.
     * @return true si se ejecutó correctamente. False si no se ejecutó.
     */
    public function ejecutarConsulta(): bool
    {
        if ($this->stmt) {
            return $this->stmt->execute();
        }
        return false;
    }

    public function setParametro(string $columna, string $valor)
    {
        $this->stmt->bindParam(":{$columna}", $valor);
    }


}


?>








<?php

class Database
{
    private $conexion; // Propiedad para la conexión
    private $stmt = null; // Propiedad para la sentencia, inicializada en null

    public function __construct(string $host, string $username, string $password, string $database)
    {
        $this->iniciarConexion($host, $username, $password, $database);
    }

    private function iniciarConexion(string $host, string $username, string $password, string $database): void
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

    public function query(string $query, string $mode, array $params = []): array
    {
        try {
            $this->stmt = $this->conexion->prepare($query);

            foreach ($params as $columna => $valor) {
                $this->stmt->bindValue(":{$columna}", $valor);
            }

            $success = $this->ejecutarConsulta();

            $response = ["success" => $success];
            if ($success && in_array($mode, ['SINGLE', 'MULTIPLE'])) {
                $response['data'] = ($mode === "SINGLE") ? $this->stmt->fetch(PDO::FETCH_ASSOC) : $this->stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            return $response;
        } catch (PDOException $e) {
            throw new Exception("Error en la ejecución de la consulta: " . $e->getMessage());
        }
    }

    private function ejecutarConsulta(): bool
    {
        return $this->stmt ? $this->stmt->execute() : false; // Verifica si $stmt está inicializado
    }

    public function cerrarConexion(): void
    {
        $this->conexion = null; // Cierra la conexión
    }

    public function __destruct()
    {
        $this->cerrarConexion(); // Cierra la conexión al destruir la clase
    }
}
