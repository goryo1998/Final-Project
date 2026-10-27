<?php
session_start();
// Security: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'config/db.php';
// Fetch current user data to pre-fill the form
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch();
?>

<form action="update_profile.php" method="POST">
    <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>">
    <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>">
    <textarea name="address"><?= htmlspecialchars($user['address']) ?></textarea>
    <button type="submit">Save Changes</button>
</form>