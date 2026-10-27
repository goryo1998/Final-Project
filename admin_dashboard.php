<?php
session_start();
if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header("Location: login.php");
    exit;
}

require_once 'config/db.php';

// Logged-in user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

// ── Stat Cards ──────────────────────────────────────────────────────────────
$studentCount = 0;
$teacherCount = 0;
$enrolledCount = 0;
$graduatingCount = 0;

try {
    $studentCount    = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
    $teacherCount    = $pdo->query("SELECT COUNT(*) FROM teachers")->fetchColumn();
    $enrolledCount   = $pdo->query("SELECT COUNT(*) FROM students WHERE status = 'enrolled'")->fetchColumn();
    $graduatingCount = $pdo->query("SELECT COUNT(*) FROM students WHERE status = 'graduating'")->fetchColumn();
} catch (Exception $e) {}

// ── Chart 1: Student Population by Year Level (Bar) ─────────────────────────
$yearLabels = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
$yearData   = [0, 0, 0, 0];
try {
    $rows = $pdo->query("
        SELECT year_level, COUNT(*) as cnt
        FROM students
        GROUP BY year_level
    ")->fetchAll(PDO::FETCH_ASSOC);

    $yearMap = [
        '1st Year' => 0, '1' => 0, '1st' => 0,
        '2nd Year' => 1, '2' => 1, '2nd' => 1,
        '3rd Year' => 2, '3' => 2, '3rd' => 2,
        '4th Year' => 3, '4' => 3, '4th' => 3,
    ];
    foreach ($rows as $row) {
        $key = trim($row['year_level']);
        if (isset($yearMap[$key])) {
            $yearData[$yearMap[$key]] = (int)$row['cnt'];
        }
    }
} catch (Exception $e) {}

// ── Chart 2: Enrollment per Semester (Line) ──────────────────────────────────
$semLabels = [];
$semData   = [];
try {
    $rows = $pdo->query("
        SELECT semester, COUNT(*) as cnt
        FROM students
        GROUP BY semester
        ORDER BY MIN(created_at)
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $semLabels[] = $row['semester'];
        $semData[]   = (int)$row['cnt'];
    }
} catch (Exception $e) {}

// Fallback if no semester data
if (empty($semLabels)) {
    $semLabels = ['No Data'];
    $semData   = [0];
}

// ── Chart 3: Graduating Students by Course (Doughnut) ────────────────────────
$courseLabels = [];
$courseData   = [];
try {
    $rows = $pdo->query("
        SELECT course, COUNT(*) as cnt
        FROM students
        WHERE status = 'graduating'
          AND course IS NOT NULL
          AND course != ''
        GROUP BY course
        ORDER BY cnt DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $courseLabels[] = $row['course'];
        $courseData[]   = (int)$row['cnt'];
    }
} catch (Exception $e) {}

if (empty($courseLabels)) {
    $courseLabels = ['No Data'];
    $courseData   = [1];
}

// ── Chart 4: Students by Gender (Doughnut) ───────────────────────────────────
$genderLabels = [];
$genderData   = [];
try {
    $rows = $pdo->query("
        SELECT gender, COUNT(*) as cnt
        FROM students
        WHERE gender IS NOT NULL AND gender != ''
        GROUP BY gender
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $genderLabels[] = ucfirst($row['gender']);
        $genderData[]   = (int)$row['cnt'];
    }
} catch (Exception $e) {}

if (empty($genderLabels)) {
    $genderLabels = ['Male', 'Female'];
    $genderData   = [0, 0];
}

// JSON-encode for JS
$yearLabels_json   = json_encode($yearLabels);
$yearData_json     = json_encode($yearData);
$semLabels_json    = json_encode($semLabels);
$semData_json      = json_encode($semData);
$courseLabels_json = json_encode($courseLabels);
$courseData_json   = json_encode($courseData);
$genderLabels_json = json_encode($genderLabels);
$genderData_json   = json_encode($genderData);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <style>
        /* ── Extra dashboard layout styles ── */
        body.admin-dashboard .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 28px;
        }

        body.admin-dashboard .stat-card {
            background: rgba(255,255,255,0.92);
            border-radius: 20px;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 12px 32px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.84);
        }

        body.admin-dashboard .stat-icon { font-size: 2rem; }

        body.admin-dashboard .stat-label {
            color: #6b7280;
            font-size: 0.8rem;
            margin-bottom: 4px;
        }

        body.admin-dashboard .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1e3a8a;
            line-height: 1;
        }

        /* ── Chart grid: 1 wide top, 3 below ── */
        body.admin-dashboard .dashboard-charts {
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: auto auto;
            gap: 22px;
        }

        /* Bar chart spans full width on top */
        body.admin-dashboard .chart-card.chart-wide {
            grid-column: 1 / -1;
        }

        body.admin-dashboard .chart-card {
            background: rgba(255,255,255,0.92);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 12px 32px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.84);
        }

        body.admin-dashboard .chart-card h3 {
            margin: 0 0 18px;
            font-size: 0.95rem;
            font-weight: 700;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        /* Make canvases a fixed height so they don't overflow */
        body.admin-dashboard .chart-card.chart-wide canvas {
            max-height: 260px;
        }

        body.admin-dashboard .chart-card canvas {
            max-height: 240px;
        }

        body.admin-dashboard .main-content {
            flex: 1;
            padding: 28px 32px;
            overflow-y: auto;
        }
    </style>
</head>
<body class="admin-dashboard">
    <header class="site-header">
        <div class="header-brand">
            <img src="images/UEPLOGO.png" alt="UEP Logo">
            <div class="brand-text">
                <span>Republic of the Philippines</span>
                <strong>University of Eastern Philippines</strong>
                <span>University Town, Northern Samar</span>
            </div>
        </div>
        <h1>UEP Administrator</h1>
    </header>

    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <nav>
                <a href="admin_dashboard.php" class="nav-item active">Dashboard</a>
                <a href="student_faculty.php" class="nav-item">Student / Faculty</a>
            </nav>
            <div class="sidebar-footer">
                <a href="user_profile.php" class="user-box" style="text-decoration:none;color:inherit;">
                    <div class="user-avatar" style="background-image:url('uploads/<?= htmlspecialchars($user['profile_picture'] ?? 'default.png'); ?>');background-size:cover;background-position:center;"></div>
                    <div class="user-info">
                        <span class="small text-muted">welcome!</span>
                        <strong><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                    </div>
                </a>
                <a href="logout.php" class="logout-btn">LOGOUT</a>
            </div>
        </aside>

        <main class="main-content">

            <!-- ── Stat Cards ── -->
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <p class="stat-label">Total Students</p>
                        <p class="stat-value"><?= number_format($studentCount); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👨‍🏫</div>
                    <div class="stat-content">
                        <p class="stat-label">Total Teachers</p>
                        <p class="stat-value"><?= number_format($teacherCount); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📋</div>
                    <div class="stat-content">
                        <p class="stat-label">Currently Enrolled</p>
                        <p class="stat-value"><?= number_format($enrolledCount); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🎓</div>
                    <div class="stat-content">
                        <p class="stat-label">Graduating</p>
                        <p class="stat-value"><?= number_format($graduatingCount); ?></p>
                    </div>
                </div>
            </div>

            <!-- ── Charts ── -->
            <div class="dashboard-charts">

                <!-- Row 1: Bar chart full width -->
                <div class="chart-card chart-wide">
                    <h3>Student Population by Year Level</h3>
                    <canvas id="populationChart"></canvas>
                </div>

                <!-- Row 2 left: Line chart -->
                <div class="chart-card">
                    <h3>Enrollment per Semester</h3>
                    <canvas id="enrollmentChart"></canvas>
                </div>

                <!-- Row 2 right: stacked doughnuts -->
                <div style="display:flex;flex-direction:column;gap:22px;">
                    <div class="chart-card" style="flex:1;">
                        <h3>Graduating Students by Course</h3>
                        <canvas id="graduatingChart"></canvas>
                    </div>
                    <div class="chart-card" style="flex:1;">
                        <h3>Students by Gender</h3>
                        <canvas id="studentsChart"></canvas>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script>
        const BLUE_DARK   = '#1e3a8a';
        const BLUE_MED    = '#2563eb';
        const BLUE_LIGHT  = '#93c5fd';
        const YELLOW      = '#fbbf24';
        const YELLOW_DARK = '#f59e0b';
        const PURPLE      = '#7c3aed';
        const GREEN       = '#10b981';
        const PINK        = '#f472b6';

        // ── Bar: Population by Year ──────────────────────────────────────────
        new Chart(document.getElementById('populationChart'), {
            type: 'bar',
            data: {
                labels: <?= $yearLabels_json ?>,
                datasets: [{
                    label: 'Students',
                    data: <?= $yearData_json ?>,
                    backgroundColor: [BLUE_DARK, BLUE_MED, YELLOW, YELLOW_DARK],
                    borderRadius: 10,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: {
                        label: ctx => ` ${ctx.parsed.y.toLocaleString()} students`
                    }}
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 },
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    x: { grid: { display: false } }
                }
            }
        });

        // ── Line: Enrollment per Semester ────────────────────────────────────
        new Chart(document.getElementById('enrollmentChart'), {
            type: 'line',
            data: {
                labels: <?= $semLabels_json ?>,
                datasets: [{
                    label: 'Enrolled',
                    data: <?= $semData_json ?>,
                    borderColor: BLUE_MED,
                    backgroundColor: 'rgba(37,99,235,0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: YELLOW,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: {
                        label: ctx => ` ${ctx.parsed.y.toLocaleString()} enrolled`
                    }}
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 },
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    x: { grid: { display: false } }
                }
            }
        });

        // ── Doughnut: Graduating by Course ───────────────────────────────────
        new Chart(document.getElementById('graduatingChart'), {
            type: 'doughnut',
            data: {
                labels: <?= $courseLabels_json ?>,
                datasets: [{
                    data: <?= $courseData_json ?>,
                    backgroundColor: [YELLOW, BLUE_DARK, BLUE_MED, PURPLE, GREEN, PINK, YELLOW_DARK, BLUE_LIGHT],
                    borderWidth: 2,
                    borderColor: '#fff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '60%',
                plugins: {
                    legend: { display: true, position: 'right', labels: { boxWidth: 14, font: { size: 11 } } }
                }
            }
        });

        // ── Doughnut: Gender ─────────────────────────────────────────────────
        new Chart(document.getElementById('studentsChart'), {
            type: 'doughnut',
            data: {
                labels: <?= $genderLabels_json ?>,
                datasets: [{
                    data: <?= $genderData_json ?>,
                    backgroundColor: [BLUE_LIGHT, YELLOW, PURPLE, GREEN],
                    borderWidth: 2,
                    borderColor: '#fff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '60%',
                plugins: {
                    legend: { display: true, position: 'bottom', labels: { boxWidth: 14, font: { size: 11 } } }
                }
            }
        });
    </script>
</body>
</html>