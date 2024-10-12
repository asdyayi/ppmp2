<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ppmp_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function log_user_activity($user_id, $action, $email = null) {
    $log_file = 'user_activity.log';
    $timestamp = date("Y-m-d H:i:s");

    if ($user_id) {
        $log_entry = "User ID: $user_id | Action: $action | Timestamp: $timestamp" . PHP_EOL;
    } else {
        $log_entry = "Failed login attempt | Email: $email | Action: $action | Timestamp: $timestamp" . PHP_EOL;
    }

    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT id, is_admin, password FROM users WHERE email='$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            log_user_activity($user['id'], 'login');

            if ($user['is_admin']) {
                $_SESSION['is_admin'] = true;
                header('Location: admin.php');
            } else {
                header('Location: index.php');
            }
            exit();
        } else {
            log_user_activity(null, 'failed_login', $email);
            header('Location: login.php?error=1');
            exit();
        }
    } else {
        log_user_activity(null, 'failed_login', $email);
        header('Location: login.php?error=1');
        exit();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" type="x-icon" href="img/logo.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        body {
            background-image: url('img/bg.png');
            background-repeat: no-repeat;
            background-attachment: fixed;
            background-size: 100% 100%;
        }

        @media (max-width: 500px){
            body {
                background-image: url('img/pbg.png');
            }
        }

        .signup-link {
            display: block;
            text-align: center;
            margin-top: 160px;
            color: #153860;
            text-decoration: none;
        }

        .signup-link:hover {
            text-decoration: underline;
        }

        .error-message-container {
            position: absolute;
            top: 230px;
            width: 100%;
            text-align: center;
            display: block;
        }

        .error-message {
            color: red;
            font-weight: bold;
            margin: 0;
        }
    </style>

</head>
<body>
    <div class="login-box">
        <h1>LOG IN</h1>
        <img src="img/logo.png" class="logo">

        <form action="login.php" method="post">
        <div class="email-container">
            <i class="fas fa-envelope email-icon"></i>
            <input type="email" name="email" placeholder="Email" class="email-input" required>
        </div>
        <div class="password-container">
            <i class="fas fa-lock password-icon"></i>
            <input type="password" name="password" placeholder="Password" class="password-input" required>
        </div>
        
        <div class="error-message-container">
            <?php if (isset($_GET['error'])): ?>
                <p class="error-message">Invalid email or password. Please try again.</p>
            <?php endif; ?>
        </div>
        
        <a href="#" class="forgot-password-link">Forgot Password?</a>
        <button type="submit" class="round-button">Login</button>
    </form>

    <a href="register.php" class="signup-link">Don't have an account? Sign up here</a>

    </div>
</body>
</html>
