<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ppmp_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$user_result = $conn->query("SELECT u.firstname, u.lastname, d.name AS department 
                             FROM users u 
                             JOIN departments d ON u.department_id = d.id 
                             WHERE u.id = $user_id");

if ($user_result === false) {
    die("Error fetching user data: " . $conn->error);
}

$user = $user_result->fetch_assoc();

// Fetch only approved account titles for the logged-in user
$account_titles_result = $conn->query("
    SELECT at.id, at.title 
    FROM account_titles at
    JOIN ppmp_submissions ps ON at.ppmp_submission_id = ps.id
    WHERE at.user_id = $user_id AND ps.approved = TRUE
");

$request_types_result = $conn->query("SELECT id, name FROM request_types");

if ($account_titles_result === false || $request_types_result === false) {
    die("Error fetching account titles or request types: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Request</title>
    <link rel="stylesheet" href="style2.css">
    <link rel="stylesheet" href="styles/create_tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<div class="nav">
  <a href="dashboard.php" onclick="toggleActive(event, this)"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
  <a href="request_form.php" onclick="toggleActive(event, this)" class="active"><i class="fas fa-plus-circle"></i> Create Tracker</a>
  <a href="add_account_title.php" onclick="toggleActive(event, this)"><i class="fas fa-plus"></i> Create PPMP</a>
  <a href="history.php" onclick="toggleActive(event, this)"><i class="fas fa-history"></i> History</a>
  <a href="chatbot.php" onclick="toggleActive(event, this)"><i class="fas fa-comments"></i> Chatbot</a>
  <a href="settings.php" class="settings" onclick="toggleActive(event, this)"><i class="fas fa-cog"></i> Settings</a>
  <a href="#" class="logout" id="logout-link" onclick="toggleActive(event, this)"><i class="fas fa-sign-out-alt"></i> Log out</a>
</div>

<div class="header">
  <img src="img/logo.png" class="logo">
  <h1>Admin <span id="datetime"></span></h1>
  <div class="user-profile">
    <i class="fas fa-bell notification-icon" onclick="toggleNotificationDropdown()"></i>
    <div class="vertical-line"></div>
    <?php include 'user_profile.php'; ?>
  </div>
</div>

<div class="content">
  <div class="create">
    <div class="image-row">
      <img src="img/info.png" alt="Image 1"> <hr>
      <img src="img/peso.png" alt="Image 2" class="transparent"> <hr>
      <img src="img/people.png" alt="Image 3" class="transparent"> <hr>
      <img src="img/process.png" alt="Image 4" class="transparent">
    </div>
    <div class="info">
        <h2> INFORMATION </h2>
    </div>

    <div class="info_form">
    <div class="vertical"></div>
    <form action="process_request.php" method="post" enctype="multipart/form-data">
        <div class="first">
            <input type="hidden" name="process" value="1">
            <label for="sender_name">Sender Name:</label>
            <input type="text" id="sender_name" name="sender_name" value="<?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>" class="display-box" readonly>

            <label for="department">Department:</label>
            <input type="text" id="department" name="department" value="<?php echo htmlspecialchars($user['department']); ?>" readonly>

            <label for="document_title">Document Title:</label>
            <input type="text" id="document_title" name="document_title" required>

            <label for="request_type">Type of Request:</label>
            <select id="request_type" name="request_type" required>
                <option value="">Select Request Type</option>
                <?php while ($row = $request_types_result->fetch_assoc()): ?>
                    <option value="<?php echo htmlspecialchars($row['id']); ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="second">
        <label for="account_title">Account:</label>
        <select id="account_title" name="account_title" required>
            <option value="">Select Account Title</option>
            <?php while ($row = $account_titles_result->fetch_assoc()): ?>
                <option value="<?php echo htmlspecialchars($row['id']); ?>"><?php echo htmlspecialchars($row['title']); ?></option>
            <?php endwhile; ?>
        </select>

        <label for="description">Description:</label>
        <textarea id="description" name="description" required></textarea>

        <label for="file1">Upload File 1:</label>
        <input type="file" id="file1" name="files[]" accept=".pdf,.doc,.docx" required>
        <label for="file2">Upload File 2:</label>
        <input type="file" id="file2" name="files[]" accept=".pdf,.doc,.docx" required>
        <label for="file3">Upload File 3:</label>
        <input type="file" id="file3" name="files[]" accept=".pdf,.doc,.docx" required>
        </div>
        <button type="submit" class="btnsub">Next</button>
    </form>
    </div>
  </div>
</div>

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
