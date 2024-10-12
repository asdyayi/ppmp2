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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_id']) && isset($_POST['action'])) {
    $user_id = $_SESSION['user_id'];
    $request_id = $_POST['request_id'];
    $action = $_POST['action'];
    $status = $action == 'approve' ? 'approved' : 'declined';

    // Update the current step status
    $sql = "UPDATE approval_steps SET status = ? WHERE request_id = ? AND approver_id = ? AND status = 'pending'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $status, $request_id, $user_id);
    $stmt->execute();
    $stmt->close();

    // Check if there is a next approver
    $next_approver_sql = "SELECT * FROM approval_steps WHERE request_id = ? AND status = 'pending' ORDER BY step_order ASC LIMIT 1";
    $next_approver_stmt = $conn->prepare($next_approver_sql);
    $next_approver_stmt->bind_param("i", $request_id);
    $next_approver_stmt->execute();
    $next_approver_result = $next_approver_stmt->get_result();
    $next_approver = $next_approver_result->fetch_assoc();
    $next_approver_stmt->close();

    if (!$next_approver) {
        // If no next approver, finalize the request
        $final_status = $action == 'approve' ? 'Approved' : 'Declined';
        $sql = "UPDATE requests SET approval_status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $final_status, $request_id);
        $stmt->execute();
        $stmt->close();

        // If approved, deduct the total expenses from the total year fund
        if ($action == 'approve') {
            $sql = "UPDATE departments d 
                    JOIN requests r ON d.id = r.account_title_id 
                    JOIN account_titles at ON r.account_title_id = at.id 
                    SET d.total_year_fund = d.total_year_fund - at.total_event_cost 
                    WHERE r.id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    header('Location: index.php');
    exit();
} else {
    echo "Invalid request.";
    exit();
}

$conn->close();
?>
