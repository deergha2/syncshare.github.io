<?php
session_start(); // Start the session

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

// Helper function to fetch user info
function getUserInfo($conn, $user_id) {
    $sql = "SELECT email, created_at, last_login FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($email, $created_at, $last_login);
    $stmt->fetch();
    $stmt->close();
    return compact('email', 'created_at', 'last_login');
}

// Helper function to fetch uploads
function getUploads($conn, $user_id) {
    $uploads = [];
    $sql = "SELECT file_id, file_name, upload_date FROM uploads WHERE user_id = ? ORDER BY upload_date DESC LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($file_id, $file_name, $upload_date);

    while ($stmt->fetch()) {
        $uploads[] = ['file_id' => $file_id, 'file_name' => $file_name, 'upload_date' => $upload_date];
    }
    $stmt->close();
    return $uploads;
}

// Helper function to fetch quick stats
function getUploadsToday($conn, $user_id) {
    $today = date('Y-m-d');
    $sql = "SELECT COUNT(*) FROM uploads WHERE user_id = ? AND DATE(upload_date) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    $stmt->bind_result($uploads_today);
    $stmt->fetch();
    $stmt->close();
    return $uploads_today;
}

// Handle search query
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$search_term = "%" . $search_query . "%"; // Use wildcard search

// Fetch all uploads based on search query
function getAllUploads($conn, $search_term) {
    $all_uploads = [];
    $sql = "SELECT file_id, file_name, upload_date FROM uploads WHERE file_name LIKE ? ORDER BY upload_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $search_term);
    $stmt->execute();
    $stmt->bind_result($file_id, $file_name, $upload_date);

    while ($stmt->fetch()) {
        $all_uploads[] = ['file_id' => $file_id, 'file_name' => $file_name, 'upload_date' => $upload_date];
    }
    $stmt->close();
    return $all_uploads;
}

// Check if a file is uploaded and handle permissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $user_id = $_SESSION['user_id'];
    
    // Check for errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo "File upload error.";
        exit();
    }

    // Sanitize file name and handle upload
    $file_name = basename($file['name']);
    $file_data = file_get_contents($file['tmp_name']);

    // Insert file into uploads table
    $sql = "INSERT INTO uploads (file_name, file_data, user_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $file_name, $file_data, $user_id);
    $stmt->execute();
    $file_id = $stmt->insert_id;
    $stmt->close();

    // Insert file permission record
    $sql = "INSERT INTO file_permissions (file_id, user_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $file_id, $user_id);
    $stmt->execute();
    $stmt->close();

    // Redirect after successful upload
    header("Location: user-dashboard.php?page=upload");
    exit();
}

// Fetch user information, uploads, and stats
$user_id = $_SESSION['user_id'];
$user_info = getUserInfo($conn, $user_id);
$uploads = getUploads($conn, $user_id);
$uploads_today = getUploadsToday($conn, $user_id);
$all_uploads = getAllUploads($conn, $search_term);

