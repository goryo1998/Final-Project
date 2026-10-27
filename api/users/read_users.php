<?php
// api/users/read_users.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

// Include database connection
require_once '../../config/db.php';

try {
    // SQL query to pull users and join their role name
    $query = "SELECT u.id, u.username, u.email, u.phone_number, u.status, r.role_name 
              FROM users u 
              JOIN roles r ON u.role_id = r.id";
              
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    
    // Fetch all records
    $users = $stmt->fetchAll();

    // Return the data as JSON
    http_response_code(200);
    echo json_encode(["status" => "success", "data" => $users]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>