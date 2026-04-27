<?php // Wassim Selama / Aissaoui Imededdine / Khettab Imededdine / Temlali Oussama
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if(isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'] == 'ar' ? 'ar' : 'en';
}
$lang = $_SESSION['lang'] ?? 'en';

$translations = [
    'en' => [
        'overview' => 'Overview',
        'faculty' => 'Faculty',
        'modules' => 'Courses',
        'eval' => 'Grades',
        'attendance' => 'Attendance',
        'schedule' => 'Schedule',
        'exams' => 'Exams',
        'admin' => 'Admin',
        'users' => 'Users',
        'intake' => 'Import',
        'catalog' => 'Modules',
        'schedules' => 'Schedules',
        'timetable' => 'Timetable',
        'system' => 'System',
        'student' => 'Student',
        'results' => 'Results',
        'transcript' => 'Transcript',
        'placement' => 'Placement',
        'my_modules' => 'My Modules',
        'my_timetable' => 'Timetable',
        'my_exams' => 'Exams',
        'my_attendance' => 'Attendance',
        'profile' => 'Profile',
        'logout' => 'Logout',
        'network' => 'USTHB Network',
        'average' => 'Average',
        'auth' => 'Role',
        'registry' => 'Navigation',
        'welcome' => 'Welcome',
        'id_dossier' => 'Profile',
        'security' => 'Security',
        'institutional_data' => 'Academic Info',
        'full_name' => 'Full Name',
        'email_addr' => 'Email',
        'reg_num' => 'Number',
        'cycle' => 'Level',
        'update_pass' => 'Change Password',
        'commence' => 'Update',
        'encrypted' => 'Secure',
        'validated' => 'Pass',
        'failed' => 'Fail',
        'pending' => 'Pending',
        'admitted' => 'Admitted',
        'relegated' => 'Relegated',
        'credits' => 'Credits',
        'coefficient' => 'Coefficient',
        'semester' => 'Semester'
    ],
    'ar' => [
        'overview' => 'الرئيسية',
        'faculty' => 'الأساتذة',
        'modules' => 'المواد',
        'eval' => 'العلامات',
        'attendance' => 'الحضور',
        'schedule' => 'البرنامج',
        'exams' => 'الامتحانات',
        'admin' => 'الإدارة',
        'users' => 'المستخدمين',
        'intake' => 'استيراد',
        'catalog' => 'المقاييس',
        'schedules' => 'الجداول',
        'timetable' => 'التوقيت',
        'system' => 'النظام',
        'student' => 'الطالب',
        'results' => 'النتائج',
        'transcript' => 'كشف النقاط',
        'placement' => 'التوجيه',
        'my_modules' => 'موادي',
        'my_timetable' => 'توقيتي',
        'my_exams' => 'امتحاناتي',
        'my_attendance' => 'حضوري',
        'profile' => 'الملف',
        'logout' => 'خروج',
        'network' => 'شبكة USTHB',
        'average' => 'المعدل',
        'auth' => 'الصفة',
        'registry' => 'القائمة',
        'welcome' => 'مرحباً',
        'id_dossier' => 'الملف الشخصي',
        'security' => 'الأمان',
        'institutional_data' => 'معلومات أكاديمية',
        'full_name' => 'الاسم الكامل',
        'email_addr' => 'البريد الإلكتروني',
        'reg_num' => 'رقم التسجيل',
        'cycle' => 'المستوى',
        'update_pass' => 'تغيير كلمة المرور',
        'commence' => 'تحديث',
        'encrypted' => 'آمن',
        'validated' => 'ناجح',
        'failed' => 'راسب',
        'pending' => 'معلق',
        'admitted' => 'مقبول',
        'relegated' => 'مرفوض',
        'credits' => 'الأرصدة',
        'coefficient' => 'المعامل',
        'semester' => 'السداسي'
    ]
];

$t = $translations[$lang];

require_once '../config/database.php';
$pdo = getDBConnection();

$active_page = basename($_SERVER['PHP_SELF']);
$user_role = $_SESSION['user_role'] ?? 'student';

