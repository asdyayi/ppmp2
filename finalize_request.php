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

// Update the request to 'processing' status
$sql = "UPDATE requests SET approval_status = 'processing' WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $request_id);

if ($stmt->execute()) {
    // Fetch the request_type_id for this request
    $request_result = $conn->query("SELECT request_type_id FROM requests WHERE id = $request_id");
    $request = $request_result->fetch_assoc();
    $request_type_id = $request['request_type_id'];

    // Now fetch the users based on the request_type_id (instead of request_id)
    $users_result = $conn->query("SELECT u.email 
                                  FROM request_people rp 
                                  JOIN users u ON rp.user_id = u.id 
                                  WHERE rp.request_type = $request_type_id");
    
    // Send emails to the people involved in the request
    while ($user = $users_result->fetch_assoc()) {
        $email = $user['email'];
        mail($email, "New Request Involvement", "You have been involved in a new request. Please check your dashboard for details.");
    }

    // Set the finalized request title in the session to display it in the index.php
    $_SESSION['finalized_request_title'] = "Sample Title"; // Replace this with the actual title from the database

    // Redirect after successful processing
    echo "<script>alert('Your request form is now being processed.'); window.location.href='indexs.php';</script>";
} else {
    echo "Error finalizing request: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
