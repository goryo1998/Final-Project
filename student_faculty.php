<?php
session_start();
if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header("Location: login.php");
    exit;
}

require_once 'config/db.php';
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

// Fetch all students
$students = $pdo->query("SELECT * FROM students ORDER BY last_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all faculty
$faculty = $pdo->query("SELECT * FROM teachers ORDER BY last_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch lookup lists for the Course / Section dropdowns
$courses  = $pdo->query("SELECT course_name FROM courses ORDER BY course_name ASC")->fetchAll(PDO::FETCH_COLUMN);
$sections = $pdo->query("SELECT section_name FROM sections ORDER BY section_name ASC")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student / Faculty</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Action buttons in table */
        .tbl-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 5px 12px;
            border: none;
            border-radius: 8px;
            font-size: 0.78rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s, transform 0.15s;
        }
        .tbl-btn:hover { opacity: 0.85; transform: translateY(-1px); }
        .tbl-btn.edit  { background: #2563eb; color: #fff; }
        .tbl-btn.delete{ background: #ef4444; color: #fff; }

        /* Actions column */
        .data-table th:last-child,
        .data-table td:last-child { text-align: center; white-space: nowrap; }

        /* Confirm delete overlay */
        .confirm-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.55);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        .confirm-overlay.visible { display: flex; }
        .confirm-box {
            background: #fff;
            border-radius: 18px;
            padding: 32px 36px;
            max-width: 380px;
            width: 90%;
            text-align: center;
            box-shadow: 0 24px 60px rgba(0,0,0,0.18);
        }
        .confirm-box h3 { margin: 0 0 10px; color: #1e3a8a; }
        .confirm-box p  { margin: 0 0 24px; color: #6b7280; font-size: 0.92rem; }
        .confirm-actions { display: flex; gap: 12px; justify-content: center; }
        .btn-cancel-confirm {
            padding: 10px 24px; border-radius: 10px; border: 2px solid #e5e7eb;
            background: #fff; color: #374151; font-weight: 600; cursor: pointer;
        }
        .btn-confirm-delete {
            padding: 10px 24px; border-radius: 10px; border: none;
            background: #ef4444; color: #fff; font-weight: 600; cursor: pointer;
        }

        /* Edit modal grid */
        .edit-modal .modal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .edit-modal .modal-row  { margin-bottom: 14px; }
        .edit-modal label       { display: flex; flex-direction: column; gap: 4px; font-size: 0.85rem; font-weight: 600; color: #374151; }
    </style>
</head>
<body class="admin-dashboard student-faculty-page">
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
                <a href="admin_dashboard.php" class="nav-item">Dashboard</a>
                <a href="student_faculty.php" class="nav-item active">Student / Faculty</a>
            </nav>
            <div class="sidebar-footer">
                <a href="user_profile.php" class="user-box" style="text-decoration:none;color:inherit;">
                    <div class="user-avatar" style="background-image:url('uploads/<?= htmlspecialchars($user['profile_picture'] ?? 'default.png'); ?>');"></div>
                    <div class="user-info">
                        <span class="small text-muted">welcome!</span>
                        <strong><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                    </div>
                </a>
                <a href="logout.php" class="logout-btn">LOGOUT</a>
            </div>
        </aside>

        <main class="main-content">
            <?php if (!empty($_SESSION['student_form_error'])): ?>
                <div class="feedback-message error">
                    <?= htmlspecialchars($_SESSION['student_form_error']); unset($_SESSION['student_form_error']); ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($_SESSION['student_form_success'])): ?>
                <div class="feedback-message success">
                    <?= htmlspecialchars($_SESSION['student_form_success']); unset($_SESSION['student_form_success']); ?>
                </div>
            <?php endif; ?>

            <section class="records-panel">
                <!-- Student Card -->
                <article class="record-card">
                    <div class="record-card-header">
                        <h2>Student Records</h2>
                        <div class="record-actions">
                            <button type="button" id="openAddStudentModal" class="btn-action"><span class="action-icon">+</span> Add Student</button>
                            <button type="button" id="openViewStudentsModal" class="btn-action"><span class="action-icon">👥</span> View Students</button>
                        </div>
                    </div>
                </article>

                <!-- Faculty Card -->
                <article class="record-card">
                    <div class="record-card-header">
                        <h2>Faculty Records</h2>
                        <div class="record-actions">
                            <button type="button" class="btn-action" id="openAddFacultyModal"><span class="action-icon">+</span> Add Faculty</button>
                            <button type="button" class="btn-action" id="openViewFacultyModal"><span class="action-icon">📋</span> View Faculty</button>
                        </div>
                    </div>
                </article>
            </section>
        </main>
    </div>

    <!-- ════════════════════════════════════════════════
         ADD STUDENT MODAL
    ════════════════════════════════════════════════ -->
    <div id="addStudentModal" class="modal-overlay">
        <div class="modal-card">
            <button type="button" class="modal-close" id="closeAddStudentModal">×</button>
            <div class="modal-header"><h2>ADD A STUDENT</h2></div>
            <form action="add_student.php" method="POST" class="modal-form">
                <div class="modal-row">
                    <label>Student ID Number
                        <input type="text" name="student_id" class="form-control" required>
                    </label>
                </div>
                <div class="modal-grid">
                    <label>First Name <input type="text" name="first_name" class="form-control" required></label>
                    <label>Middle Name <input type="text" name="middle_name" class="form-control"></label>
                    <label>Last Name <input type="text" name="last_name" class="form-control" required></label>
                    <label>Gender
                        <select name="gender" class="form-control" required>
                            <option value="">Select</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </label>
                </div>
                <div class="modal-row two-cols">
                    <label>Email <input type="email" name="email" class="form-control" required></label>
                    <label>Phone Number <input type="tel" name="phone_number" class="form-control" required></label>
                </div>
                <div class="modal-row">
                    <label>Full Address <input type="text" name="address" class="form-control" required></label>
                </div>
                <div class="modal-row two-cols">
                    <label>Year Level
                        <select name="year_level" class="form-control" required>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                        </select>
                    </label>
                    <label>Course
                        <select name="course" class="form-control" required>
                            <option value="">Select Course</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Section
                        <select name="section" class="form-control" required>
                            <option value="">Select Section</option>
                            <?php foreach ($sections as $sec): ?>
                                <option value="<?= htmlspecialchars($sec) ?>"><?= htmlspecialchars($sec) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Status
                        <select name="status" class="form-control" required>
                            <option value="enrolled">Enrolled</option>
                            <option value="graduating">Graduating</option>
                            <option value="dropout">Dropout</option>
                            <option value="graduated">Graduated</option>
                        </select>
                    </label>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="btn btn-add-student">Add Student</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════
         VIEW STUDENTS MODAL
    ════════════════════════════════════════════════ -->
    <div id="viewStudentsModal" class="modal-overlay">
        <div class="modal-card" style="max-width:900px;">
            <button type="button" class="modal-close" id="closeViewStudentsModal">×</button>
            <div class="modal-header">
                <h2>STUDENT RECORDS</h2>
                <input type="text" id="studentSearch" placeholder="Search..." onkeyup="filterStudents()" class="form-control">
            </div>
            <div class="modal-body" style="max-height:420px;overflow-y:auto;">
                <table class="data-table" style="width:100%;">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Course</th>
                            <th>Year Level</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="studentTableBody">
                        <?php foreach ($students as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['student_id']) ?></td>
                            <td><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></td>
                            <td><?= htmlspecialchars($s['course'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($s['year_level']) ?></td>
                            <td><?= htmlspecialchars(ucfirst($s['status'] ?? 'enrolled')) ?></td>
                            <td>
                                <button class="tbl-btn edit" onclick="openEditStudent(<?= htmlspecialchars(json_encode($s)) ?>)">✏️ Edit</button>
                                <button class="tbl-btn delete" onclick="confirmDelete('student', '<?= htmlspecialchars($s['student_id']) ?>', '<?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?>')">🗑️ Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($students)): ?>
                        <tr><td colspan="6" style="text-align:center;color:#9ca3af;padding:24px;">No students found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════
         EDIT STUDENT MODAL
    ════════════════════════════════════════════════ -->
    <div id="editStudentModal" class="modal-overlay edit-modal">
        <div class="modal-card" style="max-width:700px;">
            <button type="button" class="modal-close" onclick="closeModal('editStudentModal')">×</button>
            <div class="modal-header"><h2>EDIT STUDENT</h2></div>
            <form action="edit_student.php" method="POST" class="modal-form">
                <input type="hidden" name="student_id" id="edit_student_id">
                <div class="modal-grid">
                    <label>First Name <input type="text" name="first_name" id="edit_first_name" class="form-control" required></label>
                    <label>Middle Name <input type="text" name="middle_name" id="edit_middle_name" class="form-control"></label>
                    <label>Last Name <input type="text" name="last_name" id="edit_last_name" class="form-control" required></label>
                    <label>Gender
                        <select name="gender" id="edit_gender" class="form-control">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </label>
                </div>
                <div class="modal-row two-cols">
                    <label>Email <input type="email" name="email" id="edit_email" class="form-control"></label>
                    <label>Phone Number <input type="tel" name="phone_number" id="edit_phone_number" class="form-control"></label>
                </div>
                <div class="modal-row">
                    <label>Full Address <input type="text" name="address" id="edit_address" class="form-control"></label>
                </div>
                <div class="modal-grid">
                    <label>Section
                        <select name="section" id="edit_section" class="form-control">
                            <option value="">Select Section</option>
                            <?php foreach ($sections as $sec): ?>
                                <option value="<?= htmlspecialchars($sec) ?>"><?= htmlspecialchars($sec) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Course
                        <select name="course" id="edit_course" class="form-control">
                            <option value="">Select Course</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Year Level
                        <select name="year_level" id="edit_year_level" class="form-control">
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                        </select>
                    </label>
                    <label>Status
                        <select name="status" id="edit_status" class="form-control">
                            <option value="enrolled">Enrolled</option>
                            <option value="graduating">Graduating</option>
                            <option value="dropout">Dropout</option>
                            <option value="graduated">Graduated</option>
                        </select>
                    </label>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="btn btn-add-student">💾 Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════
         ADD FACULTY MODAL
    ════════════════════════════════════════════════ -->
    <div id="addFacultyModal" class="modal-overlay">
        <div class="modal-card">
            <button type="button" class="modal-close" id="closeAddFacultyModal">×</button>
            <div class="modal-header"><h2>ADD FACULTY</h2></div>
            <form action="add_faculty.php" method="POST" class="modal-form">
                <div class="modal-row">
                    <label>Faculty ID Number <input type="text" name="faculty_id" class="form-control" required></label>
                </div>
                <div class="modal-row">
                    <label>Username <input type="text" name="username" class="form-control" placeholder="e.g., jming" required></label>
                </div>
                <div class="modal-grid">
                    <label>First Name <input type="text" name="first_name" class="form-control" required></label>
                    <label>Last Name <input type="text" name="last_name" class="form-control" required></label>
                </div>
                <div class="modal-row">
                    <label>Department <input type="text" name="department" class="form-control" required></label>
                </div>
                <div class="modal-row">
                    <label>Email <input type="email" name="email" class="form-control" required></label>
                </div>
                <div class="modal-row">
                    <label>Address <input type="text" name="address" class="form-control" required></label>
                </div>
                <div class="modal-row">
                    <label>Set Initial Password <input type="password" name="password" class="form-control" required></label>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="btn btn-add-faculty">Add Faculty</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════
         VIEW FACULTY MODAL
    ════════════════════════════════════════════════ -->
    <div id="viewFacultyModal" class="modal-overlay">
        <div class="modal-card" style="max-width:900px;">
            <button type="button" class="modal-close" id="closeViewFacultyModal">×</button>
            <div class="modal-header">
                <h2>FACULTY RECORDS</h2>
                <input type="text" id="facultySearch" placeholder="Search Faculty..." onkeyup="filterFaculty()" class="form-control">
            </div>
            <div class="modal-body" style="max-height:420px;overflow-y:auto;">
                <table class="data-table" style="width:100%;">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="facultyTableBody">
                        <?php foreach ($faculty as $f): ?>
                        <tr>
                            <td><?= htmlspecialchars($f['faculty_id'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($f['first_name'] . ' ' . $f['last_name']) ?></td>
                            <td><?= htmlspecialchars($f['department'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($f['email'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($f['address'] ?? '—') ?></td>
                            <td>
                                <button class="tbl-btn edit" onclick="openEditFaculty(<?= htmlspecialchars(json_encode($f)) ?>)">✏️ Edit</button>
                                <button class="tbl-btn delete" onclick="confirmDelete('faculty', '<?= $f['id'] ?>', '<?= htmlspecialchars($f['first_name'] . ' ' . $f['last_name']) ?>')">🗑️ Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($faculty)): ?>
                        <tr><td colspan="6" style="text-align:center;color:#9ca3af;padding:24px;">No faculty found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════
         EDIT FACULTY MODAL
    ════════════════════════════════════════════════ -->
    <div id="editFacultyModal" class="modal-overlay edit-modal">
        <div class="modal-card" style="max-width:600px;">
            <button type="button" class="modal-close" onclick="closeModal('editFacultyModal')">×</button>
            <div class="modal-header"><h2>EDIT FACULTY</h2></div>
            <form action="edit_faculty.php" method="POST" class="modal-form">
                <input type="hidden" name="id" id="ef_id">
                <div class="modal-row">
                    <label>Faculty ID <input type="text" name="faculty_id" id="ef_faculty_id" class="form-control" required></label>
                </div>
                <div class="modal-grid">
                    <label>First Name <input type="text" name="first_name" id="ef_first_name" class="form-control" required></label>
                    <label>Last Name <input type="text" name="last_name" id="ef_last_name" class="form-control" required></label>
                </div>
                <div class="modal-row">
                    <label>Department <input type="text" name="department" id="ef_department" class="form-control"></label>
                </div>
                <div class="modal-row">
                    <label>Email <input type="email" name="email" id="ef_email" class="form-control"></label>
                </div>
                <div class="modal-row">
                    <label>Phone Number <input type="tel" name="phone_number" id="ef_phone_number" class="form-control"></label>
                </div>
                <div class="modal-row">
                    <label>Address <input type="text" name="address" id="ef_address" class="form-control"></label>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="btn btn-add-faculty">💾 Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════
         CONFIRM DELETE DIALOG
    ════════════════════════════════════════════════ -->
    <div id="confirmDeleteOverlay" class="confirm-overlay">
        <div class="confirm-box">
            <h3>⚠️ Confirm Delete</h3>
            <p id="confirmDeleteMsg">Are you sure you want to delete this record?</p>
            <div class="confirm-actions">
                <button class="btn-cancel-confirm" onclick="closeConfirm()">Cancel</button>
                <form id="deleteForm" method="POST" style="display:inline;">
                    <input type="hidden" name="id" id="deleteId">
                    <button type="submit" class="btn-confirm-delete">Yes, Delete</button>
                </form>
            </div>
        </div>
    </div>

    <div id="toastNotification" class="toast-hidden">
        <span id="toastMessage"></span>
    </div>

    <script>
        // ── Modal helpers ──────────────────────────────────────────────────────
        function openModal(id)  { document.getElementById(id).classList.add('visible'); }
        function closeModal(id) { document.getElementById(id).classList.remove('visible'); }

        // ── Student modals ─────────────────────────────────────────────────────
        document.getElementById('openAddStudentModal').onclick  = () => openModal('addStudentModal');
        document.getElementById('closeAddStudentModal').onclick = () => closeModal('addStudentModal');
        document.getElementById('openViewStudentsModal').onclick  = () => openModal('viewStudentsModal');
        document.getElementById('closeViewStudentsModal').onclick = () => closeModal('viewStudentsModal');

        // ── Faculty modals ─────────────────────────────────────────────────────
        document.getElementById('openAddFacultyModal').onclick  = () => openModal('addFacultyModal');
        document.getElementById('closeAddFacultyModal').onclick = () => closeModal('addFacultyModal');
        document.getElementById('openViewFacultyModal').onclick  = () => openModal('viewFacultyModal');
        document.getElementById('closeViewFacultyModal').onclick = () => closeModal('viewFacultyModal');

        // Click outside to close
        window.addEventListener('click', (e) => {
            ['addStudentModal','viewStudentsModal','editStudentModal',
             'addFacultyModal','viewFacultyModal','editFacultyModal'].forEach(id => {
                if (e.target === document.getElementById(id)) closeModal(id);
            });
        });

        // ── Edit Student ───────────────────────────────────────────────────────
        function openEditStudent(s) {
            document.getElementById('edit_student_id').value   = s.student_id;
            document.getElementById('edit_first_name').value   = s.first_name  || '';
            document.getElementById('edit_middle_name').value  = s.middle_name || '';
            document.getElementById('edit_last_name').value    = s.last_name   || '';
            document.getElementById('edit_gender').value       = s.gender      || '';
            document.getElementById('edit_email').value        = s.email       || '';
            document.getElementById('edit_phone_number').value = s.phone_number|| '';
            document.getElementById('edit_address').value      = s.address     || '';
            document.getElementById('edit_section').value      = s.section     || '';
            document.getElementById('edit_course').value       = s.course      || '';
            document.getElementById('edit_year_level').value   = s.year_level  || '1st Year';
            document.getElementById('edit_status').value       = s.status      || 'enrolled';
            // Close view modal, open edit modal
            closeModal('viewStudentsModal');
            openModal('editStudentModal');
        }

        // ── Edit Faculty ───────────────────────────────────────────────────────
        function openEditFaculty(f) {
            document.getElementById('ef_id').value           = f.id;
            document.getElementById('ef_faculty_id').value   = f.faculty_id    || '';
            document.getElementById('ef_first_name').value   = f.first_name    || '';
            document.getElementById('ef_last_name').value    = f.last_name     || '';
            document.getElementById('ef_department').value   = f.department    || '';
            document.getElementById('ef_email').value        = f.email         || '';
            document.getElementById('ef_phone_number').value = f.phone_number  || '';
            document.getElementById('ef_address').value      = f.address       || '';
            closeModal('viewFacultyModal');
            openModal('editFacultyModal');
        }

        // ── Delete confirm ─────────────────────────────────────────────────────
        function confirmDelete(type, id, name) {
            const overlay = document.getElementById('confirmDeleteOverlay');
            document.getElementById('confirmDeleteMsg').textContent =
                `Are you sure you want to delete "${name}"? This action cannot be undone.`;
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteForm').action =
                type === 'student' ? 'delete_student.php' : 'delete_faculty.php';
            overlay.classList.add('visible');
        }

        function closeConfirm() {
            document.getElementById('confirmDeleteOverlay').classList.remove('visible');
        }

        // ── Search filters ─────────────────────────────────────────────────────
        function filterStudents() {
            const q = document.getElementById('studentSearch').value.toLowerCase();
            document.querySelectorAll('#studentTableBody tr').forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(q) ? '' : 'none';
            });
        }

        function filterFaculty() {
            const q = document.getElementById('facultySearch').value.toLowerCase();
            document.querySelectorAll('#facultyTableBody tr').forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(q) ? '' : 'none';
            });
        }

        // ── Toast ──────────────────────────────────────────────────────────────
        function showToast(message, type) {
            const toast = document.getElementById('toastNotification');
            document.getElementById('toastMessage').innerText = message;
            toast.className = 'toast-visible ' + (type === 'error' ? 'toast-error' : 'toast-success');
            setTimeout(() => { toast.className = 'toast-hidden'; }, 4000);
        }
    </script>
</body>
</html>