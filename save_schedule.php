<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['teacher_authenticated']) || $_SESSION['teacher_authenticated'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

require_once 'config/db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'No data received.']);
    exit;
}

$teacher_id = $_SESSION['teacher_id'];
$subject    = trim($input['subject'] ?? '');
$room       = trim($input['room'] ?? '');
$date       = trim($input['date'] ?? '');
$start      = trim($input['start'] ?? '');
$end        = trim($input['end'] ?? '');
$color      = trim($input['color'] ?? '#6366f1');

if (empty($subject) || empty($room) || empty($date) || empty($start) || empty($end)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

try {
    $stmt = $pdo->prepare('INSERT INTO faculty_schedules (teacher_id, subject, room, schedule_date, start_time, end_time, color) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$teacher_id, $subject, $room, $date, $start, $end, $color]);
    
    echo json_encode(['success' => true, 'message' => 'Schedule saved successfully.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}