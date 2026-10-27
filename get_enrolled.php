<?php
require_once 'config/db.php';
$stmt = $pdo->prepare("SELECT section_name, student_name FROM enrollments WHERE subject_id = ?");
$stmt->execute([$_GET['subject_id']]);
$enrollments = $stmt->fetchAll();

foreach ($enrollments as $row) {
    echo "<p><strong>{$row['section_name']}</strong>: {$row['student_name']}</p>";
}
?>