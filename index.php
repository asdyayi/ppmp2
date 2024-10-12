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

// Initialize error message variable
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Prepare SQL to prevent SQL injection
    $stmt = $conn->prepare("SELECT id, is_admin, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Verify the password
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            log_user_activity($user['id'], 'login');

            // Redirect based on user role
            if ($user['is_admin']) {
                $_SESSION['is_admin'] = true;
                header('Location: admin.php');
            } else {
                header('Location: dashboard.php');
            }
            exit();
        } else {
            // Log and show error for incorrect password
            log_user_activity(null, 'failed_login', $email);
            $error_message = "Invalid email or password. Please try again.";
        }
    } else {
        // Log and show error for incorrect email
        log_user_activity(null, 'failed_login', $email);
        $error_message = "Invalid email or password. Please try again.";
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

        <form action="index.php" method="post">
            <div class="email-container">
                <i class="fas fa-envelope email-icon"></i>
                <input type="email" name="email" placeholder="Email" class="email-input" required>
            </div>
            <div class="password-container">
                <i class="fas fa-lock password-icon"></i>
                <input type="password" name="password" placeholder="Password" class="password-input" required>
            </div>
            
            <div class="error-message-container">
                <?php if (!empty($error_message)): ?>
                    <p class="error-message"><?php echo $error_message; ?></p>
                <?php endif; ?>
            </div>
            
            <a href="#" class="forgot-password-link">Forgot Password?</a>
            <button type="submit" class="round-button">Login</button>
        </form>

        <a href="register.php" class="signup-link">Don't have an account? Sign up here</a>

    </div>
</body>
</html>
