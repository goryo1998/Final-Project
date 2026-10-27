<?php
session_start();
if (!isset($_SESSION['teacher_authenticated']) || $_SESSION['teacher_authenticated'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'config/db.php';

// Verify the logged-in teacher exists in the DB
$stmt = $pdo->prepare('SELECT * FROM teachers WHERE id = ? LIMIT 1');
$stmt->execute([$_SESSION['teacher_id']]);
$teacher = $stmt->fetch();

if (!$teacher) {
    header('Location: login.php');
    exit;
}

// FIX #3: Filter subjects by the logged-in teacher's ID so teachers only see their own subjects
$subjectsStmt = $pdo->prepare(
    'SELECT DISTINCT subject_name, section, year_level
     FROM subjects
     WHERE teacher_id = ?
       AND subject_name IS NOT NULL
       AND subject_name != ""
     ORDER BY section ASC'
);
$subjectsStmt->execute([$_SESSION['teacher_id']]);
$sections = $subjectsStmt->fetchAll(PDO::FETCH_ASSOC);

$uniqueCourses  = [];
$uniqueYears    = [];
$uniqueSections = [];

foreach ($sections as $sec) {
    if (!empty($sec['section'])) {
        $uniqueSections[] = $sec['section'];
        $parts = explode('-', $sec['section']);
        $courseHeader = strtoupper(trim($parts[0]));
        if (!in_array($courseHeader, $uniqueCourses) && !empty($courseHeader)) {
            $uniqueCourses[] = $courseHeader;
        }
    }
    if (!empty($sec['year_level']) && !in_array($sec['year_level'], $uniqueYears)) {
        $uniqueYears[] = $sec['year_level'];
    }
}

$uniqueSections = array_unique($uniqueSections);
sort($uniqueCourses);
sort($uniqueYears);
sort($uniqueSections);

// Helper: fetch students for a given subject + section
function getStudentsBySubjectSection($pdo, $subject_name, $section) {
    $query = 'SELECT * FROM subjects
              WHERE subject_name = ? AND section = ?
                AND student_name IS NOT NULL AND student_name != ""
              ORDER BY student_name ASC';
    $stmt = $pdo->prepare($query);
    $stmt->execute([$subject_name, $section]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function formatAvatarText($name) {
    $parts    = explode(' ', trim($name));
    $initials = '';
    foreach ($parts as $part) {
        if ($part !== '') {
            $initials .= strtoupper($part[0]);
        }
        if (strlen($initials) >= 2) break;
    }
    return $initials ?: 'FC';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students and Records</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FIX #4: Bootstrap JS included only once, at the bottom -->

    <style>
        /* Inline header styles to force exact match with admin header */
        body.faculty-dashboard .site-header{
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 36px;
            background: rgba(255,255,255,0.88);
            border-bottom: 1px solid rgba(0,0,0,0.08);
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            position: sticky;
            top: 0;
            z-index: 10;
            gap: 24px;
            margin: 20px 15px 0; 
            border-radius: 24px;
            background: linear-gradient(to left, blue,yellow);
        }
        body.faculty-dashboard  .header-brand {
            display: flex;
            align-items: center;
            gap: 24px;
        }
        body.faculty-dashboard .header-brand img{
            width: 68px;
            border-radius: 16px;
            box-shadow: 0 14px 35px rgba(0,0,0,0.16);
        }
        body.faculty-dashboard .brand-text{ display:flex; flex-direction:column; gap:3px; }
        body.faculty-dashboard .brand-text span, body.faculty-dashboard .brand-text strong{ color:#1c1c1c; line-height:1.2; }
        body.faculty-dashboard .brand-text strong{ font-size:1rem; font-weight:700; }
        body.faculty-dashboard .site-header h1{ margin:0; font-size:1.6rem; letter-spacing:0.12em; text-transform:uppercase; color:#111; }

        .section-card { cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease; margin-bottom: 20px; border-radius: 24px !important; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); background-color: #fff; }
        .section-card:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.1); }
        .student-list-container { display: none; padding: 20px; background-color: #fafafa; border-bottom-left-radius: 24px; border-bottom-right-radius: 24px; border-top: 1px solid #eee; }
        .section-card.active .student-list-container { display: block; }
        .section-card.active .bi-chevron-down { transform: rotate(180deg); }
        .bi-chevron-down { transition: transform 0.2s ease; }
        .badge-enrolled { background-color: #1a252f; color: #fff; padding: 8px 16px; font-size: 0.85rem; }
        .badge-year { background-color: #7f8c8d; color: #fff; padding: 6px 14px; font-size: 0.8rem; }
        .records-container { margin-top: 25px; }

        /* Clean roster table */
        .roster-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .roster-table thead tr { border-bottom: 2px solid #e5e7eb; }
        .roster-table thead th { padding: 10px 14px; font-size: 0.82rem; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; background: transparent; }
        .roster-table tbody tr { border-bottom: 1px solid #f0f0f0; transition: background 0.15s; cursor: pointer; }
        .roster-table tbody tr:last-child { border-bottom: none; }
        .roster-table tbody tr:hover { background: #f8faff; }
        .roster-table tbody td { padding: 12px 14px; font-size: 0.92rem; color: #1a252f; vertical-align: middle; background: transparent !important; border: none !important; box-shadow: none !important; }
        .roster-table .col-id   { width: 170px; }
       
        .roster-table .col-gwa  { width: 110px; text-align: right; }
    </style>
</head>
<body class="faculty-dashboard">
    <header class="site-header">
        <div class="header-brand">
            <img src="images/UEPLOGO.png" alt="UEP Logo">
            <div class="brand-text">
                <span>Republic of the Philippines</span>
                <strong>University of Eastern Philippines</strong>
                <span>University Town, Northern Samar</span>
            </div>
        </div>
        <h1>UEP FACULTY</h1>
    </header>

    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <nav class="side-nav">
                <a href="faculty_dashboard.php" class="nav-item">
                    <i class="bi bi-journal-bookmark-fill"></i> Subject and sched
                </a>
                <a href="students_records.php" class="nav-item active">
                    <i class="bi bi-people-fill"></i> Students and records
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="user-box" onclick="openProfileModal()" style="cursor:pointer;" title="View Profile">
                    <div class="user-avatar"><?= htmlspecialchars(formatAvatarText($teacher['first_name'] . ' ' . $teacher['last_name'])); ?></div>
                    <div class="user-info">
                        <span class="small text-muted">welcome Faculty</span>
                        <strong><?= htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></strong>
                    </div>
                    
                </div>
                <a href="logout.php" class="logout-btn">LOGOUT</a>
            </div>
        </aside>

        <main class="main-content">
            <?php if (isset($_GET['status'])): ?>
                <div class="alert alert-<?= $_GET['status'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show mb-3" role="alert">
                    <?= htmlspecialchars($_GET['msg'] ?? ''); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <section class="page-header">
                <div>
                    <h2 style="font-weight: bold;">Student records</h2>
                </div>
            </section>

            <button type="button" class="btn btn-warning mb-3" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                <i class="bi bi-plus-circle"></i> Add Subject
            </button>



            <section class="records-container">
                <?php if (!empty($sections)): ?>
                    <?php foreach ($sections as $sec):
                        $students       = getStudentsBySubjectSection($pdo, $sec['subject_name'], $sec['section']);
                        $parts          = explode('-', $sec['section']);
                        $computedCourse = strtolower(trim($parts[0]));
                        $displayYear    = !empty($sec['year_level']) ? $sec['year_level'] : '1ST YEAR';
                    ?>
                        <div class="card section-card"
                             data-course="<?= htmlspecialchars($computedCourse); ?>"
                             data-year="<?= htmlspecialchars(strtolower($displayYear)); ?>"
                             data-section="<?= htmlspecialchars(strtolower($sec['section'])); ?>">

                            <div class="card-body d-flex justify-content-between align-items-center p-4">
                                <div>
                                    <h4 class="mb-2" style="font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                                        <?= htmlspecialchars($sec['subject_name']); ?> —
                                        <span class="text-primary"><?= htmlspecialchars($sec['section']); ?></span>
                                    </h4>
                                    <span class="badge badge-year rounded-pill text-uppercase"><?= htmlspecialchars($displayYear); ?></span>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="badge badge-enrolled rounded-pill me-3 text-uppercase"><?= count($students); ?> ENROLLED</span>
                                    <i class="bi bi-chevron-down fs-5 text-muted"></i>
                                </div>
                            </div>

                            <div class="student-list-container">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="fw-bold text-secondary mb-0">CLASS LIST

                                    </h5>
                                    <button type="button"
                                            class="btn btn-sm btn-success rounded-pill px-3 open-add-student-modal"
                                            data-subject="<?= htmlspecialchars($sec['subject_name']); ?>"
                                            data-section="<?= htmlspecialchars($sec['section']); ?>"
                                            data-year="<?= htmlspecialchars($sec['year_level']); ?>">
                                        <i class="bi bi-person-plus-fill"></i> Add Student
                                    </button>
                                </div>

                                <div style="overflow-x:auto;">
                                    <table style="width:100%;border-collapse:collapse;table-layout:fixed;">
                                        <thead>
                                            <tr style="border-bottom:2px solid #e5e7eb;">
                                                <th style="width:170px;padding:10px 14px;font-size:0.82rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;background:transparent;border:none;">Student ID No.</th>
                                                <th style="padding:10px 14px;font-size:0.82rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;background:transparent;border:none;">Student Full Name</th>
                                                <th style="width:120px;padding:10px 14px;font-size:0.82rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;text-align:right;background:transparent;border:none;">GWA</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($students)): ?>
                                                <?php foreach ($students as $student): ?>
<?php
                                                        // Fetch GWA for this student from grades table
                                                        $gwaStmt = $pdo->prepare(
                                                            "SELECT quizzes, projects, midterm_score, midterm_total, final_score, final_total FROM grades WHERE student_id_number = ? AND subject_name = ? AND section = ? AND teacher_id = ? LIMIT 1"
                                                        );
                                                        $gwaStmt->execute([$student['student_id_number'], $sec['subject_name'], $sec['section'], $_SESSION['teacher_id']]);
                                                        $gradeRow = $gwaStmt->fetch(PDO::FETCH_ASSOC);
                                                        $gwaDisplay = '—';
                                                        $gwaDot     = '#ccc';
                                                        if ($gradeRow) {
                                                            $quizzes  = json_decode($gradeRow['quizzes']  ?? '[]', true);
                                                            $projects = json_decode($gradeRow['projects'] ?? '[]', true);
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
                                                            $midAvg  = (!empty($gradeRow['midterm_total']) && $gradeRow['midterm_total'] > 0) ? ($gradeRow['midterm_score'] / $gradeRow['midterm_total']) * 100 : 0;
                                                            $finAvg  = (!empty($gradeRow['final_total'])   && $gradeRow['final_total']   > 0) ? ($gradeRow['final_score']   / $gradeRow['final_total'])   * 100 : 0;
                                                            $gwa = ($quizAvg * 0.30) + ($projAvg * 0.30) + ($midAvg * 0.20) + ($finAvg * 0.20);
                                                            $gwaDisplay = number_format($gwa, 2);
                                                            $gwaDot = $gwa >= 75 ? '#22c55e' : '#ef4444';
                                                        }
                                                        ?>
                                                    <tr class="student-item"
                                                        data-name="<?= htmlspecialchars(strtolower($student['student_name'] ?? '')); ?>"
                                                        data-student-name="<?= htmlspecialchars($student['student_name'] ?? ''); ?>"
                                                        data-student-id="<?= htmlspecialchars($student['student_id_number'] ?? '—'); ?>"
                                                        data-subject="<?= htmlspecialchars($sec['subject_name'] ?? ''); ?>"
                                                        data-section="<?= htmlspecialchars($sec['section'] ?? ''); ?>"
                                                        data-year="<?= htmlspecialchars($sec['year_level'] ?? ''); ?>"
                                                        style="cursor:pointer;border-bottom:1px solid #f0f0f0;"
                                                        onmouseover="this.style.background='#f8faff'" 
                                                        onmouseout="this.style.background='transparent'"
                                                        onclick="openStudentInfoModal(this)">
                                                        <td style="width:170px;padding:12px 14px;font-size:0.92rem;border:none;background:transparent;vertical-align:middle;"><?= htmlspecialchars($student['student_id_number'] ?? '—'); ?></td>
                                                        <td style="padding:12px 14px;font-size:0.92rem;border:none;background:transparent;vertical-align:middle;"><?= htmlspecialchars($student['student_name']); ?></td>
                                                        <td style="width:120px;padding:12px 14px;border:none;background:transparent;vertical-align:middle;text-align:right;">
                                                            <span style="display:inline-flex;align-items:center;justify-content:flex-end;gap:6px;font-weight:700;font-size:0.92rem;">
                                                                <span style="width:10px;height:10px;border-radius:50%;background:<?= $gwaDot ?>;display:inline-block;flex-shrink:0;"></span>
                                                                <?= $gwaDisplay ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td class="text-center text-muted py-3">No students registered in this section slot yet.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-light text-center p-5 shadow-sm" style="border-radius: 24px;">
                        <i class="bi bi-journal-x fs-1 text-muted d-block mb-3"></i>
                        <span class="text-muted d-block fw-semibold">No subjects added yet.</span>
                        <small class="text-muted">Click the "Add Subject" button above to register your first subject card entry.</small>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <!-- Add Subject Modal -->
    <div class="modal fade" id="addSubjectModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="save_subject.php" method="POST" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" style="font-weight: bold;">Add Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Subject Name</label>
                        <input type="text" name="subject_name" class="form-control rounded-pill" placeholder="e.g. Capstone" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Section</label>
                        <input type="text" name="section" class="form-control rounded-pill" placeholder="e.g. BSIT-3D" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Year Level</label>
                        <input type="text" name="year_level" class="form-control rounded-pill" placeholder="e.g. 3rd Year" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-info text-white">Save Subject</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="save_student.php" method="POST" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" style="font-weight: bold;">Add Student to Roster</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="modal_subject_name" name="subject_name">
                    <input type="hidden" id="modal_section" name="section">
                    <input type="hidden" id="modal_year_level" name="year_level">

                    <div class="alert alert-light border mb-3">
                        <small class="text-muted d-block">Target Class Assignment:</small>
                        <strong id="display_class_context" class="text-uppercase text-primary"></strong>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Student ID Number</label>
                        <input type="text" name="student_id_number" class="form-control rounded-pill" placeholder="e.g. 2021-00123" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Student Full Name</label>
                        <input type="text" name="student_name" class="form-control rounded-pill" placeholder="Firstname Lastname" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success text-white">Register Student</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Student Info + Grades Modal -->
    <div class="modal fade" id="studentInfoModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border-radius: 20px; border: none;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Student Information</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 pb-2">

                    <!-- Student Info -->
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div id="si_avatar" style="width:56px;height:56px;border-radius:50%;background:#1a252f;color:#fff;font-size:1.2rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;"></div>
                        <div>
                            <h5 id="si_name" class="fw-bold mb-0"></h5>
                            <span id="si_id" class="text-muted" style="font-size:0.85rem;"></span>
                        </div>
                        <span class="badge ms-auto px-3 py-2 rounded-pill" style="background:#aaff00;color:#000;font-size:0.8rem;">Enrolled</span>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <div class="p-2 rounded-3 text-center" style="background:#f0f4ff;">
                                <small class="text-muted d-block">Subject</small>
                                <strong id="si_subject" class="text-uppercase" style="font-size:0.85rem;"></strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 rounded-3 text-center" style="background:#f0fff4;">
                                <small class="text-muted d-block">Section</small>
                                <strong id="si_section" class="text-uppercase" style="font-size:0.85rem;"></strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 rounded-3 text-center" style="background:#fffbf0;">
                                <small class="text-muted d-block">Year Level</small>
                                <strong id="si_year" class="text-uppercase" style="font-size:0.85rem;"></strong>
                            </div>
                        </div>
                    </div>

                    <hr class="my-2">

                    <!-- Grades Section -->
                    <div class="row g-3 mt-1">

                        <!-- Quizzes -->
                        <div class="col-md-4">
                            <div class="p-3 rounded-3" style="background:#f8f8f8;border:1px solid #eee;">
                                <div class="fw-bold mb-2" style="font-size:0.85rem;letter-spacing:1px;">QUIZZES</div>
                                <div id="quiz_list"></div>
                                <button type="button" class="btn btn-sm btn-link text-muted px-0 mt-1" onclick="addQuiz()">
                                    <i class="bi bi-plus-circle"></i> Add quiz
                                </button>
                            </div>
                        </div>

                        <!-- Project -->
                        <div class="col-md-4">
                            <div class="p-3 rounded-3" style="background:#f8f8f8;border:1px solid #eee;">
                                <div class="fw-bold mb-2" style="font-size:0.85rem;letter-spacing:1px;">PROJECT</div>
                                <div id="project_list"></div>
                                <button type="button" class="btn btn-sm btn-link text-muted px-0 mt-1" onclick="addProject()">
                                    <i class="bi bi-plus-circle"></i> Add project
                                </button>
                            </div>
                        </div>

                        <!-- Midterm + Final -->
                        <div class="col-md-4 d-flex flex-column gap-3">
                            <div class="p-3 rounded-3" style="background:#f8f8f8;border:1px solid #eee;">
                                <div class="fw-bold mb-2" style="font-size:0.85rem;letter-spacing:1px;">MIDTERM EXAM</div>
                                <div class="d-flex align-items-center gap-2">
                                    <span style="font-size:0.85rem;">Midterm exam</span>
                                    <input type="number" id="midterm_score" class="form-control form-control-sm text-center" style="width:60px;" min="0" placeholder="0">
                                    <span class="text-muted">/</span>
                                    <input type="number" id="midterm_total" class="form-control form-control-sm text-center" style="width:60px;" min="0" placeholder="100">
                                </div>
                            </div>
                            <div class="p-3 rounded-3" style="background:#f8f8f8;border:1px solid #eee;">
                                <div class="fw-bold mb-2" style="font-size:0.85rem;letter-spacing:1px;">FINAL EXAM</div>
                                <div class="d-flex align-items-center gap-2">
                                    <span style="font-size:0.85rem;">Final exam</span>
                                    <input type="number" id="final_score" class="form-control form-control-sm text-center" style="width:60px;" min="0" placeholder="0">
                                    <span class="text-muted">/</span>
                                    <input type="number" id="final_total" class="form-control form-control-sm text-center" style="width:60px;" min="0" placeholder="100">
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer border-0 pt-2">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn rounded-pill px-4 fw-bold" style="background:#aaff00;color:#000;" onclick="saveGrades()">Save Grades</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Quick-View Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:20px;border:none;overflow:hidden;">
                <!-- Header banner -->
                <div style="background:linear-gradient(135deg,#1a252f,#2d6a4f);padding:30px 24px 50px;position:relative;">
                    <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal"></button>
                    <div style="width:72px;height:72px;border-radius:50%;background:#fff;color:#1a252f;font-size:1.5rem;font-weight:800;display:flex;align-items:center;justify-content:center;border:3px solid rgba(255,255,255,0.4);">
                        <?= htmlspecialchars(formatAvatarText($teacher['first_name'] . ' ' . $teacher['last_name'])); ?>
                    </div>
                </div>
                <div class="modal-body pt-0 px-4 pb-4" style="margin-top:-20px;">
                    <div style="background:#fff;border-radius:16px;padding:16px 20px;box-shadow:0 2px 12px rgba(0,0,0,0.08);margin-bottom:16px;">
                        <h5 class="fw-bold mb-0"><?= htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></h5>
                        <span class="text-muted" style="font-size:0.85rem;">Faculty Member</span>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div style="background:#f8f9fa;border-radius:12px;padding:12px;">
                                <small class="text-muted d-block mb-1"><i class="bi bi-envelope-fill me-1"></i>Email</small>
                                <strong style="font-size:0.82rem;word-break:break-all;"><?= htmlspecialchars($teacher['email'] ?? 'Not set'); ?></strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div style="background:#f8f9fa;border-radius:12px;padding:12px;">
                                <small class="text-muted d-block mb-1"><i class="bi bi-telephone-fill me-1"></i>Phone</small>
                                <strong style="font-size:0.82rem;"><?= htmlspecialchars($teacher['phone'] ?? 'Not set'); ?></strong>
                            </div>
                        </div>
                        <div class="col-12">
                            <div style="background:#f8f9fa;border-radius:12px;padding:12px;">
                                <small class="text-muted d-block mb-1"><i class="bi bi-person-badge-fill me-1"></i>Employee ID</small>
                                <strong style="font-size:0.82rem;"><?= htmlspecialchars($teacher['employee_id'] ?? 'Not set'); ?></strong>
                            </div>
                        </div>
                    </div>
                    <a href="teacher_profile.php" class="btn w-100 fw-bold" style="background:#1a252f;color:#fff;border-radius:12px;">
                        <i class="bi bi-pencil-square me-2"></i>Edit Full Profile
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Teacher Profile Modal -->
    <div class="modal fade" id="teacherProfileModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:20px;border:none;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">My Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 pb-4">
                    <!-- Avatar -->
                    <div class="text-center mb-4">
                        <div style="width:72px;height:72px;border-radius:50%;background:#1a252f;color:#fff;font-size:1.6rem;font-weight:700;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                            <?= htmlspecialchars(formatAvatarText($teacher['first_name'] . ' ' . $teacher['last_name'])); ?>
                        </div>
                        <h5 class="fw-bold mb-0"><?= htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></h5>
                        <small class="text-muted">Faculty</small>
                    </div>
                    <hr>
                    <!-- Info -->
                    <div class="row g-3 mt-1">
                        <div class="col-6">
                            <div class="p-3 rounded-3" style="background:#f0f4ff;">
                                <small class="text-muted d-block mb-1"><i class="bi bi-person-fill me-1"></i>First Name</small>
                                <strong><?= htmlspecialchars($teacher['first_name']); ?></strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-3" style="background:#f0f4ff;">
                                <small class="text-muted d-block mb-1"><i class="bi bi-person-fill me-1"></i>Last Name</small>
                                <strong><?= htmlspecialchars($teacher['last_name']); ?></strong>
                            </div>
                        </div>
                        <?php if (!empty($teacher['email'])): ?>
                        <div class="col-12">
                            <div class="p-3 rounded-3" style="background:#f0fff4;">
                                <small class="text-muted d-block mb-1"><i class="bi bi-envelope-fill me-1"></i>Email</small>
                                <strong><?= htmlspecialchars($teacher['email']); ?></strong>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="d-grid mt-4">
                        <a href="teacher_profile.php" class="btn btn-dark rounded-pill fw-semibold">
                            <i class="bi bi-pencil-fill me-2"></i>Edit Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const courseFilter  = document.getElementById('courseFilter');
        const yearFilter    = document.getElementById('yearFilter');
        const sectionFilter = document.getElementById('sectionFilter');
        const studentSearch = document.getElementById('studentSearch');

        // 1. Accordion toggle — ignore clicks inside modals, buttons, filters
        document.addEventListener('click', function(e) {
            if (e.target.closest('.modal') ||
                e.target.closest('.open-add-student-modal') ||
                e.target.closest('.student-item') ||
                e.target.closest('[data-bs-toggle="modal"]') ||
                e.target.closest('.filter-dropdown') ||
                e.target.id === 'studentSearch') {
                return;
            }
            const card = e.target.closest('.section-card');
            if (card) {
                card.classList.toggle('active');
            }
        });

        // 2. Populate hidden fields in the Add Student modal
        document.querySelectorAll('.open-add-student-modal').forEach(button => {
            button.addEventListener('click', function(e) {
                e.stopPropagation();

                const subject = this.getAttribute('data-subject');
                const section = this.getAttribute('data-section');
                const year    = this.getAttribute('data-year');

                document.getElementById('modal_subject_name').value  = subject;
                document.getElementById('modal_section').value       = section;
                document.getElementById('modal_year_level').value    = year;
                document.getElementById('display_class_context').innerText = `${subject} (${section})`;

                const modalElement = document.getElementById('addStudentModal');
                let studentModal = bootstrap.Modal.getInstance(modalElement);
                if (!studentModal) {
                    studentModal = new bootstrap.Modal(modalElement);
                }
                studentModal.show();
            });
        });

        // 3. Filter engine
        function filterCards() {
            const course  = courseFilter.value.toLowerCase();
            const year    = yearFilter.value.toLowerCase();
            const section = sectionFilter.value.toLowerCase();
            const search  = studentSearch.value.toLowerCase();

            document.querySelectorAll('.section-card').forEach(card => {
                let isVisible = true;

                if (course  && !card.dataset.course.toLowerCase().includes(course))   isVisible = false;
                if (year    && !card.dataset.year.toLowerCase().includes(year))        isVisible = false;
                if (section && !card.dataset.section.toLowerCase().includes(section)) isVisible = false;

                card.style.display = isVisible ? '' : 'none';

                if (isVisible && search) {
                    const students = card.querySelectorAll('.student-item');
                    let hasMatch = false;

                    students.forEach(student => {
                        const name = student.dataset.name || '';
                        if (name.includes(search)) {
                            student.style.display = '';
                            hasMatch = true;
                        } else {
                            student.style.display = 'none';
                        }
                    });

                    if (!hasMatch) {
                        card.style.display = 'none';
                    } else {
                        card.classList.add('active');
                    }
                } else if (isVisible) {
                    card.querySelectorAll('.student-item').forEach(s => s.style.display = '');
                }

                if (!isVisible) card.classList.remove('active');
            });
        }

        if (courseFilter)  courseFilter.addEventListener('change', filterCards);
        if (yearFilter)    yearFilter.addEventListener('change', filterCards);
        if (sectionFilter) sectionFilter.addEventListener('change', filterCards);
        if (studentSearch) studentSearch.addEventListener('input', filterCards);

    // Profile modal
    function openProfileModal() {
        const modal = new bootstrap.Modal(document.getElementById('profileModal'));
        modal.show();
    }

    // 4. Student info + grades modal
    let quizCount    = 0;
    let projectCount = 0;

    function openStudentInfoModal(row) {
        const name    = row.dataset.studentName;
        const id      = row.dataset.studentId;
        const subject = row.dataset.subject;
        const section = row.dataset.section;
        const year    = row.dataset.year;

        // Avatar initials
        const parts    = name.trim().split(' ');
        const initials = parts.map(p => p[0]).join('').substring(0, 2).toUpperCase();

        document.getElementById('si_avatar').innerText  = initials;
        document.getElementById('si_name').innerText    = name;
        document.getElementById('si_id').innerText      = 'ID: ' + id;
        document.getElementById('si_subject').innerText = subject;
        document.getElementById('si_section').innerText = section;
        document.getElementById('si_year').innerText    = year || 'N/A';

        // Reset grades
        quizCount    = 0;
        projectCount = 0;
        document.getElementById('quiz_list').innerHTML    = '';
        document.getElementById('project_list').innerHTML = '';
        document.getElementById('midterm_score').value   = '';
        document.getElementById('midterm_total').value   = '';
        document.getElementById('final_score').value     = '';
        document.getElementById('final_total').value     = '';
        addQuiz();
        addProject();

        const modal = new bootstrap.Modal(document.getElementById('studentInfoModal'));
        modal.show();
    }

    function addQuiz() {
        quizCount++;
        const div = document.createElement('div');
        div.className = 'd-flex align-items-center gap-2 mb-2';
        div.innerHTML = `
            <span style="font-size:0.85rem;min-width:55px;">Quiz #${quizCount}</span>
            <input type="number" class="form-control form-control-sm text-center quiz-score" style="width:55px;" min="0" placeholder="0">
            <span class="text-muted">/</span>
            <input type="number" class="form-control form-control-sm text-center quiz-total" style="width:55px;" min="0" placeholder="0">
        `;
        document.getElementById('quiz_list').appendChild(div);
    }

    function addProject() {
        projectCount++;
        const div = document.createElement('div');
        div.className = 'd-flex align-items-center gap-2 mb-2';
        div.innerHTML = `
            <span style="font-size:0.85rem;min-width:65px;">Project ${projectCount}</span>
            <input type="number" class="form-control form-control-sm text-center project-score" style="width:55px;" min="0" placeholder="0">
            <span class="text-muted">/</span>
            <input type="number" class="form-control form-control-sm text-center project-total" style="width:55px;" min="0" placeholder="0">
        `;
        document.getElementById('project_list').appendChild(div);
    }

    function saveGrades() {
        const quizScores  = [...document.querySelectorAll('.quiz-score')].map(i => i.value);
        const quizTotals  = [...document.querySelectorAll('.quiz-total')].map(i => i.value);
        const projScores  = [...document.querySelectorAll('.project-score')].map(i => i.value);
        const projTotals  = [...document.querySelectorAll('.project-total')].map(i => i.value);

        const payload = {
            student_name:      document.getElementById('si_name').innerText,
            student_id_number: document.getElementById('si_id').innerText.replace('ID: ', ''),
            subject_name:      document.getElementById('si_subject').innerText,
            section:           document.getElementById('si_section').innerText,
            quizzes:   quizScores.map((s, i) => ({ score: s, total: quizTotals[i] })),
            projects:  projScores.map((s, i) => ({ score: s, total: projTotals[i] })),
            midterm_score: document.getElementById('midterm_score').value,
            midterm_total: document.getElementById('midterm_total').value,
            final_score:   document.getElementById('final_score').value,
            final_total:   document.getElementById('final_total').value,
        };

        const saveBtn = document.querySelector('#studentInfoModal .modal-footer .btn:last-child');
        saveBtn.disabled    = true;
        saveBtn.innerText   = 'Saving...';

        fetch('save_grades.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                saveBtn.innerText = 'Saved!';
                setTimeout(() => {
                    location.reload(); // Reload so GWA updates on the roster
                }, 1000);
            } else {
                alert('Error: ' + data.message);
                saveBtn.disabled  = false;
                saveBtn.innerText = 'Save Grades';
            }
        })
        .catch(() => {
            alert('Network error. Please try again.');
            saveBtn.disabled  = false;
            saveBtn.innerText = 'Save Grades';
        });
    }
    // Profile modal
    function openProfileModal() {
        const modal = new bootstrap.Modal(document.getElementById('teacherProfileModal'));
        modal.show();
    }
    </script>
</body>
</html>