<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method Not Allowed"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

// Validate required fields (added first_name, last_name, and address)
if (!empty($data->role_id) && !empty($data->username) && !empty($data->password) && !empty($data->email) && !empty($data->first_name) && !empty($data->last_name)) {
    
    $role_id = filter_var($data->role_id, FILTER_VALIDATE_INT);
    $username = htmlspecialchars(strip_tags($data->username));
    $first_name = htmlspecialchars(strip_tags($data->first_name)); // New
    $middle_name = htmlspecialchars(strip_tags($data->middle_name ?? '')); // New
    $last_name = htmlspecialchars(strip_tags($data->last_name)); // New
    $address = htmlspecialchars(strip_tags($data->address ?? '')); // New
    $email = filter_var($data->email, FILTER_VALIDATE_EMAIL);
    $phone_number = !empty($data->phone_number) ? htmlspecialchars(strip_tags($data->phone_number)) : null;
    
    $password_hashed = password_hash($data->password, PASSWORD_BCRYPT);

    if (!$email) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid email format."]);
        exit;
    }

    try {
        // Updated SQL query with new columns
        $query = "INSERT INTO users (role_id, username, first_name, middle_name, last_name, address, password, email, phone_number) 
                  VALUES (:role_id, :username, :first_name, :middle_name, :last_name, :address, :password, :email, :phone_number)";
        
        $stmt = $pdo->prepare($query);

        $stmt->execute([
            ':role_id' => $role_id,
            ':username' => $username,
            ':first_name' => $first_name,
            ':middle_name' => $middle_name,
            ':last_name' => $last_name,
            ':address' => $address,
            ':password' => $password_hashed,
            ':email' => $email,
            ':phone_number' => $phone_number
        ]);

        http_response_code(201);
        echo json_encode(["status" => "success", "message" => "User created successfully."]);

    } catch (PDOException $e) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Incomplete data. Please fill all required fields."]);
}
?>