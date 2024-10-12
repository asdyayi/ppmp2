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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_id'], $_POST['feedback'])) {
    $request_id = $_POST['request_id'];
    $feedback = $_POST['feedback'];
    $user_id = $_SESSION['user_id'];

    // Update the request status to declined and add feedback
    $stmt = $conn->prepare("UPDATE request_people SET approval_status = 'declined', feedback = ? WHERE request_id = ? AND user_id = ?");
    $stmt->bind_param('sii', $feedback, $request_id, $user_id);
    $stmt->execute();

    // Redirect to the dashboard or another appropriate page
    header('Location: index.php');
    exit();
}

$conn->close();
?>
