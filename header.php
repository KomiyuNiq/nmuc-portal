<?php
// header.php - Shared Navigation Sidebar & Top Bar
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<div class="header">
    <h1>NMUC <span>STUDENT PORTAL</span></h1>
    <div>
        Student: <strong><?= htmlspecialchars($user['full_name']) ?></strong> | 
        <a href="logout.php" style="color: var(--nmuc-gold); text-decoration: none;">Logout</a>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    </div>
</div>

<div class="portal-wrapper">
    <!-- SIDEBAR NAVIGATION -->
    <div class="sidebar">
        <ul class="sidebar-menu">
    <li>
        <a href="student_dashboard.php" class="<?= $currentPage === 'student_dashboard.php' ? 'active' : '' ?>">
            <i class="fa-regular fa-file-lines nav-icon"></i> Application Forms
        </a>
    </li>
    <li>
        <a href="student_notifications.php" class="<?= $currentPage === 'student_notifications.php' ? 'active' : '' ?>">
            <i class="fa-regular fa-bell nav-icon"></i> Notifications & Status
        </a>
    </li>
</ul>
    </div>
    
    <div class="main-content">