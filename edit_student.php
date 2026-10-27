<?php
session_start();
if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header("Location: login.php"); exit;
}
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id           = trim($_POST['student_id']   ?? '');
    $first_name   = trim($_POST['first_name']   ?? '');
    $middle_name  = trim($_POST['middle_name']  ?? '');
    $last_name    = trim($_POST['last_name']    ?? '');
    $gender       = trim($_POST['gender']       ?? '');
    $email        = trim($_POST['email']        ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $address      = trim($_POST['address']      ?? '');
    $section      = trim($_POST['section']      ?? '');
    $course       = trim($_POST['course']       ?? '');
    $year_level   = trim($_POST['year_level']   ?? '');
    $status       = trim($_POST['status']       ?? 'enrolled');

    if (empty($id) || empty($first_name) || empty($last_name)) {
        $_SESSION['student_form_error'] = 'Required fields are missing.';
        header("Location: student_faculty.php"); exit;
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE students SET
                first_name   = :first_name,
                middle_name  = :middle_name,
                last_name    = :last_name,
                gender       = :gender,
                email        = :email,
                phone_number = :phone_number,
                address      = :address,
                section      = :section,
                course       = :course,
                year_level   = :year_level,
                status       = :status,
                updated_at   = NOW()
            WHERE student_id = :student_id
        ");
        $stmt->execute([
            ':first_name'   => $first_name,
            ':middle_name'  => $middle_name,
            ':last_name'    => $last_name,
            ':gender'       => $gender,
            ':email'        => $email,
            ':phone_number' => $phone_number,
            ':address'      => $address,
            ':section'      => $section,
            ':course'       => $course,
            ':year_level'   => $year_level,
            ':status'       => $status,
            ':student_id'   => $id,
        ]);
        $_SESSION['student_form_success'] = "Student record updated successfully.";
    } catch (Exception $e) {
        $_SESSION['student_form_error'] = "Error updating student: " . $e->getMessage();
    }
}

header("Location: student_faculty.php");
exit;
