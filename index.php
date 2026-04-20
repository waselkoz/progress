<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USTHB - Student Portal</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,600;1,600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/index.css">
</head>

<body>

    <nav class="landing-nav">
        <div class="brand">
            <img src="img/USTHB.png" alt="Logo">
            <h2>USTHB</h2>
        </div>
        <ul class="nav-links-horiz">
            <li><a href="index.php">Home</a></li>
            <li><a href="#about">About</a></li>
            <li><a href="components/login.php">Login</a></li>
            <li><a href="components/register.php">Register</a></li>
        </ul>
    </nav>

    <main class="hero">
        <div class="hero-content">
            <h1 class="hero-title">USTHB <br> Academic Portal</h1>
            <p class="hero-desc">
                <br>
                Access grades, modules, and teacher management for 2025/2026.
            </p>
            <div class="action-btns">
                <a href="components/login.php" class="primary-btn">Sign In</a>
                <a href="components/register.php" class="secondary-btn">Create Account</a>
            </div>
        </div>
    </main>

    <section class="about-section" id="about">
        <h2 style="font-size: 32px; color: #0A2B8E; margin-bottom: 15px;">About the System</h2>
        <p
            style="color: #666; font-size: 16px; margin-bottom: 50px; max-width: 600px; margin-left: auto; margin-right: auto;">
            This system was designed to digitalize student tracking at USTHB. It provides fluid and centralized
            management of academic data.
        </p>

        <div class="about-container">
            <div class="about-card">
                <h3>Student Tracking</h3>
                <p>Real-time monitoring of students, resits, schedules, attendance, and automatic GPA calculation based
                    on coefficients.</p>
            </div>
            <div class="about-card">
                <h3>Teacher Space</h3>
                <p>Intuitive interface for secure grade entry and attendance tracking, connected directly to the central
                    database.</p>
            </div>
            <div class="about-card">
                <h3>Administration</h3>
                <p>Powerful operating mode giving scolary department absolute control over modules, courses, and users.
                </p>
            </div>
        </div>
    </section>

    <footer class="footer-info">
        &copy; 2026 USTHB C. LAACHEMI. All rights reserved 38.
    </footer>

</body>

</html>