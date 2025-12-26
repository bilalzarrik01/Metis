<?php

class DB
{
    private string $host = "localhost";
    private string $db   = "metis";
    private string $user = "root";
    private string $pass = "azer1234";
    private int $port = 3306;

    private static ?PDO $instance = null;

    private function __construct() {}

    public static function connect(): PDO
    {
        if (self::$instance === null) {
            try {
                self::$instance = new PDO(
                    "mysql:host=localhost;dbname=metis;port=3306;charset=utf8",
                    "root",
                    "azer1234",
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]
                );
            } catch (PDOException $e) {
                die("DB connection error: " . $e->getMessage());
            }
        }

        return self::$instance;
    }
}
