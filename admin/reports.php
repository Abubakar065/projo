<?php
/**
 * Reports Page
 * Project Tracking and Reporting Application
 */

define('PTRA_ACCESS', true);
require_once '../inc/config.php';
require_once '../inc/db.php';
require_once '../inc/auth.php';

startSecureSession();
requireLogin();

$db = getDB();

// Get project statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_projects,
        SUM(CASE WHEN pp.engineering_design >= 100 AND pp.procurement >= 100 AND pp.civil >= 100 AND pp.installation >= 100 AND pp.testing_commissioning >= 100 THEN 1 ELSE 0 END) as completed_projects,
        SUM(contract_value_ngn) as total_value_ngn,
        SUM(contract_value_usd) as total_value_usd,
        AVG(CASE WHEN pp.id IS NOT NULL THEN (pp.engineering_design * 0.2 + pp.procurement * 0.25 + pp.civil * 0.25 + pp.installation * 0.25 + pp.testing_commissioning * 0.05) ELSE 0 END) as avg_progress
    FROM projects p
    LEFT JOIN project_progress pp ON p.id = pp.project_id
    WHERE p.is_active = 1
";

$stats_result = $db->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Get projects with progress for detailed view
$projects_query = "
    SELECT 
        p.id,
        p.project_name,
        p.contract_value_ngn,
        p.contractual_completion,
        pp.engineering_design,
        pp.procurement,
        pp.civil,
        pp.installation,
        pp.testing_commissioning,
        pp.disbursement_progress,
        (pp.engineering_design * 0.2 + pp.procurement * 0.25 + pp.civil * 0.25 + pp.installation * 0.25 + pp.testing_commissioning * 0.05) as overall_progress
    FROM projects p
    LEFT JOIN project_progress pp ON p.id = pp.project_id
    WHERE p.is_active = 1
    ORDER BY p.created_at DESC
";

$projects_result = $db->query($projects_query);
$projects = [];

if ($projects_result) {
    while ($row = $projects_result->fetch_assoc()) {
        $projects[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - <?php echo APP_NAME; ?></title>
    
    <!-- Tabler CSS -->
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="../assets/style.css" rel="stylesheet">
</head>
<body>
    <div class="page">
        <!-- Navigation Header (same as other pages) -->
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
                                <a class="nav-link active" href="reports.php">
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
                            <div class="page-pretitle">Analytics</div>
                            <h2 class="page-title">Project Reports</h2>
                        </div>
                        <div class="col-auto ms-auto d-print-none">
                            <button onclick="window.print()" class="btn btn-outline-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M17 17h2a2 2 0 0 0 2 -2v-4a2 2 0 0 0 -2 -2h-14a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h2"></path>
                                    <path d="M17 9v-4a2 2 0 0 1 0 -7.75"></path>
                                    <rect x="7" y="13" width="10" height="8" rx="2"></rect>
                                </svg>
                                Print Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Page Body -->
            <div class="page-body">
                <div class="container-xl">
                    <!-- Summary Statistics -->
                    <div class="row row-deck row-cards mb-4">
                        <div class="col-sm-6 col-lg-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="subheader">Total Projects</div>
                                    </div>
                                    <div class="h1 mb-3"><?php echo $stats['total_projects'] ?? 0; ?></div>
                                    <div class="d-flex mb-2">
                                        <div class="flex-fill">
                                            <div class="progress progress-sm">
                                                <div class="progress-bar bg-primary" style="width: 100%" role="progressbar"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-sm-6 col-lg-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="subheader">Completed Projects</div>
                                    </div>
                                    <div class="h1 mb-3"><?php echo $stats['completed_projects'] ?? 0; ?></div>
                                    <div class="d-flex mb-2">
                                        <div class="flex-fill">
                                            <div class="progress progress-sm">
                                                <div class="progress-bar bg-success" style="width: <?php echo $stats['total_projects'] > 0 ? round(($stats['completed_projects'] / $stats['total_projects']) * 100) : 0; ?>%" role="progressbar"></div>
                                            </div>
                                        </div>
                                        <div class="ms-2">
                                            <span class="text-muted"><?php echo $stats['total_projects'] > 0 ? round(($stats['completed_projects'] / $stats['total_projects']) * 100) : 0; ?>%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-sm-6 col-lg-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="subheader">Total Value (NGN)</div>
                                    </div>
                                    <div class="h1 mb-3">₦<?php echo number_format($stats['total_value_ngn'] ?? 0); ?></div>
                                    <div class="d-flex mb-2">
                                        <div class="flex-fill">
                                            <div class="progress progress-sm">
                                                <div class="progress-bar bg-warning" style="width: 100%" role="progressbar"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-sm-6 col-lg-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="subheader">Average Progress</div>
                                    </div>
                                    <div class="h1 mb-3"><?php echo round($stats['avg_progress'] ?? 0, 1); ?>%</div>
                                    <div class="d-flex mb-2">
                                        <div class="flex-fill">
                                            <div class="progress progress-sm">
                                                <div class="progress-bar bg-info" style="width: <?php echo round($stats['avg_progress'] ?? 0); ?>%" role="progressbar"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Detailed Projects Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Project Progress Details</h3>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-vcenter card-table">
                                        <thead>
                                            <tr>
                                                <th>Project Name</th>
                                                <th>Contract Value</th>
                                                <th>Completion Date</th>
                                                <th>Overall Progress</th>
                                                <th>Engineering</th>
                                                <th>Procurement</th>
                                                <th>Civil</th>
                                                <th>Installation</th>
                                                <th>Testing</th>
                                                <th>Disbursement</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($projects)): ?>
                                            <tr>
                                                <td colspan="10" class="text-center text-muted py-4">
                                                    No projects found
                                                </td>
                                            </tr>
                                            <?php else: ?>
                                            <?php foreach ($projects as $project): ?>
                                            <tr>
                                                <td>
                                                    <a href="../project.php?id=<?php echo $project['id']; ?>" class="text-reset">
                                                        <?php echo htmlspecialchars($project['project_name']); ?>
                                                    </a>
                                                </td>
                                                <td>₦<?php echo number_format($project['contract_value_ngn']); ?></td>
                                                <td><?php echo $project['contractual_completion'] ? date('M j, Y', strtotime($project['contractual_completion'])) : 'N/A'; ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="me-2"><?php echo round($project['overall_progress'] ?? 0, 1); ?>%</span>
                                                        <div class="progress" style="width: 60px;">
                                                            <div class="progress-bar bg-success" style="width: <?php echo round($project['overall_progress'] ?? 0); ?>%"></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary"><?php echo round($project['engineering_design'] ?? 0); ?>%</span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo round($project['procurement'] ?? 0); ?>%</span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-warning"><?php echo round($project['civil'] ?? 0); ?>%</span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-orange"><?php echo round($project['installation'] ?? 0); ?>%</span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-purple"><?php echo round($project['testing_commissioning'] ?? 0); ?>%</span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="me-2"><?php echo round($project['disbursement_progress'] ?? 0); ?>%</span>
                                                        <div class="progress" style="width: 40px;">
                                                            <div class="progress-bar bg-yellow" style="width: <?php echo round($project['disbursement_progress'] ?? 0); ?>%"></div>
                                                        </div>
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
        </div>
    </div>
    
    <!-- Tabler JS -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/js/tabler.min.js"></script>
    
    <!-- Custom JS -->
    <script src="../assets/script.js"></script>
</body>
</html>
