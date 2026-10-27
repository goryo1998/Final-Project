<?php
session_start();
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, address = ? WHERE id = ?");
    $stmt->execute([
        $_POST['first_name'],
        $_POST['last_name'],
        $_POST['email'],
        $_POST['address'],
        $_SESSION['user_id']
    ]);

    // Redirect back to profile
    header("Location: user_profile.php?success=1");
    exit;
}
?>