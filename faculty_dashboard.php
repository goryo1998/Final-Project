<?php
session_start();
if (!isset($_SESSION['teacher_authenticated']) || $_SESSION['teacher_authenticated'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'config/db.php';

$stmt = $pdo->prepare('SELECT * FROM teachers WHERE id = ? LIMIT 1');
$stmt->execute([$_SESSION['teacher_id']]);
$teacher = $stmt->fetch();

if (!$teacher) {
    header('Location: login.php');
    exit;
}

// Fetch permanent schedules from the database for this specific teacher
$schedStmt = $pdo->prepare('SELECT id, subject, room, schedule_date AS date, TIME_FORMAT(start_time, "%H:%i") AS start, TIME_FORMAT(end_time, "%H:%i") AS end, color FROM faculty_schedules WHERE teacher_id = ?');
$schedStmt->execute([$teacher['id']]);
$databaseSchedules = $schedStmt->fetchAll(PDO::FETCH_ASSOC);

function formatAvatarText($name) {
    $parts = explode(' ', trim($name));
    $initials = '';
    foreach ($parts as $part) {
        if ($part !== '') {
            $initials .= strtoupper($part[0]);
        }
        if (strlen($initials) >= 2) {
            break;
        }
    }
    return $initials ?: 'FC';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UEP Faculty Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body.faculty-dashboard .site-header{
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 36px;
            background: rgba(255, 255, 255, 0.88);
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            position: sticky;
            top: 0;
            z-index: 10;
            gap: 24px;
            margin: 20px 15px 0;
            border-radius: 24px;
            background: linear-gradient(to left, blue, yellow);
        }
        body.faculty-dashboard .header-brand{
            display: flex;
            align-items: center;
            gap: 24px;
        }
        body.faculty-dashboard .header-brand img{
            width: 68px;
            border-radius: 16px;
            box-shadow: 0 14px 35px rgba(0,0,0,0.16);
        }
        body.faculty-dashboard .brand-text{ 
            display:flex; 
            flex-direction:column; 
            gap:3px; 
        }
        body.faculty-dashboard .brand-text span, body.faculty-dashboard .brand-text strong{ 
            color:#1c1c1c; 
            line-height:1.2;
         }
        body.faculty-dashboard .brand-text strong{ 
            font-size:1rem; 
            font-weight:700; 
        }
        body.faculty-dashboard .site-header h1{
             margin:0; 
             font-size:1.6rem; 
             letter-spacing:0.12em; 
             text-transform:uppercase; 
             color:#111; }
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
                <a href="faculty_dashboard.php" class="nav-item active">
                    <i class="bi bi-journal-bookmark-fill"></i>
                    Subject and sched
                </a>
                <a href="students_records.php" class="nav-item">
                    <i class="bi bi-people-fill"></i>
                    Students and records
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
            <section class="page-header">
                <div>
                    <p class="eyebrow">SUBJECT & SCHEDULES</p>
                    <h2>Today’s classroom flow</h2
                    >
                </div>
                <button class="btn btn-primary add-schedule-btn" id="openScheduleModal">
                    <i class="bi bi-plus-lg"></i> Add Schedule
                </button>
            </section>

            <section class="dashboard-grid">
                <article class="schedule-board">
                    <div class="board-head">
                        <div>
                            <p class="board-label">Schedule board</p>
                            <h3 id="selectedDayLabel"></h3>
                        </div>
                        <span class="badge">Live</span>
                    </div>
                    <div class="timeline" id="timelineContainer"></div>
                </article>

                <aside class="calendar-panel">
                    <div class="calendar-header">mn
                        <button class="calendar-nav" id="prevMonth"><i class="bi bi-chevron-left"></i></button>
                        <div class="calendar-title">
                            <select id="monthSelect"></select>
                            <select id="yearSelect"></select>
                        </div>
                        <button class="calendar-nav" id="nextMonth"><i class="bi bi-chevron-right"></i></button>
                    </div>
                    <div class="calendar-grid" id="calendarGrid"></div>
                </aside>
            </section>

            <section class="course-preview" id="courseCards">
            </section>
        </main>
    </div>

    <div class="modal-overlay" id="scheduleModal">
        <div class="modal-card">
            <div class="modal-header">
                <h3>Add New Schedule</h3>
                <button class="close-modal" id="closeModal"><i class="bi bi-x-lg"></i></button>
            </div>
            <form id="scheduleForm">
                <label>Subject</label>
                <input type="text" id="subjectField" class="form-control" placeholder="e.g. Web Development" required>
                <label>Room</label>
                <input type="text" id="roomField" class="form-control" placeholder="e.g. Room 310" required>
                <div class="modal-row">
                    <div>
                        <label>Date</label>
                        <input type="date" id="dateField" class="form-control" required>
                    </div>
                    <div>
                        <label>Time</label>
                        <input type="time" id="startField" class="form-control" required>
                        <input type="time" id="endField" class="form-control mt-3" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Save Schedule</button>
            </form>
        </div>
    </div>

    <div class="modal fade" id="teacherProfileModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:20px;border:none;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">My Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 pb-4">
                    <div class="text-center mb-4">
                        <div style="width:72px;height:72px;border-radius:50%;background:#1a252f;color:#fff;font-size:1.6rem;font-weight:700;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                            <?= htmlspecialchars(formatAvatarText($teacher['first_name'] . ' ' . $teacher['last_name'])); ?>
                        </div>
                        <h5 class="fw-bold mb-0"><?= htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></h5>
                        <small class="text-muted">Faculty</small>
                    </div>
                    <hr>
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

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const todayObj = new Date();
        const todayStr = `${todayObj.getFullYear()}-${String(todayObj.getMonth() + 1).padStart(2, '0')}-${String(todayObj.getDate()).padStart(2, '0')}`;
        
        // Force drop buggy string variables out of browser cache storage completely
        localStorage.removeItem('faculty_selected_date');
        let selectedDate = todayStr;

        const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        // Connect directly to backend SQL payload array data
        let scheduleEvents = <?= json_encode($databaseSchedules); ?>;

        const monthSelect = document.getElementById('monthSelect');
        const yearSelect = document.getElementById('yearSelect');
        const calendarGrid = document.getElementById('calendarGrid');
        const selectedDayLabel = document.getElementById('selectedDayLabel');
        const timelineContainer = document.getElementById('timelineContainer');
        const courseCards = document.getElementById('courseCards');
        const scheduleModal = document.getElementById('scheduleModal');
        const openModalBtn = document.getElementById('openScheduleModal');
        const closeModalBtn = document.getElementById('closeModal');

        const parsedSelectedDate = selectedDate.split('-');
        let currentMonth = parseInt(parsedSelectedDate[1], 10) - 1;
        let currentYear = parseInt(parsedSelectedDate[0], 10);

        function updateSelectedDate(dateString) {
            selectedDate = dateString;
            localStorage.setItem('faculty_selected_date', selectedDate);
            renderCalendar();
            renderScheduleBoard();
            renderCourseCards();
        }

        function renderCalendar() {
            monthSelect.innerHTML = '';
            for (let i = 0; i < monthNames.length; i++) {
                const option = document.createElement('option');
                option.value = i;
                option.innerText = monthNames[i];
                if (i === currentMonth) option.selected = true;
                monthSelect.appendChild(option);
            }

            yearSelect.innerHTML = '';
            const yearRange = Array.from({ length: 6 }, (_, idx) => currentYear - 2 + idx);
            yearRange.forEach(year => {
                const option = document.createElement('option');
                option.value = year;
                option.innerText = year;
                if (year === currentYear) option.selected = true;
                yearSelect.appendChild(option);
            });

            const first = new Date(currentYear, currentMonth, 1);
            const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
            const startDay = first.getDay();

            calendarGrid.innerHTML = '';
            ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'].forEach(day => {
                const label = document.createElement('div');
                label.className = 'calendar-cell calendar-header-cell';
                label.innerText = day;
                calendarGrid.appendChild(label);
            });

            for (let i = 0; i < startDay; i++) {
                const empty = document.createElement('div');
                empty.className = 'calendar-cell empty-cell';
                calendarGrid.appendChild(empty);
            }

            for (let day = 1; day <= daysInMonth; day++) {
                const mm = String(currentMonth + 1).padStart(2, '0');
                const dd = String(day).padStart(2, '0');
                const dateString = `${currentYear}-${mm}-${dd}`;

                const cell = document.createElement('button');
                cell.type = 'button';
                cell.className = 'calendar-cell day-cell';
                cell.innerHTML = `<span>${day}</span>`;

                if (dateString === selectedDate) cell.classList.add('selected');
                if (dateString === todayStr) cell.classList.add('today');

                const eventsOnDate = scheduleEvents.filter(event => event.date === dateString);
                if (eventsOnDate.length) {
                    const dot = document.createElement('span');
                    dot.className = 'event-dot';
                    cell.appendChild(dot);
                }

                cell.addEventListener('click', () => updateSelectedDate(dateString));
                calendarGrid.appendChild(cell);
            }
        }

        function renderScheduleBoard() {
            const parts = selectedDate.split('-');
            const year = parseInt(parts[0], 10);
            const month = parseInt(parts[1], 10) - 1;
            const day = parseInt(parts[2], 10);
            
            const localDateInstance = new Date(year, month, day);
            selectedDayLabel.textContent = `${dayNames[localDateInstance.getDay()]}, ${monthNames[month]} ${day}`;
            
            const dayEvents = scheduleEvents
                .filter(event => event.date === selectedDate)
                .sort((a, b) => a.start.localeCompare(b.start));

            timelineContainer.innerHTML = '';
            if (!dayEvents.length) {
                timelineContainer.innerHTML = `<div class="empty-state">No schedule yet for this day. Add a new subject to start teaching.</div>`;
                return;
            }

            dayEvents.forEach(event => {
                const item = document.createElement('div');
                item.className = 'schedule-card';
                item.style.borderColor = event.color;
                item.innerHTML = `
                    <div class="schedule-top" style="background:${event.color}33; color:${event.color}">
                        <strong>${event.subject}</strong>
                        <span>${event.room}</span>
                    </div>
                    <div class="schedule-meta">
                        <span><i class="bi bi-clock"></i> ${event.start} - ${event.end}</span>
                        <span><i class="bi bi-calendar-event"></i> ${monthNames[month].slice(0, 3)} ${day}</span>
                    </div>
                `;
                timelineContainer.appendChild(item);
            });
        }

        function renderCourseCards() {
            const upcoming = scheduleEvents
                .filter(event => event.date >= todayStr)
                .sort((a,b) => a.date.localeCompare(b.date) || a.start.localeCompare(b.start))
                .slice(0, 3);

            courseCards.innerHTML = upcoming.map(event => {
                const parts = event.date.split('-');
                const mIdx = parseInt(parts[1], 10) - 1;
                const dayNum = parseInt(parts[2], 10);
                return `
                    <div class="course-card">
                        <div>
                            <h3>${event.subject}</h3>
                            <p>${event.room}</p>
                        </div>
                        <div class="course-meta">
                            <span><i class="bi bi-clock"></i> ${event.start}</span>
                            <span><i class="bi bi-calendar3"></i> ${monthNames[mIdx].slice(0, 3)} ${dayNum}</span>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function openModal() {
            scheduleModal.classList.add('visible');
            document.getElementById('dateField').value = selectedDate;
        }

        function closeModal() {
            scheduleModal.classList.remove('visible');
            document.getElementById('scheduleForm').reset();
        }

        openModalBtn.addEventListener('click', openModal);
        closeModalBtn.addEventListener('click', closeModal);
        scheduleModal.addEventListener('click', (event) => {
            if (event.target === scheduleModal) closeModal();
        });

        document.getElementById('scheduleForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const subject = document.getElementById('subjectField').value.trim();
            const room = document.getElementById('roomField').value.trim();
            const date = document.getElementById('dateField').value; 
            const start = document.getElementById('startField').value;
            const end = document.getElementById('endField').value;

            if (!subject || !room || !date || !start || !end) return;

            const colors = ['#6366f1', '#f97316', '#22c55e', '#ec4899', '#0ea5e9'];
            const color = colors[Math.floor(Math.random() * colors.length)];

            const payload = { subject, room, date, start, end, color };

            // Fire real-time asynchronous background AJAX directly down to database
            fetch('save_schedule.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Instantly sync dashboard layout upon active backend injection
                    scheduleEvents.push({ id: Date.now(), date, subject, room, start, end, color });
                    
                    const dateParts = date.split('-');
                    currentMonth = parseInt(dateParts[1], 10) - 1;
                    currentYear = parseInt(dateParts[0], 10);
                    
                    updateSelectedDate(date);
                    closeModal();
                } else {
                    alert('Error saving schedule: ' + data.message);
                }
            })
            .catch(err => {
                console.error('Network Error:', err);
                alert('Could not connect to server.');
            });
        });

        document.getElementById('prevMonth').addEventListener('click', () => {
            currentMonth -= 1;
            if (currentMonth < 0) { currentMonth = 11; currentYear -= 1; }
            renderCalendar();
        });

        document.getElementById('nextMonth').addEventListener('click', () => {
            currentMonth += 1;
            if (currentMonth > 11) { currentMonth = 0; currentYear += 1; }
            renderCalendar();
        });

        monthSelect.addEventListener('change', () => {
            currentMonth = parseInt(monthSelect.value, 10);
            renderCalendar();
        });

        yearSelect.addEventListener('change', () => {
            currentYear = parseInt(yearSelect.value, 10);
            renderCalendar();
        });

        function openProfileModal() {
            new bootstrap.Modal(document.getElementById('teacherProfileModal')).show();
        }

        renderCalendar();
        renderScheduleBoard();
        renderCourseCards();
    </script>
</body>
</html>