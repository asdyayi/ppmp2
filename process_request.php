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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process']) && $_POST['process'] == '1') {
    $user_id = $_SESSION['user_id'];
    $document_title = $_POST['document_title'];
    $request_type_id = $_POST['request_type'];
    $account_title_id = $_POST['account_title'];
    $description = $_POST['description'];

    // Handle file uploads
    $uploaded_files = [];
    for ($i = 0; $i < 3; $i++) {
        if (isset($_FILES['files']['name'][$i]) && $_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
            $target_dir = "uploads/";
            $target_file = $target_dir . basename($_FILES['files']['name'][$i]);
            move_uploaded_file($_FILES['files']['tmp_name'][$i], $target_file);
            $uploaded_files[] = $target_file;
        } else {
            $uploaded_files[] = null;
        }
    }

    // Ensure there are three file slots, even if some are null
    while (count($uploaded_files) < 3) {
        $uploaded_files[] = null;
    }

    // Insert the request with 'approved' set to 0 (pending)
    $sql = "INSERT INTO requests (user_id, document_title, request_type_id, account_title_id, description, file1, file2, file3, approved) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isiiisss", $user_id, $document_title, $request_type_id, $account_title_id, $description, $uploaded_files[0], $uploaded_files[1], $uploaded_files[2]);
    $stmt->execute();
    $request_id = $stmt->insert_id;
    $stmt->close();

    // Fetch approvers from the same department
    $approvers = [];
    $approver_result = $conn->query("SELECT id FROM users WHERE department_id = (SELECT department_id FROM users WHERE id = $user_id)");
    if ($approver_result) {
        $step_order = 1;
        while ($approver = $approver_result->fetch_assoc()) {
            $approvers[] = ['approver_id' => $approver['id'], 'step_order' => $step_order];
            $step_order++;
        }
    }

    // Insert approval steps with 'pending' status
    foreach ($approvers as $approver) {
        $sql = "INSERT INTO approval_steps (request_id, approver_id, step_order, status) VALUES (?, ?, ?, 'pending')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $request_id, $approver['approver_id'], $approver['step_order']);
        $stmt->execute();
        $stmt->close();
    }

    // Store request ID in session and redirect to expenses page
    $_SESSION['request_id'] = $request_id;
    header('Location: expenses.php');
    exit();
} else {
    echo "Invalid request.";
    exit();
}

$conn->close();
?>
