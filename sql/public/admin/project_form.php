<?php
require_once "../inc/db.php";
require_once "../inc/auth.php";
check_auth('admin');

$id = $_GET['id'] ?? null;
$project = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id=?");
    $stmt->execute([$id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'])) die("CSRF failed.");

    $fields = [
        $_POST['name'], $_POST['description'], $_POST['contract_value_ngn'],
        $_POST['contract_value_usd'], $_POST['notice_award'], $_POST['contract_signed'],
        $_POST['commencement'], $_POST['completion'], $_POST['contractual_completion']
    ];

    if ($id) {
        $stmt = $pdo->prepare("UPDATE projects SET 
            name=?, description=?, contract_value_ngn=?, contract_value_usd=?, 
            notice_award=?, contract_signed=?, commencement=?, completion=?, contractual_completion=? 
            WHERE id=?");
        $fields[] = $id;
        $stmt->execute($fields);
    } else {
        $stmt = $pdo->prepare("INSERT INTO projects
            (name,description,contract_value_ngn,contract_value_usd,notice_award,contract_signed,commencement,completion,contractual_completion)
            VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute($fields);
    }
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head><title>Project Form</title></head>
<body>
<h2><?= $id ? "Edit Project" : "New Project" ?></h2>
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <label>Name</label><br>
    <input type="text" name="name" value="<?= $project['name'] ?? '' ?>"><br>
    <label>Description</label><br>
    <textarea name="description"><?= $project['description'] ?? '' ?></textarea><br>
    <label>Contract Value (NGN)</label><br>
    <input type="number" step="0.01" name="contract_value_ngn" value="<?= $project['contract_value_ngn'] ?? '' ?>"><br>
    <label>Contract Value (USD)</label><br>
    <input type="number" step="0.01" name="contract_value_usd" value="<?= $project['contract_value_usd'] ?? '' ?>"><br>
    <label>Notice of Award</label><br>
    <input type="date" name="notice_award" value="<?= $project['notice_award'] ?? '' ?>"><br>
    <label>Contract Signed</label><br>
    <input type="date" name="contract_signed" value="<?= $project['contract_signed'] ?? '' ?>"><br>
    <label>Commencement Date</label><br>
    <input type="date" name="commencement" value="<?= $project['commencement'] ?? '' ?>"><br>
    <label>Completion Date</label><br>
    <input type="date" name="completion" value="<?= $project['completion'] ?? '' ?>"><br>
    <label>Contractual Completion</label><br>
    <input type="date" name="contractual_completion" value="<?= $project['contractual_completion'] ?? '' ?>"><br>
    <br>
    <button type="submit">Save</button>
</form>
</body>
</html>
