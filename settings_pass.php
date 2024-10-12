<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ppmp_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 1) {
    $row = $result->fetch_assoc();
    $userEmail = $row['email'];
    $_SESSION['email'] = $userEmail; // Store email in session
} else {
    die("User not found.");
}

$stmt->close();
$conn->close();

$generatedOTP = rand(1000, 9999);
$_SESSION['generated_otp'] = $generatedOTP;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $newPassword = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $newPassword, $user_id);

    if ($stmt->execute()) {
        $_SESSION['password_reset_success'] = true;
        header('Location: index.php');
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="shortcut icon" type="x-icon" href="img/logo.png">
<link rel="stylesheet" href="style2.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<script type="text/javascript" src="https://cdn.emailjs.com/dist/email.min.js"></script>
<script type="text/javascript">
  (function() {
    emailjs.init("mcNkkDqtmS4UMDEZ4");
  })();
</script>
<title>Reset Password</title>
<style>
  .content {
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding-top: 20px;
    flex-direction: column;
    width: 80%;
    margin: 0 auto;
  }
  .tracker {
    display: flex;
    align-items: center;
    padding: 20px;
  }
  h2 {
    font-family: 'bold';
    color: #153860;
    font-size: 25px;
  }
  .input-box {
    display: flex;
    align-items: center;
    margin-left: 20px;
  }
  .rounded-input {
    border-radius: 15px;
    padding: 10px;
    border: 1px solid #ccc;
    margin-right: 10px;
  }
  .button-image {
    width: 40px;
    height: 40px;
    cursor: pointer;
  }
  .underline {
    position: absolute;
    width: 21%;
    height: 1px;
    background-color: black;
    margin-top: -11px;
    margin-left: -10px;
  }
  .pending {
    position: absolute;
    top: 170px;
    left: 270px;
    z-index: 2;
    width: 53%;
    height: 30%;
    padding: 20px;
  }
  h3 {
    color: #153860;
    font-size: 28px;
    font-family: 'bold';
  }
  .details {
    background-color: rgba(255, 255, 255, 0.76);
    padding: 20px;
    height: 90px;
    border-radius: 25px;
  }
  .fund {
    position: absolute;
    top: 170px;
    right: 40px;
    z-index: 3;
    width: 23%;
    height: 30%;
    padding: 20px;
  }
  .cfund {
    background-color: rgba(255, 255, 255, 0.76);
    padding: 20px;
    height: 150px;
    border-radius: 25px;
  }
  .vertical-line {
    width: 2px;
    height: 70px;
    background-color: #000000;
    margin: 0 10px;
  }
  .notification-icon {
    font-size: 35px;
    cursor: pointer;
  }
  .reset-password-container {
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    width: 80%;
  }
  .reset-password-container input {
    width: 100%;
    padding: 15px;
    border-radius: 5px;
    border: none;
    margin-bottom: 20px;
  }
  .requirements {
    text-align: left;
  }
  .requirements p {
    margin: 0;
    font-size: 17px;
    color: #333;
    font-family: 'Sans';
  }
  .requirements i {
    color: #0e76a8;
  }
  .valid i {
    color: #005990;
  }
  #verification-modal {
    height: 200px;
  }
  .verification-container {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    background-color: white;
    padding: 20px;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 1000;
    width: 30%;
    box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
  }
  .verification-container h2 {
    margin-bottom: 10px;
    font-size: 22px;
    color: #153860;
  }
  .verification-code {
    display: flex;
    width: 100%;
    margin-bottom: 20px;
    margin-left: 100px;
  }
  .verification-code input {
    width: 13%;
    padding: 10px;
    text-align: center;
    border: 1px solid #ccc;
    font-size: 20px;
    background-color: #c6d3e3;
    margin-right: 10px;
  }
  .verification-buttons {
    display: flex;
    justify-content: space-between;
    width: 100%;
  }
  .verification-buttons button {
    padding: 10px 20px;
    margin: 10px;
    border: none;
    border-radius: 25px;
    cursor: pointer;
    font-size: 16px;
  }
  .confirm-btn, .resend-btn {
    background-color: #0e76a8;
    color: white;
  }
  .modal-backdrop {
    display: block;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 999;
  }
  .pass {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100%;
    width: 100%;
    margin-left: 100px;
  }
  #password {
    background-color: #c6d3e3;
  }
  .p {
    font-size: 18px;
  }
  .done-btn {
    background-color: #0e76a8;
    color: white;
    border: none;
    border-radius: 25px;
    padding: 10px 20px;
    font-size: 16px;
    cursor: pointer;
    margin-top: 10px;
    transition: background-color 0.3s;
  }
  .done-btn:disabled {
    background-color: #aaa;
    cursor: not-allowed;
  }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
  var userEmail = '<?php echo $_SESSION['email']; ?>';
  var generatedOTP = '<?php echo $generatedOTP; ?>';

  sendOTP(userEmail, generatedOTP);
});

