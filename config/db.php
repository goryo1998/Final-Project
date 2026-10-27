<?php
// config/db.php

$host = 'localhost';
$db   = 'student_management_db';
$user = 'root'; // Change if you set a password for MySQL
$pass = '153088';     // Change if you set a password for MySQL
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throws exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetches data as associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Turns off emulation for real prepared statements
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // If connection fails, return a JSON error response
     header('Content-Type: application/json');
     echo json_encode(["status" => "error", "message" => "Database connection failed."]);
     exit;
}
?>