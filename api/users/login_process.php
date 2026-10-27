<?php
// api/users/login_process.php
session_start();

ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

// 1. Include Database Connection
require_once '../../config/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 2. Absolute Path Configuration using XAMPP htdocs Root
$project_root = $_SERVER['DOCUMENT_ROOT'] . '/IPT LAST NA TO';
$vendor_dir   = $project_root . '/PHPMailer-master/src/';

$exceptionPath = $vendor_dir . 'Exception.php';
$phpmailerPath = $vendor_dir . 'PHPMailer.php';
$smtpPath      = $vendor_dir . 'SMTP.php';

if (!file_exists($exceptionPath) || !file_exists($phpmailerPath) || !file_exists($smtpPath)) {
    http_response_code(500);
    echo json_encode([
        "status" => "error", 
        "message" => "PHPMailer files missing from vendor folder."
    ]);
    exit;
}

require_once $exceptionPath;
require_once $phpmailerPath;
require_once $smtpPath;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method Not Allowed"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->username) && !empty($data->password) && !empty($data->role)) {
    
    $username = htmlspecialchars(strip_tags($data->username));
    $password = $data->password;
    $selected_role = htmlspecialchars(strip_tags($data->role)); 

    try {
        // Retrieves the dynamic email address provided upon registration
        $query = "SELECT u.id, u.username, u.password, u.email, u.status, r.role_name 
                  FROM users u 
                  JOIN roles r ON u.role_id = r.id 
                  WHERE u.username = :username LIMIT 1";
                  
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch();

        if ($user) {
            if (isset($user['status']) && $user['status'] !== 'active') {
                http_response_code(403);
                echo json_encode(["status" => "error", "message" => "Your account is deactivated."]);
                exit;
            }

            if (strcasecmp($user['role_name'], $selected_role) !== 0) {
                http_response_code(401);
                echo json_encode(["status" => "error", "message" => "Unauthorized access: Role mismatch."]);
                exit;
            }

            if (password_verify($password, $user['password'])) {
                
                // Generate a random 6-digit code
                $otp_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $otp_expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));

                // Save parameters directly into the database row
                $update_query = "UPDATE users SET otp_code = :otp, otp_expires_at = :expires WHERE id = :id";
                $update_stmt = $pdo->prepare($update_query);
                $update_stmt->execute([
                    ':otp' => $otp_code,
                    ':expires' => $otp_expires_at,
                    ':id' => $user['id']
                ]);

                $_SESSION['temp_user_id'] = $user['id'];

                // --- PHPMailer Configuration ---
                $mail = new PHPMailer(true);

                try {
                    $mail->isSMTP();                                            
                    $mail->Host       = 'smtp.gmail.com';                     
                    $mail->SMTPAuth   = true;                                   
                    
                    // 🌟 CONFIGURATION REQUIREMENTS 🌟
                    $mail->Username   = 'gregsazon8@gmail.com'; // 1. Put your sending Gmail here
                    $mail->Password   = 'dcjysxpczcnyhizv';          // 2. Put your 16-character Google App Password here (NO SPACES)
                    
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         
                    $mail->Port       = 587;                                    
                    $mail->Timeout    = 25; 

                    $mail->SMTPOptions = array(
                        'ssl' => array(
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true
                        )
                    );

                    // 3. Sender address MUST match the Username Gmail address above
                    $mail->setFrom('gregsazon8@gmail.com', 'UEP Portal Security');
                    
                    // Sends directly to the user's dynamic registered email from the database
                    $mail->addAddress($user['email']);                        

                    $mail->isHTML(true);                                  
                    $mail->Subject = 'UEP Portal Secure Access Key';
                    $mail->Body    = "
                        <div style='font-family: Arial, sans-serif; padding: 25px; max-width: 480px; border: 1px solid #e0e0e0; border-radius: 12px; margin: auto;'>
                            <h2 style='color: #0022ff; margin-bottom: 5px;'>UEP Portal Login Verification</h2>
                            <p style='color: #555; font-size: 14px;'>Hello <b>" . htmlspecialchars($user['username']) . "</b>,</p>
                            <p style='color: #555; font-size: 14px;'>Your login confirmation security authentication key code is:</p>
                            <div style='background-color: #f7f9fc; border: 1px dashed #0022ff; padding: 15px; text-align: center; font-size: 28px; font-weight: 800; letter-spacing: 6px; margin: 25px 0; color: #000; border-radius: 8px;'>
                                " . $otp_code . "
                            </div>
                            <p style='font-size: 11px; color: #888;'>This code expires in 5 minutes.</p>
                        </div>
                    ";

                    $mail->send();
                    
                    echo json_encode([
                        "status" => "success",
                        "message" => "An authentication code has been sent directly to your registered email address.",
                        "redirect" => "verify_email.php"
                    ]);
                    exit;

                } catch (Exception $e) {
                    // Strictly outputs the genuine server error if authentication fails
                    http_response_code(500);
                    echo json_encode([
                        "status" => "error",
                        "message" => "Email transmission failed. Mailer Error: " . $mail->ErrorInfo
                    ]);
                    exit;
                }

            } else {
                http_response_code(401);
                echo json_encode(["status" => "error", "message" => "Incorrect password. Please try again."]);
                exit;
            }
        } else {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Username does not exist."]);
            exit;
        }

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Database table mismatch: " . $e->getMessage()]);
        exit;
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Missing login entry fields."]);
    exit;
}
?>