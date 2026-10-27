<?php
// save_student.php
session_start();

if (!isset($_SESSION['teacher_authenticated']) || $_SESSION['teacher_authenticated'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_name      = trim($_POST['subject_name']      ?? '');
    $section           = trim($_POST['section']           ?? '');
    $year_level        = trim($_POST['year_level']        ?? '');
    $student_name      = trim($_POST['student_name']      ?? '');
    $student_id_number = trim($_POST['student_id_number'] ?? '');
    $teacher_id        = $_SESSION['teacher_id'] ?? null;

    if (!empty($subject_name) && !empty($section) && !empty($student_name) && !empty($teacher_id)) {
        try {
            $query = "INSERT INTO subjects (subject_name, section, year_level, teacher_id, student_name, student_id_number) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt  = $pdo->prepare($query);
            $success = $stmt->execute([$subject_name, $section, $year_level, $teacher_id, $student_name, $student_id_number]);

            if ($success) {
                header('Location: students_records.php?status=success&msg=' . urlencode('Student added successfully!'));
                exit;
            } else {
                header('Location: students_records.php?status=error&msg=' . urlencode('Failed to add student.'));
                exit;
            }
        } catch (PDOException $e) {
            header('Location: students_records.php?status=error&msg=' . urlencode('Database Error: ' . $e->getMessage()));
            exit;
        }
    } else {
        header('Location: students_records.php?status=error&msg=' . urlencode('Validation Error: Student name cannot be empty.'));
        exit;
    }
} else {
    header('Location: students_records.php');
    exit;
}