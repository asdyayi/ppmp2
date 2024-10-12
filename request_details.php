<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Database connection settings
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ppmp_db";

// Create a new database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check if the connection was successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the request ID from the URL
$request_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Retrieve the details of the specific request
$sql = "SELECT * FROM requests WHERE id = $request_id AND user_id = " . $_SESSION['user_id'];
$result = $conn->query($sql);

// Check if the request exists
if ($result->num_rows > 0) {
    $request = $result->fetch_assoc();
} else {
    die("Request not found or you do not have permission to view this request.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Request Details</title>
    <link rel="stylesheet" href="styles/index_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .request-details {
            margin: 20px;
        }
        .request-details h2 {
            margin-bottom: 20px;
        }
        .request-details p {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<div class="request-details">
    <h2>Request Details</h2>
    <p><strong>Document Title:</strong> <?php echo htmlspecialchars($request['document_title']); ?></p>
    <p><strong>Status:</strong> <?php echo htmlspecialchars($request['approval_status']); ?></p>
    <p><strong>Submitted On:</strong> <?php echo isset($request['submitted_on']) ? htmlspecialchars($request['submitted_on']) : 'N/A'; ?></p>
    <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($request['description'])); ?></p>
    <!-- Add more fields as necessary -->
    <a href="history.php">Back to Request History</a>
</div>

</body>
</html>

<?php
$conn->close();
?>
