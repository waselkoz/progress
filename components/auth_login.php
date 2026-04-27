<?php // Wassim Selama / Aissaoui Imededdine / Khettab Imededdine / Temlali Oussama
ini_set('session.gc_maxlifetime', 2592000);
session_set_cookie_params(2592000);
session_start();

$host = "localhost";
$user = "root";
$password = "";
$database = "student_portal";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$email = $_POST['email'];
$password_input = $_POST['password'];

$sql = "SELECT * FROM users WHERE email = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 1) {
    $user_data = mysqli_fetch_assoc($result);

    if (password_verify($password_input, $user_data['password'])) {
        if ($user_data['is_active'] != 1) {
            header("Location: login.php?error=unverified");
            exit();
        }
        $_SESSION['user_id'] = $user_data['id'];
        $_SESSION['user_name'] = $user_data['name'];
        $_SESSION['user_role'] = $user_data['role'];
        $_SESSION['user_email'] = $user_data['email'];

        header("Location: dashboard.php");
        exit();
    } else {
        header("Location: login.php?error=1");
        exit();
    }
} else {
    header("Location: login.php?error=1");
    exit();
}

$conn->close();
?>