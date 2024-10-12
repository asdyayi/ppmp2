<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: admin_login.php');
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

// Handle approval or decline directly within this script
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $ppmp_submission_id = $_POST['ppmp_submission_id'];

    if ($action === 'approve') {
        $conn->query("UPDATE ppmp_submissions SET approved = TRUE WHERE id = $ppmp_submission_id");
        $conn->query("UPDATE account_titles SET approved = TRUE WHERE ppmp_submission_id = $ppmp_submission_id");
        echo json_encode(['status' => 'success', 'message' => 'PPMP approved successfully!']);
    } elseif ($action === 'decline') {
        $conn->query("DELETE FROM account_titles WHERE ppmp_submission_id = $ppmp_submission_id");
        $conn->query("DELETE FROM ppmp_submissions WHERE id = $ppmp_submission_id");
        echo json_encode(['status' => 'success', 'message' => 'PPMP declined successfully!']);
    }
    $conn->close();
    exit();
}

$ppmp_result = $conn->query("
    SELECT ps.id, ps.created_at, u.firstname, u.lastname, d.name AS department,
           SUM(at.total_event_cost) AS total_year_budget
    FROM ppmp_submissions ps
    JOIN users u ON ps.user_id = u.id 
    JOIN departments d ON ps.department_id = d.id 
    JOIN account_titles at ON ps.id = at.ppmp_submission_id
    WHERE ps.approved = FALSE
    GROUP BY ps.id, ps.created_at, u.firstname, u.lastname, d.name
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Approve PPMPs</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="style2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 800px;
            border-radius: 10px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover, .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        .approve-btn, .decline-btn {
            margin-top: 20px;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
        }
        .approve-btn {
            background-color: #4CAF50;
            color: white;
        }
        .decline-btn {
            background-color: #f44336;
            color: white;
        }
    </style>
</head>
<body>
<div class="nav">
  <a href="admin.php" onclick="toggleActive(event, this)" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
  <a href="request_form.php" onclick="toggleActive(event, this)"><i class="fas fa-plus-circle"></i> Create Tracker</a>
  <a href="add_account_title.php" onclick="toggleActive(event, this)"><i class="fas fa-plus"></i> Create PPMP</a>
  <a href="history.php" onclick="toggleActive(event, this)"><i class="fas fa-history"></i> History</a>
  <a href="orgPPMP.php" onclick="toggleActive(event, this)"><i class="fas fa-history"></i> Department PPMP</a>
  <a href="chatbot.php" onclick="toggleActive(event, this)"><i class="fas fa-comments"></i> Chatbot</a>
  <a href="settings.php" class="settings" onclick="toggleActive(event, this)"><i class="fas fa-cog"></i> Settings</a>
  <a href="#" class="logout" id="logout-link" onclick="toggleActive(event, this)"><i class="fas fa-sign-out-alt"></i> Log out</a>
</div>

    <div class="header">
        <img src="img/logo.png" class="logo">
        <h1>Admin Dashboard <span id="datetime"></span></h1>
        <div class="user-profile">
            <i class="fas fa-bell notification-icon" onclick="toggleNotificationDropdown()"></i>
            <div class="vertical-line"></div>
            <?php include 'user_profile.php'; ?>
        </div>
    </div>

    <div class="content">
        <h1>Admin - Approve PPMPs</h1>
        <?php if ($ppmp_result->num_rows > 0): ?>
            <table>
                <tr>
                    <th>College</th>
                    <th>Submitted By</th>
                    <th>Whole Year Budget</th>
                    <th>Submitted When</th>
                    <th>View Details</th>
                </tr>
                <?php while ($row = $ppmp_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['department']); ?></td>
                        <td><?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></td>
                        <td><?php echo number_format($row['total_year_budget'], 2); ?></td>
                        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                        <td>
                            <button type="button" onclick="showDetails(<?php echo htmlspecialchars($row['id']); ?>)">View Details</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>No PPMPs pending approval.</p>
        <?php endif; ?>

        <div id="detailsModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal()">&times;</span>
                <div id="modalContent"></div>
                <button class="approve-btn" onclick="approvePPMP()">Approve</button>
                <button class="decline-btn" onclick="declinePPMP()">Decline</button>
            </div>
        </div>
    </div>

    <script>
        var currentPPMPId;

        function showDetails(ppmpSubmissionId) {
            currentPPMPId = ppmpSubmissionId;
            var modal = document.getElementById("detailsModal");
            modal.style.display = "block";

            var xhr = new XMLHttpRequest();
            xhr.open("GET", "view_details.php?ppmp_submission_id=" + ppmpSubmissionId, true);
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    document.getElementById("modalContent").innerHTML = xhr.responseText;
                }
            };
            xhr.send();
        }

        function closeModal() {
            var modal = document.getElementById("detailsModal");
            modal.style.display = "none";
        }

        function approvePPMP() {
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "admin.php", true);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    var response = JSON.parse(xhr.responseText);
                    alert(response.message);
                    closeModal();
                    location.reload(); // Refresh the page to update the list
                }
            };
            xhr.send("action=approve&ppmp_submission_id=" + currentPPMPId);
        }

        function declinePPMP() {
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "admin.php", true);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    var response = JSON.parse(xhr.responseText);
                    alert(response.message);
                    closeModal();
                    location.reload(); // Refresh the page to update the list
                }
            };
            xhr.send("action=decline&ppmp_submission_id=" + currentPPMPId);
        }

        window.onclick = function(event) {
            var modal = document.getElementById("detailsModal");
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>

