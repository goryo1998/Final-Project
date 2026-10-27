<?php
session_start();
require_once 'config/db.php';

// 1. Define the correct upload path
$target_dir = __DIR__ . "/uploads/"; 

// 2. Double-check the folder exists before moving
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true); 
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    
    // Determine user role and corresponding database updates based on active sessions
    if (isset($_SESSION['user_authenticated']) && $_SESSION['user_authenticated'] === true) {
        // ADMIN LOGGED IN
        $user_id = $_SESSION['user_id'];
        $file_name = 'admin_' . $user_id . '_' . time() . '_' . basename($_FILES["avatar"]["name"]);
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
            $stmt = $pdo->prepare("UPDATE users SET profile_picture = :pic WHERE id = :id");
            $stmt->execute([':pic' => $file_name, ':id' => $user_id]);
            header("Location: user_profile.php?upload=success");
            exit;
        } else {
            header("Location: user_profile.php?upload=error");
            exit;
        }

    } elseif (isset($_SESSION['teacher_authenticated']) && $_SESSION['teacher_authenticated'] === true) {
        // FACULTY MEMBER LOGGED IN
        $teacher_id = $_SESSION['teacher_id'];
        $file_name = 'faculty_' . $teacher_id . '_' . time() . '_' . basename($_FILES["avatar"]["name"]);
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
            // Assuming your teachers table uses a column named 'profile_picture' as well
            $stmt = $pdo->prepare("UPDATE teachers SET profile_picture = :pic WHERE id = :id");
            $stmt->execute([':pic' => $file_name, ':id' => $teacher_id]);
            
            // Redirect back to whichever dashboard or file requested the upload handler
            $redirect_back = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : 'faculty_dashboard.php';
            header("Location: " . $redirect_back . "?upload=success");
            exit;
        } else {
            $redirect_back = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : 'faculty_dashboard.php';
            header("Location: " . $redirect_back . "?upload=error");
            exit;
        }
    } else {
        header("Location: login.php");
        exit;
    }
}
?>