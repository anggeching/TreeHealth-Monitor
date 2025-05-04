<?php
// db/pdo_conn.php

function getPDOConnection($dbname = '') {
    $host = 'localhost';
    $user = 'root';
    $pass = '';

    try {
        $conn = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, 
                PDO::ATTR_PERSISTENT => true
            ]
        );
        return $conn;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}
?>