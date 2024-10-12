<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ppmp_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $department = $_POST['department'];
    $admin_password = $_POST['admin_password'];

    // Add department if not exists
    $sql = "INSERT INTO departments (name) VALUES ('$department') ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)";
    $conn->query($sql);
    $department_id = $conn->insert_id;

    // Hash the password before storing it
    $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

    // Add user with admin privileges
    $sql = "INSERT INTO users (firstname, lastname, email, department_id, is_admin, password) VALUES ('$firstname', '$lastname', '$email', $department_id, TRUE, '$hashed_password')";
    if ($conn->query($sql) === TRUE) {
        echo "Admin user created successfully";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Admin</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Create Admin User</h1>
    <form action="create_admin.php" method="post">
        <input type="text" name="firstname" placeholder="First Name" required>
        <input type="text" name="lastname" placeholder="Last Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="text" name="department" placeholder="Department" required>
        <input type="password" name="admin_password" placeholder="Password" required>
        <button type="submit">Create Admin</button>
    </form>
</body>
</html>
