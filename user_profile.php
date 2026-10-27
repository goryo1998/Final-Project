<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_authenticated'])) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Profile</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="admin-dashboard">
    <header class="site-header">
        <div class="header-brand">
            <img src="images/UEPLOGO.png" alt="UEP Logo">
            <div class="brand-text">
                <span>Republic of the Philippines</span>
                <strong>University of Eastern Philippines</strong>
            </div>
        </div>
        <h1>UEP Administrator</h1>
    </header>

    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <nav>
                <a href="admin_dashboard.php" class="nav-item">Dashboard</a>
                <a href="student_faculty.php" class="nav-item">Student / Faculty</a>
            </nav>
            <div class="sidebar-footer">
                <div class="user-box">
                    <div class="user-avatar" style="background-image: url('uploads/<?= htmlspecialchars($user['profile_picture'] ?? 'default.png'); ?>'); background-size: cover; background-position: center;"></div>
                    <div class="user-info">
                        <strong><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn">LOGOUT</a>
            </div>
        </aside>

<main class="main-content">
    <section class="records-panel">
        <article class="record-card">
            <!-- Profile Header -->
            <div class="profile-header" style="text-align: center; margin-bottom: 20px;">
                <img src="uploads/<?= htmlspecialchars($user['profile_picture'] ?? 'default.png'); ?>" alt="Profile" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover;">
                <h2><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h2>
            </div>

            <!-- Personal Info Header with Edit button -->
            <div class="card-header">
                <h2>Personal Information</h2>
                <button type="button" class="edit-btn" id="openEditProfileModal"><i class="fa fa-pencil"></i>Edit</button>
            </div>
            <!-- Grid for details -->
            <div class="info-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <label style="display: block; font-weight: bold; color: #666; font-size: 0.9em;">First Name</label>
                    <p style="margin: 5px 0;"><?= htmlspecialchars($user['first_name']); ?></p>
                </div>
                <div>
                    <label style="display: block; font-weight: bold; color: #666; font-size: 0.9em;">Last Name</label>
                    <p style="margin: 5px 0;"><?= htmlspecialchars($user['last_name']); ?></p>
                </div>
                <div>
                    <label style="display: block; font-weight: bold; color: #666; font-size: 0.9em;">Email</label>
                    <p style="margin: 5px 0;"><?= htmlspecialchars($user['email']); ?></p>
                </div>
                <div>
                    <label style="display: block; font-weight: bold; color: #666; font-size: 0.9em;">Address</label>
                    <p style="margin: 5px 0;"><?= htmlspecialchars($user['address'] ?? 'Not set'); ?></p>
                </div>
            </div>
        </article>
    </section>
</main>
    </div>
<div id="editProfileModal" class="modal-overlay" style="margin: 10px; display: none; position: fixed; inset: 0; align-items: center; justify-content: center; z-index: 1000;">
    <div class="modal-card" style="margin: 10px; background: white; padding: 30px; border-radius: 20px; width: 400px;">
        <h3>Edit Profile</h3>
        <form action="update_user.php" method="POST">
            <div style="margin-bottom: 15px;">
                <label>First Name</label>
                <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']); ?>" class="form-control" required>
            </div>
            <div style="margin-bottom: 15px;">
                <label>Last Name</label>
                <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']); ?>" class="form-control" required>
            </div>
            <div style="margin-bottom: 15px;">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" class="form-control" required>
            </div>
            <div style="margin-bottom: 15px;">
                <label>Address</label>
                <input type="text" name="address" value="<?= htmlspecialchars($user['address'] ?? ''); ?>" class="form-control">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeEditModal()" class="btn-action">Cancel</button>
                <button type="submit" class="btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById('openEditProfileModal').addEventListener('click', openEditModal);
    function openEditModal() { document.getElementById('editProfileModal').style.display = 'flex'; }
    function closeEditModal() { document.getElementById('editProfileModal').style.display = 'none'; }
</script>

</body>
</html>