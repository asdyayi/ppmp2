<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ppmp_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$email = $conn->real_escape_string($_GET['email']);
$user_result = $conn->query("SELECT id FROM users WHERE email = '$email'");
$response = ['exists' => $user_result->num_rows > 0];

$conn->close();

header('Content-Type: application/json');
echo json_encode($response);
?>
