<?php
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

// Fetch request types
$request_types_result = $conn->query("SELECT id, name FROM request_types");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $department = $_POST['department'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        die("Passwords do not match.");
    }
    
 
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $profileImage = $_FILES['profile_image']['name'];
        $profileImageTmp = $_FILES['profile_image']['tmp_name'];
        $uploadDir = 'uploads/';
        $profileImagePath = $uploadDir . basename($profileImage);

        if (move_uploaded_file($profileImageTmp, $profileImagePath)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO departments (name) VALUES ('$department') ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)";
            $conn->query($sql);
            $department_id = $conn->insert_id;

            $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, email, department_id, password, profile_image) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $firstname, $lastname, $email, $department_id, $hashed_password, $profileImage);
            if ($stmt->execute()) {
                header('Location: login.php');
                exit();
            } else {
                die("Error: " . $stmt->error);
            }
            $stmt->close();
        } else {
            die("Failed to move uploaded file.");
        }
    } else {
        die("Failed to upload profile image.");
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Font Face */
        @font-face {
            font-family: "Sans";
            src: url(CanvaSans.ttf);
        }

        @font-face {
            font-family: "bold";
            src: url(bold.ttf);
        }

        body {
            background-image: url('img/bg.png');
            background-repeat: no-repeat;
            background-attachment: fixed;
            background-size: 100% 100%;
        }
        /* Basic Reset */
        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Sans';
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-box {
            position: relative;
            width: 500px;
            height: 650px;
            background-color: #C6D3E3;
            border: 1px solid #ccc;
            padding: 20px;
            box-sizing: border-box;
            text-align: center;
            top:70px;
        }

        h1 {
            color: #153860;
            margin: 5px 0;
            font-family: 'bold';
            position: relative;
            z-index: 1;
            font-size: 30px;
        }

        .logo {
            position: absolute;
            width: 70px;
            top: -10px;
            left: 62%;
            z-index: 2;
        }

        .input-container {
            display: flex;
            align-items: center;
            margin-top: 0px;
            position: relative;
        }

        .input-container i {
            font-size: 20px;
            color: #000000;
            margin-right: 25px;
            margin-left: 60px;
        }  

        .input-container input,
        .input-container select {
            border: none;
            outline: none;
            background: transparent;
            font-family: 'Sans';
            font-size: 20px;
            color: #000000;
            width: calc(100% - 40px);
            padding: 1px;
        }

        .input-container input::placeholder {
            color: #000000;
        }

        .input-container::after {
            content: '';
            position: absolute;
            bottom: -10px;
            right: 10px;
            left: 50px;
            width: 80%;
            height: 2px;
            background-color: #153860;
        }

        button {
            position: absolute;
            bottom: 40px;
            right: 45px;
            width: 170px;
            height: 50px;
            border-radius: 25px;
            background-color: #005990;
            color: #fff;
            font-size: 25px;
            cursor: pointer;
            font-family: 'bold';
            border: none;
        }

        button:hover {
            background-color: #0f2c52;
        }

        i{
            margin-top:30px;    
        }
        input{
            margin-top:30px;
        }

        .text{
            margin-top:-10px;
        }
</style>
</head>
<body>
    <div class="login-box">
        <h1>SIGN UP <img src="img/logo.png" alt="Logo" class="logo"></h1>
        <form action="register.php" method="post" enctype="multipart/form-data">
            <div class = "text">
                <div class="input-container">
                    <i class="fas fa-user"></i>
                    <input type="text" name="firstname" placeholder="First Name" required>
                </div>
                <div class="input-container">
                    <i class="fas fa-user"></i>
                    <input type="text" name="lastname" placeholder="Last Name" required>
                </div>
                <div class="input-container">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="Email" required>
                </div>
                <div class="input-container">
                    <i class="fas fa-building"></i>
                    <input type="text" name="department" placeholder="Department" required>
                </div>
                <div class="input-container">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <div class="input-container">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                </div>
                <div class="input-container">
                    <i class="fas fa-image"></i>
                    <input type="file" name="profile_image" accept="image/*" required>
                </div>
            </div>
            <button type="submit">Register</button>
        </form>
    </div>
</body>
</html>
