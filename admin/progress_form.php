<?php
/**
 * Progress Update Form
 * Project Tracking and Reporting Application
 */

define('PTRA_ACCESS', true);
require_once '../inc/config.php';
require_once '../inc/db.php';
require_once '../inc/auth.php';

startSecureSession();
requireRole('pm'); // PM or Admin can update progress

$db = getDB();
$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$project_id) {
    header('Location: dashboard.php');
    exit();
}

// Get project details
$stmt = $db->prepare("SELECT * FROM projects WHERE id = ? AND is_active = 1");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: dashboard.php?error=project_not_found');
    exit();
}

$project = $result->fetch_assoc();

// Get current progress
$stmt = $db->prepare("SELECT * FROM project_progress WHERE project_id = ?");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();

$progress = [
    'engineering_design' => 0,
    'procurement' => 0,
    'civil' => 0,
    'installation' => 0,
    'testing_commissioning' => 0,
    'disbursement_progress' => 0,
    'planned_progress' => 0,
    'actual_progress' => 0
];

if ($result->num_rows > 0) {
    $existing_progress = $result->fetch_assoc();
    $progress = array_merge($progress, $existing_progress);
}

$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['csrf_token']) && verifyCSRFToken($_POST['csrf_token'])) {
        // Sanitize and validate input
        $engineering_design = max(0, min(100, (float)$_POST['engineering_design']));
        $procurement = max(0, min(100, (float)$_POST['procurement']));
        $civil = max(0, min(100, (float)$_POST['civil']));
        $installation = max(0, min(100, (float)$_POST['installation']));
        $testing_commissioning = max(0, min(100, (float)$_POST['testing_commissioning']));
        $disbursement_progress = max(0, min(100, (float)$_POST['disbursement_progress']));
        $planned_progress = max(0, min(100, (float)$_POST['planned_progress']));
        $actual_progress = max(0, min(100, (float)$_POST['actual_progress']));
        
        // Calculate overall progress
        $overall_progress = (
            ($engineering_design * 0.2) +
            ($procurement * 0.25) +
            ($civil * 0.25) +
            ($installation * 0.25) +
            ($testing_commissioning * 0.05)
        );
        
        // Update or insert progress
        if ($result->num_rows > 0) {
            $stmt = $db->prepare("
                UPDATE project_progress SET 
                    engineering_design = ?, 
                    procurement = ?, 
                    civil = ?, 
                    installation = ?, 
                    testing_commissioning = ?, 
                    disbursement_progress = ?, 
                    planned_progress = ?, 
                    actual_progress = ?,
                    updated_by = ?
                WHERE project_id = ?
            ");
            $stmt->bind_param("ddddddddii", 
                $engineering_design, $procurement, $civil, $installation, 
                $testing_commissioning, $disbursement_progress, 
                $planned_progress, $actual_progress, $_SESSION['user_id'], $project_id
            );
        } else {
            $stmt = $db->prepare("
                INSERT INTO project_progress (
                    project_id, engineering_design, procurement, civil, 
                    installation, testing_commissioning, disbursement_progress, 
                    planned_progress, actual_progress, updated_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iddddddddi", 
                $project_id, $engineering_design, $procurement, $civil, 
                $installation, $testing_commissioning, $disbursement_progress, 
                $planned_progress, $actual_progress, $_SESSION['user_id']
            );
        }
        
        if ($stmt->execute()) {
            $success_message = 'Progress updated successfully. Overall progress: ' . number_format($overall_progress, 1) . '%';
            
            // Update the progress array for display
            $progress = [
                'engineering_design' => $engineering_design,
                'procurement' => $procurement,
                'civil' => $civil,
                'installation' => $installation,
                'testing_commissioning' => $testing_commissioning,
                'disbursement_progress' => $disbursement_progress,
                'planned_progress' => $planned_progress,
                'actual_progress' => $actual_progress
            ];
        } else {
            $error_message = 'Error updating progress.';
        }
    } else {
        $error_message = 'Security token validation failed. Please try again.';
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Progress - <?php echo htmlspecialchars($project['project_name']); ?> - <?php echo APP_NAME; ?></title>
    
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
                                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="../project.php?id=<?php echo $project_id; ?>"><?php echo htmlspecialchars($project['project_name']); ?></a></li>
                                    <li class="breadcrumb-item active">Update Progress</li>
                                </ol>
                            </nav>
                            <h2 class="page-title">Update Progress</h2>
                            <div class="page-subtitle"><?php echo htmlspecialchars($project['project_name']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Page Body -->
            <div class="page-body">
                <div class="container-xl">
                    <div class="row justify-content-center">
                        <div class="col-12 col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Progress Percentages</h3>
                                    <div class="card-subtitle">Update the completion percentage for each project phase</div>
                                </div>
                                <div class="card-body">
                                    <?php if ($error_message): ?>
                                    <div class="alert alert-danger" role="alert">
                                        <?php echo htmlspecialchars($error_message); ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($success_message): ?>
                                    <div class="alert alert-success" role="alert">
                                        <?php echo htmlspecialchars($success_message); ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <form method="POST" id="progressForm">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        
                                        <!-- Technical Progress -->
                                        <h4 class="mb-3">Technical Progress (Weighted for Overall Calculation)</h4>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Engineering Design (20% weight)</label>
                                                    <div class="input-group">
                                                        <input type="number" name="engineering_design" class="form-control" 
                                                               value="<?php echo $progress['engineering_design']; ?>" 
                                                               min="0" max="100" step="0.1">
                                                        <span class="input-group-text">%</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Procurement (25% weight)</label>
                                                    <div class="input-group">
                                                        <input type="number" name="procurement" class="form-control" 
                                                               value="<?php echo $progress['procurement']; ?>" 
                                                               min="0" max="100" step="0.1">
                                                        <span class="input-group-text">%</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Civil Works (25% weight)</label>
                                                    <div class="input-group">
                                                        <input type="number" name="civil" class="form-control" 
                                                               value="<?php echo $progress['civil']; ?>" 
                                                               min="0" max="100" step="0.1">
                                                        <span class="input-group-text">%</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Installation (25% weight)</label>
                                                    <div class="input-group">
                                                        <input type="number" name="installation" class="form-control" 
                                                               value="<?php echo $progress['installation']; ?>" 
                                                               min="0" max="100" step="0.1">
                                                        <span class="input-group-text">%</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Testing & Commissioning (5% weight)</label>
                                                    <div class="input-group">
                                                        <input type="number" name="testing_commissioning" class="form-control" 
                                                               value="<?php echo $progress['testing_commissioning']; ?>" 
                                                               min="0" max="100" step="0.1">
                                                        <span class="input-group-text">%</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Overall Progress (Calculated)</label>
                                                    <div class="input-group">
                                                        <input type="text" id="overall_progress" class="form-control" readonly>
                                                        <span class="input-group-text">%</span>
                                                    </div>
                                                    <div class="form-hint">Automatically calculated based on weighted averages</div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Financial and Planning Progress -->
                                        <h4 class="mt-4 mb-3">Financial & Planning Progress</h4>
                                        
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Disbursement Progress</label>
                                                    <div class="input-group">
                                                        <input type="number" name="disbursement_progress" class="form-control" 
                                                               value="<?php echo $progress['disbursement_progress']; ?>" 
                                                               min="0" max="100" step="0.1">
                                                        <span class="input-group-text">%</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Planned Progress</label>
                                                    <div class="input-group">
                                                        <input type="number" name="planned_progress" class="form-control" 
                                                               value="<?php echo $progress['planned_progress']; ?>" 
                                                               min="0" max="100" step="0.1">
                                                        <span class="input-group-text">%</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Actual Progress</label>
                                                    <div class="input-group">
                                                        <input type="number" name="actual_progress" class="form-control" 
                                                               value="<?php echo $progress['actual_progress']; ?>" 
                                                               min="0" max="100" step="0.1">
                                                        <span class="input-group-text">%</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Form Actions -->
                                        <div class="form-footer">
                                            <div class="btn-list">
                                                <a href="../project.php?id=<?php echo $project_id; ?>" class="btn btn-secondary">
                                                    Cancel
                                                </a>
                                                <button type="submit" class="btn btn-primary" data-original-text="Update Progress">
                                                    Update Progress
                                                </button>
                                            </div>
                                        </div>
                                    </form>
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
    
    <script>
        // Calculate overall progress in real-time
        function calculateOverallProgress() {
            const engineering = parseFloat(document.querySelector('input[name="engineering_design"]').value) || 0;
            const procurement = parseFloat(document.querySelector('input[name="procurement"]').value) || 0;
            const civil = parseFloat(document.querySelector('input[name="civil"]').value) || 0;
            const installation = parseFloat(document.querySelector('input[name="installation"]').value) || 0;
            const testing = parseFloat(document.querySelector('input[name="testing_commissioning"]').value) || 0;
            
            const overall = (engineering * 0.2) + (procurement * 0.25) + (civil * 0.25) + (installation * 0.25) + (testing * 0.05);
            
            document.getElementById('overall_progress').value = overall.toFixed(1);
        }
        
        // Add event listeners to all progress inputs
        document.addEventListener('DOMContentLoaded', function() {
            const progressInputs = document.querySelectorAll('input[name="engineering_design"], input[name="procurement"], input[name="civil"], input[name="installation"], input[name="testing_commissioning"]');
            
            progressInputs.forEach(input => {
                input.addEventListener('input', calculateOverallProgress);
            });
            
            // Calculate initial value
            calculateOverallProgress();
        });
    </script>
</body>
</html>
