<?php
session_start();

if (!isset($_SESSION['teacher_authenticated']) || $_SESSION['teacher_authenticated'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'config/db.php';

$teacher_id = $_SESSION['teacher_id'];

// Fetch teacher
$stmt = $pdo->prepare('SELECT * FROM teachers WHERE id = ? LIMIT 1');
$stmt->execute([$teacher_id]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$teacher) {
    header('Location: login.php');
    exit;
}

$success_msg = '';
$error_msg   = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_info') {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name  = trim($_POST['last_name']  ?? '');
        $email      = trim($_POST['email']      ?? '');

        if (empty($first_name) || empty($last_name)) {
            $error_msg = 'First name and last name are required.';
        } else {
            try {
                $upd = $pdo->prepare('UPDATE teachers SET first_name = ?, last_name = ?, email = ? WHERE id = ?');
                $upd->execute([$first_name, $last_name, $email, $teacher_id]);
                $teacher['first_name'] = $first_name;
                $teacher['last_name']  = $last_name;
                $teacher['email']      = $email;
                $success_msg = 'Profile updated successfully!';
            } catch (PDOException $e) {
                $error_msg = 'Database error: ' . $e->getMessage();
            }
        }

    } elseif ($action === 'change_password') {
        $current  = $_POST['current_password']  ?? '';
        $new_pass = $_POST['new_password']       ?? '';
        $confirm  = $_POST['confirm_password']   ?? '';

        if (empty($current) || empty($new_pass) || empty($confirm)) {
            $error_msg = 'All password fields are required.';
        } elseif ($new_pass !== $confirm) {
            $error_msg = 'New passwords do not match.';
        } elseif (strlen($new_pass) < 6) {
            $error_msg = 'New password must be at least 6 characters.';
        } else {
            // Verify current password
            $verified = password_verify($current, $teacher['password'] ?? '');
            // Fallback: plain text comparison (in case passwords aren't hashed yet)
            if (!$verified && $current === ($teacher['password'] ?? '')) {
                $verified = true;
            }
            if (!$verified) {
                $error_msg = 'Current password is incorrect.';
            } else {
                try {
                    $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                    $upd = $pdo->prepare('UPDATE teachers SET password = ? WHERE id = ?');
                    $upd->execute([$hashed, $teacher_id]);
                    $success_msg = 'Password changed successfully!';
                } catch (PDOException $e) {
                    $error_msg = 'Database error: ' . $e->getMessage();
                }
            }
        }
    }
}

