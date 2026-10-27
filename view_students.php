<?php
session_start();
require_once 'config/db.php';

// Fetch all students
$stmt = $pdo->query("SELECT * FROM students ORDER BY last_name ASC");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="style.css">
    <title>View Students</title>
</head>
<body class="admin-dashboard">
    <main class="main-content">
        <div class="search-container">
            <input type="text" id="studentSearch" placeholder="Search by name or ID..." onkeyup="filterTable()">
        </div>

        <table id="studentTable" class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Course</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                <tr>
                    <td><?= htmlspecialchars($student['student_id']) ?></td>
                    <td><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></td>
                    <td><?= htmlspecialchars($student['course']) ?></td>
                    <td><button onclick="showDetails(<?= htmlspecialchars(json_encode($student)) ?>)">View</button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>

    <div id="detailsModal" class="modal-overlay">
        <div class="modal-card">
            <button onclick="closeDetails()">Close</button>
            <div id="modalContent"></div>
        </div>
    </div>

<script>
    // Search Function
    function filterTable() {
        let input = document.getElementById("studentSearch").value.toLowerCase();
        let rows = document.querySelectorAll("#studentTable tbody tr");
        rows.forEach(row => {
            row.style.display = row.innerText.toLowerCase().includes(input) ? "" : "none";
        });
    }

    // Modal Details Function
    function showDetails(student) {
        document.getElementById('modalContent').innerHTML = `
            <h3>${student.first_name} ${student.last_name}</h3>
            <p>ID: ${student.student_id}</p>
            <p>Email: ${student.email}</p>
            <p>Course: ${student.course}</p>
        `;
        document.getElementById('detailsModal').classList.add('visible');
    }
    
    function closeDetails() { document.getElementById('detailsModal').classList.remove('visible'); }
</script>
</body>
</html>