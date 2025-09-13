<?php
require_once "../inc/db.php";
require_once "../inc/auth.php";
check_auth('admin');

// Fetch users
$users = $pdo->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head><title>User Management</title></head>
<body>
<h2>User Management</h2>
<a href="user_form.php">Add User</a>
<table border="1">
<tr><th>ID</th><th>Username</th><th>Role</th><th>Action</th></tr>
<?php foreach ($users as $u): ?>
<tr>
  <td><?= $u['id'] ?></td>
  <td><?= $u['username'] ?></td>
  <td><?= $u['role'] ?></td>
  <td>
    <a href="user_form.php?id=<?= $u['id'] ?>">Edit</a> |
    <a href="user_delete.php?id=<?= $u['id'] ?>" onclick="return confirm('Delete user?')">Delete</a>
  </td>
</tr>
<?php endforeach; ?>
</table>
</body>
</html>
