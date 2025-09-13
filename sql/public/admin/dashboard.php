<?php
require_once "../inc/db.php";
require_once "../inc/auth.php";
check_auth();

$projects = $pdo->query("SELECT * FROM projects")->fetchAll(PDO::FETCH_ASSOC);

function get_progress($pdo, $project_id, $category) {
    $stmt = $pdo->prepare("SELECT percentage FROM progress WHERE project_id=? AND category=? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$project_id, $category]);
    return $stmt->fetchColumn() ?: 0;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<h2>Dashboard</h2>
<a href="project_form.php">+ New Project</a> | 
<a href="users.php">Manage Users</a> | 
<a href="../logout.php">Logout</a>
<hr>
<div class="grid">
<?php foreach ($projects as $p): ?>
    <div class="card">
        <h3><?= htmlspecialchars($p['name']) ?></h3>
        <p>Overall Progress: <?= get_progress($pdo, $p['id'], 'overall') ?>%</p>
        <p>Disbursement: <?= get_progress($pdo, $p['id'], 'disbursement') ?>%</p>
        <p>Planned: <?= get_progress($pdo, $p['id'], 'planned') ?>%</p>
        <a href="../project.php?id=<?= $p['id'] ?>">View Details</a>
    </div>
<?php endforeach; ?>
</div>
</body>
</html>
