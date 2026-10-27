<?php
// faculty_verify.php - Frontend OTP Entry Form + Backend Verification
session_start();

// Gatekeeper: If no active faculty login attempt is found, redirect back
if (!isset($_SESSION['temp_teacher_id'])) {
    header("Location: login.php");
    exit;
}

// Handle backend verification requests (POST with JSON)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    header("Content-Type: application/json; charset=UTF-8");
    
    require_once __DIR__ . '/config/db.php';
    
    $data = json_decode(file_get_contents("php://input"));
    
    if (empty($data->otp)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Missing verification code."]);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id, otp_code, otp_expires_at FROM teachers WHERE id = ?");
        $stmt->execute([$_SESSION['temp_teacher_id']]);
        $teacher = $stmt->fetch();
        
        if ($teacher && $teacher['otp_code'] === $data->otp && date('Y-m-d H:i:s') <= $teacher['otp_expires_at']) {
            // Clear OTP and set authenticated session
            $pdo->prepare("UPDATE teachers SET otp_code = NULL, otp_expires_at = NULL WHERE id = ?")->execute([$teacher['id']]);
            
            $_SESSION['teacher_id'] = $teacher['id'];
            $_SESSION['teacher_authenticated'] = true;
            unset($_SESSION['temp_teacher_id']);
            
            echo json_encode(["status" => "success", "message" => "Verified successfully.", "redirect" => "faculty_dashboard.php"]);
            exit;
        } else {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Invalid or expired code."]);
            exit;
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Database error."]);
        exit;
    }
}

// Display frontend form (GET request or page load)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Verification - UEP Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        /* Consistent Fluid Translucent Background Setup */
        body {
            background: linear-gradient(135deg, rgba(255,255,0,0.85) 0%, rgba(0,0,255,0.85) 100%), 
                        url('uep-background.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Glassmorphism Card Style */
        .glass-card {
            background: rgba(255, 255, 255, 0.45);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 25px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
            padding: 3rem 2rem;
            text-align: center;
        }
        
        /* Interactive Badge - Faculty Theme (Purple/Violet) */
        .auth-badge {
            width: 70px;
            height: 70px;
            background-color: #7c3aed;
            color: #fff;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(124, 58, 237, 0.3);
        }

        /* Translucent Pill OTP Input */
        .otp-input {
            background: rgba(255, 255, 255, 0.5) !important;
            border: none !important;
            border-radius: 50px !important;
            padding: 0.75rem;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 6px;
            text-align: center;
            color: #000;
        }
        .otp-input::placeholder {
            letter-spacing: normal;
            font-size: 1rem;
            font-weight: normal;
            color: #555;
        }

        /* Submit Button Styling - Faculty Purple */
        .btn-verify {
            background-color: #7c3aed;
            color: #fff;
            font-weight: 700;
            text-transform: uppercase;
            border-radius: 50px;
            padding: 0.75rem;
            border: none;
            transition: all 0.3s ease;
        }
        .btn-verify:hover {
            background-color: #6d28d9;
            box-shadow: 0 4px 15px rgba(124, 58, 237, 0.3);
        }
    </style>
</head>
<body>

    <div class="glass-card mx-3">
        <div class="auth-badge">
            <i class="bi bi-shield-check"></i>
        </div>

        <h3 class="fw-bold mb-2">Faculty Verification</h3>
        <p class="text-dark small mb-4">
            A 6-digit verification security code has been sent to your registered 
            <strong>Email Address</strong> inbox.
        </p>

        <div id="verifyAlert" class="alert d-none small p-2 rounded-4" role="alert"></div>

        <form id="otpForm">
            <div class="mb-4">
                <input type="text" class="form-control otp-input" id="otp_code" maxlength="6" placeholder="Enter 6-Digit Code" required autocomplete="off" pattern="\d{6}">
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-verify">Verify Code</button>
            </div>
        </form>
        
        <div class="mt-4">
            <a href="login.php" class="text-dark small fw-semibold text-decoration-none">← Return to Login</a>
        </div>
    </div>

    <script>
        // Process OTP verification via API
        document.getElementById('otpForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const inputCodeValue = document.getElementById('otp_code').value;
            const alertBox = document.getElementById('verifyAlert');
            
            alertBox.className = "alert small p-2 rounded-4 alert-info d-block";
            alertBox.innerText = "Verifying faculty code...";

            // Submit to this same page (faculty_verify.php backend)
            fetch('faculty_verify.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ otp: inputCodeValue })
            })
            .then(res => {
                if (!res.ok) throw new Error("HTTP error status " + res.status);
                return res.json();
            })
            .then(data => {
                if(data.status === 'success') {
                    alertBox.className = "alert small p-2 rounded-4 alert-success d-block";
                    alertBox.innerText = "Code verified! Logging you in now...";
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500);
                } else {
                    alertBox.className = "alert small p-2 rounded-4 alert-danger d-block";
                    alertBox.innerText = data.message;
                }
            })
            .catch(err => {
                console.error("Error:", err);
                alertBox.className = "alert small p-2 rounded-4 alert-danger d-block";
                alertBox.innerText = "An error occurred. Please try again.";
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
