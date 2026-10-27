<?php
session_start();

if (!isset($_SESSION['teacher_authenticated']) || $_SESSION['teacher_authenticated'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $subject_name = trim($_POST['subject_name'] ?? '');
    $section      = trim($_POST['section']      ?? '');
    $year_level   = trim($_POST['year_level']   ?? '');
    $teacher_id   = $_SESSION['teacher_id']     ?? null;

    // Only validate subject fields — no student_id_number here
    if (!empty($subject_name) && !empty($section) && !empty($year_level) && $teacher_id) {
        try {
            $query = "INSERT INTO subjects (subject_name, section, year_level, teacher_id) VALUES (?, ?, ?, ?)";
            $stmt  = $pdo->prepare($query);
            $success = $stmt->execute([$subject_name, $section, $year_level, $teacher_id]);

            if ($success) {
                header('Location: students_records.php?status=success&msg=' . urlencode('Subject added successfully!'));
                exit;
            } else {
                header('Location: students_records.php?status=error&msg=' . urlencode('Failed to store subject row.'));
                exit;
            }
        } catch (PDOException $e) {
            header('Location: students_records.php?status=error&msg=' . urlencode('Database Error: ' . $e->getMessage()));
            exit;
        }
    } else {
        header('Location: students_records.php?status=error&msg=' . urlencode('All registration fields are required.'));
        exit;
    }
} else {
    header('Location: students_records.php');
    exit;
}