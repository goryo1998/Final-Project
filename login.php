<?php
// login.php
session_start();

// If the user is already logged in, redirect them back to the main portal
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University of Eastern Philippines - Login Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        /* Shared Background from Landing Screen */
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
            width: 90px;
            height: auto;
            margin-bottom: 1rem;
        }

        .text-header {
            font-size: 0.85rem;
            color: #111;
            line-height: 1.4;
        }

        /* Dynamic Title Styling */
        .dynamic-title {
            font-size: 1.8rem;
            font-weight: 800;
            text-transform: uppercase;
            color: #000;
            margin-top: 1.5rem;
            margin-bottom: 1rem;
            letter-spacing: 0.5px;
        }

        /* Back Button Link */
        .back-btn {
            color: #000;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin-bottom: 2rem;
            transition: transform 0.2s ease;
        }
        .back-btn:hover {
            transform: translateX(-3px);
            color: #222;
        }

        /* Form Controls - Pill Shaped & Translucent */
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

        /* Password Wrapper for Eye Icon Toggle */
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

        /* Buttons Styling */
        .btn-pill-login {
            background-color: #2200ff;
            color: #fff;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.85rem;
            border-radius: 50px;
            padding: 0.7rem;
            border: none;
            transition: all 0.3s ease;
            letter-spacing: 0.5px;
        }
        .btn-pill-login:hover {
            background-color: #1a00cc;
            box-shadow: 0 4px 15px rgba(34, 0, 255, 0.3);
        }

        .btn-pill-create {
            background-color: #e1f541;
            color: #000;
            font-weight: 700;
            font-size: 0.85rem;
            border-radius: 50px;
            padding: 0.7rem;
            border: none;
            transition: all 0.3s ease;
        }
        .btn-pill-create:hover {
            background-color: #d4e635;
            box-shadow: 0 4px 15px rgba(225, 245, 65, 0.4);
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

        <h2 class="dynamic-title" id="loginHeader">Login</h2>

        <a href="index.php" class="back-btn">
            <i class="bi bi-arrow-left"></i> Back
        </a>

        <div id="loginAlert" class="alert d-none small p-2 rounded-4" role="alert"></div>

        <form id="loginForm">
            <div class="mb-3">
                <input type="text" class="form-control input-pill" id="username" placeholder="Enter your username" required>
            </div>

            <div class="mb-4 password-container">
                <input type="password" class="form-control input-pill" id="password" placeholder="Password" required>
                <i class="bi bi-eye-slash password-toggle" id="togglePassword"></i> 
            </div>

            <div class="d-grid gap-3">
                <button type="submit" class="btn btn-pill-login">Login</button>
                <button type="button" class="btn btn-pill-create" onclick="window.location.href='register.php'">Create an Account</button>
            </div>
        </form>
    </div>
    

    <script>
        // 1. Dynamic UI Assignment based on chosen Landing role Selection
        document.addEventListener("DOMContentLoaded", function() {
            const selectedRole = localStorage.getItem('selectedRole') || 'User';
            document.getElementById('loginHeader').innerText = `LOGIN AS ${selectedRole.toUpperCase()}`;
        });

        // 2. Interactive Password Eye/Slash Toggle controller
        const togglePassword = document.querySelector('#togglePassword');
        const passwordInput = document.querySelector('#password');

        togglePassword.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('bi-eye');
            this.classList.toggle('bi-eye-slash');
        });

        // 3. Form API Handler
       // 3. Form API Handler (Updated)
    // 3. Form API Handler (Updated)
// 3. Form API Handler (Updated)
document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const alertBox = document.getElementById('loginAlert');
    alertBox.classList.remove('d-none', 'alert-danger', 'alert-success');
    alertBox.classList.add('alert-info');
    alertBox.innerText = "Verifying credentials...";

    const role = localStorage.getItem('selectedRole'); // Gets 'Admin' or 'Faculty'
    
    // Choose the API endpoint based on the role
    const loginUrl = (role === 'Faculty') 
        ? 'faculty_login_process.php' 
        : 'api/users/login_process.php';

    const loginData = {
        email: document.getElementById('username').value, 
        username: document.getElementById('username').value,
        password: document.getElementById('password').value,
        role: role
    };

    fetch(loginUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(loginData)
    })
    .then(response => response.json())
    .then(data => {
        alertBox.classList.remove('alert-info');
        
        if(data.status === 'success') {
            alertBox.classList.add('alert-success');
            alertBox.innerText = data.message;
            setTimeout(() => { window.location.href = data.redirect; }, 1000);
        } else {
            alertBox.classList.add('alert-danger');
            alertBox.innerText = data.message;
        }
    })
    .catch(error => {
        alertBox.classList.remove('alert-info');
        alertBox.classList.add('alert-danger');
        alertBox.innerText = "An error occurred during authentication.";
    });
});
    </script>
</body>
</html>