// Close database connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <style>
        /* Add your existing CSS styles here */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
        }
        .sidebar {
            width: 200px;
            background: #333;
            color: #fff;
            height: 100vh;
            padding: 20px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            position: fixed;
        }
        .sidebar h2 {
            font-size: 1.5em;
            margin: 0;
            padding: 0;
        }
        .sidebar ul {
            list-style: none;
            padding: 0;
        }
        .sidebar ul li {
            margin: 20px 0;
        }
        .sidebar ul li a {
            color: #fff;
            text-decoration: none;
            font-size: 1.1em;
            display: block;
            padding: 10px;
            border-radius: 5px;
        }
        .sidebar ul li a:hover {
            background: #575757;
        }
        .content {
            margin-left: 250px;
            padding: 20px;
            width: calc(100% - 250px);
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin-top: 10px;
            background: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
        }
        .btn:hover {
            background: #0056b3;
        }
        .file-list {
            list-style: none;
            padding: 0;
        }
        .file-list li {
            margin: 10px 0;
        }
        .file-list a {
            color: #007bff;
            text-decoration: none;
        }
        .file-list a:hover {
            text-decoration: underline;
        }
        .quick-stats {
            margin-top: 20px;
        }
        .feedback-form {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Dashboard</h2>
        <ul>
            <li><a href="?page=upload">Upload</a></li>
            <li><a href="?page=manage">Manage</a></li>
            <li><a href="?page=settings">Account Settings</a></li>
            <li><a href="?page=notifications">Notifications</a></li>
            <li><a href="?page=activity">Recent Activity</a></li>
            <li><a href="?page=analytics">Analytics</a></li>
            <li><a href="?page=support">Help & Support</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>
    <div class="content">
        <h1>User Dashboard</h1>
        <?php
        // Determine which page to include
        $page = isset($_GET['page']) ? $_GET['page'] : 'upload';

        switch ($page) {
            case 'upload':
                include 'upload.php'; // Handle file uploads
                break;
            case 'manage':
                include 'manage.php'; // Manage items or content
                break;
            case 'settings':
                include 'settings.php'; // Edit account settings
                break;
            case 'notifications':
                include 'notifications.php'; // Display notifications
                break;
            case 'activity':
                include 'activity.php'; // Show recent activity
                break;
            case 'analytics':
                include 'analytics.php'; // Show user analytics
                break;
            case 'support':
                include 'support.php'; // Help and support section
                break;
            default:
                include 'upload.php'; // Default to upload if no page specified
                break;
        }
        ?>
        

        <!-- Display Recent Uploads -->
        <div class="recent-uploads">
            <h3>Recent Uploads</h3>
            <ul class="file-list">
                <?php foreach ($uploads as $upload): ?>
                    <li>
                        <?php echo htmlspecialchars($upload['file_name']); ?> 
                        (Uploaded on <?php echo htmlspecialchars($upload['upload_date']); ?>)
                        <a href="download-handler.php?id=<?php echo $upload['file_id']; ?>" class="btn" style="background: #dc3545;">Download</a>
                        <a href="delete-handler.php?id=<?php echo $upload['file_id']; ?>" class="btn" style="background: #dc3545;">Delete</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Display All Uploads -->
        <div class="all-uploads">
            <h3>All Uploaded Files</h3>
            <!-- Search Files -->
            <div class="search-container">
                <form method="GET" action="">
                    <input type="text" name="search" placeholder="Search files..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit" class="btn">Search</button>
                </form>
            </div>
            <ul class="file-list">
                <?php if (empty($all_uploads)): ?>
                    <li>No files found.</li>
                <?php else: ?>
                    <?php foreach ($all_uploads as $upload): ?>
                        <li>
                            <?php echo htmlspecialchars($upload['file_name']); ?> 
                            (Uploaded on <?php echo htmlspecialchars($upload['upload_date']); ?>)
                            <a href="download-handler.php?id=<?php echo $upload['file_id']; ?>" class="btn" style="background: #dc3545;">Download</a>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Display Quick Stats -->
        <div class="quick-stats">
            <h3>Quick Stats</h3>
            <p><strong>Uploads Today:</strong> <?php echo $uploads_today; ?></p>
        </div>

        <!-- Display Account Overview -->
        <div class="account-overview">
            <h3>Account Overview</h3>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user_info['email']); ?></p>
            <p><strong>Account Created:</strong> <?php echo htmlspecialchars($user_info['created_at']); ?></p>
            <p><strong>Last Login:</strong> <?php echo htmlspecialchars($user_info['last_login']); ?></p>
        </div>

        <!-- User Feedback Form -->
        <div class="feedback-form">
            <h3>Submit Feedback</h3>
            <form action="feedback-handler.php" method="POST">
                <textarea name="feedback" rows="5" cols="40" placeholder="Enter your feedback here" required></textarea>
                <br>
                <button type="submit" class="btn">Submit Feedback</button>
            </form>
        </div>
    </div>
</body>
</html>
