<?php
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../config/database.php';
$pdo = getDBConnection();

$active_page = basename($_SERVER['PHP_SELF']);
$user_role = $_SESSION['user_role'] ?? 'student';

if ($user_role === 'student') {
    $stmt = $pdo->prepare("SELECT id, student_number, section_id, group_id FROM student WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student_info = $stmt->fetch();
    if($student_info) {
        $_SESSION['student_id'] = $student_info['id'];
    }
} elseif ($user_role === 'teacher') {
    $stmt = $pdo->prepare("SELECT id, employee_number, hire_date FROM teacher WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $teacher_info = $stmt->fetch();
    if($teacher_info) {
        $_SESSION['teacher_id'] = $teacher_info['id'];
    }
} elseif ($user_role === 'admin') {
    $stmt = $pdo->prepare("SELECT id, admin_level FROM admin WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $admin_info = $stmt->fetch();
    if($admin_info) {
        $_SESSION['admin_id'] = $admin_info['id'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,600;1,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/dashboard.css">
    <script src="../js/dashboard.js"></script>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../img/USTHB.png" alt="Logo">
            <h2>Student Portal</h2>
        </div>
        <ul class="nav-links">
            <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'teacher'): ?>
                <li class="<?= $active_page == 'dashboard.php' ? 'active' : '' ?>"><a href="dashboard.php">Dashboard</a></li>
                <li class="<?= $active_page == 'teacher_classes.php' ? 'active' : '' ?>"><a href="teacher_classes.php">My Modules</a></li>
                <li class="<?= $active_page == 'teacher_grades.php' ? 'active' : '' ?>"><a href="teacher_grades.php">Grade Input</a></li>
                <li class="<?= $active_page == 'teacher_attendance.php' ? 'active' : '' ?>"><a href="teacher_attendance.php">Attendance</a></li>
                <li class="<?= $active_page == 'timetable.php' ? 'active' : '' ?>"><a href="timetable.php">Weekly Schedule</a></li>
                <li class="<?= $active_page == 'exams.php' ? 'active' : '' ?>"><a href="exams.php">Exam Dates</a></li>
                <li class="<?= $active_page == 'profile.php' ? 'active' : '' ?>"><a href="profile.php">My Profile</a></li>
            <?php elseif(isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                <li class="<?= $active_page == 'dashboard.php' ? 'active' : '' ?>"><a href="dashboard.php">Dashboard</a></li>
                <li class="<?= $active_page == 'admin_users.php' ? 'active' : '' ?>"><a href="admin_users.php">User Management</a></li>
                <li class="<?= $active_page == 'admin_courses.php' ? 'active' : '' ?>"><a href="admin_courses.php">Module Management</a></li>
                <li class="<?= $active_page == 'admin_schedules.php' ? 'active' : '' ?>"><a href="admin_schedules.php">Schedules Management</a></li>
                <li class="<?= $active_page == 'timetable.php' ? 'active' : '' ?>"><a href="timetable.php">View Live Schedule</a></li>
                <li class="<?= $active_page == 'admin_notes.php' ? 'active' : '' ?>"><a href="admin_notes.php">Grade Management</a></li>
                <li class="<?= $active_page == 'admin_system.php' ? 'active' : '' ?>"><a href="admin_system.php">System Settings</a></li>
                <li class="<?= $active_page == 'profile.php' ? 'active' : '' ?>"><a href="profile.php">Profile</a></li>
            <?php else: ?>
                <li class="<?= $active_page == 'dashboard.php' ? 'active' : '' ?>"><a href="dashboard.php">Dashboard</a></li>
                <li class="<?= $active_page == 'exam_grades.php' ? 'active' : '' ?>"><a href="exam_grades.php">My Grades</a></li>
                <li class="<?= $active_page == 'academic_transcript.php' ? 'active' : '' ?>"><a href="academic_transcript.php">Academic Transcript</a></li>
                <li class="<?= $active_page == 'enrolments.php' ? 'active' : '' ?>"><a href="enrolments.php">My Placement</a></li>
                <li class="<?= $active_page == 'my_modules.php' ? 'active' : '' ?>"><a href="my_modules.php">Module Catalog</a></li>
                <li class="<?= $active_page == 'timetable.php' ? 'active' : '' ?>"><a href="timetable.php">Weekly Timetable</a></li>
                <li class="<?= $active_page == 'exams.php' ? 'active' : '' ?>"><a href="exams.php">Exam Schedule</a></li>
                <li class="<?= $active_page == 'attendance.php' ? 'active' : '' ?>"><a href="attendance.php">Attendance</a></li>
                <li class="<?= $active_page == 'profile.php' ? 'active' : '' ?>"><a href="profile.php">My Profile</a></li>
            <?php endif; ?>
        </ul>
    </div>
    <div class="main-content">
        <div class="header">
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
            <a href="logout.php" class="logout-btn">Log out</a>
        </div>
