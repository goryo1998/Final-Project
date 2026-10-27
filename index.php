<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University of Eastern Philippines - Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for Icons (Optional if you want to swap the emojis for crisp vector icons later) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Fullscreen background layout mimicking your gradient/image mix */
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
            padding: 2.5rem 2rem;
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

        .section-title {
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 1px;
            margin-top: 3rem;
            margin-bottom: 1.5rem;
            color: #000;
        }

        /* Role Selection Buttons */
        .role-card {
            background: #e1f541; /* Lime yellow shade matching your layout */
            border-radius: 15px;
            border: none;
            padding: 1.2rem;
            width: 120px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-decoration: none;
            display: inline-block;
        }

        .role-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            background: #d4e635;
        }

        .role-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .role-text {
            font-size: 0.85rem;
            font-weight: 800;
            color: #000;
            text-transform: uppercase;
        }
        /* Small and compact PNG icons inside the buttons */
        .role-png-icon {
            width: 40px;          /* Made it smaller to match compact designs */
            height: 40px;
            object-fit: contain;  /* Keeps the correct aspect ratio without warping */
            margin-bottom: 0.5rem;
            display: block;
            margin-left: auto;    /* Center alignment */
            margin-right: auto;
        }

        /* Optional adjustment to make the yellow cards match the smaller icons */
        .role-card {
            background: #e1f541; 
            border-radius: 12px;
            border: none;
            padding: 0.8rem;      /* Reduced padding for a tighter fit */
            width: 110px;         /* Made the card slightly narrower */
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-decoration: none;
            display: inline-block;
        }
    </style>
</head>
<body>

    <div class="glass-card mx-3">
        <!-- University Branding -->
        <img src="images/UEPLOGO.png" alt="UEP Logo" class="university-logo">
        <div class="text-header mb-4">
            <div class="text-muted small">Republic of the Philippines</div>
            <strong class="d-block text-uppercase my-1">University of Eastern Philippines</strong>
            <div class="small text-secondary">University Town, Northern Samar</div>
        </div>

        <!-- Prompt -->
        <div class="section-title text-uppercase">Please Choose Your Role</div>

            <!-- Selection Flexbox Container -->
            <div class="d-flex justify-content-center gap-4">
                
                <!-- Admin Selection -->
                <a href="login.php" onclick="selectRole('Admin')" class="role-card">
                    <!-- New PNG Image replacing the emoji -->
                    <img src="images/admin.png" alt="Admin Icon" class="role-png-icon">
                    <span class="role-text">Admin</span>
                </a>

                <!-- Faculty Selection -->
                <a href="#" onclick="selectRole('Faculty')" class="role-card">
                    <!-- New PNG Image replacing the emoji -->
                    <img src="images/presentation.png" alt="Faculty Icon" class="role-png-icon">
                    <span class="role-text">Faculty</span>
                </a>

            </div>
    </div>

    <!-- JavaScript Navigation Controller -->
    <script>
        function selectRole(role) {
            // Store the selected role in localStorage so the login page knows who is trying to log in
            localStorage.setItem('selectedRole', role);
            
            // Redirect to your login view page
            window.location.href = 'login.php'; 
        }
    </script>

</body>
</html>