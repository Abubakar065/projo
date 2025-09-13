<?php
require_once "../inc/db.php";
require_once "../inc/auth.php";
check_auth();

$project_id = $_GET['project_id'] ?? null;
if (!$project_id) die("Project required");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'])) die("CSRF failed.");

    $stmt = $pdo->prepare("INSERT INTO progress (project_id,category,percentage,month) VALUES (?,?,?,YEAR(CURDATE()))");
    $stmt->execute([$project_id, $_POST['category'], $_POST['percentage']]);
    header("Location: ../project.php?id=$project_id");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head><title>Update Progress</title></head>
<body>
<h2>Update Progress</h2>
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <label>Category</label><br>
    <select name="category">
        <option value="engineering">Engineering Design</option>
        <option value="procurement">Procurement</option>
        <option value="civil">Civil</option>
        <option value="installation">Installation</option>
        <option value="testing">Testing & Commissioning</option>
        <option value="overall">Overall Progress</option>
        <option value="disbursement">Disbursement</option>
        <option value="planned">Planned</option>
    </select><br>
    <label>Percentage</label><br>
    <input type="number" name="percentage" min="0" max="100"><br>
    <button type="submit">Save</button>
</form>
</body>
</html>
