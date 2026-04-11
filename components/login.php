<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/login.css">
</head>
<body>
    <div class="login-card">
        <div class="login-sidebar">
            <img src="../img/USTHB.png" alt="Logo">
            <h2>Student Portal</h2>
            <p style="color: #718096; text-align: center; font-size: 14px;">University of Science and Technology Houari Boumediene<br>Academic Management</p>
        </div>
        <div class="login-form">
            <h1>Welcome Back</h1>
            <p>Please log in to access your account.</p>
            <?php if (isset($_GET['error'])): ?>
                <div class="error-msg">Incorrect email or password!</div>
            <?php endif; ?>
            <form action="auth_login.php" method="POST">
                <input type="email" name="email" class="form-input" placeholder="Email Address" required>
                <input type="password" name="password" class="form-input" placeholder="Password" required>
                <button type="submit" class="submit-btn">Sign In</button>
            </form>
            <a href="register.php" class="nav-link">Don't have an account? Register Now</a>
            <a href="../index.php" class="nav-link" style="margin-top: 10px;">&larr; Back to Home</a>
        </div>
    </div>
</body>
</html>