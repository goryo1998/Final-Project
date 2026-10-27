<?php
session_start();
if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header("Location: login.php"); exit;
}
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id           = trim($_POST['id']           ?? '');
    $faculty_id   = trim($_POST['faculty_id']   ?? '');
    $first_name   = trim($_POST['first_name']   ?? '');
    $last_name    = trim($_POST['last_name']    ?? '');
    $department   = trim($_POST['department']   ?? '');
    $email        = trim($_POST['email']        ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $address      = trim($_POST['address']      ?? '');

    if (empty($id) || empty($first_name) || empty($last_name)) {
        $_SESSION['student_form_error'] = 'Required fields are missing.';
        header("Location: student_faculty.php"); exit;
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE teachers SET
                faculty_id   = :faculty_id,
                first_name   = :first_name,
                last_name    = :last_name,
                department   = :department,
                email        = :email,
                phone_number = :phone_number,
                address      = :address
            WHERE id = :id
        ");
        $stmt->execute([
            ':faculty_id'   => $faculty_id,
            ':first_name'   => $first_name,
            ':last_name'    => $last_name,
            ':department'   => $department,
            ':email'        => $email,
            ':phone_number' => $phone_number,
            ':address'      => $address,
            ':id'           => $id,
        ]);
        $_SESSION['student_form_success'] = "Faculty record updated successfully.";
    } catch (Exception $e) {
        $_SESSION['student_form_error'] = "Error updating faculty: " . $e->getMessage();
    }
}

header("Location: student_faculty.php");
exit;
