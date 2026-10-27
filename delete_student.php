<?php
session_start();
if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header("Location: login.php"); exit;
}
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = trim($_POST['id'] ?? '');

    if (empty($id)) {
        $_SESSION['student_form_error'] = 'Invalid student ID.';
        header("Location: student_faculty.php"); exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM students WHERE student_id = :id");
        $stmt->execute([':id' => $id]);
        $_SESSION['student_form_success'] = "Student deleted successfully.";
    } catch (Exception $e) {
        $_SESSION['student_form_error'] = "Error deleting student: " . $e->getMessage();
    }
}

header("Location: student_faculty.php");
exit;