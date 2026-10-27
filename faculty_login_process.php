<?php
// faculty_login_process.php
session_start();

header("Content-Type: application/json; charset=UTF-8");

// Use absolute include paths relative to this file to avoid path issues
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$data = json_decode(file_get_contents("php://input"));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method Not Allowed"]);
    exit;
}

if (empty($data->email) || empty($data->password)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Missing login fields."]);
    exit;
}

$email = filter_var($data->email, FILTER_SANITIZE_EMAIL);

try {
    // 1. Find teacher by email
    $stmt = $pdo->prepare("SELECT id, email, password, first_name FROM teachers WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $teacher = $stmt->fetch();

    // 2. Verify password
    if ($teacher && password_verify($data->password, $teacher['password'])) {

        // 3. Generate OTP
        $otp_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $otp_expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        // 4. Save OTP to teachers table
        $update = $pdo->prepare("UPDATE teachers SET otp_code = ?, otp_expires_at = ? WHERE id = ?");
        $update->execute([$otp_code, $otp_expires_at, $teacher['id']]);

        $_SESSION['temp_teacher_id'] = $teacher['id'];

        // 5. Send Email via PHPMailer
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'gregsazon8@gmail.com';
            $mail->Password   = 'dcjysxpczcnyhizv';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('gregsazon8@gmail.com', 'UEP Faculty Portal');
            $mail->addAddress($teacher['email']);
            $mail->Subject = 'Faculty Portal Secure Access';
            $mail->Body = "Your verification code is: " . $otp_code;

            $mail->send();

            echo json_encode(["status" => "success", "message" => "Code sent to email.", "redirect" => "faculty_verify.php"]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Failed to send email: " . $mail->ErrorInfo]);
            exit;
        }

    } else {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Invalid email or password."]);
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
    exit;
}
?>