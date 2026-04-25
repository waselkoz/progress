<?php
session_start();
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'en';
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'] == 'ar' ? 'ar' : 'en';
    $_SESSION['lang'] = $lang;
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USTHB — Academic Portal</title>
    <meta name="description"
        content="The official USTHB student progress portal. Access grades, schedules, modules, and academic records.">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,600;1,600&family=Noto+Sans+Arabic:wght@400;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/index.css">
    <style>
        body {
            font-family: 'Inter',
                <?= $lang == 'ar' ? "'Noto Sans Arabic'," : "" ?>
                sans-serif;
        }

        .landing-nav {
            display: flex;
            padding: 0 60px;
            height: 72px;
            justify-content: space-between;
            align-items: center;
            background: #0A2B8E;
            position: sticky;
            top: 0;
            width: 100%;
            box-sizing: border-box;
            z-index: 1000;
            box-shadow: 0 2px 20px rgba(10, 43, 142, 0.35);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
        }

        .brand img {
            width: 42px;
            height: 42px;
            background: white;
            border-radius: 6px;
            padding: 4px;
            object-fit: contain;
        }

        .brand-text {
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .brand-name {
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            color: #fff;
            font-weight: 700;
            line-height: 1;
        }

        .brand-sub {
            font-size: 10px;
            color: rgba(255, 255, 255, 0.55);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 600;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .nav-links-horiz {
            display: flex;
            gap: 5px;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .nav-links-horiz a {
            color: rgba(255, 255, 255, 0.75);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            padding: 8px 14px;
            border-radius: 6px;
            transition: background 0.2s, color 0.2s;
        }

        .nav-links-horiz a:hover,
        .nav-links-horiz a.active {
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
        }

        .nav-lang {
            display: flex;
            gap: 4px;
            background: rgba(255, 255, 255, 0.1);
            padding: 4px;
            border-radius: 6px;
        }

        .lang-pill {
            font-size: 11px;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            padding: 4px 10px;
            border-radius: 4px;
            transition: background 0.2s, color 0.2s;
        }

        .lang-pill:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.15);
        }

        .lang-pill.active {
            background: #fff;
            color: #0A2B8E;
        }

        .nav-signin {
            background: #fff;
            color: #0A2B8E;
            font-weight: 700;
            font-size: 14px;
            padding: 9px 22px;
            border-radius: 6px;
            text-decoration: none;
            transition: background 0.2s, color 0.2s;
            white-space: nowrap;
        }

        .nav-signin:hover {
            background: #e8f0fe;
        }
    </style>
</head>

<body>

    <nav class="landing-nav">
        <a href="index.php" class="brand">
            <img src="img/USTHB.png" alt="USTHB Logo">
            <div class="brand-text">
                <span class="brand-name">USTHB</span>
                <span class="brand-sub">Progress Portal</span>
            </div>
        </a>

        <div class="nav-right">
            <ul class="nav-links-horiz">
                <li><a href="index.php" class="active"><?= $lang == 'ar' ? 'الرئيسية' : 'Home' ?></a></li>
                <li><a href="#about"><?= $lang == 'ar' ? 'عن النظام' : 'About' ?></a></li>
            </ul>

            <div class="nav-lang">
                <a href="?lang=en" class="lang-pill <?= $lang == 'en' ? 'active' : '' ?>">EN</a>
                <a href="?lang=ar" class="lang-pill <?= $lang == 'ar' ? 'active' : '' ?>">AR</a>
            </div>

            <a href="components/login.php" class="nav-signin">
                <i class="fas fa-sign-in-alt" style="margin-right: 6px;"></i><?= $lang == 'ar' ? 'دخول' : 'Sign In' ?>
            </a>
        </div>
    </nav>

    <main class="hero">
        <div class="hero-content">
            <h1 class="hero-title">USTHB<br><?= $lang == 'ar' ? 'البوابة الأكاديمية' : 'Academic Portal' ?></h1>
            <p class="hero-desc">
                <?= $lang == 'ar'
                    ? 'إدارة العلامات والمقاييس والأساتذة للعام الأكاديمي 2025/2026.'
                    : 'Access grades, modules, and teacher management for 2025/2026.' ?>
            </p>
            <div class="action-btns">
                <a href="components/login.php" class="primary-btn"><?= $lang == 'ar' ? 'تسجيل الدخول' : 'Sign In' ?></a>
            </div>
        </div>
    </main>

    <section class="about-section" id="about">
        <h2><?= $lang == 'ar' ? 'عن النظام' : 'About the System' ?></h2>
        <p>
            <?= $lang == 'ar'
                ? 'صُمِّم هذا النظام لرقمنة تتبع الطلاب في جامعة USTHB وتوفير إدارة مركزية للبيانات الأكاديمية.'
                : 'Designed to digitalize student tracking at USTHB with centralized academic data management.' ?>
        </p>

        <div class="about-container">
            <div class="about-card">
                <h3><i class="fas fa-user-graduate"
                        style="color:#0A2B8E; margin-right:8px;"></i><?= $lang == 'ar' ? 'الطالب' : 'Students' ?></h3>
                <p><?= $lang == 'ar'
                    ? 'مراقبة فورية للعلامات، الجداول، الحضور، وحساب تلقائي للمعدل بحسب المعاملات.'
                    : 'Real-time monitoring of grades, schedules, attendance, and automatic average calculation.' ?>
                </p>
            </div>
            <div class="about-card">
                <h3><i class="fas fa-chalkboard-teacher"
                        style="color:#0A2B8E; margin-right:8px;"></i><?= $lang == 'ar' ? 'الأستاذ' : 'Teachers' ?></h3>
                <p><?= $lang == 'ar'
                    ? 'واجهة بسيطة لإدخال العلامات وتتبع الحضور متصلة مباشرة بقاعدة البيانات.'
                    : 'Simple interface for secure grade entry and attendance tracking connected to the database.' ?>
                </p>
            </div>
            <div class="about-card">
                <h3><i class="fas fa-user-shield"
                        style="color:#0A2B8E; margin-right:8px;"></i><?= $lang == 'ar' ? 'الإدارة' : 'Administration' ?>
                </h3>
                <p><?= $lang == 'ar'
                    ? 'تحكم كامل في المقاييس، المستخدمين، الجداول الزمنية، وإعدادات النظام.'
                    : 'Full control over modules, users, timetables, and system settings.' ?></p>
            </div>
        </div>
    </section>

    <footer class="footer-info">
        &copy; 2026 USTHB — <?= $lang == 'ar' ? 'جميع الحقوق محفوظة.' : 'All rights reserved.' ?>
        <p>wassim selama/Aissaoui Imededdine/Khettab Imededdine/Temlali Oussama
        </p>
    </footer>

</body>

</html>