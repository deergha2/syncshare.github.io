<?php

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "syncshare";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

// Retrieve user's upload history
$sql = "SELECT file_id, file_name, upload_date FROM uploads WHERE user_id = ? ORDER BY upload_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$uploads = [];
while ($row = $result->fetch_assoc()) {
    $uploads[] = $row;
}
$stmt->close();

// Retrieve users for permission setting
$sql = "SELECT id, email FROM users";
$users_result = $conn->query($sql);

$users = [];
while ($row = $users_result->fetch_assoc()) {
    $users[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            width: 80%;
            margin: auto;
            overflow: hidden;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2, h3 {
            color: #333;
        }
        form {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }
        input[type="file"],
        select,
        button {
            display: block;
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 15px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        button {
            background-color: #007bff;
            color: #fff;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .file-list {
            list-style: none;
            padding: 0;
        }
        .file-list li {
            margin: 10px 0;
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .file-list a {
            color: #007bff;
            text-decoration: none;
            margin-left: 10px;
        }
        .file-list a:hover {
            text-decoration: underline;
        }
        .btn {
            display: inline-block;
            padding: 8px 15px;
            margin: 0 5px;
            border-radius: 4px;
            text-decoration: none;
            color: #fff;
            font-weight: bold;
            text-align: center;
            border: none;
            cursor: pointer;
        }
        .btn-download {
            background-color: #90EE90;
        }
        .btn-download:hover {
            background-color: #76c7c0;
        }
        .btn-delete {
            background-color: #dc3545;
        }
        .btn-delete:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <h2>Upload Files</h2>
    <form action="upload-handler.php" method="POST" enctype="multipart/form-data">
        <label for="file">Choose file:</label>
        <input type="file" name="file" id="file" required>
        <label for="permission_user">Select User for Permission:</label>
        <select name="permission_user" id="permission_user">
            <option value="">Select a user</option>
            <?php foreach ($users as $user): ?>
                <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['email']); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Upload</button>
    </form>

    <div class="upload-history">
        <h3>Upload History</h3>
        <ul class="file-list">
            <?php foreach ($uploads as $upload): ?>
                <li>
                    <?php echo htmlspecialchars($upload['file_name']); ?> 
                    (Uploaded on <?php echo htmlspecialchars($upload['upload_date']); ?>)
                    <a href="download-handler.php?id=<?php echo $upload['file_id']; ?>" class="btn" style="background: #90EE90;">Download</a>
                    <a href="delete-handler.php?id=<?php echo $upload['file_id']; ?>" class="btn" style="background: #dc3545;">Delete</a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</body>
</html>
