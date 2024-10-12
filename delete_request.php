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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_id'])) {
    $request_id = intval($_POST['request_id']);
    $user_id = $_SESSION['user_id'];

    // Check if the request belongs to the user
    $check_request = $conn->query("SELECT id FROM requests WHERE id = $request_id AND user_id = $user_id");

    if ($check_request->num_rows > 0) {
        // Delete the request
        $conn->query("DELETE FROM requests WHERE id = $request_id");

        // Optionally, delete associated approval steps or other related records
        $conn->query("DELETE FROM approval_steps WHERE request_id = $request_id");

        echo "<script>alert('Request successfully deleted.'); window.location.href='index.php';</script>";
    } else {
        echo "<script>alert('Request not found or you do not have permission to delete this request.'); window.location.href='index.php';</script>";
    }
}

$conn->close();
?>
