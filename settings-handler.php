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

// Collect and sanitize input data
$user_id = $_SESSION['user_id'];
$new_email = isset($_POST['email']) ? trim($_POST['email']) : '';
$new_password = isset($_POST['password']) ? trim($_POST['password']) : '';

// Update email and password if provided
if (!empty($new_email)) {
    $sql = "UPDATE users SET email = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $new_email, $user_id);
    $stmt->execute();
    $stmt->close();
}

if (!empty($new_password)) {
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
    $sql = "UPDATE users SET password = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $hashed_password, $user_id);
    $stmt->execute();
    $stmt->close();
}

$conn->close();
echo "Settings updated successfully!";
?>
