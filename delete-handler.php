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

// Retrieve file ID and user ID from session
$file_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

if ($file_id > 0) {
    // Begin transaction
    $conn->begin_transaction();

    try {
        // Check if the user has permission to delete the file
        $sql = "SELECT COUNT(*) FROM file_permissions WHERE file_id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Database query preparation failed: " . $conn->error);
        }

        $stmt->bind_param("ii", $file_id, $user_id);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            // User has permission, proceed with file deletion

            // Remove permissions related to this file
            $sql = "DELETE FROM file_permissions WHERE file_id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Database query preparation failed: " . $conn->error);
            }

            $stmt->bind_param("i", $file_id);
            $stmt->execute();
            $stmt->close();

            // Delete the file record
            $sql = "DELETE FROM uploads WHERE file_id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Database query preparation failed: " . $conn->error);
            }

            $stmt->bind_param("i", $file_id);
            $stmt->execute();
            $stmt->close();

            // Commit transaction
            $conn->commit();

            // Redirect after successful deletion
            header("Location: user-dashboard.php?page=upload");
            exit();
        } else {
            throw new Exception("You do not have permission to delete this file.");
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo "Error: " . htmlspecialchars($e->getMessage());
    }
} else {
    echo "Invalid file ID.";
}

$conn->close();
?>
