<?php
/**
 * Project Details Page
 * Project Tracking and Reporting Application
 */

define('PTRA_ACCESS', true);
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/auth.php';

startSecureSession();
requireLogin();

$db = getDB();
$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$project_id) {
    header('Location: admin/dashboard.php');
    exit();
}

// Get project details
$stmt = $db->prepare("
    SELECT 
        p.*,
        pp.engineering_design,
        pp.procurement,
        pp.civil,
        pp.installation,
        pp.testing_commissioning,
        pp.disbursement_progress,
        pp.planned_progress,
        pp.actual_progress,
        u.full_name as created_by_name
    FROM projects p
    LEFT JOIN project_progress pp ON p.id = pp.project_id
    LEFT JOIN users u ON p.created_by = u.id
    WHERE p.id = ? AND p.is_active = 1
");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: admin/dashboard.php?error=project_not_found');
    exit();
}

$project = $result->fetch_assoc();

// Calculate overall progress
$overall_progress = (
    ($project['engineering_design'] * 0.2) +
    ($project['procurement'] * 0.25) +
    ($project['civil'] * 0.25) +
    ($project['installation'] * 0.25) +
    ($project['testing_commissioning'] * 0.05)
);
$project['overall_progress'] = round($overall_progress, 1);

// Get current month activities
$current_month = date('Y-m');
$stmt = $db->prepare("
    SELECT * FROM monthly_summary 
    WHERE project_id = ? AND month_year = ? 
    ORDER BY serial_number
");
$stmt->bind_param("is", $project_id, $current_month);
$stmt->execute();
$monthly_activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get next month planned activities
$next_month = date('Y-m', strtotime('+1 month'));
$stmt = $db->prepare("
    SELECT * FROM planned_activities 
    WHERE project_id = ? AND month_year = ? 
    ORDER BY serial_number
");
$stmt->bind_param("is", $project_id, $next_month);
$stmt->execute();
$planned_activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get project images
$stmt = $db->prepare("
    SELECT pi.*, u.full_name as uploaded_by_name
    FROM project_images pi
    LEFT JOIN users u ON pi.uploaded_by = u.id
    WHERE pi.project_id = ?
    ORDER BY pi.uploaded_at DESC
");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$project_images = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Helper functions
function formatCurrency($amount, $currency = 'NGN') {
    if ($currency === 'NGN') {
        return '₦' . number_format($amount, 0);
    } else {
        return '$' . number_format($amount, 0);
    }
}

function getProgressColor($percentage) {
    if ($percentage >= 80) return 'success';
    if ($percentage >= 60) return 'info';
    if ($percentage >= 40) return 'warning';
    return 'danger';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($project['project_name']); ?> - <?php echo APP_NAME; ?></title>
    
    <!-- Tabler CSS -->
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/style.css" rel="stylesheet">
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
                                <?php echo strtoupper(substr($_SESSION['full_name'], 0, 2)); ?>
                            </span>
                            <div class="d-none d-xl-block ps-2">
                                <div class="text-white"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                                <div class="mt-1 small text-white-50"><?php echo ucfirst($_SESSION['role']); ?></div>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="logout.php" class="dropdown-item text-danger">
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
                                <a class="nav-link" href="admin/dashboard.php">
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
                                <a class="nav-link active" href="admin/projects.php">
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
                                <a class="nav-link" href="admin/reports.php">
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
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="admin/dashboard.php">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="admin/projects.php">Projects</a></li>
                                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($project['project_name']); ?></li>
                                </ol>
                            </nav>
                            <h2 class="page-title"><?php echo htmlspecialchars($project['project_name']); ?></h2>
                        </div>
                        <div class="col-auto ms-auto d-print-none">
                            <?php if (hasRole('admin') || hasRole('pm')): ?>
                            <div class="btn-list">
                                <a href="admin/project_form.php?id=<?php echo $project['id']; ?>" class="btn btn-outline-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="m7 7 10 10"></path>
                                        <path d="m17 7 -10 10"></path>
                                    </svg>
                                    Edit Project
                                </a>
                                <a href="admin/progress_form.php?id=<?php echo $project['id']; ?>" class="btn btn-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"></path>
                                        <path d="M9 12l2 2l4 -4"></path>
                                    </svg>
                                    Update Progress
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Page Body -->
            <div class="page-body">
                <div class="container-xl">
                    <div class="row row-deck row-cards">
                        <!-- Project Overview -->
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Project Overview</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Contract Description</label>
                                                <p class="text-muted"><?php echo nl2br(htmlspecialchars($project['contract_description'] ?? 'Not specified')); ?></p>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Scope of Work</label>
                                                <p class="text-muted"><?php echo nl2br(htmlspecialchars($project['scope_of_work'] ?? 'Not specified')); ?></p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Contract Value (NGN)</label>
                                                        <div class="h4 text-success"><?php echo formatCurrency($project['contract_value_ngn']); ?></div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Contract Value (USD)</label>
                                                        <div class="h4 text-info"><?php echo formatCurrency($project['contract_value_usd'], 'USD'); ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Created By</label>
                                                <p class="text-muted"><?php echo htmlspecialchars($project['created_by_name'] ?? 'Unknown'); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Key Dates -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Key Dates</h3>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <tbody>
                                                <tr>
                                                    <td class="fw-bold">Notice of Award</td>
                                                    <td><?php echo $project['notice_of_award'] ? date('M j, Y', strtotime($project['notice_of_award'])) : 'Not set'; ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-bold">Contract Signed</td>
                                                    <td><?php echo $project['contract_signed'] ? date('M j, Y', strtotime($project['contract_signed'])) : 'Not set'; ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-bold">Commencement</td>
                                                    <td><?php echo $project['commencement_date'] ? date('M j, Y', strtotime($project['commencement_date'])) : 'Not set'; ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-bold">Proposed Completion</td>
                                                    <td><?php echo $project['proposed_completion'] ? date('M j, Y', strtotime($project['proposed_completion'])) : 'Not set'; ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-bold">Contractual Completion</td>
                                                    <td class="<?php echo $project['contractual_completion'] && new DateTime($project['contractual_completion']) < new DateTime() ? 'text-danger' : ''; ?>">
                                                        <?php echo $project['contractual_completion'] ? date('M j, Y', strtotime($project['contractual_completion'])) : 'Not set'; ?>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Progress Overview -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Progress Overview</h3>
                                </div>
                                <div class="card-body">
                                    <div class="progress-container">
                                        <div class="progress-label">
                                            <span>Overall Progress</span>
                                            <span class="fw-bold text-<?php echo getProgressColor($project['overall_progress']); ?>">
                                                <?php echo number_format($project['overall_progress'], 1); ?>%
                                            </span>
                                        </div>
                                        <div class="progress mb-3">
                                            <div class="progress-bar bg-<?php echo getProgressColor($project['overall_progress']); ?>" 
                                                 style="width: <?php echo $project['overall_progress']; ?>%"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="text-muted small">Engineering Design</div>
                                            <div class="fw-bold"><?php echo number_format($project['engineering_design'] ?? 0, 1); ?>%</div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted small">Procurement</div>
                                            <div class="fw-bold"><?php echo number_format($project['procurement'] ?? 0, 1); ?>%</div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted small">Civil Works</div>
                                            <div class="fw-bold"><?php echo number_format($project['civil'] ?? 0, 1); ?>%</div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted small">Installation</div>
                                            <div class="fw-bold"><?php echo number_format($project['installation'] ?? 0, 1); ?>%</div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted small">Testing & Commissioning</div>
                                            <div class="fw-bold"><?php echo number_format($project['testing_commissioning'] ?? 0, 1); ?>%</div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted small">Disbursement</div>
                                            <div class="fw-bold text-warning"><?php echo number_format($project['disbursement_progress'] ?? 0, 1); ?>%</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Monthly Activities -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Current Month Activities (<?php echo date('F Y'); ?>)</h3>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($monthly_activities)): ?>
                                    <div class="text-center text-muted py-3">
                                        <p>No activities recorded for this month.</p>
                                        <?php if (hasRole('admin') || hasRole('pm')): ?>
                                        <a href="admin/activities_form.php?project_id=<?php echo $project['id']; ?>&type=monthly" class="btn btn-sm btn-outline-primary">
                                            Add Activities
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                    <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>S/No.</th>
                                                    <th>Description</th>
                                                    <th>Duration (Weeks)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($monthly_activities as $activity): ?>
                                                <tr>
                                                    <td><?php echo $activity['serial_number']; ?></td>
                                                    <td><?php echo htmlspecialchars($activity['activity_description']); ?></td>
                                                    <td><?php echo $activity['duration_weeks']; ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Planned Activities -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Planned Activities (<?php echo date('F Y', strtotime('+1 month')); ?>)</h3>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($planned_activities)): ?>
                                    <div class="text-center text-muted py-3">
                                        <p>No planned activities for next month.</p>
                                        <?php if (hasRole('admin') || hasRole('pm')): ?>
                                        <a href="admin/activities_form.php?project_id=<?php echo $project['id']; ?>&type=planned" class="btn btn-sm btn-outline-primary">
                                            Add Planned Activities
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                    <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>S/No.</th>
                                                    <th>Description</th>
                                                    <th>Duration</th>
                                                    <th>Responsible Party</th>
                                                    <th>Remarks</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($planned_activities as $activity): ?>
                                                <tr>
                                                    <td><?php echo $activity['serial_number']; ?></td>
                                                    <td><?php echo htmlspecialchars($activity['activity_description']); ?></td>
                                                    <td><?php echo $activity['duration_weeks']; ?> weeks</td>
                                                    <td><?php echo htmlspecialchars($activity['responsible_party'] ?? 'Not assigned'); ?></td>
                                                    <td><?php echo htmlspecialchars($activity['remarks'] ?? ''); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Photo Gallery -->
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <h3 class="card-title">Photo Gallery</h3>
                                        </div>
                                        <div class="col-auto">
                                            <?php if (hasRole('admin') || hasRole('pm')): ?>
                                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#uploadModal">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                                </svg>
                                                Upload Photo
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($project_images)): ?>
                                    <div class="text-center text-muted py-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg mb-3" width="48" height="48" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <rect x="3" y="3" width="18" height="14" rx="2"></rect>
                                            <circle cx="9" cy="9" r="2"></circle>
                                            <path d="m21 15 -3.086 -3.086a2 2 0 0 0 -2.828 0l-6.086 6.086"></path>
                                        </svg>
                                        <p>No photos uploaded yet.</p>
                                        <?php if (hasRole('admin') || hasRole('pm')): ?>
                                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                                            Upload First Photo
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    <?php else: ?>
                                    <div class="photo-gallery">
                                        <?php foreach ($project_images as $image): ?>
                                        <div class="photo-item">
                                            <img src="uploads/<?php echo htmlspecialchars($image['image_filename']); ?>" 
                                                 alt="<?php echo htmlspecialchars($image['image_caption'] ?? 'Project photo'); ?>"
                                                 loading="lazy">
                                            <?php if ($image['image_caption']): ?>
                                            <div class="photo-caption">
                                                <?php echo htmlspecialchars($image['image_caption']); ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Upload Modal -->
    <?php if (hasRole('admin') || hasRole('pm')): ?>
    <div class="modal modal-blur fade" id="uploadModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Photo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="upload.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Select Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*" required>
                            <div class="form-hint">Maximum file size: 5MB. Supported formats: JPG, PNG, GIF</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Caption (Optional)</label>
                            <textarea name="caption" class="form-control" rows="3" placeholder="Describe what this photo shows..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Upload Photo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Tabler JS -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/js/tabler.min.js"></script>
    
    <!-- Custom JS -->
    <script src="assets/script.js"></script>
</body>
</html>
