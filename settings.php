<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings</title>
</head>
<body>
    <h2>Account Settings</h2>
    <form action="settings-handler.php" method="POST">
        <label for="email">Email:</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
        <br>
        <label for="password">New Password:</label>
        <input type="password" name="password">
        <br>
        <button type="submit">Update Settings</button>
    </form>
</body>
</html>
