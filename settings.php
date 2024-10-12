<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ppmp_db";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$stmt = $conn->prepare("SELECT firstname, lastname, department_id, profile_image FROM users WHERE id = ?");
if ($stmt === false) {
    die("Failed to prepare statement: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result === false) {
    die("Failed to execute query: " . $stmt->error);
}
$user = $result->fetch_assoc();
$stmt->close();
$conn->close();

$first_name = htmlspecialchars($user['firstname'] ?? 'John');
$last_name = htmlspecialchars($user['lastname'] ?? 'Doe');
$department = htmlspecialchars($user['department_id'] ?? 'Administrator');
$profile_image = htmlspecialchars($user['profile_image'] ?? 'user.png');
$user_image_path = 'uploads/' . $profile_image;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="shortcut icon" type="x-icon" href="img/logo.png">
<link rel="stylesheet" href="style2.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<title>Settings</title>
<style>
  body, html {
    height: 100%;
    margin: 0;
  }
  .content {
    margin-top: 50px;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding-top: 15px;
  }
  .image-row {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-top: 20px;
  }
  .image-row img {
    width: 70px;
    height: auto;
  }
  hr {
    width: 100px;
    height: 2px;
    background-color: black;
  }
  .header {
    background-color: white;
    padding: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    height: 60px;
    position: relative;
    z-index: 1;
  }
  .header h1 {
    margin-left: 80px;
    margin-top: 30px;
    font-size: 31px;
  }
  .header h1 span {
    margin-left: 60px;
    font-size: 0.8rem;
    color: #555;
  }
  .user-profile {
    display: flex;
    align-items: center;
    gap: 10px;
    position: relative;
  }
  .notification-icon {
    font-size: 20px;
    cursor: pointer;
  }
  .user-image {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
  }
  .user-details {
    display: flex;
    flex-direction: column;
    text-align: right;
  }
  .user-name {
    font-size: 14px;
    font-weight: bold;
  }
  .user-position {
    font-size: 12px;
    color: #666;
  }
  .vertical-line {
    width: 1px;
    height: 40px;
    background-color: #ccc;
    margin: 0 10px;
  }
  .notification-dropdown {
    display: none;
    position: absolute;
    top: 50px;
    right: 0;
    width: 200px;
    background-color: #fff;
    border: 1px solid #ddd;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
    z-index: 10;
    padding: 10px;
  }
  .notification-dropdown.active {
    display: block;
  }
  .notification-dropdown p {
    margin: 0;
    padding: 8px;
    border-bottom: 1px solid #ddd;
  }
  .notification-dropdown p:last-child {
    border-bottom: none;
  }
  .settings-options {
    display: flex;
    flex-direction: column;
    align-items: left;
    justify-content: center;
    height: 150px;
    background-color: #fff;
    margin-top: 150px;
    width: 700px;
  }
  .settings-option a {
    text-decoration: none;
    color: #333;
    font-size: 18px;
    font-weight: bold;
    margin-top: 10px;
    margin-bottom: 5px;
    margin-right: 15px;
    padding: 10px 20px;
    display: inline-block;
  }
  .settings-option a:hover {
    color: #007bff;
    background-color: #C6D3E3;
    border-radius: 25px;
    width: 600px;
    margin-left: 20px;
  }
  hr {
    width: 700px;
    height: 0.5px;
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
</style>
</head>
<body>

<div class="nav">
  <a href="dashboard.php" onclick="toggleActive(event, this)"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
  <a href="request_form.php" onclick="toggleActive(event, this)"><i class="fas fa-plus-circle"></i> Create Tracker</a>
  <a href="add_account_title.php" onclick="toggleActive(event, this)"><i class="fas fa-plus"></i> Create PPMP</a>
  <a href="history.php" onclick="toggleActive(event, this)"><i class="fas fa-history"></i> History</a>
  <a href="chatbot.php" onclick="toggleActive(event, this)"><i class="fas fa-comments"></i> Chatbot</a>
  <a href="settings.php" class="settings active" onclick="toggleActive(event, this)"><i class="fas fa-cog"></i> Settings</a>
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
  <img src="img/logo.png" class="logo" alt="Logo">
  <h1>PPA <span id="datetime"></span></h1>
  <div class="user-profile">
    <i class="fas fa-bell notification-icon" onclick="toggleNotificationDropdown()"></i>
    <div class="vertical-line"></div>
    <div class="profile-info">
      <img src="<?php echo $user_image_path; ?>" alt="Profile Image" class="user-image">
      <div class="user-details">
        <span class="user-name"><?php echo $first_name . ' ' . $last_name; ?></span>
      </div>
    </div>
  </div>
</div>

<div class="content">
  <div class="settings-options">
    <div class="settings-option">
      <a href="settings_acc.php">Account Settings</a>
    </div>
    <hr> 
    <div class="settings-option">
      <a href="settings_pass.php">Change Password</a>
    </div>
  </div>
</div>

<script>
  function toggleNotificationDropdown() {
    const dropdown = document.getElementById('notificationDropdown');
    dropdown.classList.toggle('active');
  }

  document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('notificationDropdown');
    const icon = document.querySelector('.notification-icon');
    if (!dropdown.contains(event.target) && !icon.contains(event.target)) {
      dropdown.classList.remove('active');
    }
  });
</script>

<!-- LOG OUT -->
<div id="logoutModal" class="modal">
  <div class="modal-content">
    <p>Are you sure you want to log out?</p>
    <button id="yesBtn">Yes</button>
    <button id="noBtn">No</button>
  </div>
</div>

<script src="scripts.js"></script>

</body>
</html>