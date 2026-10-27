<?php
session_start();

if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: student_faculty.php');
    exit;
}

$student_id   = trim($_POST['student_id'] ?? '');
$first_name   = trim($_POST['first_name'] ?? '');
$middle_name  = trim($_POST['middle_name'] ?? '');
$last_name    = trim($_POST['last_name'] ?? '');
$gender       = trim($_POST['gender'] ?? '');
$email        = trim($_POST['email'] ?? '');
$phone_number = trim($_POST['phone_number'] ?? '');
$address      = trim($_POST['address'] ?? '');
$section      = trim($_POST['section'] ?? '');
$course       = trim($_POST['course'] ?? '');

// Basic validation: ensure required fields are present.
if ($student_id === '' || $first_name === '' || $last_name === '' || $gender === '' || $email === '' || $phone_number === '' || $address === '' || $section === '' || $course === '') {
    $_SESSION['student_form_error'] = 'Please fill in all required fields.';
    header('Location: student_faculty.php');
    exit;
}

try {
    $stmt = $pdo->prepare(
        'INSERT INTO students (student_id, first_name, middle_name, last_name, gender, email, phone_number, address, section, course)
         VALUES (:student_id, :first_name, :middle_name, :last_name, :gender, :email, :phone_number, :address, :section, :course)'
    );

    $stmt->execute([
        ':student_id'   => $student_id,
        ':first_name'   => $first_name,
        ':middle_name'  => $middle_name !== '' ? $middle_name : null,
        ':last_name'    => $last_name,
        ':gender'       => $gender,
        ':email'        => $email,
        ':phone_number' => $phone_number,
        ':address'      => $address,
        ':section'      => $section,
        ':course'       => $course,
    ]);

    $_SESSION['student_form_success'] = 'Student successfully added to the database.';
    header('Location: student_faculty.php');
    exit;
} catch (PDOException $e) {
    // This will stop the script and show you the actual database error
    die("Database Error: " . $e->getMessage()); 
}