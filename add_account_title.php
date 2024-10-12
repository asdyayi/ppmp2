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

$department_id_result = $conn->query("SELECT department_id FROM users WHERE id = $user_id");
$department_id_row = $department_id_result->fetch_assoc();
$department_id = $department_id_row['department_id'];

$current_year = date("Y");

$year_cost_result = $conn->query("SELECT year FROM year_costs WHERE year = $current_year");

if ($year_cost_result->num_rows == 0) {
    $sql = "INSERT INTO year_costs (year, total_year_cost) VALUES ($current_year, 0)";
    if (!$conn->query($sql)) {
        error_log("Year cost creation failed: " . $conn->error);
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $account_titles = $_POST['account_titles'];
    $subtitles = $_POST['subtitles'];
    $items = $_POST['items'];
    $unit_sessions = $_POST['unit_sessions'];
    $quantity_sizes = $_POST['quantity_sizes'];
    $unit_costs = $_POST['unit_costs'];
    $schedules = $_POST['schedules']; // Get the schedule for each account title

    error_log(print_r($_POST, true));

    $sql = "INSERT INTO ppmp_submissions (user_id, department_id, approved, created_at) VALUES ($user_id, $department_id, FALSE, NOW())";
    if (!$conn->query($sql)) {
        error_log("PPMP submission creation failed: " . $conn->error);
    }
    $ppmp_submission_id = $conn->insert_id;

    foreach ($account_titles as $account_index => $account_title) {
        $total_event_cost = 0;
        $schedule = $schedules[$account_index]; // Use schedule for this account title

        $sql = "INSERT INTO account_titles (user_id, title, approved, created_at, ppmp_submission_id, year, schedule) 
                VALUES ($user_id, '$account_title', FALSE, NOW(), $ppmp_submission_id, $current_year, '$schedule')";
        if (!$conn->query($sql)) {
            error_log("Account title insertion failed: " . $conn->error);
        }
        $account_title_id = $conn->insert_id;

        if (isset($subtitles[$account_index])) {
            foreach ($subtitles[$account_index] as $subtitle_index => $subtitle) {
                $sql = "INSERT INTO subtitles (account_title_id, subtitle) VALUES ($account_title_id, '$subtitle')";
                if (!$conn->query($sql)) {
                    error_log("Subtitle insertion failed: " . $conn->error);
                }
                $subtitle_id = $conn->insert_id;

                if (isset($items[$account_index][$subtitle_index])) {
                    foreach ($items[$account_index][$subtitle_index] as $item_index => $item) {
                        $unit_session = $unit_sessions[$account_index][$subtitle_index][$item_index];
                        $quantity_size = $quantity_sizes[$account_index][$subtitle_index][$item_index];
                        $unit_cost = $unit_costs[$account_index][$subtitle_index][$item_index];
                        $total_cost = $quantity_size * $unit_cost;
                        $total_event_cost += $total_cost;

                        $sql = "INSERT INTO items (subtitle_id, item, unit_session, quantity_size, unit_cost, total_cost) 
                                VALUES ($subtitle_id, '$item', '$unit_session', $quantity_size, $unit_cost, $total_cost)";
                        if (!$conn->query($sql)) {
                            error_log("Item insertion failed: " . $conn->error);
                        }
                    }
                }
            }
        }

        $sql = "UPDATE account_titles SET total_event_cost = $total_event_cost WHERE id = $account_title_id";
        if (!$conn->query($sql)) {
            error_log("Total event cost update failed: " . $conn->error);
        }

        $sql = "UPDATE year_costs SET total_year_cost = total_year_cost + $total_event_cost WHERE year = $current_year";
        if (!$conn->query($sql)) {
            error_log("Year cost update failed: " . $conn->error);
        }
    }

    echo "<script>alert('PPMP submitted successfully!'); window.location.href='index.php';</script>";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Account Title</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="x-icon" href="img/logo.png">
    <link rel="stylesheet" href="styles/add.css">
    <link rel="stylesheet" href="style2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .content {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding-top: 20px;
        }
        .account-title, .subtitle, .item {
            border: 1px solid #ddd;
            margin-bottom: 10px;
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 8px;
        }
        .subtitle, .item {
            margin-left: 20px;
        }
        input[type="text"], input[type="number"], input[type="date"], button {
            padding: 10px;
            margin: 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        table td, table th {
            padding: 8px;
            border: 1px solid #ddd;
        }
        table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        table th {
            background-color: #f4f4f4;
            text-align: left;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('account-titles-container').addEventListener('click', function (event) {
                if (event.target.matches('input[name="account_titles[]"]')) {
                    event.target.focus();
                }
            });
        });

        function addAccountTitle() {
            let accountTitlesDiv = document.getElementById('account-titles-container');
            let accountIndex = document.querySelectorAll('.account-title').length;
            let accountTitleDiv = document.createElement('div');
            accountTitleDiv.className = 'account-title';
            accountTitleDiv.innerHTML = `
                <div class="title">
                    <h3><input type="text" name="account_titles[${accountIndex}]" placeholder="Account Title" required></h3>
                    <label for="schedule">Schedule:</label>
                    <input type="date" name="schedules[${accountIndex}]" placeholder="Schedule" required>
                    <div class="subtitles-container"></div>
                    <button type="button" onclick="addSubtitle(this, ${accountIndex})">Add Subtitle</button>
                    <h4>Total Cost for Account Title: <span class="total-account-cost">0</span></h4>
                </div>`;
            accountTitlesDiv.appendChild(accountTitleDiv);
        }

        function addSubtitle(button, accountIndex) {
            let subtitlesDiv = button.previousElementSibling;
            let subtitleIndex = subtitlesDiv.querySelectorAll('.subtitle').length;
            let subtitleDiv = document.createElement('div');
            subtitleDiv.className = 'subtitle';
            subtitleDiv.innerHTML = `
                <h4><input type="text" name="subtitles[${accountIndex}][${subtitleIndex}]" placeholder="Subtitle" required></h4>
                <div class="items"></div>
                <button type="button" onclick="addItem(this, ${accountIndex}, ${subtitleIndex})">Add Item</button>
                <p><strong>Total Cost for Subtitle: </strong><span class="total-subtitle-cost">0</span></p>`;
            subtitlesDiv.appendChild(subtitleDiv);
        }

        function addItem(button, accountIndex, subtitleIndex) {
            let itemsDiv = button.previousElementSibling;
            let itemIndex = itemsDiv.querySelectorAll('.item').length;
            let itemDiv = document.createElement('div');
            itemDiv.className = 'item';
            itemDiv.innerHTML = `
                <table>
                    <tr>
                        <th>Item</th>
                        <th>Unit Session</th>
                        <th>Quantity/Size</th>
                        <th>Unit Cost</th>
                        <th>Total Cost</th>
                    </tr>
                    <tr>
                        <td><input type="text" name="items[${accountIndex}][${subtitleIndex}][${itemIndex}]" placeholder="Item" required></td>
                        <td><input type="text" name="unit_sessions[${accountIndex}][${subtitleIndex}][${itemIndex}]" placeholder="Unit/Session"></td>
                        <td><input type="number" name="quantity_sizes[${accountIndex}][${subtitleIndex}][${itemIndex}]" placeholder="Quantity/Size" onchange="calculateTotalCost(this)"></td>
                        <td><input type="number" name="unit_costs[${accountIndex}][${subtitleIndex}][${itemIndex}]" placeholder="Unit Cost" onchange="calculateTotalCost(this)"></td>
                        <td><span class="total-cost">0</span></td>
                    </tr>
                </table>`;
            itemsDiv.appendChild(itemDiv);
        }

        function calculateTotalCost(input) {
            let itemDiv = input.closest('.item');
            let quantity = itemDiv.querySelector('input[name^="quantity_sizes"]').value;
            let unitCost = itemDiv.querySelector('input[name^="unit_costs"]').value;
            let totalCost = itemDiv.querySelector('.total-cost');
            totalCost.innerText = quantity * unitCost;

            calculateTotalSubtitleCost(itemDiv.closest('.subtitle'));
        }

        function calculateTotalSubtitleCost(subtitleDiv) {
            let totalSubtitleCost = 0;
            subtitleDiv.querySelectorAll('.total-cost').forEach(costSpan => {
                totalSubtitleCost += parseFloat(costSpan.innerText);
            });
            subtitleDiv.querySelector('.total-subtitle-cost').innerText = totalSubtitleCost;

            calculateTotalAccountCost(subtitleDiv.closest('.account-title'));
        }

        function calculateTotalAccountCost(accountTitleDiv) {
            let totalAccountCost = 0;
            accountTitleDiv.querySelectorAll('.total-subtitle-cost').forEach(costSpan => {
                totalAccountCost += parseFloat(costSpan.innerText);
            });
            accountTitleDiv.querySelector('.total-account-cost').innerText = totalAccountCost;

            calculateTotalYearCost();
        }

        function calculateTotalYearCost() {
            let totalYearCost = 0;
            document.querySelectorAll('.total-account-cost').forEach(costSpan => {
                totalYearCost += parseFloat(costSpan.innerText);
            });
            document.getElementById('total-year-cost').innerText = totalYearCost;
        }
    </script>
</head>
<body>
<div class="nav">
  <a href="index.php" onclick="toggleActive(event, this)"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
  <a href="request_form.php" onclick="toggleActive(event, this)"><i class="fas fa-plus-circle"></i> Create Tracker</a>
  <a href="add_account_title.php" onclick="toggleActive(event, this)" class="active"><i class="fas fa-plus"></i> Create PPMP</a>
  <a href="history.php" onclick="toggleActive(event, this)"><i class="fas fa-history"></i> History</a>
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
    <?php include 'user_profile.php'; ?>
  </div>
</div>

<div class="info">
    <h2> CREATE PPMP </h2>
</div>  

<div class="hi">
  <div class="hello">
    <form action="add_account_title.php" method="post">
        <div id="account-titles-container"></div>
        <button type="button" onclick="addAccountTitle()">Add Account Title</button>
        <h2>Total Year Cost: <span id="total-year-cost">0</span></h2>
        <button type="submit" class="btnsub">Submit</button>
    </form>
  </div>
</div>

<script src="scripts.js"></script>
</body>
</html>