function validatePassword() {
  var password = document.getElementById("password").value;
  var length = document.getElementById("length");
  var number = document.getElementById("number");
  var doneBtn = document.getElementById("doneBtn");

  if (password.length >= 6) {
    length.classList.add("valid");
    length.classList.remove("invalid");
  } else {
    length.classList.add("invalid");
    length.classList.remove("valid");
  }

  if (/\d/.test(password)) {
    number.classList.add("valid");
    number.classList.remove("invalid");
  } else {
    number.classList.add("invalid");
    number.classList.remove("valid");
  }

  if (password.length >= 6 && /\d/.test(password)) {
    doneBtn.disabled = false;
  } else {
    doneBtn.disabled = true;
  }
}

function showVerificationModal() {
  document.getElementById("verificationModal").style.display = "flex";
  document.getElementById("modalBackdrop").style.display = "block";
}

function sendOTP(email, otp) {
  emailjs.send("service_89o715c", "template_2wjdjo8", {
    to_email: email,
    otp: otp
  }).then(function(response) {
    console.log('OTP sent successfully!', response.status, response.text);
    showVerificationModal();
  }, function(error) {
    console.error('Failed to send OTP', error);
  });
}

function verifyOTP() {
  let enteredOTP = Array.from(document.querySelectorAll('.verification-code input')).map(input => input.value).join('');
  let generatedOTP = '<?php echo $_SESSION['generated_otp']; ?>';
  if (enteredOTP == generatedOTP) {
    alert("OTP verified successfully!");
    document.getElementById("verificationModal").style.display = "none";
    document.getElementById("modalBackdrop").style.display = "none";
    document.getElementById("password").disabled = false;
    document.getElementById("doneBtn").disabled = false;
  } else {
    alert("Invalid OTP. Please try again.");
  }
}
</script>
</head>
<body>

<div class="nav">
  <a href="dashboard.php" onclick="toggleActive(event, this)" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
  <a href="request_form.php" onclick="toggleActive(event, this)"><i class="fas fa-plus-circle"></i> Create Tracker</a>
  <a href="add_account_title.php" onclick="toggleActive(event, this)"><i class="fas fa-plus"></i> Create PPMP</a>
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
  <h1>PPA <span id="datetime"></span></h1>
  <div class="user-profile">
    <i class="fas fa-bell notification-icon" onclick="toggleNotificationDropdown()"></i>
    <div class="vertical-line"></div>
    <?php include 'user_profile.php'; ?>
  </div>
</div>

<div class="content">
  <div class="pass">
    <div class="reset-password-container">
      <h2>Reset Your Password</h2>
      <p>Please enter your new password</p>
      <form method="POST" action="">
        <input type="password" id="password" name="password" placeholder="New Password" oninput="validatePassword()" disabled>
        <div class="requirements">
          <p id="length" class="invalid"><i class="fas fa-check-circle"></i> At least 6 characters</p>
          <p id="number" class="invalid"><i class="fas fa-check-circle"></i> Contain a number</p>
        </div>
        <button class="done-btn" id="doneBtn" disabled>DONE</button>
      </form>
    </div>

    <div id="verificationModal" class="verification-container" style="display: none;">
      <h2>ENTER VERIFICATION NUMBER</h2>
      <p>We sent you an email</p>
      <div class="verification-code">
        <input type="number" maxlength="1">
        <input type="number" maxlength="1">
        <input type="number" maxlength="1">
        <input type="number" maxlength="1">
      </div>
      <div class="verification-buttons">
        <button class="confirm-btn" onclick="verifyOTP()">CONFIRM</button>
        <button class="resend-btn" onclick="sendOTP('<?php echo $_SESSION['email']; ?>', '<?php echo $generatedOTP; ?>')">RESEND</button>
      </div>
    </div>
    <div id="modalBackdrop" class="modal-backdrop" style="display: none;"></div>
  </div>
</div>

</body>
</html>
