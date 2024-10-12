<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="shortcut icon" type="x-icon" href="img/logo.png">
<link rel="stylesheet" href="style2.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<title>White Header with Left-Side Nav</title>
<style>
  .content {
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
    padding-top: 20px;
    width: 80%;
    text-align: center;
  }
  .pending {
    width: 100%;
    padding: 20px;
    text-align: center;
  }
  h3 {
    color: #153860;
    font-size: 28px;
    font-family: 'bold';
  }
  .vertical-line {
    width: 2px;
    height: 70px;
    background-color: #000000;
    margin: 0 10px;
  }
  .notification-icon {
    font-size: 35px;
    cursor: pointer;
  }
  .image {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-top: 20px;
  }
  .profile-info img {
    border-radius: 50%;
    width: 200px;
    height: 200px;
    margin-right: 20px;
  }
  .profile-image {
    width: 200px;
    height: 200px;
  }
  .profile-details {
    display: flex;
    flex-direction: column;
    align-items: center;
  }
  .profile-position {
    font-size: 20px;
    color: #666;
  }
  .edit-profile-button {
    margin-top: 10px;
    padding: 5px 10px;
    font-size: 16px;
    background-color: #153860;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
  }
  #file-input {
    display: none;
  }
  .user-info {
    text-align: left;
    margin-top: 20px;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 5px;
    background-color: #f9f9f9;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    max-width: 600px;
    margin: 20px auto;
  }
  .user-info p {
    margin: 10px 0;
    font-size: 16px;
    line-height: 1.5;
  }
  .user-info strong {
    color: #153860;
    font-weight: bold;
  }
  .user-info span {
    color: #666;
  }
</style>
</head>
<body>
<?php
session_start();
?>

<div class="nav">
  <a href="dashboard.php" onclick="toggleActive(event, this)"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
  <a href="request_form.php" onclick="toggleActive(event, this)"><i class="fas fa-plus-circle"></i> Create Tracker</a>
  <a href="add_account_title.php" onclick="toggleActive(event, this)"><i class="fas fa-plus"></i> Create PPMP</a>
  <a href="history.php" onclick="toggleActive(event, this)"><i class="fas fa-history"></i> History</a>
  <a href="chatbot.php" onclick="toggleActive(event, this)"><i class="fas fa-comments"></i> Chatbot</a>
  <a href="settings.php" class="settings" onclick="toggleActive(event, this)" class="active"><i class="fas fa-cog"></i> Settings</a>
  <a href="#" class="logout" id="logout-link" onclick="toggleActive(event, this)"><i class="fas fa-sign-out-alt"></i> Log out</a>
</div>

<div id="logoutModal" class="modal">
  <div class="modal-content">
    <p>Are you sure you want to log out?</p>
    <button id="yesBtn">Yes</button>
    <button id="noBtn">No</button>
  </div>
</div>

<div class="header">
  <img src="img/logo.png" class="logo">
  <h1>PPA <span id="datetime"></span></h1>
  <div class="user-profile">
    <i class="fas fa-bell notification-icon" onclick="toggleNotificationDropdown()"></i>
    <div class="vertical-line"></div>
    <?php include 'user_profile.php'; ?>
  </div>
</div>

<div class="content">
  <div class="pending"> 
    <h3>Account Information</h3>
    <?php
    if (isset($_SESSION['user_id'])) {
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "ppmp_db";

        $conn = new mysqli($servername, $username, $password, $dbname);

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $user_id = $_SESSION['user_id'];
        $sql = "SELECT firstname, lastname, profile_image, department_id, email FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $first_name = htmlspecialchars($row['firstname']);
            $last_name = htmlspecialchars($row['lastname']);
            $profileImage = htmlspecialchars($row['profile_image']);
            $department = htmlspecialchars($row['department_id']);
            $email = htmlspecialchars($row['email']);

            echo "<div class='image'>";
            echo "<img src='uploads/" . $profileImage . "' alt='Profile Image' class='profile-image'>";
            echo "<div class='profile-details'>";
            echo "<button class='edit-profile-button' onclick='document.getElementById(\"file-input\").click()'><i class='fas fa-edit'></i></button>";
            echo "</div>";
            echo "</div>";
        } else {
            echo "<span>Error: Unable to fetch account information.</span>";
        }

        echo "<div class='user-info'>";
        echo "<p><strong>NAME:</strong> $first_name $last_name</p>";
        echo "<p><strong>S.Y:</strong> 2024-2025</p>";
        echo "<p><strong>DEPARTMENT:</strong> $department</p>";
        echo "<p><strong>EMAIL:</strong> $email</p>";
        echo "</div>";

        $stmt->close();
        $conn->close();
    } else {
        echo "<span>Error: User not logged in.</span>";
    }
    ?>

    <input type="file" id="file-input" onchange="uploadProfileImage()">
  </div>
</div>

<script>
function uploadProfileImage() {
    var fileInput = document.getElementById("file-input");
    var file = fileInput.files[0];
    
    if (file) {
        var formData = new FormData();
        formData.append('profile_image', file);
        
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'upload_image.php', true);
        xhr.onload = function () {
            if (xhr.status === 200) {
                location.reload();
            } else {
                alert('An error occurred!');
            }
        };
        xhr.send(formData);
    }
}
</script>

<script src="scripts.js"></script>

</body>
</html>
