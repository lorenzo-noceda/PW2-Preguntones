<?php
class MysqlObjectDatabase
{
    private $conn;
    public function __construct($host, $port, $username, $password, $database)
    {
        $this->conn = new mysqli($host, $username, $password, $database, $port);
    }

    public function query($sql){
        $result = $this->conn->query($sql);
        return  $result->fetch_all( MYSQLI_ASSOC );
    }

    public function __destruct()
    {
        $this->conn->close();
    }
}
