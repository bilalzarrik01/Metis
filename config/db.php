<?php

class DB
{
    private string $host = "localhost";
    private string $db   = "metis";
    private string $user = "root";
    private string $pass = "";
    private int $port = 3307;

    private static $instant;
    public function connect(): PDO
    {
        try {
            return new PDO(
                "mysql:host={$this->host};dbname={$this->db};port={$this->port};charset=utf8",
                $this->user,
                $this->pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            die("DB connection error: " . $e->getMessage());
        }


    }
}
