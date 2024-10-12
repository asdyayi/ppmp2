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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $document_title = $_POST['document_title'];
    $request_type_id = $_POST['request_type_id'];
    $account_title_id = $_POST['account_title_id'];
    $description = $_POST['description'];

    // Insert the request
    $sql = "INSERT INTO requests (user_id, document_title, request_type_id, account_title_id, description, approval_status) VALUES (?, ?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isdds", $user_id, $document_title, $request_type_id, $account_title_id, $description);
    $stmt->execute();
    $request_id = $stmt->insert_id;
    $stmt->close();

    // Insert the approval steps (Jeri -> Sophia)
    $approval_steps = [
        ['approver_id' => 2, 'step_order' => 1], // Jeri
        ['approver_id' => 3, 'step_order' => 2]  // Sophia
    ];

    foreach ($approval_steps as $step) {
        $sql = "INSERT INTO approval_steps (request_id, approver_id, step_order, status) VALUES (?, ?, ?, 'pending')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $request_id, $step['approver_id'], $step['step_order']);
        $stmt->execute();
        $stmt->close();
    }

    echo "<script>alert('Request created successfully!'); window.location.href='index.php';</script>";
}

$conn->close();
?>
