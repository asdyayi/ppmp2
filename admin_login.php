<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ppmp_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the user is already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
    header('Location: admin.php'); // Redirect if already logged in
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $admin_password = $_POST['password'];

    // Use a prepared statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ? AND is_admin = TRUE");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($admin_password, $user['password'])) {
            session_regenerate_id();
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['is_admin'] = true;

            header('Location: admin.php'); // Redirect to admin dashboard
            exit();
        } else {
            $error_message = "Invalid login credentials";
        }
    } else {
        $error_message = "Invalid login credentials";
    }

    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" type="x-icon" href="img/logo.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        body {
            background-image: url('img/bg.png');
            background-repeat: no-repeat;
            background-attachment: fixed;
            background-size: 100% 100%;
        }
        @media (max-width: 500px) {
            body {
                background-image: url('img/pbg.png');
            }
        }
        .login-box {
            text-align: center;
            margin-top: 100px;
        }
        .error-message {
            color: red;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>Admin Login</h1>
        <form action="" method="post">
            <div class="email-container">
                <i class="fas fa-envelope email-icon"></i>
                <input type="email" name="email" placeholder="Email" required>
            </div>
            <div class="password-container">
                <i class="fas fa-lock password-icon"></i>
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <button type="submit" class="round-button">Login</button>
        </form>

        <?php if (isset($error_message)): ?>
            <p class="error-message"><?php echo $error_message; ?></p>
        <?php endif; ?>
    </div>
</body>
</html>
