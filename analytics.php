<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics</title>
</head>
<body>
    <h2>Analytics</h2>
    <div class="analytics">
        <p><strong>Total Uploads:</strong> <?php echo $upload_count; ?></p>
        <p><strong>Total Storage Used:</strong> <?php echo round($storage_used / 1024 / 1024, 2); ?> MB</p>
    </div>
</body>
</html>