function formatAvatarText($name) {
    $parts = explode(' ', trim($name));
    $initials = '';
    foreach ($parts as $part) {
        if ($part !== '') $initials .= strtoupper($part[0]);
        if (strlen($initials) >= 2) break;
    }
    return $initials ?: 'FC';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Inline header styles to force exact match with admin header */
        body.faculty-dashboard .site-header{
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 36px;
            background: rgba(255,255,255,0.88);
            border-bottom: 1px solid rgba(0,0,0,0.08);
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            position: sticky;
            top: 0;
            z-index: 10;
            gap: 24px;
        }
        body.faculty-dashboard .header-brand{
            display: flex;
            align-items: center;
            gap: 18px;
        }
        body.faculty-dashboard .header-brand img{
            width: 68px;
            border-radius: 16px;
            box-shadow: 0 14px 35px rgba(0,0,0,0.16);
        }
        body.faculty-dashboard .brand-text{ display:flex; flex-direction:column; gap:3px; }
        body.faculty-dashboard .brand-text span, body.faculty-dashboard .brand-text strong{ color:#1c1c1c; line-height:1.2; }
        body.faculty-dashboard .brand-text strong{ font-size:1rem; font-weight:700; }
        body.faculty-dashboard .site-header h1{ margin:0; font-size:1.6rem; letter-spacing:0.12em; text-transform:uppercase; color:#111; }

        .profile-card   { background:#fff; border-radius:24px; border:none; box-shadow:0 4px 20px rgba(0,0,0,0.07); padding:32px; }
        .avatar-circle  { width:90px;height:90px;border-radius:50%;background:#1a252f;color:#fff;font-size:2rem;font-weight:700;display:flex;align-items:center;justify-content:center;margin:0 auto 12px; }
        .tab-pill       { border-radius:50px !important; padding:8px 24px; font-weight:600; }
        .tab-pill.active{ background:#1a252f !important; color:#fff !important; border-color:#1a252f !important; }
        .form-control   { border-radius:12px; padding:10px 16px; }
        .btn-save       { background:#1a252f; color:#fff; border-radius:50px; padding:10px 32px; font-weight:600; }
        .btn-save:hover { background:#2c3e50; color:#fff; }
        .section-label  { font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:#9ca3af; margin-bottom:12px; }
    </style>
</head>
<body class="faculty-dashboard">
    <header class="site-header">
        <div class="header-brand">
            <img src="images/UEPLOGO.png" alt="UEP Logo">
            <div class="brand-text">
                <span>Republic of the Philippines</span>
                <strong>University of Eastern Philippines</strong>
                <span>University Town, Northern Samar</span>
            </div>
        </div>
        <h1>UEP FACULTY</h1>
    </header>

    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <nav class="side-nav">
                <a href="faculty_dashboard.php" class="nav-item">
                    <i class="bi bi-journal-bookmark-fill"></i> Subject and sched
                </a>
                <a href="students_records.php" class="nav-item">
                    <i class="bi bi-people-fill"></i> Students and records
                </a>
            </nav>
            <div class="sidebar-footer">
                <div class="user-box" onclick="openProfileModal()" style="cursor:pointer;" title="View Profile">
                    <div class="user-avatar"><?= htmlspecialchars(formatAvatarText($teacher['first_name'] . ' ' . $teacher['last_name'])); ?></div>
                    <div class="user-info">
                        <span class="small text-muted">welcome Faculty</span>
                        <strong><?= htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></strong>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn">LOGOUT</a>
            </div>
        </aside>

        <main class="main-content">
            <section class="page-header mb-4">
                <h2 style="font-weight:bold;">My Profile</h2>
            </section>

            <?php if ($success_msg): ?>
                <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success_msg); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($error_msg): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
                    <i class="bi bi-exclamation-circle-fill me-2"></i><?= htmlspecialchars($error_msg); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Left: Avatar card -->
                <div class="col-md-4">
                    <div class="profile-card text-center">
                        <div class="avatar-circle">
                            <?= htmlspecialchars(formatAvatarText($teacher['first_name'] . ' ' . $teacher['last_name'])); ?>
                        </div>
                        <h5 class="fw-bold mb-0"><?= htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></h5>
                        <small class="text-muted d-block mb-3">Faculty</small>
                        <?php if (!empty($teacher['email'])): ?>
                            <div class="p-2 rounded-3 mb-2" style="background:#f0f4ff;">
                                <small class="text-muted"><i class="bi bi-envelope-fill me-1"></i><?= htmlspecialchars($teacher['email']); ?></small>
                            </div>
                        <?php endif; ?>
                        <div class="p-2 rounded-3" style="background:#f0fff4;">
                            <small class="text-muted"><i class="bi bi-person-badge-fill me-1"></i>Teacher ID: <?= htmlspecialchars($teacher['id']); ?></small>
                        </div>
                    </div>
                </div>

                <!-- Right: Edit forms -->
                <div class="col-md-8">
                    <div class="profile-card">
                        <!-- Tabs -->
                        <div class="d-flex gap-2 mb-4">
                            <button class="btn btn-outline-secondary tab-pill active" id="tab-info" onclick="switchTab('info')">
                                <i class="bi bi-person-fill me-1"></i> Personal Info
                            </button>
                            <button class="btn btn-outline-secondary tab-pill" id="tab-pass" onclick="switchTab('pass')">
                                <i class="bi bi-lock-fill me-1"></i> Change Password
                            </button>
                        </div>

                        <!-- Personal Info Form -->
                        <div id="panel-info">
                            <p class="section-label">Edit Personal Information</p>
                            <form method="POST" action="teacher_profile.php">
                                <input type="hidden" name="action" value="update_info">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">First Name</label>
                                        <input type="text" name="first_name" class="form-control"
                                               value="<?= htmlspecialchars($teacher['first_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Last Name</label>
                                        <input type="text" name="last_name" class="form-control"
                                               value="<?= htmlspecialchars($teacher['last_name']); ?>" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Email Address</label>
                                        <input type="email" name="email" class="form-control"
                                               value="<?= htmlspecialchars($teacher['email'] ?? ''); ?>"
                                               placeholder="e.g. faculty@uep.edu.ph">
                                    </div>
                                    <div class="col-12 text-end">
                                        <button type="submit" class="btn btn-save">
                                            <i class="bi bi-save-fill me-2"></i>Save Changes
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Change Password Form -->
                        <div id="panel-pass" style="display:none;">
                            <p class="section-label">Change Password</p>
                            <form method="POST" action="teacher_profile.php">
                                <input type="hidden" name="action" value="change_password">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Current Password</label>
                                        <div class="input-group">
                                            <input type="password" name="current_password" id="curr_pass" class="form-control" required>
                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePass('curr_pass', this)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">New Password</label>
                                        <div class="input-group">
                                            <input type="password" name="new_password" id="new_pass" class="form-control" minlength="6" required>
                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePass('new_pass', this)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Confirm New Password</label>
                                        <div class="input-group">
                                            <input type="password" name="confirm_password" id="conf_pass" class="form-control" minlength="6" required>
                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePass('conf_pass', this)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-12 text-end">
                                        <button type="submit" class="btn btn-save">
                                            <i class="bi bi-lock-fill me-2"></i>Update Password
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function switchTab(tab) {
            document.getElementById('panel-info').style.display = tab === 'info' ? '' : 'none';
            document.getElementById('panel-pass').style.display = tab === 'pass' ? '' : 'none';
            document.getElementById('tab-info').classList.toggle('active', tab === 'info');
            document.getElementById('tab-pass').classList.toggle('active', tab === 'pass');
        }

        function togglePass(id, btn) {
            const input = document.getElementById(id);
            const isText = input.type === 'text';
            input.type = isText ? 'password' : 'text';
            btn.innerHTML = isText ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
        }

        // Auto-switch to password tab if password error
        <?php if ($error_msg && isset($_POST['action']) && $_POST['action'] === 'change_password'): ?>
        switchTab('pass');
        <?php endif; ?>
    </script>

    <!-- Teacher Profile Modal -->
    <div class="modal fade" id="teacherProfileModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:20px;border:none;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">My Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 pb-4">
                    <div class="text-center mb-4">
                        <div style="width:72px;height:72px;border-radius:50%;background:#1a252f;color:#fff;font-size:1.6rem;font-weight:700;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                            <?= htmlspecialchars(formatAvatarText($teacher['first_name'] . ' ' . $teacher['last_name'])); ?>
                        </div>
                        <h5 class="fw-bold mb-0"><?= htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></h5>
                        <small class="text-muted">Faculty</small>
                    </div>
                    <hr>
                    <div class="row g-3 mt-1">
                        <div class="col-6">
                            <div class="p-3 rounded-3" style="background:#f0f4ff;">
                                <small class="text-muted d-block mb-1"><i class="bi bi-person-fill me-1"></i>First Name</small>
                                <strong><?= htmlspecialchars($teacher['first_name']); ?></strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-3" style="background:#f0f4ff;">
                                <small class="text-muted d-block mb-1"><i class="bi bi-person-fill me-1"></i>Last Name</small>
                                <strong><?= htmlspecialchars($teacher['last_name']); ?></strong>
                            </div>
                        </div>
                        <?php if (!empty($teacher['email'])): ?>
                        <div class="col-12">
                            <div class="p-3 rounded-3" style="background:#f0fff4;">
                                <small class="text-muted d-block mb-1"><i class="bi bi-envelope-fill me-1"></i>Email</small>
                                <strong><?= htmlspecialchars($teacher['email']); ?></strong>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="d-grid mt-4">
                        <a href="teacher_profile.php" class="btn btn-dark rounded-pill fw-semibold">
                            <i class="bi bi-pencil-fill me-2"></i>Edit Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    function openProfileModal() {
        new bootstrap.Modal(document.getElementById('teacherProfileModal')).show();
    }
    </script>
</body>
</html>