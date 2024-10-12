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
$tracking_number = "TRK-" . uniqid();

// Update request tracking number
$stmt = $conn->prepare("UPDATE requests SET tracking_number = ? WHERE id = ?");
$stmt->bind_param("si", $tracking_number, $request_id);
if (!$stmt->execute()) {
    die("Error executing update: " . $stmt->error);
}
$stmt->close();

// Fetch request details
$stmt = $conn->prepare("SELECT r.document_title, r.tracking_number, rt.name AS request_type, at.title AS account_title, r.request_type_id, CAST(r.description AS CHAR) AS description 
                        FROM requests r 
                        JOIN request_types rt ON r.request_type_id = rt.id 
                        JOIN account_titles at ON r.account_title_id = at.id 
                        WHERE r.id = ?");
$stmt->bind_param("i", $request_id);
if (!$stmt->execute()) {
    die("Error executing select request: " . $stmt->error);
}
$request_result = $stmt->get_result();
$request = $request_result->fetch_assoc();
$stmt->close();

// Fetch people related to the request
$stmt = $conn->prepare("SELECT u.id, u.firstname, u.lastname, u.email, u.profile_image
                        FROM users u
                        JOIN request_people rp ON u.id = rp.user_id
                        JOIN requests r ON rp.request_type = r.request_type_id
                        WHERE r.id = ?");
$stmt->bind_param("i", $request_id);
$stmt->execute();

$people_result = $stmt->get_result();
if ($people_result === false) {
    die("Error fetching people: " . $conn->error);
}
$stmt->close();

// Fetch expenses related to the request
$expenses_stmt = $conn->prepare("SELECT particular, quantity, unit_cost, total_estimated FROM expenses WHERE request_id = ?");
$expenses_stmt->bind_param("i", $request_id);
if (!$expenses_stmt->execute()) {
    die("Error executing select expenses: " . $expenses_stmt->error);
}
$expenses_result = $expenses_stmt->get_result();
$expenses_stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Request Summary</title>
    <link rel="stylesheet" href="style2.css">
    <link rel="stylesheet" href="styles/summary.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .signatory-path {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 20px;
        }

        .signatory {
            text-align: center;
            margin: 0 10px;
        }

        .signatory img {
            border-radius: 50%;
            width: 80px;
            height: 80px;
            object-fit: cover;
        }

        .signatory h4 {
            margin-top: 10px;
            font-size: 16px;
        }

        .signatory p {
            margin-top: 5px;
            font-size: 14px;
        }

        .arrow {
            font-size: 24px;
            margin: 0 10px;
        }
    </style>
    <script>
        function finalizeRequest() {
            window.location.href = 'finalize_request.php';
        }
    </script>
</head>
<body>

<div class="nav">
  <a href="dashboard.php" onclick="toggleActive(event, this)" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
  <a href="create1.php" onclick="toggleActive(event, this)"><i class="fas fa-plus-circle"></i> Create Tracker</a>
  <a href="createPPMP.php" onclick="toggleActive(event, this)"><i class="fas fa-plus"></i> Create PPMP</a>
  <a href="history.php" onclick="toggleActive(event, this)"><i class="fas fa-history"></i> History</a>
  <a href="chatbot.php" onclick="toggleActive(event, this)"><i class="fas fa-comments"></i> Chatbot</a>
  <a href="settings.php" class="settings" onclick="toggleActive(event, this)"><i class="fas fa-cog"></i> Settings</a>
  <a href="#" class="logout" id="logout-link" onclick="toggleActive(event, this)"><i class="fas fa-sign-out-alt"></i> Log out</a>
</div>

<div id="logoutModal" class="modal">
  <div class="modal-content">
    <p>Are you sure you want to log out?</p>
    <button id="yesBtn">Yes</button>
    <button id="noBtn">No</button>
  </div>
</div>

<div class="header">
  <img src="img/logo.png" class="logo">
  <h1>Admin <span id="datetime"></span></h1>
  <div class="user-profile">
    <i class="fas fa-bell notification-icon" onclick="toggleNotificationDropdown()"></i>
    <div class="vertical-line"></div>
    <?php include 'user_profile.php'; ?>
  </div>
</div>

<div class="content">
  <div class="all">
    <div class="image-row">
      <img src="img/info.png" alt="Image 1"> <hr>
      <img src="img/peso.png" alt="Image 2" class="transparent"> <hr>
      <img src="img/people.png" alt="Image 3" class="transparent"> <hr>
      <img src="img/process.png" alt="Image 4" class="transparent"> 
    </div>
    <div class="info">
      <h2> SUMMARY </h2>
    </div>  

    <div class="summary">
      <table>
        <tr>
          <th>Tracking Number</th>
          <td><?php echo strtoupper(htmlspecialchars($request['tracking_number'])); ?></td>
        </tr>
        <tr>
          <th>Document Title</th>
          <td><?php echo htmlspecialchars($request['document_title']); ?></td>
        </tr>
        <tr>
          <th>Type of Request</th>
          <td><?php echo htmlspecialchars($request['request_type']); ?></td>
        </tr>
        <tr>
          <th>Account Title</th>
          <td><?php echo htmlspecialchars($request['account_title']); ?></td>
        </tr>
        <tr>
          <th>Description</th>
          <td><?php echo htmlspecialchars($request['description']); ?></td>
        </tr>
      </table>

      <h2>Signatory Path</h2>
      <div class="signatory-path">
        <?php while ($row = $people_result->fetch_assoc()): ?>
          <div class="signatory">
            <img src="<?php echo htmlspecialchars($row['profile_image']) ? 'uploads/' . htmlspecialchars($row['profile_image']) : 'uploads/default.png'; ?>" alt="<?php echo htmlspecialchars($row['firstname']) . ' ' . htmlspecialchars($row['lastname']); ?>">
            <h4><?php echo htmlspecialchars($row['firstname']) . ' ' . htmlspecialchars($row['lastname']); ?></h4>
            <p><?php echo htmlspecialchars($row['email']); ?></p>
          </div>
          <div class="arrow">&rarr;</div>
        <?php endwhile; ?>
      </div>

      <h2>Expenses</h2>
      <div class="summary-table">
        <table>
          <thead>
            <tr>
              <th>Particular</th>
              <th>Quantity</th>
              <th>Unit Cost</th>
              <th>Total Estimated</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = $expenses_result->fetch_assoc()): ?>
            <tr>
              <td><?php echo htmlspecialchars($row['particular']); ?></td>
              <td><?php echo htmlspecialchars($row['quantity']); ?></td>
              <td><?php echo htmlspecialchars($row['unit_cost']); ?></td>
              <td><?php echo htmlspecialchars($row['total_estimated']); ?></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>

      <br><br>
      <button onclick="finalizeRequest()" class="btnsub">Finalize Request</button>
    </div>
  </div>
</div>

</body>
</html>
