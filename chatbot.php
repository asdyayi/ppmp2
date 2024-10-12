<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "chatbotDB";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$messages = [];

if (!isset($_SESSION['context'])) {
    $_SESSION['context'] = array();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = $_POST['message'];
    $sender = 'user';

    $stmt = $conn->prepare("INSERT INTO messages (user_id, sender, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $sender, $message);
    $stmt->execute();
    $stmt->close();

    $bot_response = findResponse($conn, $message, $_SESSION['context']);

    if (empty($bot_response)) {
        $bot_response = "Sorry, I seem to not understand.";
    }

    $sender = 'bot';
    $stmt = $conn->prepare("INSERT INTO messages (user_id, sender, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $sender, $bot_response);
    $stmt->execute();
    $stmt->close();
}

function findResponse($conn, $question, &$context) {
  $lowered_question = strtolower($question);

  if (isset($context['awaiting_info'])) {
      if ($context['awaiting_info'] === 'dean_name') {
          // Handle dean-related queries
          $stmt = $conn->prepare("SELECT name, department FROM deans WHERE name LIKE ?");
          $search_name = "%$question%";
          $stmt->bind_param("s", $search_name);
          $stmt->execute();
          $stmt->bind_result($name, $department);

          if ($stmt->fetch()) {
              $response = "Dean $name is the head of the $department department.";
              $stmt->close();
              $context['awaiting_info'] = null;
              return $response;
          }
          $stmt->close();

          $stmt = $conn->prepare("SELECT name FROM deans WHERE department LIKE ?");
          $search_department = "%$question%";
          $stmt->bind_param("s", $search_department);
          $stmt->execute();
          $stmt->bind_result($name);

          if ($stmt->fetch()) {
              $response = "The dean of the $question department is Dean $name.";
              $stmt->close();
              $context['awaiting_info'] = null; 
              return $response;
          }
          $stmt->close();

          return "I'm not sure which dean or department you're referring to. Could you provide more details?";
      }

      if ($context['awaiting_info'] === 'department_name') {
          $stmt = $conn->prepare("SELECT d.department, de.name FROM deans d INNER JOIN deans de ON d.department = de.department WHERE d.department LIKE ?");
          $search_name = "%$question%";
          $stmt->bind_param("s", $search_name);
          $stmt->execute();
          $stmt->bind_result($department, $dean);

          if ($stmt->fetch()) {
              $response = "The department you referred to is $department, and the dean of this department is Dean $dean.";
              $stmt->close();
              $context['awaiting_info'] = null;
              return $response;
          }
          $stmt->close();

          return "I couldn’t find the department you’re looking for. Can you provide more details?";
      }
  }

  if (strpos($lowered_question, 'help') !== false || strpos($lowered_question, 'can you') !== false) {
      return "I can assist with information about deans, departments, and general queries. What would you like to know?";
  }

  if (strpos($lowered_question, 'dean') !== false) {
      $context['awaiting_info'] = 'dean_name';
      return "Which dean are you referring to? Could you specify the name or department?";
  }

  if (strpos($lowered_question, 'department') !== false || strpos($lowered_question, 'departments') !== false) {
      $context['awaiting_info'] = 'department_name';
      return "Which department are you asking about? Please provide the name or some details.";
  }

  $stmt = $conn->prepare("SELECT question, response FROM responses");
  $stmt->execute();
  $stmt->bind_result($db_question, $response);

  $best_match = null;
  $best_similarity = 0;

  while ($stmt->fetch()) {
      similar_text($lowered_question, strtolower($db_question), $similarity);
      if ($similarity > $best_similarity) {
          $best_similarity = $similarity;
          $best_match = $response;
      }
  }

  $stmt->close();
  return $best_match ?: "Sorry, I seem to not understand.";
}

if (isset($_GET['delete_all'])) {
    $stmt = $conn->prepare("DELETE FROM messages WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: chatbot.php");
    exit();
}

$stmt = $conn->prepare("SELECT id, sender, message, timestamp FROM messages WHERE user_id = ? ORDER BY timestamp ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
} else {
    echo "Error retrieving messages: " . $conn->error;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="shortcut icon" type="x-icon" href="img/logo.png">
<link rel="stylesheet" href="style2.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<title>Chatbot</title>
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
  #chatbot {
    padding: 10px;
    margin-top: 30px;
    width: 100%;
    margin-left: 100px;
  }
  #chatbox {
    height: 500px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
  }
  #chatlogs {
    margin-bottom: 10px;
  }
  .chat {
    padding: 10px;
    margin: 5px 0;
    max-width: 60%;
    border-radius: 10px;
  }
  .chat.user {
    background-color: #e0e0e0;
    text-align: right;
    margin-left: auto;
  }
  .chat.bot {
    background-color: #f1f1f1;
    text-align: left;
    margin-right: auto;
  }
  .delete-all-btn {
    position: fixed;
    top: 100px;
    right: 10px;
    display: inline-block;
    padding: 10px;
    color: #666;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    text-decoration: none;
  }
  .delete-all-btn:hover {
    color: #f00;
  }
  form {
    display: flex;
    align-items: center;
    margin-top: 10px;
  }
  form input[type="text"] {
    flex: 1;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 16px;
  }
  form button[type="submit"] {
    margin-left: 10px;
    padding: 10px 20px;
    background-color: #153860;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
  }
  form button[type="submit"]:hover {
    background-color: #45a049;
  }
  #micButton {
    background-color: #007bff;
    color: white;
    padding: 10px;
    border-radius: 5px;
    margin-left: 10px;
    cursor: pointer;
  }
  #micButton:hover {
    background-color: #0056b3;
  }
