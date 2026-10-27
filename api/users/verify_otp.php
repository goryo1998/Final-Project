<?php

session_start();

// Disable standard HTML error displays so they don't break the JSON response formatting
ini_set('display_errors', 0); 
header("Content-Type: application/json; charset=UTF-8");

// Securely trace the absolute path to your database configuration file
$db_file_path = __DIR__ . '/../../config/db.php';

if (!file_exists($db_file_path)) {
    echo json_encode([
        "status" => "error", 
        "message" => "System configuration error: Database connector location configuration mismatch."
    ]);
    exit;
}

require_once $db_file_path;

// Accept raw JSON payload from the incoming JavaScript fetch request
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->otp) && !empty($_SESSION['temp_user_id'])) {
    $user_id = $_SESSION['temp_user_id'];
    $otp_input = trim(htmlspecialchars(strip_tags($data->otp)));

    try {
        // Query the database for the active OTP parameters assigned to this user
        $query = "SELECT u.id, u.username, u.otp_code, u.otp_expires_at, r.role_name AS role_name
                  FROM users u
                  LEFT JOIN roles r ON u.role_id = r.id
                  WHERE u.id = :id LIMIT 1";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':id' => $user_id]);
        $user = $stmt->fetch();

        if ($user) {
            $current_time = date('Y-m-d H:i:s');

            // Verify if the code matches and has not expired yet
            if ($user['otp_code'] === $otp_input && $current_time <= $user['otp_expires_at']) {
                
                // Clear out the used email OTP token data fields
                $clear_query = "UPDATE users SET otp_code = NULL, otp_expires_at = NULL WHERE id = :id";
                $pdo->prepare($clear_query)->execute([':id' => $user_id]);

                // Promote the session into a fully authenticated user state
                unset($_SESSION['temp_user_id']);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role_name'] ?? 'User';

                $_SESSION['user_authenticated'] = true;

                echo json_encode([
                    "status" => "success",
                    "message" => "Email verified successfully. Logging you in now.",
                    "redirect" => "admin_dashboard.php"
                ]);
                exit;
            } else {
                echo json_encode(["status" => "error", "message" => "Invalid or expired Email token code entry."]);
                exit;
            }
        } else {
            echo json_encode(["status" => "error", "message" => "User context validation mapping missing."]);
            exit;
        }
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Database failure handler tripped: " . $e->getMessage()]);
        exit;
    }
} else {
    echo json_encode(["status" => "error", "message" => "Authentication parameter validation block empty."]);
    exit;
}


?>