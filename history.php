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
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

$sql = "SELECT id, document_title, approval_status FROM requests WHERE user_id = $user_id";
if ($filter === 'approved') {
    $sql .= " AND approval_status = 'approved'";
} elseif ($filter === 'declined') {
    $sql .= " AND approval_status = 'declined'";
}

$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Request History</title>
    <link rel="shortcut icon" type="x-icon" href="img/logo.png">
    <link rel="stylesheet" href="style2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Add your existing CSS here */
        body {
            font-family: Arial, sans-serif;
        }
        .filter-form {
            margin-bottom: 20px;
        }
        .filter-form select {
            padding: 5px;
            font-size: 16px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        .history {
            width: 50%;
            margin-top: 40px;
            margin-left: 470px;
            background-color: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        .filter-form {
            margin-bottom: 20px;
        }
        .filter-form label {
            font-weight: bold;
            margin-right: 10px;
        }
        .filter-form select {
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        a {
            color: #4CAF50;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        /* Modal styles */
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1; 
            padding-top: 60px; 
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgb(0,0,0); 
            background-color: rgba(0,0,0,0.4); 
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto; 
            padding: 20px;
            border: 1px solid #888;
            width: 80%; 
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>

<div class="nav">
  <a href="dashboard.php" onclick="toggleActive(event, this)" ><i class="fas fa-tachometer-alt"></i> Dashboard</a>
  <a href="request_form.php" onclick="toggleActive(event, this)"><i class="fas fa-plus-circle"></i> Create Tracker</a>
  <a href="add_account_title.php" onclick="toggleActive(event, this)"><i class="fas fa-plus"></i> Create PPMP</a>
  <a href="history.php" onclick="toggleActive(event, this)" class="active"><i class="fas fa-history"></i> History</a>
  <a href="chatbot.php" onclick="toggleActive(event, this)"><i class="fas fa-comments"></i> Chatbot</a>
  <a href="settings.php" class="settings" onclick="toggleActive(event, this)"><i class="fas fa-cog"></i> Settings</a>
  <a href="#" class="logout" id="logout-link" onclick="toggleActive(event, this)"><i class="fas fa-sign-out-alt"></i> Log out</a>
</div>

<!-- LOG OUT -->
<div id="logoutModal" class="modal">
  <div class="modal-content">
    <p>Are you sure you want to log out?</p>
    <button id="yesBtn">Yes</button>
    <button id="noBtn">No</button>
  </div>
</div>

<div class="header">
  <img src="img/logo.png" class="logo">
  <h1>Admin <span id="datetime"></span></h1>
  <div class="user-profile">
    <i class="fas fa-bell notification-icon" onclick="toggleNotificationDropdown()"></i>
    <div class="vertical-line"></div>
  </div>
</div>

<div class="history">

<form method="GET" action="history.php" class="filter-form">
    <label for="filter">Filter:</label>
    <select id="filter" name="filter" onchange="this.form.submit()">
        <option value="all" <?php if ($filter === 'all') echo 'selected'; ?>>All</option>
        <option value="approved" <?php if ($filter === 'approved') echo 'selected'; ?>>Approved</option>
        <option value="declined" <?php if ($filter === 'declined') echo 'selected'; ?>>Declined</option>
    </select>
</form>

<table>
    <tr>
        <th>Document Title</th>
        <th>Status</th>
        <th>Action</th>
    </tr>
    <?php
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['document_title']) . "</td>";
            echo "<td>" . htmlspecialchars($row['approval_status']) . "</td>";
            echo '<td><a href="#" class="view-details" data-id="' . htmlspecialchars($row['id']) . '">View Details</a></td>';
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='3'>No records found</td></tr>";
    }
    ?>
</table>


<!-- Modal -->
<div id="detailsModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <div id="modal-body">
            <!-- Details will be loaded here -->
        </div>
    </div>
</div>

<script>
    // Get the modal
    var modal = document.getElementById("detailsModal");

    // Get the <span> element that closes the modal
    var span = document.getElementsByClassName("close")[0];

    // When the user clicks on <span> (x), close the modal
    span.onclick = function() {
        modal.style.display = "none";
    }

    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    // Get all view details links
    var viewDetailsLinks = document.getElementsByClassName("view-details");

    // Add click event to each view details link
    Array.from(viewDetailsLinks).forEach(function(link) {
        link.onclick = function(event) {
            event.preventDefault();
            var requestId = this.getAttribute("data-id");

            // Make an AJAX request to fetch details
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "request_details.php?id=" + requestId, true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    document.getElementById("modal-body").innerHTML = xhr.responseText;
                    modal.style.display = "block";
                } else {
                    alert("Failed to fetch details. Please try again.");
                }
            };
            xhr.send();
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
