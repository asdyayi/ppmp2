<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['request_id'])) {
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

$request_id = $_SESSION['request_id'];

// Fetch the request_type_id for the current request
$request_result = $conn->query("SELECT request_type_id FROM requests WHERE id = $request_id");
if ($request_result === false) {
    die("Error fetching request type: " . $conn->error);
}
$request = $request_result->fetch_assoc();
$request_type_id = $request['request_type_id'];

// Prepare the SQL statement to fetch users associated with the request type
$sql = "SELECT u.id, u.email 
        FROM users u
        JOIN request_people rp ON u.id = rp.user_id
        WHERE rp.request_type = ?";

// Prepare the statement
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

// Bind the parameter
$stmt->bind_param("i", $request_type_id);

// Execute the statement
$stmt->execute();

// Get the result
$people_result = $stmt->get_result();
if ($people_result === false) {
    die("Error fetching people: " . $conn->error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['process'] == '3') {
    $people = $_POST['people'] ?? [];
    $new_people = $_POST['new_people'] ?? [];

    foreach ($new_people as $email) {
        $email = $conn->real_escape_string(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            die("Invalid email format: " . htmlspecialchars($email));
        }
    
        // Check if the email exists in the users table
        $user_result = $conn->query("SELECT id FROM users WHERE email = '$email'");
        if ($user_result && $user_row = $user_result->fetch_assoc()) {
            $user_id = $user_row['id'];
    
            // Check if the user is already associated with the request_type
            $check_result = $conn->query("SELECT * FROM request_people WHERE request_type = $request_type_id AND user_id = $user_id");
            if ($check_result && $check_result->num_rows == 0) {
                // Insert the user with request_type and user_id
                $insert_sql = "INSERT INTO request_people (request_type, user_id) VALUES (?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                if ($insert_stmt) {
                    $insert_stmt->bind_param("ii", $request_type_id, $user_id);
                    $insert_stmt->execute();
                    $insert_stmt->close();
                } else {
                    die("Error preparing statement: " . $conn->error);
                }
            }
        } else {
            // This email does not exist in the users table
            echo "<script>alert('Email $email is not registered in the system.');</script>";
        }
    }

    header('Location: summary.php');
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>People Involved</title>
    <link rel="stylesheet" href="style2.css">
    <link rel="stylesheet" href="styles/people.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <script>
        function addPerson() {
            let emailInput = document.getElementById('new-email');
            let email = emailInput.value.trim();

            if (email === '') {
                alert('Please enter an email address.');
                return;
            }

            fetch(`check_email.php?email=${encodeURIComponent(email)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        let table = document.getElementById('people-table');
                        let rowCount = table.rows.length;
                        let row = table.insertRow(rowCount);
                        row.innerHTML = `
                            <td>${email}</td>
                            <td><button type="button" onclick="deleteRow(this)">Delete</button></td>
                            <input type="hidden" name="new_people[]" value="${email}">
                        `;
                        emailInput.value = '';
                    } else {
                        alert('Email is not registered in the system.');
                    }
                })
                .catch(error => console.error('Error checking email:', error));
        }

        function deleteRow(button) {
            let row = button.closest('tr');
            row.remove();
        }

        function deleteExistingPerson(button, email) {
            let row = button.closest('tr');
            row.remove();
        }
    </script>
</head>
<body>

<div class="nav">
  <a href="dashboard.php" onclick="toggleActive(event, this)"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
  <a href="create1.php" onclick="toggleActive(event, this)"><i class="fas fa-plus-circle"></i> Create Tracker</a>
  <a href="createPPMP.php" onclick="toggleActive(event, this)"><i class="fas fa-plus"></i> Create PPMP</a>
  <a href="history.php" onclick="toggleActive(event, this)"><i class="fas fa-history"></i> History</a>
  <a href="chatbot.php" onclick="toggleActive(event, this)"><i class="fas fa-comments"></i> Chatbot</a>
  <a href="settings.php" class="settings" onclick="toggleActive(event, this)"><i class="fas fa-cog"></i> Settings</a>
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
  <h1>Admin <span id="datetime"></span></h1>
  <div class="user-profile">
    <i class="fas fa-bell notification-icon" onclick="toggleNotificationDropdown()"></i>
    <div class="vertical-line"></div>
    <?php include 'user_profile.php'; ?>
  </div>
</div>

<div class="content">
  <div class="img">
    <div class="image-row">
        <img src="img/info.png" alt="Image 1"> <hr>
        <img src="img/peso.png" alt="Image 2"> <hr>
        <img src="img/people.png" alt="Image 3" class="transparent"> <hr>
        <img src="img/process.png" alt="Image 4" class="transparent">
    </div>
    <div class="ex">
        <h2>PEOPLE INVOLVED</h2>
    </div>
    <div class="class">
        <div class="people-involved">
            <form action="people_involved.php" method="post">
                <input type="hidden" name="process" value="3">
                <div>
                    <input type="email" id="new-email" placeholder="Enter email address">
                    <button type="button" onclick="addPerson()">Add Person</button>
                </div>
                <table id="people-table">
                    <tr>
                        <th>Email</th>
                        <th>Action</th>
                    </tr>
                    <?php if ($people_result): ?>
                        <?php while ($row = $people_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td>
                                    <button type="button" onclick="deleteExistingPerson(this, '<?php echo htmlspecialchars($row['email']); ?>')">Delete</button>
                                    <input type="hidden" name="people[]" value="<?php echo htmlspecialchars($row['email']); ?>">
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2">No people available</td>
                        </tr>
                    <?php endif; ?>
                </table>
                <button type="submit" class="btnsub">Next</button>
            </form>
        </div>
    </div>
</div>
<script src="scripts.js"></script>
</body>
</html>
