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

if (!isset($_GET['request_id']) || !is_numeric($_GET['request_id'])) {
    echo "<p>Invalid request ID</p>";
    exit();
}

$request_id = intval($_GET['request_id']);

// Fetch request details
$sql = "SELECT r.document_title, 
               COALESCE(rt.name, 'N/A') AS request_type, 
               COALESCE(at.title, 'N/A') AS account_title, 
               r.description, 
               r.tracking_number
        FROM requests r
        LEFT JOIN request_types rt ON r.request_type_id = rt.id
        LEFT JOIN account_titles at ON r.account_title_id = at.id
        WHERE r.id = $request_id";

$request_result = $conn->query($sql);

if ($request_result === false) {
    echo "<p>SQL Error: " . $conn->error . "</p>";
    exit();
}

if ($request_result->num_rows > 0) {
    $request = $request_result->fetch_assoc();

    echo "<div>
            <h2>Request Details</h2>
            <p>Document Title: " . htmlspecialchars($request['document_title']) . "</p>
            <p>Request Type: " . htmlspecialchars($request['request_type']) . "</p>
            <p>Account Title: " . htmlspecialchars($request['account_title']) . "</p>
            <p>Description: " . htmlspecialchars($request['description']) . "</p>
            <p>Tracking Number: " . htmlspecialchars($request['tracking_number']) . "</p>
          </div>";
          
    // Expenses section
    $expenses_result = $conn->query("SELECT particular, quantity, unit_cost, (quantity * unit_cost) AS total_cost 
                                     FROM expenses 
                                     WHERE request_id = $request_id");

    echo "<h2>Expenses</h2>";
    if ($expenses_result->num_rows > 0) {
        echo "<table>
                <tr>
                    <th>Particular</th>
                    <th>Quantity</th>
                    <th>Unit Cost</th>
                    <th>Total Estimated</th>
                </tr>";
        while ($expense = $expenses_result->fetch_assoc()) {
            echo "<tr>
                    <td>" . htmlspecialchars($expense['particular']) . "</td>
                    <td>" . htmlspecialchars($expense['quantity']) . "</td>
                    <td>" . htmlspecialchars($expense['unit_cost']) . "</td>
                    <td>" . htmlspecialchars($expense['total_cost']) . "</td>
                  </tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No expenses found</p>";
    }

    // People involved section
    $people_result = $conn->query("SELECT u.firstname, u.lastname 
                                   FROM request_people rp
                                   JOIN users u ON rp.user_id = u.id
                                   WHERE rp.request_type = $request_id");

    echo "<h2>People Involved</h2>";
    if ($people_result->num_rows > 0) {
        while ($person = $people_result->fetch_assoc()) {
            echo "<p>" . htmlspecialchars($person['firstname']) . " " . htmlspecialchars($person['lastname']) . "</p>";
        }
    } else {
        echo "<p>No people involved</p>";
    }
} else {
    echo "<p>No request found for the provided ID</p>";
}

$conn->close();
?>
