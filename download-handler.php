<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
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

// Retrieve file info
$file_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

if ($file_id > 0) {
    // Prepare the SQL statement to prevent SQL injection
    $sql = "SELECT u.file_name, u.file_data
            FROM uploads u
            JOIN file_permissions fp ON u.file_id = fp.file_id
            WHERE u.file_id = ? AND fp.user_id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("ii", $file_id, $user_id);
        $stmt->execute();
        $stmt->bind_result($file_name, $file_data);
        $stmt->fetch();
        $stmt->close();

        // Check if file was found and user has permission
        if ($file_name && $file_data) {
            // Set headers for file download
            header("Content-Description: File Transfer");
            header("Content-Type: application/octet-stream");
            header("Content-Disposition: attachment; filename=\"" . basename($file_name) . "\"");
            header("Content-Length: " . strlen($file_data));
            header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
            header("Pragma: no-cache"); // HTTP 1.0.
            header("Expires: 0"); // Proxies.

            // Clear the output buffer
            ob_clean(); 
            flush(); // Flush system output buffer

            // Output file data
            echo $file_data;
            exit;
        } else {
            echo "You do not have permission to access this file or the file does not exist.";
        }
    } else {
        echo "Database query preparation failed: " . $conn->error;
    }
} else {
    echo "Invalid file ID.";
}

$conn->close();
?>
