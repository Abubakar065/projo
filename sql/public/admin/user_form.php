<?php
require_once "../inc/db.php";
require_once "../inc/auth.php";
check_auth('admin');

$id = $_GET['id'] ?? null;
$user = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'])) die("CSRF validation failed.");

    $username = $_POST['username'];
    $role = $_POST['role'];
    if ($id) {
        if ($_POST['password']) {
            $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET username=?, password=?, role=? WHERE id=?");
            $stmt->execute([$username, $password, $role, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username=?, role=? WHERE id=?");
            $stmt->execute([$username, $role, $id]);
        }
    } else {
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users(username,password,role) VALUES(?,?,?)");
        $stmt->execute([$username, $password, $role]);
    }
    header("Location: users.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head><title>User Form</title></head>
<body>
<h2><?= $id ? "Edit User" : "Add User" ?></h2>
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <label>Username</label><br>
    <input type="text" name="username" value="<?= $user['username'] ?? '' ?>"><br>
    <label>Password</label><br>
    <input type="password" name="password"><br>
    <label>Role</label><br>
    <select name="role">
        <option value="admin" <?= isset($user['role']) && $user['role']=='admin'?'selected':'' ?>>Admin</option>
        <option value="pm" <?= isset($user['role']) && $user['role']=='pm'?'selected':'' ?>>Project Manager</option>
        <option value="viewer" <?= isset($user['role']) && $user['role']=='viewer'?'selected':'' ?>>Viewer</option>
    </select><br><br>
    <button type="submit">Save</button>
</form>
</body>
</html>
