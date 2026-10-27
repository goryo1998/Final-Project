<?php
// verify_email.php
session_start();

// Gatekeeper: If no active login attempt sequence is found, kick them back to login
if (!isset($_SESSION['temp_user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - UEP Portal</title>
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
        
        /* Interactive Circle Envelope Badge */
        .auth-badge {
            width: 70px;
            height: 70px;
            background-color: #2200ff; /* Matches premium Royal Blue theme accent */
            color: #fff;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(34, 0, 255, 0.3);
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

        /* Submit Button Styling */
        .btn-verify {
            background-color: #2200ff;
            color: #fff;
            font-weight: 700;
            text-transform: uppercase;
            border-radius: 50px;
            padding: 0.75rem;
            border: none;
            transition: all 0.3s ease;
        }
        .btn-verify:hover {
            background-color: #1a00cc;
            box-shadow: 0 4px 15px rgba(34, 0, 255, 0.3);
        }
    </style>
</head>
<body>

    <div class="glass-card mx-3">
        <div class="auth-badge">
            <i class="bi bi-envelope-check-fill"></i>
        </div>

        <h3 class="fw-bold mb-2">Email Verification</h3>
        <p class="text-dark small mb-4">
            A 6-digit verification security code has been sent to your registered 
            <strong>Email Address</strong> inbox.
        </p>

        <div id="verifyAlert" class="alert d-none small p-2 rounded-4" role="alert"></div>

        <form id="otpForm">
            <div class="mb-4">
                <input type="text" class="form-control otp-input" id="otp_code" maxlength="6" placeholder="Enter 6-Digit Code" required autocomplete="off">
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-verify">Verify OTP Code</button>
            </div>
        </form>
        
        <div class="mt-4">
            <a href="login.php" class="text-dark small fw-semibold text-decoration-none">← Return to Login</a>
        </div>
    </div>

    <script>
        // Process token verification API submission handler
        document.getElementById('otpForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const inputCodeValue = document.getElementById('otp_code').value;
            const alertBox = document.getElementById('verifyAlert');
            
            alertBox.className = "alert small p-2 rounded-4 alert-info d-block";
            alertBox.innerText = "Checking token code validity...";

            // Targets the endpoint securely using a clean relative path layout
            fetch('api/users/verify_otp.php', {
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
                    alertBox.innerText = "Email confirmed! Logging you in now...";
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500);
                } else {
                    alertBox.className = "alert small p-2 rounded-4 alert-danger d-block";
                    alertBox.innerText = data.message;
                }
            })
            .catch(err => {
                console.error("Error details:", err);
                alertBox.className = "alert small p-2 rounded-4 alert-danger d-block";
                alertBox.innerText = "An error occurred during authentication connecting to server.";
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>