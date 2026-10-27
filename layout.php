<header class="navbar p-3 shadow-sm bg-white">
    <div class="d-flex align-items-center">
        <img src="logo.png" style="width: 50px;">
        <h5 class="ms-3 fw-bold">University of Eastern Philippines</h5>
        <h2 class="ms-auto fw-bold">UEP ADMINISTRATOR</h2>
    </div>
</header>

<div class="d-flex">
    <aside class="sidebar p-4" style="width: 280px;">
        <nav class="nav flex-column mb-5">
            <a href="admin_dashboard.php" class="nav-link <?= ($page == 'dashboard') ? 'active bg-primary text-white' : '' ?>">Dashboard</a>
            <a href="student_faculty.php" class="nav-link <?= ($page == 'student_faculty') ? 'active bg-primary text-white' : '' ?>">Student / Faculty</a>
        </nav>
        
        <div class="user-block bg-light p-3 rounded">
            <p class="mb-1">welcome!</p>
            <strong><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></strong>
            <a href="logout.php" class="btn btn-warning w-100 mt-2 rounded-pill">LOGOUT</a>
        </div>
    </aside>

    <main class="flex-grow-1 p-5">
        <?php echo $content; // This is where the middle content will swap ?>
    </main>
</div>