</style>
</head>
<body>

<div class="nav">
  <a href="dashboard.php" onclick="toggleActive(event, this)"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
  <a href="create1.php" onclick="toggleActive(event, this)"><i class="fas fa-plus-circle"></i> Create Tracker</a>
  <a href="createPPMP.php" onclick="toggleActive(event, this)"><i class="fas fa-plus"></i> Create PPMP</a>
  <a href="history.php" onclick="toggleActive(event, this)"><i class="fas fa-history"></i> History</a>
  <a href="chatbot.php" onclick="toggleActive(event, this)" class="active"><i class="fas fa-comments"></i> Chatbot</a>
  <a href="settings.php" class="settings" onclick="toggleActive(event, this)"><i class="fas fa-cog"></i> Settings</a>
  <a href="#" class="logout" id="logout-link" onclick="toggleActive(event, this)"><i class="fas fa-sign-out-alt"></i> Log out</a>
</div>

<!-- LOG OUT  -->
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
</div>

<div class="content">
  <div id="chatbot">
    <div id="chatbox">
      <div id="chatlogs">
        <?php foreach ($messages as $message): ?>
          <div class="chat <?= $message['sender'] ?>">
            <?= $message['sender'] === 'user' ? 'You' : '<i class="fas fa-robot"></i>' ?>: <?= htmlspecialchars($message['message']) ?>
          </div>
        <?php endforeach; ?>
      </div>
      <a href="chatbot.php?delete_all=true" class="delete-all-btn" onclick="return confirm('Are you sure you want to delete all chat history?')">
        <i class="fas fa-trash"></i> Delete All
      </a>
    </div>
    <form method="post" action="chatbot.php">
      <input type="text" id="messageInput" name="message" placeholder="Type your message here..." required>
      <button type="submit">Send</button>
      <button id="micButton" type="button"><i class="fas fa-microphone"></i></button>
    </form>
  </div>
</div>

<script>
const recognition = new webkitSpeechRecognition();
recognition.continuous = false;
recognition.interimResults = false;

recognition.onresult = function(event) {
    const userMessage = event.results[0][0].transcript;
    document.querySelector('input[name="message"]').value = userMessage;
    document.querySelector('form').submit();
};

recognition.onerror = function(event) {
    if (event.error === 'no-speech') {
        const errorMessage = "No speech detected. Please try again.";
        const chatbox = document.querySelector('#chatbox');
        const messageDiv = document.createElement('div');
        messageDiv.classList.add('chat', 'bot');
        messageDiv.innerHTML = `<i class="fas fa-robot"></i> ${errorMessage}`;
        chatbox.appendChild(messageDiv);
        chatbox.scrollTop = chatbox.scrollHeight;
    }
};

document.getElementById('micButton').addEventListener('click', function() {
    recognition.start();
});

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('messageInput').focus();
  });

  function focusOnInput() {
    document.getElementById('messageInput').focus();
  }

  document.querySelector('form').addEventListener('submit', function(event) {
    setTimeout(focusOnInput, 100);
  });

  function scrollToBottom() {
    var chatbox = document.getElementById('chatbox');
    chatbox.scrollTop = chatbox.scrollHeight;
  }

  window.onload = scrollToBottom;

  document.querySelector('form').addEventListener('submit', function() {
    setTimeout(scrollToBottom, 100); 
  });

</script>

<!-- LOG OUT -->
<div id="logoutModal" class="modal">
  <div class="modal-content">
    <p>Are you sure you want to log out?</p>
    <button id="yesBtn">Yes</button>
    <button id="noBtn">No</button>
  </div>
</div>
<script src="scripts.js"></script>

</body>
</html>
