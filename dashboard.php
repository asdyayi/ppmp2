<?php
session_start();

if (!isset($_SESSION['user_id']) || (isset($_SESSION['is_admin']) && $_SESSION['is_admin'])) {
    header('Location: index.php'); // Prevent admins from accessing this page
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

// Fetch the user details
$user_stmt = $conn->prepare("SELECT u.id, u.firstname, u.lastname, d.id AS department_id, d.name AS department
                             FROM users u 
                             JOIN departments d ON u.department_id = d.id 
                             WHERE u.id = ?");
if (!$user_stmt) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
}
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

if (!$user) {
    die("User not found.");
}

$department_id = $user['department_id'];

// Fetch PPMP data for user's department
$ppmp_exists_result = $conn->query("SELECT COUNT(*) AS ppmp_count, SUM(at.total_event_cost) AS total_approved_cost
                                    FROM account_titles at 
                                    JOIN users u ON at.user_id = u.id 
                                    WHERE u.department_id = $department_id AND at.approved = 1");
$ppmp_data = $ppmp_exists_result->fetch_assoc();
$ppmp_exists = $ppmp_data['ppmp_count'] > 0;
$total_approved_cost = $ppmp_data['total_approved_cost'] ?? 0;

// Calculate remaining fund based on requests that have been approved by the admin
$remaining_fund_result = $conn->query("SELECT SUM(at.total_event_cost) AS total_requested_cost
                                       FROM account_titles at
                                       JOIN users u ON at.user_id = u.id
                                       WHERE u.department_id = $department_id AND at.approved = 1");
$remaining_fund_data = $remaining_fund_result->fetch_assoc();
$total_requested_cost = $remaining_fund_data['total_requested_cost'] ?? 0;

// Set the year fund based on total approved cost
$year_fund = $total_approved_cost;
$remaining_fund = $year_fund - $total_requested_cost;

// Fetch upcoming events created by the logged-in user
$events_result = $conn->query("SELECT at.title, DATE_FORMAT(at.schedule, '%M %d, %Y') AS event_date 
                               FROM account_titles at
                               WHERE at.user_id = $user_id AND at.schedule >= CURDATE() AND at.approved = 1
                               ORDER BY at.schedule ASC");

$upcoming_events = [];
if ($events_result && $events_result->num_rows > 0) {
    while ($row = $events_result->fetch_assoc()) {
        $upcoming_events[] = $row;
    }
}


$sql = "SELECT title, total_event_cost FROM account_titles";
$result = $conn->query($sql);

$account_titles = [];
$total_costs = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $account_titles[] = $row['title'];
        $total_costs[] = $row['total_event_cost'];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PPMP Management</title>
    <link rel="stylesheet" href="styles/index_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="style2.css">

    
</head>
<body>

<div class="nav">
  <a href="dashboard.php" onclick="toggleActive(event, this)" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
  <a href="request_form.php" onclick="toggleActive(event, this)"><i class="fas fa-plus-circle"></i> Create Tracker</a>
  <a href="add_account_title.php" onclick="toggleActive(event, this)"><i class="fas fa-plus"></i> Create PPMP</a>
  <a href="history.php" onclick="toggleActive(event, this)"><i class="fas fa-history"></i> History</a>
  <a href="chatbot.php" onclick="toggleActive(event, this)"><i class="fas fa-comments"></i> Chatbot</a>
  <a href="settings.php" class="settings" onclick="toggleActive(event, this)"><i class="fas fa-cog"></i> Settings</a>
  <a href="#" class="logout" id="logout-link" onclick="toggleActive(event, this)"><i class="fas fa-sign-out-alt"></i> Log out</a>
</div>

<div class="header">
  <img src="img/logo.png" class="logo">
  <h1>User Dashboard <span id="datetime"></span></h1>
  <div class="user-profile">
    <i class="fas fa-bell notification-icon" onclick="toggleNotificationDropdown()"></i>
    <div class="vertical-line"></div>
    <?php include 'user_profile.php'; ?>
  </div>
</div>

<div class="content">
    <div class="tracker">
        <div>
            <h2>ENTER TRACKER NUMBER</h2>
            <div class="underline"></div>
        </div>
        <div class="input-box">
            <input type="text" class="rounded-input" placeholder="Tracker Number">
            <img src="img/btn.png" class="button-image" alt="Button" onclick="handleButtonClick()" />
        </div>
    </div>

    <div class = "stats">

    </div>
    <div class="Pending">
    <h3>Pending...</h3>
    <div class="pending-content">
        <div class="details">
            <p><strong>Title:</strong></p> 
            <!-- Assuming you are using a server-side language like PHP to retrieve data -->
            <p><strong>Title:</strong> <?php echo $row['document_title']; ?></p>
            <!-- The above will fetch the title from the 'request' table -->
            <p><strong>Status:</strong> Pending</p>
        </div>
        <a href="#" class="view-details">View Details</a>
    </div>
</div>
                               


    <div class = "CFund">
        <h3>           College Fund</h3>
        <div class="fund">
            <?php if ($ppmp_exists && $total_approved_cost > 0): ?>
                <div class="fund-details">
                    <div class="fund-item">
                        <span class="label">Year Fund:</span>
                        <span class="value"><?php echo number_format($year_fund, 2); ?></span>
                    </div>
                    <hr style="margin: 10px 0;">
                    <div class="fund-item">
                        <span class="label">Remaining Fund:</span>
                        <span class="value"><?php echo number_format($remaining_fund, 2); ?></span>
                    </div>
                </div>
                <div class="chart-container" style="max-width: 200px; max-height: 100px; margin: auto;">
                    <canvas id="accountTitlesChart"></canvas>
                </div>
            <?php elseif (!$ppmp_exists): ?>
                <p>No PPMP exists for your department yet.</p>
            <?php else: ?>
                <p>Your PPMP is pending approval.</p>
            <?php endif; ?>
        </div>
    </div>


    <div class="events">
    <h3>Upcoming Events</h3>
    <div class="event-list">
        <?php if (!empty($upcoming_events)): ?>
            <?php foreach ($upcoming_events as $event): ?>
                <div class="event-item">
                    <div class="event-date">
                        <?php echo date('F', strtotime($event['event_date'])); ?>
                    </div>
                    <div class="event-content">
                        <div class="event-title"><?php echo htmlspecialchars($event['title']); ?></div>
                        <div class="event-description"> <?php echo htmlspecialchars($event['event_date']); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No upcoming events scheduled.</p>
        <?php endif; ?>
    </div>
</div>
<script>
    // Get the data from PHP
    const accountTitles = <?php echo json_encode($account_titles); ?>;
    const totalCosts = <?php echo json_encode($total_costs); ?>;

    const ctx = document.getElementById('accountTitlesChart').getContext('2d');
    const accountTitlesChart = new Chart(ctx, {
    type: 'pie',
    data: {
        labels: accountTitles,
        datasets: [{
            data: totalCosts,
            backgroundColor: [
                '#6ce5e8', '#41b8d5', '#2d8bba', '#2f5f98', '#31356e'
            ],
            borderColor: [
                '#6ce5e8', '#41b8d5', '#2d8bba', '#2f5f98', '#31356e'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false, // This makes sure it can adjust to the smaller size
        plugins: {
            legend: {
                display: true,
                position: 'right',
                labels: {
                    boxWidth: 10,
                    font: {
                        size: 12 // Smaller font size
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(tooltipItem) {
                        return `${tooltipItem.label}: ${tooltipItem.raw}`;
                    }
                }
            }
        }
    }
});
</script>

</div>

<script src="scripts.js"></script>

</body>
</html>
