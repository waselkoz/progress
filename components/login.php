<?php
session_start();
$lang = $_SESSION['lang'] ?? 'en';

$t = [
    'en' => [
        'title'       => 'Login — USTHB Progress',
        'portal'      => 'Student Portal',
        'inst'        => 'University of Science and Technology Houari Boumediene',
        'mgmt'        => 'Academic Management',
        'welcome'     => 'Welcome Back',
        'instruction' => 'Sign in to access your account.',
        'error'       => 'Incorrect email or password.',
        'email'       => 'Email',
        'password'    => 'Password',
        'signin'      => 'Sign In',
        'back'        => 'Back to Home'
    ],
    'ar' => [
        'title'       => 'تسجيل الدخول — USTHB Progress',
        'portal'      => 'بوابة الطالب',
        'inst'        => 'جامعة العلوم والتكنولوجيا هواري بومدين',
        'mgmt'        => 'التسيير الأكاديمي',
        'welcome'     => 'مرحباً بعودتك',
        'instruction' => 'سجّل دخولك للوصول إلى حسابك.',
        'error'       => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.',
        'email'       => 'البريد الإلكتروني',
        'password'    => 'كلمة المرور',
        'signin'      => 'دخول',
        'back'        => 'الرئيسية'
    ]
][$lang];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['title'] ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Noto+Sans+Arabic:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/login.css">
    <style>
        body { font-family: 'Inter', <?= $lang == 'ar' ? "'Noto Sans Arabic'," : "" ?> sans-serif; direction: ltr; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-sidebar">
            <img src="../img/USTHB.png" alt="Logo">
            <h2><?= $t['portal'] ?></h2>
            <p style="color: #718096; text-align: center; font-size: 14px;"><?= $t['inst'] ?><br><?= $t['mgmt'] ?></p>
        </div>
        <div class="login-form">
            <h1><?= $t['welcome'] ?></h1>
            <p><?= $t['instruction'] ?></p>
            <?php if (isset($_GET['error'])): ?>
                <div class="error-msg"><?= $t['error'] ?></div>
            <?php endif; ?>
            <form action="auth_login.php" method="POST">
                <input type="email" name="email" class="form-input" placeholder="<?= $t['email'] ?>" required>
                <input type="password" name="password" class="form-input" placeholder="<?= $t['password'] ?>" required>
                <button type="submit" class="submit-btn"><?= $t['signin'] ?></button>
            </form>
            <a href="../index.php" class="nav-link" style="margin-top: 10px;">&larr; <?= $t['back'] ?></a>
        </div>
    </div>
</body>
</html>