<?php
session_start();

if (!isset($_SESSION['teacher_authenticated']) || $_SESSION['teacher_authenticated'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $input = json_decode(file_get_contents('php://input'), true);

    $student_name      = trim($input['student_name']      ?? '');
    $student_id_number = trim($input['student_id_number'] ?? '');
    $subject_name      = trim($input['subject_name']      ?? '');
    $section           = trim($input['section']           ?? '');
    $teacher_id        = $_SESSION['teacher_id']          ?? null;

    $quizzes       = $input['quizzes']       ?? [];   // [{score, total}, ...]
    $projects      = $input['projects']      ?? [];   // [{score, total}, ...]
    $midterm_score = $input['midterm_score'] ?? null;
    $midterm_total = $input['midterm_total'] ?? null;
    $final_score   = $input['final_score']   ?? null;
    $final_total   = $input['final_total']   ?? null;

    if (empty($student_name) || empty($subject_name) || empty($section) || !$teacher_id) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
        exit;
    }

    try {
        // Check if a grade record already exists for this student + subject + section
        $check = $pdo->prepare(
            'SELECT id FROM grades 
             WHERE student_id_number = ? AND subject_name = ? AND section = ? AND teacher_id = ?'
        );
        $check->execute([$student_id_number, $subject_name, $section, $teacher_id]);
        $existing = $check->fetch();

        $quizzes_json  = json_encode($quizzes);
        $projects_json = json_encode($projects);

        if ($existing) {
            // UPDATE existing record
            $stmt = $pdo->prepare(
                'UPDATE grades SET
                    student_name      = ?,
                    quizzes           = ?,
                    projects          = ?,
                    midterm_score     = ?,
                    midterm_total     = ?,
                    final_score       = ?,
                    final_total       = ?,
                    updated_at        = NOW()
                 WHERE id = ?'
            );
            $stmt->execute([
                $student_name,
                $quizzes_json,
                $projects_json,
                $midterm_score,
                $midterm_total,
                $final_score,
                $final_total,
                $existing['id']
            ]);
        } else {
            // INSERT new record
            $stmt = $pdo->prepare(
                'INSERT INTO grades 
                    (student_name, student_id_number, subject_name, section, teacher_id,
                     quizzes, projects, midterm_score, midterm_total, final_score, final_total,
                     created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
            );
            $stmt->execute([
                $student_name,
                $student_id_number,
                $subject_name,
                $section,
                $teacher_id,
                $quizzes_json,
                $projects_json,
                $midterm_score,
                $midterm_total,
                $final_score,
                $final_total
            ]);
        }

        // Compute GWA to return in response
        $quizPct = 0; $quizCount = 0;
        foreach ($quizzes as $q) {
            if (!empty($q['total']) && $q['total'] > 0) {
                $quizPct += ($q['score'] / $q['total']) * 100;
                $quizCount++;
            }
        }
        $quizAvg = $quizCount > 0 ? $quizPct / $quizCount : 0;

        $projPct = 0; $projCount = 0;
        foreach ($projects as $p) {
            if (!empty($p['total']) && $p['total'] > 0) {
                $projPct += ($p['score'] / $p['total']) * 100;
                $projCount++;
            }
        }
        $projAvg = $projCount > 0 ? $projPct / $projCount : 0;

        $midAvg = (!empty($midterm_total) && $midterm_total > 0) ? ($midterm_score / $midterm_total) * 100 : 0;
        $finAvg = (!empty($final_total)   && $final_total   > 0) ? ($final_score   / $final_total)   * 100 : 0;

        $gwa = ($quizAvg * 0.30) + ($projAvg * 0.30) + ($midAvg * 0.20) + ($finAvg * 0.20);

        echo json_encode([
            'success' => true,
            'message' => 'Grades saved successfully!',
            'gwa'     => round($gwa, 2),
            'passing' => $gwa >= 75
        ]);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
    }

} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
}