if ($user_role === 'student') {
    $stmt = $pdo->prepare("SELECT id, student_number, section_id, group_id FROM student WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student_info = $stmt->fetch();
    if($student_info) { $_SESSION['student_id'] = $student_info['id']; }
} elseif ($user_role === 'teacher') {
    $stmt = $pdo->prepare("SELECT id, employee_number, hire_date FROM teacher WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $teacher_info = $stmt->fetch();
    if($teacher_info) { $_SESSION['teacher_id'] = $teacher_info['id']; }
} elseif ($user_role === 'admin') {
    $stmt = $pdo->prepare("SELECT id, admin_level FROM admin WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $admin_info = $stmt->fetch();
    if($admin_info) { $_SESSION['admin_id'] = $admin_info['id']; }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <title>USTHB Progress</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Noto+Sans+Arabic:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <script src="../js/dashboard.js" defer></script>
    <style>
        body { font-family: 'Inter', <?= $lang == 'ar' ? "'Noto Sans Arabic'," : "" ?> sans-serif; direction: ltr; }
        .sidebar { background: #0f172a; width: 260px; height: 100vh; position: fixed; left: 0; top: 0; color: #94a3b8; z-index: 1000; border-right: 1px solid #1e293b; overflow-y: auto; }
        .sidebar-header { padding: 30px 25px; border-bottom: 1px solid #1e293b; text-align: center; }
        .active-nav a { background: #3b82f6; color: white !important; box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.4); }
        .nav-link { display: flex; align-items: center; gap: 12px; padding: 10px 15px; border-radius: 8px; color: inherit; text-decoration: none; font-size: 14px; font-weight: 500; transition: all 0.2s; }
        .nav-link:hover:not(.active-nav a) { background: rgba(255,255,255,0.05); color: white !important; }
        .lang-toggle { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #64748b; text-decoration: none; padding: 5px 10px; border-radius: 4px; border: 1px solid #e2e8f0; }
        .lang-toggle:hover { background: #f1f5f9; color: #0A2B8E; }
        .lang-active { background: #0A2B8E; color: white !important; border-color: #0A2B8E; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../img/USTHB.png" alt="USTHB Logo" style="width: 80px; margin-bottom: 15px;">
            <div style="color: white; font-weight: 700; font-size: 18px; letter-spacing: -0.5px;">USTHB <span style="color: #3b82f6;">Progress</span></div>
        </div>

        <div style="padding: 20px; border-bottom: 1px solid #1e293b;">
            <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 1.5px; color: #475569; font-weight: 800; margin-bottom: 15px;"><?= $t['registry'] ?></div>
            <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 5px;">
                <li class="<?= $active_page == 'dashboard.php' ? 'active-nav' : '' ?>">
                    <a href="dashboard.php" class="nav-link"><i class="fas fa-th-large" style="width: 18px;"></i> <?= $t['overview'] ?></a>
                </li>
            </ul>
        </div>

        <div style="padding: 20px;">
            <?php if($user_role == 'teacher'): ?>
                <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 1.5px; color: #475569; font-weight: 800; margin-bottom: 15px;"><?= $t['faculty'] ?></div>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 5px;">
                    <li class="<?= $active_page == 'teacher_classes.php' ? 'active-nav' : '' ?>"><a href="teacher_classes.php" class="nav-link"><i class="fas fa-laptop-code" style="width: 18px;"></i> <?= $t['modules'] ?></a></li>
                    <li class="<?= $active_page == 'teacher_grades.php' || $active_page == 'teacher_grade_input.php' ? 'active-nav' : '' ?>"><a href="teacher_grades.php" class="nav-link"><i class="fas fa-award" style="width: 18px;"></i> <?= $t['eval'] ?></a></li>
                    <li class="<?= $active_page == 'teacher_attendance.php' ? 'active-nav' : '' ?>"><a href="teacher_attendance.php" class="nav-link"><i class="fas fa-calendar-check" style="width: 18px;"></i> <?= $t['attendance'] ?></a></li>
                    <li class="<?= $active_page == 'timetable.php' ? 'active-nav' : '' ?>"><a href="timetable.php" class="nav-link"><i class="fas fa-clock" style="width: 18px;"></i> <?= $t['schedule'] ?></a></li>
                    <li class="<?= $active_page == 'exams.php' ? 'active-nav' : '' ?>"><a href="exams.php" class="nav-link"><i class="fas fa-file-signature" style="width: 18px;"></i> <?= $t['exams'] ?></a></li>
                </ul>
            <?php elseif($user_role == 'admin'): ?>
                <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 1.5px; color: #475569; font-weight: 800; margin-bottom: 15px;"><?= $t['admin'] ?></div>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 5px;">
                    <li class="<?= $active_page == 'admin_users.php' ? 'active-nav' : '' ?>"><a href="admin_users.php" class="nav-link"><i class="fas fa-users-cog" style="width: 18px;"></i> <?= $t['users'] ?></a></li>
                    <li class="<?= $active_page == 'admin_import_students.php' ? 'active-nav' : '' ?>"><a href="admin_import_students.php" class="nav-link"><i class="fas fa-file-import" style="width: 18px;"></i> <?= $t['intake'] ?></a></li>
                    <li class="<?= $active_page == 'admin_courses.php' ? 'active-nav' : '' ?>"><a href="admin_courses.php" class="nav-link"><i class="fas fa-book-open" style="width: 18px;"></i> <?= $t['catalog'] ?></a></li>
                    <li class="<?= $active_page == 'admin_schedules.php' ? 'active-nav' : '' ?>"><a href="admin_schedules.php" class="nav-link"><i class="fas fa-calendar-alt" style="width: 18px;"></i> <?= $t['schedules'] ?></a></li>
                    <li class="<?= $active_page == 'timetable.php' ? 'active-nav' : '' ?>"><a href="timetable.php" class="nav-link"><i class="fas fa-clock" style="width: 18px;"></i> <?= $t['timetable'] ?></a></li>
                    <li class="<?= $active_page == 'admin_system.php' ? 'active-nav' : '' ?>"><a href="admin_system.php" class="nav-link"><i class="fas fa-server" style="width: 18px;"></i> <?= $t['system'] ?></a></li>
                </ul>
            <?php else: ?>
                <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 1.5px; color: #475569; font-weight: 800; margin-bottom: 15px;"><?= $t['student'] ?></div>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 5px;">
                    <li class="<?= $active_page == 'exam_grades.php' ? 'active-nav' : '' ?>"><a href="exam_grades.php" class="nav-link"><i class="fas fa-chart-line" style="width: 18px;"></i> <?= $t['results'] ?></a></li>
                    <li class="<?= $active_page == 'academic_transcript.php' ? 'active-nav' : '' ?>"><a href="academic_transcript.php" class="nav-link"><i class="fas fa-file-invoice" style="width: 18px;"></i> <?= $t['transcript'] ?></a></li>
                    <li class="<?= $active_page == 'enrolments.php' ? 'active-nav' : '' ?>"><a href="enrolments.php" class="nav-link"><i class="fas fa-graduation-cap" style="width: 18px;"></i> <?= $t['placement'] ?></a></li>
                    <li class="<?= $active_page == 'my_modules.php' ? 'active-nav' : '' ?>"><a href="my_modules.php" class="nav-link"><i class="fas fa-book-reader" style="width: 18px;"></i> <?= $t['my_modules'] ?></a></li>
                    <li class="<?= $active_page == 'timetable.php' ? 'active-nav' : '' ?>"><a href="timetable.php" class="nav-link"><i class="fas fa-clock" style="width: 18px;"></i> <?= $t['my_timetable'] ?></a></li>
                    <li class="<?= $active_page == 'exams.php' ? 'active-nav' : '' ?>"><a href="exams.php" class="nav-link"><i class="fas fa-calendar-day" style="width: 18px;"></i> <?= $t['my_exams'] ?></a></li>
                    <li class="<?= $active_page == 'attendance.php' ? 'active-nav' : '' ?>"><a href="attendance.php" class="nav-link"><i class="fas fa-user-check" style="width: 18px;"></i> <?= $t['my_attendance'] ?></a></li>
                </ul>
            <?php endif; ?>
            
            <div style="margin-top: 30px; border-top: 1px solid #1e293b; padding-top: 20px;">
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 5px;">
                    <li class="<?= $active_page == 'profile.php' ? 'active-nav' : '' ?>"><a href="profile.php" class="nav-link"><i class="fas fa-user-circle" style="width: 18px;"></i> <?= $t['profile'] ?></a></li>
                    <li><a href="logout.php" class="nav-link" style="color: #f43f5e; font-weight: 700; margin-top: 10px;"><i class="fas fa-sign-out-alt" style="width: 18px;"></i> <?= $t['logout'] ?></a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="main-content" style="margin-left: 260px; min-height: 100vh; background: #f8fafc;">
        <div class="header" style="background: white; border-bottom: 1px solid #e2e8f0; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 999;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <div style="background: #f1f5f9; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #64748b; font-weight: 700;">
                    <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
                </div>
                <div>
                    <div style="font-size: 14px; font-weight: 700; color: #1e293b;"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
                    <div style="font-size: 11px; color: #94a3b8; text-transform: uppercase; font-weight: 800;"><?= strtoupper($_SESSION['user_role']) ?> <?= $t['auth'] ?></div>
                </div>
            </div>
            <div style="display: flex; gap: 20px; align-items: center;">
                <div style="display: flex; gap: 5px; background: #f8fafc; padding: 5px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <a href="?lang=en" class="lang-toggle <?= $lang == 'en' ? 'lang-active' : '' ?>">EN</a>
                    <a href="?lang=ar" class="lang-toggle <?= $lang == 'ar' ? 'lang-active' : '' ?>">AR</a>
                </div>
                <div style="width: 1px; height: 20px; background: #e2e8f0;"></div>
                <div style="font-size: 12px; color: #94a3b8; font-weight: 500;"><?= $t['network'] ?></div>
            </div>
        </div>
        <div style="padding: 40px;">
