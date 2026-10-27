<?php
// api/users/verify_process.php
session_start();

ini_set('display_errors', 0); 
header("Content-Type: application/json; charset=UTF-8");

// Connect database properties
require_once '../../config/db.php';

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->otp) && !empty($_SESSION['temp_user_id'])) {
    $user_id = $_SESSION['temp_user_id'];
    $otp_input = trim(htmlspecialchars(strip_tags($data->otp)));

    try {
        // Look up active user verification tokens and user role if available
        $query = "SELECT u.id, u.username, u.otp_code, u.otp_expires_at, r.role_name AS role_name
                  FROM users u
                  \

                  LEFT JOIN roles r ON u.role_id = r.id
                  WHERE u.id = :id LIMIT 1";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':id' => $user_id]);
        $user = $stmt->fetch();

        if ($user) {
            $current_time = date('Y-m-d H:i:s');

            if ($user['otp_code'] === $otp_input && $current_time <= $user['otp_expires_at']) {
                
                // Clear original email verification variables
                $clear_query = "UPDATE users SET otp_code = NULL, otp_expires_at = NULL WHERE id = :id";
                $pdo->prepare($clear_query)->execute([':id' => $user_id]);

                unset($_SESSION['temp_user_id']);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'] ?? 'User';

                echo json_encode([
                    "status" => "success",
                    "message" => "Email verified successfully. Logging you in now.",
                    "redirect" => "admin_dashboard.p1hp"
                ]);
                exit;
            } else {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Invalid or expired confirmation code."]);
                exit;
            }
        } else {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "Active session matching data not found."]);
            exit;
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Database link connectivity interruption."]);
        exit;
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Bad request credentials payload."]);
    exit;
}
?>
