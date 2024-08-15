<?php
session_start();

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

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']) && isset($_POST['permission_user'])) {
    $file = $_FILES['file'];
    $permission_user_id = intval($_POST['permission_user']);
    $user_id = $_SESSION['user_id'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo "File upload error.";
        exit();
    }

    $file_name = basename($file['name']);
    $file_data = file_get_contents($file['tmp_name']);

    $sql = "INSERT INTO uploads (file_name, file_data, user_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $file_name, $file_data, $user_id);
    $stmt->execute();
    $file_id = $stmt->insert_id;
    $stmt->close();

    if ($permission_user_id) {
        // Insert file permission record
        $sql = "INSERT INTO file_permissions (file_id, user_id, permission_type) VALUES (?, ?, 'read')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $file_id, $permission_user_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: user-dashboard.php?page=upload");
    exit();
}


$conn->close();
?>
