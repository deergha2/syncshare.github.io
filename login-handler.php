<?php
session_start(); // Start the session

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
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

// Validate input
if (empty($email) || empty($password)) {
    echo "Please fill in all fields.";
    exit();
}

// Retrieve user data
$sql = "SELECT id, password_hash FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($user_id, $hashed_password);
$stmt->fetch();

// Check if user exists and password is correct
if ($stmt->num_rows > 0 && password_verify($password, $hashed_password)) {
    $_SESSION['user_id'] = $user_id;
    header("Location: user-dashboard.php"); // Redirect to user-specific page
    exit();
} else {
    echo "Invalid email or password.";
}

$stmt->close();
$conn->close();
?>
