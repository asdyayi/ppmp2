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

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: admin_login.php');
    exit();
}

$selected_year = isset($_GET['year']) ? intval($_GET['year']) : '';
$search_query = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

$ppmp_result = $conn->query("
    SELECT ps.id, ps.created_at, u.firstname, u.lastname, d.name AS department,
           SUM(at.total_event_cost) AS total_year_budget
    FROM ppmp_submissions ps
    JOIN users u ON ps.user_id = u.id 
    JOIN departments d ON ps.department_id = d.id 
    JOIN account_titles at ON ps.id = at.ppmp_submission_id
    WHERE ps.approved = TRUE 
    AND (u.firstname LIKE '%$search_query%' OR u.lastname LIKE '%$search_query%' OR d.name LIKE '%$search_query%')
" . ($selected_year ? " AND YEAR(ps.created_at) = $selected_year" : "") . "
    GROUP BY ps.id, ps.created_at, u.firstname, u.lastname, d.name
    ORDER BY ps.created_at DESC
");

// Handle AJAX request for PPMP details
if (isset($_GET['id'])) {
    $ppmp_id = intval($_GET['id']);
    $details_result = $conn->query("SELECT * FROM ppmp_submissions WHERE id = $ppmp_id");
    
    if ($details_result->num_rows > 0) {
        $details = $details_result->fetch_assoc();
        echo "<p><strong>Department:</strong> " . htmlspecialchars($details['department_id']) . "</p>";
        echo "<p><strong>Budget:</strong> " . number_format($details['total_budget'], 2) . "</p>";
        // Add more fields as needed
    } else {
        echo "No details found.";
    }
    exit; // Ensure no further output is sent
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Approved PPMPs</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="style2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
        .search-container {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 10px;
        }
        .search-container input {
            padding: 5px;
            margin-right: 10px;
        }
        .sort-btn, .year-select, .details-btn {
            padding: 5px 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            margin-left: 10px;
        }
        .details-btn {
            background-color: #007BFF; /* Different color for details button */
        }
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1; 
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            background-color: rgba(0,0,0,0.4); 
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto; 
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
    </style>
</head>
<body>
<div class="nav">
    <a href="index.php" onclick="toggleActive(event, this)"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="request_form.php" onclick="toggleActive(event, this)"><i class="fas fa-plus-circle"></i> Create Tracker</a>
    <a href="add_account_title.php" onclick="toggleActive(event, this)"><i class="fas fa-plus"></i> Create PPMP</a>
    <a href="history.php" onclick="toggleActive(event, this)"><i class="fas fa-history"></i> History</a>
    <a href="orgPPMP.php" onclick="toggleActive(event, this)" class="active"><i class="fas fa-history"></i> Department PPMP</a>
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
    <h1>Approved PPMPs</h1>
    
    <div class="search-container">
        <form method="GET" action="" style="display: inline;">
            <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search_query); ?>">
            <button type="submit" class="sort-btn">Search</button>
        </form>
        <form method="GET" action="" style="display: inline;">
            <select name="year" class="year-select" onchange="this.form.submit()">
                <option value="">Sort by Year</option>
                <?php for ($year = 2000; $year <= 2024; $year++): ?>
                    <option value="<?php echo $year; ?>" <?php echo ($selected_year == $year) ? 'selected' : ''; ?>><?php echo $year; ?></option>
                <?php endfor; ?>
            </select>
        </form>
    </div>
    
    <?php if ($ppmp_result->num_rows > 0): ?>
        <table>
            <tr>
                <th>College</th>
                <th>Submitted By</th>
                <th>Whole Year Budget</th>
                <th>Submitted When</th>
                <th>Details</th>
            </tr>
            <?php while($row = $ppmp_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['department']); ?></td>
                    <td><?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></td>
                    <td><?php echo number_format($row['total_year_budget'], 2); ?></td>
                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                    <td>
                        <button type="button" class="details-btn" onclick="fetchPPMPDetails(<?php echo $row['id']; ?>)">Details</button>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No approved PPMPs available.</p>
    <?php endif; ?>
</div>

<div id="detailsModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>PPMP Details</h2>
        <div id="modalDetails"></div>
    </div>
</div>

<script>
function fetchPPMPDetails(ppmpId) {
    const modal = document.getElementById("detailsModal");
    const modalDetails = document.getElementById("modalDetails");

    const xhr = new XMLHttpRequest();
    xhr.open("GET", "?id=" + ppmpId, true);
    xhr.onload = function() {
        if (this.status === 200) {
            modalDetails.innerHTML = this.responseText;
            modal.style.display = "block";
        }
    };
    xhr.send();
}

function closeModal() {
    document.getElementById("detailsModal").style.display = "none";
}
</script> 

</body>
</html>