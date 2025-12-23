<?php

$host = "localhost";
$db   = "Metis";
$user = "root";
$pass = "";
$port = 3307;

$dsn = "mysql:host=$host;dbname=$db;port=$port;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       
        PDO::ATTR_EMULATE_PREPARES   => false,                 
    ]);
} catch (PDOException $e) {
    die("Erreur connexion DB : " . $e->getMessage());
}
