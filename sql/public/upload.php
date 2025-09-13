<?php
require_once "inc/db.php";
require_once "inc/auth.php";
check_auth();

$project_id = $_GET['project_id'] ?? null;
if (!$project_id) die("Project required");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'])) die("CSRF failed.");

    $target_dir = __DIR__ . "/uploads/";
    $filename = basename($_FILES["file"]["name"]);
    $target_file = $target_dir . $filename;

    // Limit to images < 2MB
    if ($_FILES["file"]["size"] > 2000000) die("File too large.");
    $allowed = ['jpg','jpeg','png','gif'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) die("Invalid file type.");

    if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
        echo "Upload successful!";
    } else {
        echo "Upload failed.";
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Upload Photo</title></head>
<body>
<h2>Upload Project Photo</h2>
<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <input type="file" name="file" required>
    <button type="submit">Upload</button>
</form>
</body>
</html>
