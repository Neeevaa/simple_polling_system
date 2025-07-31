<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "polling_system_db";

$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->query("CREATE DATABASE IF NOT EXISTS $dbname");
$conn->select_db($dbname);

echo "Database '$dbname' selected successfully.<br>";

$sql_users_table = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql_users_table) === TRUE) {
    echo "Table 'users' is ready.<br>";
} else { die("Error creating users table: " . $conn->error); }

$sql_polls_table = "CREATE TABLE IF NOT EXISTS polls (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    question TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql_polls_table) === TRUE) {
    echo "Table 'polls' is ready.<br>";
} else { die("Error creating polls table: " . $conn->error); }

$sql_votes_table = "CREATE TABLE IF NOT EXISTS poll_votes (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    poll_id INT(11) NOT NULL,
    choice VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    voted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_poll_vote (user_id, poll_id)
)";
if ($conn->query($sql_votes_table) === TRUE) {
    echo "Table 'poll_votes' is ready.<br>";
} else { die("Error creating poll_votes table: " . $conn->error); }

/* questions are referenced here using the id*/
$polls = [
    1 => "Which is the best programming language to code in?",
    2 => "Which is the most used Operating System?",
    3 => "Which social media app do you use the most?"
];

$stmt = $conn->prepare("INSERT INTO polls (id, question) VALUES (?, ?) ON DUPLICATE KEY UPDATE question=VALUES(question)");

foreach ($polls as $id => $question) {
    $stmt->bind_param("is", $id, $question);
    $stmt->execute();
}
echo "Poll questions have been inserted/updated.<br>";
$stmt->close();

echo "<h2>Database setup is complete!</h2>";
$conn->close();
?>