<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit();
}

// Database connection settings
$servername = "localhost";
$username = "root";
$password = ""; // Your MySQL password
$dbname = "syncshare"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Collect and sanitize feedback
$user_id = $_SESSION['user_id'];
$feedback = isset($_POST['feedback']) ? trim($_POST['feedback']) : '';

if (!empty($feedback)) {
    $sql = "INSERT INTO feedback (user_id, feedback, created_at) VALUES (?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $feedback);
    $stmt->execute();
    $stmt->close();
    echo "Thank you for your feedback!";
} else {
    echo "Feedback cannot be empty.";
}

$conn->close();
?>
