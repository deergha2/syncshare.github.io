<?php
// Database connection settings
$servername = "localhost";
$db_username = "root"; // Changed variable name to avoid conflict with form username
$db_password = ""; // Your MySQL password
$dbname = "syncshare"; // Your database name

// Create connection
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Collect and sanitize input data
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$terms = isset($_POST['terms']); // Checkbox value

// Validate input
if (empty($email) || empty($password) || empty($username) || !$terms) {
    echo "Please fill in all fields and agree to the terms.";
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "Invalid email format.";
    exit();
}

// Check if email already exists
$sql = "SELECT id FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo "Email already exists.";
    $stmt->close();
    $conn->close();
    exit();
}

$stmt->close(); // Close the previous statement

// Check if username already exists
$sql = "SELECT id FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo "Username already exists.";
    $stmt->close();
    $conn->close();
    exit();
}

$stmt->close(); // Close the previous statement

// Hash the password
$hashed_password = password_hash($password, PASSWORD_BCRYPT);

// Insert new user
$sql = "INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $username, $email, $hashed_password);

if ($stmt->execute()) {
    $user_id = $stmt->insert_id; // Get the ID of the newly inserted user
    session_start();
    $_SESSION['user_id'] = $user_id; // Set session variable for user ID
    header("Location: user-dashboard.php"); // Redirect to user-specific page
    exit();
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
