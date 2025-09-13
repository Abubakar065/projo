<?php
/**
 * Main Dashboard
 * Project Tracking and Reporting Application
 */

define('PTRA_ACCESS', true);
require_once '../inc/config.php';
require_once '../inc/db.php';
require_once '../inc/auth.php';

startSecureSession();
requireLogin();

$db = getDB();
$current_user = $_SESSION['full_name'];
$user_role = $_SESSION['role'];

// Get projects with their progress data
$query = "
    SELECT 
        p.id,
        p.project_name,
        p.contract_value_ngn,
        p.contract_value_usd,
        p.proposed_completion,
        p.contractual_completion,
        pp.engineering_design,
        pp.procurement,
        pp.civil,
        pp.installation,
        pp.testing_commissioning,
        pp.disbursement_progress,
        pp.planned_progress,
        pp.actual_progress
    FROM projects p
    LEFT JOIN project_progress pp ON p.id = pp.project_id
    WHERE p.is_active = 1
    ORDER BY p.created_at DESC
";

$result = $db->query($query);
$projects = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Calculate overall progress (weighted average)
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

// Helper function to get progress bar color
function getProgressColor($percentage) {
    if ($percentage >= 80) return 'success';
    if ($percentage >= 60) return 'info';
    if ($percentage >= 40) return 'warning';
    return 'danger';
}

// Helper function to format currency
function formatCurrency($amount, $currency = 'NGN') {
    if ($currency === 'NGN') {
        return '₦' . number_format($amount, 0);
    } else {
        return '$' . number_format($amount, 0);
    }
}

