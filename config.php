<?php
$jawsdb_url = getenv('https://mysql.jawsdb.com/resource/dashboard');

// Parse the JawsDB URL to get connection components
$dbparts = parse_url($jawsdb_url);

$hostname = $dbparts['g9fej9rujq0yt0cd.cbetxkdyhwsb.us-east-1.rds.amazonaws.com	'];     // Database Hostname
$username = $dbparts['ml6qmkew0vgj894u'];     // Database Username
$database1 = 'chatbotdb';  // First database name
$database2 = 'ppmp_db'; // Second database name

// Establish connection using PDO (you can also use MySQLi if preferred)
try {
    $pdo = new PDO("mysql:host=$hostname;dbname=$database", $username, $password);
    // Set PDO error mode to exception to handle errors more clearly
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected successfully to JawsDB MySQL!";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
