<?php
// register.php
session_start();

// If already logged in, skip registration and head to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University of Eastern Philippines - Create Account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        /* Shared Background Layout */
        body {
            background: linear-gradient(135deg, rgba(255,255,0,0.85) 0%, rgba(0,0,255,0.85) 100%), 
                        url('images/bg.png') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 2rem 0; /* Extra spacing for mobile scrolling */
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
            max-width: 500px;
            padding: 2.5rem 2.5rem;
            text-align: center;
        }

        .university-logo {
            width: 80px;
            height: auto;
            margin-bottom: 1rem;
        }

        .text-header {
            font-size: 0.85rem;
            color: #111;
            line-height: 1.4;
        }

        .page-title {
            font-size: 1.6rem;
            font-weight: 800;
            text-transform: uppercase;
            color: #000;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
            letter-spacing: 0.5px;
        }

        .back-btn {
            color: #000;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin-bottom: 1.5rem;
            transition: transform 0.2s ease;
        }
        .back-btn:hover {
            transform: translateX(-3px);
            color: #222;
        }

        /* Translucent Pill Inputs */
        .input-pill {
            background: rgba(255, 255, 255, 0.5) !important;
            border: none !important;
            border-radius: 50px !important;
            padding: 0.65rem 1.5rem;
            font-size: 0.95rem;
            color: #000 !important;
        }
        .input-pill::placeholder {
            color: #666;
        }
        .input-pill:focus {
            box-shadow: 0 0 0 2px rgba(0, 0, 255, 0.2) !important;
        }

        /* Dropdown Select Field Styling Adjustment */
        select.input-pill {
            color: #444 !important;
        }

        /* Password Toggle Container */
        .password-container {
            position: relative;
        }
        .password-toggle {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #555;
            z-index: 10;
        }

        /* Royal Blue Action Button */
        .btn-pill-register {
            background-color: #2200ff;
            color: #fff;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.85rem;
            border-radius: 50px;
            padding: 0.75rem;
            border: none;
            transition: all 0.3s ease;
            letter-spacing: 0.5px;
        }
        .btn-pill-register:hover {
            background-color: #1a00cc;
            box-shadow: 0 4px 15px rgba(34, 0, 255, 0.3);
        }
    </style>
</head>
<body>

    <div class="glass-card mx-3">
        <img src="images/UEPLOGO.png" alt="UEP Logo" class="university-logo">
        <div class="text-header">
            <div class="text-muted small">Republic of the Philippines</div>
            <strong class="d-block text-uppercase my-1">University of Eastern Philippines</strong>
            <div class="small text-secondary">University Town, Northern Samar</div>
        </div>

        <h2 class="page-title">Create Account</h2>

        <a href="login.php" class="back-btn">
            <i class="bi bi-arrow-left"></i> Back to Login
        </a>

        <div id="regAlert" class="alert d-none small p-2 rounded-4" role="alert"></div>

        <form id="registerForm">
            
            <div class="mb-3">
                <select class="form-select input-pill" id="role_id" required>
                    <option value="" selected disabled>Select Account Role</option>
                    <option value="1">Admin</option>
                    <option value="2">Faculty</option>
                </select>
            </div>

            <div class="mb-3">
                <input type="text" class="form-control input-pill" id="username" placeholder="Username" required>
            </div>

            <div class="mb-3">
                <input type="email" class="form-control input-pill" id="email" placeholder="Email Address" required>
            </div>

            <div class="mb-3">
                <input type="text" class="form-control input-pill" id="phone_number" placeholder="Phone Number (e.g., 09123456789)">
            </div>

            <div class="mb-4 password-container">
                <input type="password" class="form-control input-pill" id="password" placeholder="Password" required>
                <i class="bi bi-eye-slash password-toggle" id="toggleRegPassword"></i>
            </div>
            <!-- Add these inside your <form id="registerForm"> -->
            <div class="mb-3">
                <input type="text" class="form-control input-pill" id="first_name" placeholder="First Name" required>
            </div>
            <div class="mb-3">
                <input type="text" class="form-control input-pill" id="middle_name" placeholder="Middle Name">
            </div>
            <div class="mb-3">
                <input type="text" class="form-control input-pill" id="last_name" placeholder="Last Name" required>
            </div>
            <div class="mb-3">
                <input type="text" class="form-control input-pill" id="address" placeholder="Complete Address" required>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-pill-register">Register Account</button>
            </div>
        </form>
    </div>

    <script>
        // 1. Password Visibility Eye Toggle Handler
        const toggleRegPassword = document.querySelector('#toggleRegPassword');
        const passwordInput = document.querySelector('#password');

        toggleRegPassword.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('bi-eye');
            this.classList.toggle('bi-eye-slash');
        });

        // 2. Form Submission Fetch Integration 
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const alertBox = document.getElementById('regAlert');
            alertBox.className = "alert d-none small p-2 rounded-4"; // reset alerts classes cleanly

            // Map standard layout form objects 
            const registrationPayload = {
                role_id: document.getElementById('role_id').value,
                username: document.getElementById('username').value,
                email: document.getElementById('email').value,
                phone_number: document.getElementById('phone_number').value,
                password: document.getElementById('password').value,
                first_name: document.getElementById('first_name').value,    // Added
                middle_name: document.getElementById('middle_name').value,  // Added
                last_name: document.getElementById('last_name').value,      // Added
                address: document.getElementById('address').value           // Added
            };

            alertBox.classList.remove('d-none');
            alertBox.classList.add('alert-info');
            alertBox.innerText = "Processing submission registration...";

            // Hit your previously built secure endpoint helper
            fetch('api/users/create_user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(registrationPayload)
            })
            .then(response => response.json())
            .then(data => {
                alertBox.classList.remove('alert-info');
                
                if (data.status === 'success') {
                    alertBox.classList.add('alert-success');
                    alertBox.innerText = data.message + " Redirecting to login portal...";
                    document.getElementById('registerForm').reset();
                    
                    // Automatically smoothly route them back to login page upon registration
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 2000);
                } else {
                    alertBox.classList.add('alert-danger');
                    alertBox.innerText = data.message;
                }
            })
            .catch(error => {
                alertBox.classList.remove('alert-info');
                alertBox.classList.add('alert-danger');
                alertBox.innerText = "Network connection or compilation script error occurred.";
            });
        });
    </script>
</body>
</html>