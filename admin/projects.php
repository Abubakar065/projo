<?php
/**
 * Projects List Page
 * Project Tracking and Reporting Application
 */

define('PTRA_ACCESS', true);
require_once '../inc/config.php';
require_once '../inc/db.php';
require_once '../inc/auth.php';

startSecureSession();
requireLogin();

$db = getDB();

// Handle project deletion (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_project']) && hasRole('admin')) {
    if (isset($_POST['csrf_token']) && verifyCSRFToken($_POST['csrf_token'])) {
        $project_id = (int)$_POST['project_id'];
        
        // Delete project (cascade will handle related records)
        $stmt = $db->prepare("UPDATE projects SET is_active = 0 WHERE id = ?");
        $stmt->bind_param("i", $project_id);
        
        if ($stmt->execute()) {
            $success_message = "Project deleted successfully.";
        } else {
            $error_message = "Error deleting project.";
        }
    }
}

// Get all projects
$query = "
    SELECT 
        p.id,
        p.project_name,
        p.contract_value_ngn,
        p.contract_value_usd,
        p.commencement_date,
        p.contractual_completion,
        p.created_at,
        pp.engineering_design,
        pp.procurement,
        pp.civil,
        pp.installation,
        pp.testing_commissioning,
        u.full_name as created_by_name
    FROM projects p
    LEFT JOIN project_progress pp ON p.id = pp.project_id
    LEFT JOIN users u ON p.created_by = u.id
    WHERE p.is_active = 1
    ORDER BY p.created_at DESC
";

