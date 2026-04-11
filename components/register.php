<?php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/email_service.php';

if(session_status() === PHP_SESSION_NONE) session_start();

$error = '';
$success = '';

if (isset($_GET['action']) && $_GET['action'] == 'reset') {
    unset($_SESSION['pending_user_id']);
    header("Location: register.php");
    exit();
}

$step = isset($_SESSION['pending_user_id']) ? 2 : 1;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_btn'])) {
    $name = $_POST['name'];
    $personal_email = $_POST['email']; 
    $pass = $_POST['password'];
    $confirm = $_POST['confirm'];
    $role = $_POST['role'];

    if (!preg_match('/@gmail\.com$/', $personal_email)) {
        $error = "Error: Only @gmail.com addresses are allowed.";
    } elseif ($pass !== $confirm) {
        $error = "Warning: Passwords do not match!";
    } else {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id, is_active FROM users WHERE personal_email = ?");
        $stmt->execute([$personal_email]);
        $existing = $stmt->fetch();

        if ($existing && $existing['is_active'] == 1) {
            $error = "Email address is already in use.";
        } else {
            $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            
            if ($existing) {
                $uid = $existing['id'];
                $pdo->prepare("UPDATE users SET verification_code = ? WHERE id = ?")->execute([$code, $uid]);
                $_SESSION['pending_user_id'] = $uid;
            } else {
                $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, personal_email, email, password, role, verification_code, is_active) VALUES (?, ?, ?, ?, ?, ?, 0)");
                $stmt->execute([$name, $personal_email, $personal_email, $hashed_pass, $role, $code]);
                $new_user_id = $pdo->lastInsertId();
                $_SESSION['pending_user_id'] = $new_user_id;

                if ($role == 'student') {
                    $birth_date = $_POST['birth_date'] ?? null;
                    $enroll_year = $_POST['enroll_year'] ?? date('Y');
                    $stmtCount = $pdo->prepare("SELECT MAX(SUBSTRING(student_number, 8)) FROM student WHERE enrollment_year = ?");
                    $stmtCount->execute([$enroll_year]);
                    $maxVal = (int)$stmtCount->fetchColumn();
                    $student_number = 'STU' . $enroll_year . str_pad($maxVal + 1, 4, '0', STR_PAD_LEFT);
                    $stmt = $pdo->prepare("INSERT INTO student (user_id, section_id, group_id, student_number, birth_date, enrollment_year) VALUES (?, 1, 1, ?, ?, ?)");
                    $stmt->execute([$new_user_id, $student_number, $birth_date, $enroll_year]);
                } elseif ($role == 'teacher') {
                    $stmtCount = $pdo->prepare("SELECT MAX(SUBSTRING(employee_number, 8)) FROM teacher WHERE employee_number LIKE ?");
                    $stmtCount->execute(['EMP' . date('Y') . '%']);
                    $maxVal = (int)$stmtCount->fetchColumn();
                    $employee_number = 'EMP' . date('Y') . str_pad($maxVal + 1, 4, '0', STR_PAD_LEFT);
                    $pdo->prepare("INSERT INTO teacher (user_id, employee_number, hire_date) VALUES (?, ?, ?)")
                        ->execute([$new_user_id, $employee_number, date('Y-m-d')]);
                }
            }

            EmailService::sendVerificationCode($personal_email, $code);
            $step = 2;
            $success = "Verification code sent to $personal_email!";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_btn'])) {
    $code_input = $_POST['code'];
    $uid = $_SESSION['pending_user_id'] ?? null;

    if($uid) {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND verification_code = ?");
        $stmt->execute([$uid, $code_input]);
        if ($stmt->fetch()) {
            $pdo->prepare("UPDATE users SET is_active = 2, verification_code = NULL WHERE id = ?")->execute([$uid]);
            unset($_SESSION['pending_user_id']);
            $success = "Email verified! Your account request has been sent to the Administration. You will receive your official USTHB email shortly.";
            $step = 3; 
        } else {
            $error = "Invalid verification code. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/login.css">
</head>
<body>
    <div class="login-card">
        <div class="login-sidebar">
            <img src="../img/USTHB.png" alt="Logo">
            <h2>Welcome!</h2>
            <p style="color: #718096; text-align: center; font-size: 14px;">Verify your identity to complete registration.</p>
        </div>
        <div class="login-form">
            <?php if ($step == 1): ?>
                <h1>Create Account</h1>
                <p style="color:#718096; margin-bottom: 25px;">Fill in your details to get started.</p>
                <?php if ($error): ?><div class="error-msg"><?= $error ?></div><?php endif; ?>
                <form method="POST">
                    <input type="text" name="name" class="form-input" placeholder="Full Name" required>
                    <input type="email" name="email" class="form-input" placeholder="Email (@gmail.com)" required pattern=".*@gmail\.com$">
                    <div style="display: flex; gap: 15px;">
                        <input type="password" name="password" class="form-input" placeholder="Password" required>
                        <input type="password" name="confirm" class="form-input" placeholder="Confirm" required>
                    </div>
                    <select name="role" id="role-select" class="form-input" required onchange="toggleExtraFields(this.value)">
                        <option value="student">Student</option>
                        <option value="teacher">Teacher</option>
                    </select>
                    <div id="student-fields">
                        <div style="display: flex; gap: 15px;">
                            <div style="flex:1;">
                                <label style="font-size: 13px; color: #718096;">Birth Date</label>
                                <input type="date" name="birth_date" class="form-input" required>
                            </div>
                            <div style="flex:1;">
                                <label style="font-size: 13px; color: #718096;">Enrollment Year</label>
                                <input type="number" name="enroll_year" class="form-input" value="<?= date('Y') ?>" min="2000" max="2099" required>
                            </div>
                        </div>
                    </div>
                    <script>
                        function toggleExtraFields(role) {
                            const studentFields = document.getElementById('student-fields');
                            const inputs = studentFields.querySelectorAll('input');
                            
                            if (role === 'student') {
                                studentFields.style.display = 'block';
                                inputs.forEach(i => i.setAttribute('required', ''));
                            } else {
                                studentFields.style.display = 'none';
                                inputs.forEach(i => i.removeAttribute('required'));
                            }
                        }
                    </script>
                    <button type="submit" name="register_btn" class="submit-btn" style="margin-top: 10px;">Send Verification Code</button>
                </form>
            <?php elseif ($step == 2): ?>
                <h1>Verify Email</h1>
                <p style="color:#718096; margin-bottom: 25px;">Enter the 6-digit code sent to your email.</p>
                <?php if ($error): ?><div class="error-msg"><?= $error ?></div><?php endif; ?>
                <?php if ($success): ?><div class="success-msg"><?= $success ?></div><?php endif; ?>
                <form method="POST">
                    <input type="text" name="code" class="form-input" placeholder="6-digit Code" maxlength="6" required style="text-align: center; font-size: 24px; letter-spacing: 15px;">
                    <button type="submit" name="verify_btn" class="submit-btn">Verify Account</button>
                </form>
                <div style="text-align: center; margin-top: 20px;">
                    <p style="font-size: 13px; color: #718096;">Didn't receive the email? <br> 
                    <a href="?action=reset" style="color: #4a90e2; text-decoration: none; font-weight: 600;">Restart registration with a different email</a></p>
                </div>
            <?php else: ?>
                <h1>Success!</h1>
                <div class="success-msg"><?= $success ?></div>
                <a href="login.php" class="submit-btn" style="display: block; text-align: center; text-decoration: none;">Continue to Login</a>
            <?php endif; ?>

            <?php if ($step != 3): ?>
                <a href="login.php" class="nav-link">Already have an account? Sign In</a>
                <a href="../index.php" class="nav-link" style="margin-top: 5px;">Back to Home</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>