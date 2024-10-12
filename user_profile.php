<?php
// Check if session is started and start if not
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "ppmp_db";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $user_id = $_SESSION['user_id'];
    $sql = "SELECT users.firstname, users.lastname, users.profile_image, departments.name AS department_name 
            FROM users 
            JOIN departments ON users.department_id = departments.id 
            WHERE users.id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    if (!$result) {
        die("Get result failed: " . $stmt->error);
    }

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $firstname = htmlspecialchars($row['firstname']);
        $lastname = htmlspecialchars($row['lastname']);
        $departmentName = htmlspecialchars($row['department_name']);
        $profileImage = htmlspecialchars($row['profile_image']);

        $profileImageUrl = $profileImage ? 'uploads/' . $profileImage : 'uploads/default-profile.png';

        echo "<div class='profile-info'>";
        echo "<img src='" . $profileImageUrl . "' alt='Profile Image' class='profile-image'>";
        echo "<div class='profile-details'>";
        echo "<span class='profile-name'>" . $firstname . " " . $lastname . "</span>";
        echo "<span class='profile-department'> " . $departmentName . "</span>";
        echo "</div>";
        echo "</div>";
    } else {
        echo "<span>Error: User not found or multiple users found.</span>";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "<span>Error: User not logged in.</span>";
}
?>

<style>
.profile-info {
    display: flex;
    align-items: center;
    margin: 20px;
}

.profile-info img {
    border-radius: 50%;
    width: 60px;
    height: 60px;
    margin-right: 15px;
}

.profile-details {
    display: flex;
    flex-direction: column;
}

.profile-name {
    font-size: 20px;
    color: #153860;
    font-weight: bold;
}

.profile-department {
    font-size: 18px;
    color: #666;
}

.user-profile {
    display: flex;
    align-items: center;
    gap: 10px;
}
</style>