$result = $db->query($query);
$projects = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Calculate overall progress
        $overall_progress = (
            ($row['engineering_design'] * 0.2) +
            ($row['procurement'] * 0.25) +
            ($row['civil'] * 0.25) +
            ($row['installation'] * 0.25) +
            ($row['testing_commissioning'] * 0.05)
        );
        
        $row['overall_progress'] = round($overall_progress, 1);
        $projects[] = $row;
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects - <?php echo APP_NAME; ?></title>
    
    <!-- Tabler CSS -->
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="../assets/style.css" rel="stylesheet">
</head>
<body>
    <div class="page">
        <!-- Navigation (same as dashboard) -->
        <header class="navbar navbar-expand-md navbar-dark bg-primary">
            <div class="container-xl">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <h1 class="navbar-brand navbar-brand-autodark">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                        <rect x="3" y="4" width="18" height="8" rx="1"></rect>
                        <rect x="12" y="4" width="9" height="16" rx="1"></rect>
                        <path d="m3 4 5 4 5 -4"></path>
                    </svg>
                    <?php echo APP_NAME; ?>
                </h1>
                
                <div class="navbar-nav flex-row order-md-last">
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown">
                            <span class="avatar avatar-sm bg-white text-primary">
                                <?php echo strtoupper(substr($_SESSION['full_name'], 0, 2)); ?>
                            </span>
                            <div class="d-none d-xl-block ps-2">
                                <div class="text-white"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                                <div class="mt-1 small text-white-50"><?php echo ucfirst($_SESSION['role']); ?></div>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="../logout.php" class="dropdown-item text-danger">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2"></path>
                                    <path d="m9 12 4 0"></path>
                                    <path d="m13 15 3 -3l-3 -3"></path>
                                </svg>
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="collapse navbar-collapse" id="navbar-menu">
                    <div class="d-flex flex-column flex-md-row flex-fill align-items-stretch align-items-md-center">
                        <ul class="navbar-nav">
                            <li class="nav-item">
                                <a class="nav-link" href="dashboard.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <polyline points="5 12 3 12 12 3 21 12 19 12"></polyline>
                                            <path d="m5 12 0 7a2 2 0 0 0 2 2l10 0a2 2 0 0 0 2 -2l0 -7"></path>
                                        </svg>
                                    </span>
                                    <span class="nav-link-title">Home</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active" href="projects.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <rect x="3" y="4" width="18" height="8" rx="1"></rect>
                                            <rect x="12" y="4" width="9" height="16" rx="1"></rect>
                                            <path d="m3 4 5 4 5 -4"></path>
                                        </svg>
                                    </span>
                                    <span class="nav-link-title">Projects</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="reports.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"></path>
                                            <path d="M9 12l2 2l4 -4"></path>
                                        </svg>
                                    </span>
                                    <span class="nav-link-title">Reports</span>
                                </a>
                            </li>
                            <?php if (hasRole('admin')): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="users.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <circle cx="9" cy="7" r="4"></circle>
                                            <path d="m3 21 0 -2a4 4 0 0 1 4 -4l4 0a4 4 0 0 1 4 4l0 2"></path>
                                            <path d="m16 3.13a4 4 0 0 1 0 7.75"></path>
                                            <path d="m21 21 0 -2a4 4 0 0 0 -3 -3.85"></path>
                                        </svg>
                                    </span>
                                    <span class="nav-link-title">Users</span>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Page Content -->
        <div class="page-wrapper">
            <div class="page-header d-print-none">
                <div class="container-xl">
                    <div class="row g-2 align-items-center">
                        <div class="col">
                            <div class="page-pretitle">Management</div>
                            <h2 class="page-title">All Projects</h2>
                        </div>
                        <div class="col-auto ms-auto d-print-none">
                            <?php if (hasRole('admin') || hasRole('pm')): ?>
                            <a href="project_form.php" class="btn btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                New Project
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Page Body -->
            <div class="page-body">
                <div class="container-xl">
                    <?php if (isset($success_message)): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="card">
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead>
                                    <tr>
                                        <th>Project Name</th>
                                        <th>Contract Value</th>
                                        <th>Progress</th>
                                        <th>Completion Date</th>
                                        <th>Created By</th>
                                        <th class="w-1">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($projects)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            No projects found. <a href="project_form.php">Create your first project</a>.
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($projects as $project): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold">
                                                <a href="../project.php?id=<?php echo $project['id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($project['project_name']); ?>
                                                </a>
                                            </div>
                                            <div class="text-muted small">
                                                Started: <?php echo $project['commencement_date'] ? date('M j, Y', strtotime($project['commencement_date'])) : 'Not set'; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-bold">₦<?php echo number_format($project['contract_value_ngn'], 0); ?></div>
                                            <div class="text-muted small">$<?php echo number_format($project['contract_value_usd'], 0); ?></div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="me-2"><?php echo number_format($project['overall_progress'], 1); ?>%</span>
                                                <div class="progress" style="width: 100px;">
                                                    <div class="progress-bar bg-<?php echo $project['overall_progress'] >= 80 ? 'success' : ($project['overall_progress'] >= 60 ? 'info' : ($project['overall_progress'] >= 40 ? 'warning' : 'danger')); ?>" 
                                                         style="width: <?php echo $project['overall_progress']; ?>%"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($project['contractual_completion']): ?>
                                            <div><?php echo date('M j, Y', strtotime($project['contractual_completion'])); ?></div>
                                            <?php 
                                            $today = new DateTime();
                                            $completion = new DateTime($project['contractual_completion']);
                                            $diff = $completion->diff($today);
                                            
                                            if ($completion < $today): ?>
                                                <div class="text-danger small">Overdue by <?php echo $diff->days; ?> days</div>
                                            <?php elseif ($diff->days <= 30): ?>
                                                <div class="text-warning small"><?php echo $diff->days; ?> days remaining</div>
                                            <?php else: ?>
                                                <div class="text-muted small"><?php echo $diff->days; ?> days remaining</div>
                                            <?php endif; ?>
                                            <?php else: ?>
                                            <span class="text-muted">Not set</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div><?php echo htmlspecialchars($project['created_by_name'] ?? 'Unknown'); ?></div>
                                            <div class="text-muted small"><?php echo date('M j, Y', strtotime($project['created_at'])); ?></div>
                                        </td>
                                        <td>
                                            <div class="btn-list flex-nowrap">
                                                <a href="../project.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    View
                                                </a>
                                                <?php if (hasRole('admin') || hasRole('pm')): ?>
                                                <a href="project_form.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                    Edit
                                                </a>
                                                <?php endif; ?>
                                                <?php if (hasRole('admin')): ?>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this project?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                    <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                                    <button type="submit" name="delete_project" class="btn btn-sm btn-outline-danger">
                                                        Delete
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabler JS -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/js/tabler.min.js"></script>
    
    <!-- Custom JS -->
    <script src="../assets/script.js"></script>
</body>
</html>
