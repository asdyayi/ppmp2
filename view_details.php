<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: admin_login.php');
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

// Retrieve the PPMP submission ID from the URL
$ppmp_submission_id = $_GET['ppmp_submission_id'];

// Fetch PPMP details
$ppmp_result = $conn->query("
    SELECT at.title, at.total_event_cost, DATE_FORMAT(at.schedule, '%M %d, %Y') AS event_date, s.subtitle, i.item, i.quantity_size, i.unit_cost, i.total_cost 
    FROM account_titles at
    JOIN subtitles s ON at.id = s.account_title_id
    JOIN items i ON s.id = i.subtitle_id
    WHERE at.ppmp_submission_id = $ppmp_submission_id
");

if ($ppmp_result->num_rows > 0): ?>
    <h2>PPMP Details</h2>
    <table>
        <tr>
            <th>Title</th>
            <th>Subtitle</th>
            <th>Item</th>
            <th>Quantity</th>
            <th>Unit Cost</th>
            <th>Total Cost</th>
            <th>Scheduled Date</th>
        </tr>
        <?php while ($row = $ppmp_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['title']); ?></td>
                <td><?php echo htmlspecialchars($row['subtitle']); ?></td>
                <td><?php echo htmlspecialchars($row['item']); ?></td>
                <td><?php echo htmlspecialchars($row['quantity_size']); ?></td>
                <td><?php echo number_format($row['unit_cost'], 2); ?></td>
                <td><?php echo number_format($row['total_cost'], 2); ?></td>
                <td><?php echo htmlspecialchars($row['event_date']); ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
<?php else: ?>
    <p>No details available for this PPMP.</p>
<?php endif;

$conn->close();
?>