// Helper function to get project status
function getProjectStatus($completion_date) {
    if (!$completion_date) return 'In Progress';
    
    $today = new DateTime();
    $completion = new DateTime($completion_date);
    
    if ($completion < $today) {
        return 'Overdue';
    } elseif ($completion->diff($today)->days <= 30) {
        return 'Due Soon';
    } else {
        return 'On Track';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    
    <!-- Tabler CSS -->
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css" rel="stylesheet">
    
    <!-- Tabler Icons -->
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons@latest/icons-sprite.svg" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="../assets/style.css" rel="stylesheet">
</head>
<body>
    <div class="page">
        <!-- Navigation Header -->
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
                                <?php echo strtoupper(substr($current_user, 0, 2)); ?>
                            </span>
                            <div class="d-none d-xl-block ps-2">
                                <div class="text-white"><?php echo htmlspecialchars($current_user); ?></div>
                                <div class="mt-1 small text-white-50"><?php echo ucfirst($user_role); ?></div>
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
                                <a class="nav-link active" href="dashboard.php">
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
                                <a class="nav-link" href="projects.php">
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
                            <div class="page-pretitle">Overview</div>
                            <h2 class="page-title">Project Dashboard</h2>
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
                    <?php if (empty($projects)): ?>
                    <!-- Empty State -->
                    <div class="empty">
                        <div class="empty-img">
                            <img src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/img/undraw_printing_invoices_5r4r.svg" height="128" alt="">
                        </div>
                        <p class="empty-title">No projects found</p>
                        <p class="empty-subtitle text-muted">
                            Get started by creating your first project to track progress and manage activities.
                        </p>
                        <?php if (hasRole('admin') || hasRole('pm')): ?>
                        <div class="empty-action">
                            <a href="project_form.php" class="btn btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                Create your first project
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <!-- Project Cards Grid -->
                    <div class="row row-deck row-cards">
                        <?php foreach ($projects as $project): ?>
                        <div class="col-sm-6 col-lg-4">
                            <div class="card project-card">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <a href="../project.php?id=<?php echo $project['id']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($project['project_name']); ?>
                                        </a>
                                    </h3>
                                    <div class="card-actions">
                                        <span class="badge bg-<?php echo getProjectStatus($project['contractual_completion']) === 'Overdue' ? 'danger' : (getProjectStatus($project['contractual_completion']) === 'Due Soon' ? 'warning' : 'success'); ?>">
                                            <?php echo getProjectStatus($project['contractual_completion']); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <!-- Contract Value -->
                                    <div class="mb-3">
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="text-muted small">Contract Value</div>
                                                <div class="fw-bold"><?php echo formatCurrency($project['contract_value_ngn']); ?></div>
                                            </div>
                                            <div class="col-6">
                                                <div class="text-muted small">USD Equivalent</div>
                                                <div class="fw-bold"><?php echo formatCurrency($project['contract_value_usd'], 'USD'); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Progress Bars -->
                                    <div class="progress-container">
                                        <div class="progress-label">
                                            <span class="text-muted small">Overall Progress</span>
                                            <span class="fw-bold text-<?php echo getProgressColor($project['overall_progress']); ?>">
                                                <?php echo number_format($project['overall_progress'], 1); ?>%
                                            </span>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar bg-<?php echo getProgressColor($project['overall_progress']); ?>" 
                                                 style="width: <?php echo $project['overall_progress']; ?>%" 
                                                 aria-valuenow="<?php echo $project['overall_progress']; ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="progress-container">
                                        <div class="progress-label">
                                            <span class="text-muted small">Disbursement</span>
                                            <span class="fw-bold text-warning">
                                                <?php echo number_format($project['disbursement_progress'] ?? 0, 1); ?>%
                                            </span>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar bg-warning" 
                                                 style="width: <?php echo $project['disbursement_progress'] ?? 0; ?>%" 
                                                 aria-valuenow="<?php echo $project['disbursement_progress'] ?? 0; ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="progress-container">
                                        <div class="progress-label">
                                            <span class="text-muted small">Planned vs Actual</span>
                                            <span class="fw-bold">
                                                <span class="text-info"><?php echo number_format($project['planned_progress'] ?? 0, 1); ?>%</span>
                                                /
                                                <span class="text-primary"><?php echo number_format($project['actual_progress'] ?? 0, 1); ?>%</span>
                                            </span>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar bg-info" 
                                                 style="width: <?php echo $project['planned_progress'] ?? 0; ?>%" 
                                                 aria-valuenow="<?php echo $project['planned_progress'] ?? 0; ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100"></div>
                                        </div>
                                        <div class="progress mt-1">
                                            <div class="progress-bar bg-primary" 
                                                 style="width: <?php echo $project['actual_progress'] ?? 0; ?>%" 
                                                 aria-valuenow="<?php echo $project['actual_progress'] ?? 0; ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                    
                                    <!-- Completion Date -->
                                    <?php if ($project['contractual_completion']): ?>
                                    <div class="mt-3 pt-2 border-top">
                                        <div class="text-muted small">Completion Date</div>
                                        <div class="fw-bold">
                                            <?php echo date('M j, Y', strtotime($project['contractual_completion'])); ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <a href="../project.php?id=<?php echo $project['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                View Details
                                            </a>
                                        </div>
                                        <div class="col-auto">
                                            <?php if (hasRole('admin') || hasRole('pm')): ?>
                                            <a href="progress_form.php?id=<?php echo $project['id']; ?>" class="btn btn-primary btn-sm">
                                                Update Progress
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Summary Statistics -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Project Summary</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-sm-6 col-lg-3">
                                            <div class="text-center">
                                                <div class="h1 text-primary"><?php echo count($projects); ?></div>
                                                <div class="text-muted">Total Projects</div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 col-lg-3">
                                            <div class="text-center">
                                                <div class="h1 text-success">
                                                    <?php 
                                                    $completed = array_filter($projects, function($p) { 
                                                        return $p['overall_progress'] >= 100; 
                                                    });
                                                    echo count($completed);
                                                    ?>
                                                </div>
                                                <div class="text-muted">Completed</div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 col-lg-3">
                                            <div class="text-center">
                                                <div class="h1 text-warning">
                                                    <?php 
                                                    $in_progress = array_filter($projects, function($p) { 
                                                        return $p['overall_progress'] > 0 && $p['overall_progress'] < 100; 
                                                    });
                                                    echo count($in_progress);
                                                    ?>
                                                </div>
                                                <div class="text-muted">In Progress</div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 col-lg-3">
                                            <div class="text-center">
                                                <div class="h1 text-info">
                                                    <?php 
                                                    $total_value = array_sum(array_column($projects, 'contract_value_ngn'));
                                                    echo formatCurrency($total_value);
                                                    ?>
                                                </div>
                                                <div class="text-muted">Total Value</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
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
