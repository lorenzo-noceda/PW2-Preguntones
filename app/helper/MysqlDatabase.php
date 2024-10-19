<?php
class MysqlDatabase
{
    private $conn;
    public function __construct($host, $port, $username, $password, $database)
    {
        $this->conn = mysqli_connect($host, $username, $password, $database, $port);

        if (!$this->conn) {
            die("Connection failed: " . mysqli_connect_error());
        }
    }

    public function query($sql){
        $result = mysqli_query($this->conn, $sql);
        return  mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    public function execute($sql){
        mysqli_query($this->conn, $sql);
        return $this->conn->affected_rows;
    }

    public function __destruct()
    {
        mysqli_close($this->conn);
    }
}
