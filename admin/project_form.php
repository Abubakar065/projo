<?php
/**
 * Project Create/Edit Form
 * Project Tracking and Reporting Application
 */

define('PTRA_ACCESS', true);
require_once '../inc/config.php';
require_once '../inc/db.php';
require_once '../inc/auth.php';

startSecureSession();
requireRole('pm'); // PM or Admin can create/edit projects

$db = getDB();
$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_edit = $project_id > 0;

$project = [
    'project_name' => '',
    'contract_description' => '',
    'scope_of_work' => '',
    'contract_value_ngn' => '',
    'contract_value_usd' => '',
    'notice_of_award' => '',
    'contract_signed' => '',
    'commencement_date' => '',
    'proposed_completion' => '',
    'contractual_completion' => ''
];

$error_message = '';
$success_message = '';

// Load existing project data for editing
if ($is_edit) {
    $stmt = $db->prepare("SELECT * FROM projects WHERE id = ? AND is_active = 1");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('Location: dashboard.php?error=project_not_found');
        exit();
    }
    
    $project = $result->fetch_assoc();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['csrf_token']) && verifyCSRFToken($_POST['csrf_token'])) {
        // Sanitize and validate input
        $project_name = sanitizeInput($_POST['project_name']);
        $contract_description = sanitizeInput($_POST['contract_description']);
        $scope_of_work = sanitizeInput($_POST['scope_of_work']);
        $contract_value_ngn = (float)$_POST['contract_value_ngn'];
        $contract_value_usd = (float)$_POST['contract_value_usd'];
        $notice_of_award = $_POST['notice_of_award'] ?: null;
        $contract_signed = $_POST['contract_signed'] ?: null;
        $commencement_date = $_POST['commencement_date'] ?: null;
        $proposed_completion = $_POST['proposed_completion'] ?: null;
        $contractual_completion = $_POST['contractual_completion'] ?: null;
        
        // Validation
        if (empty($project_name)) {
            $error_message = 'Project name is required.';
        } elseif ($contract_value_ngn <= 0) {
            $error_message = 'Contract value (NGN) must be greater than zero.';
        } else {
            if ($is_edit) {
                // Update existing project
                $stmt = $db->prepare("
                    UPDATE projects SET 
                        project_name = ?, 
                        contract_description = ?, 
                        scope_of_work = ?, 
                        contract_value_ngn = ?, 
                        contract_value_usd = ?, 
                        notice_of_award = ?, 
                        contract_signed = ?, 
                        commencement_date = ?, 
                        proposed_completion = ?, 
                        contractual_completion = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                $stmt->bind_param("sssddsssssi", 
                    $project_name, $contract_description, $scope_of_work,
                    $contract_value_ngn, $contract_value_usd,
                    $notice_of_award, $contract_signed, $commencement_date,
                    $proposed_completion, $contractual_completion, $project_id
                );
                
                if ($stmt->execute()) {
                    $success_message = 'Project updated successfully.';
                } else {
                    $error_message = 'Error updating project.';
                }
            } else {
                // Create new project
                $stmt = $db->prepare("
                    INSERT INTO projects (
                        project_name, contract_description, scope_of_work,
                        contract_value_ngn, contract_value_usd,
                        notice_of_award, contract_signed, commencement_date,
                        proposed_completion, contractual_completion, created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("sssddsssssi", 
                    $project_name, $contract_description, $scope_of_work,
                    $contract_value_ngn, $contract_value_usd,
                    $notice_of_award, $contract_signed, $commencement_date,
                    $proposed_completion, $contractual_completion, $_SESSION['user_id']
                );
                
                if ($stmt->execute()) {
                    $new_project_id = $db->getLastInsertId();
                    
                    // Create initial progress record
                    $stmt = $db->prepare("
                        INSERT INTO project_progress (project_id, updated_by) 
                        VALUES (?, ?)
                    ");
                    $stmt->bind_param("ii", $new_project_id, $_SESSION['user_id']);
                    $stmt->execute();
                    
                    header('Location: ../project.php?id=' . $new_project_id);
                    exit();
                } else {
                    $error_message = 'Error creating project.';
                }
            }
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
    <title><?php echo $is_edit ? 'Edit' : 'Create'; ?> Project - <?php echo APP_NAME; ?></title>
    
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
                                    <li class="breadcrumb-item"><a href="projects.php">Projects</a></li>
                                    <li class="breadcrumb-item active"><?php echo $is_edit ? 'Edit' : 'Create'; ?> Project</li>
                                </ol>
                            </nav>
                            <h2 class="page-title"><?php echo $is_edit ? 'Edit' : 'Create New'; ?> Project</h2>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Page Body -->
            <div class="page-body">
                <div class="container-xl">
                    <div class="row justify-content-center">
                        <div class="col-12 col-lg-10">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Project Information</h3>
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
                                    
                                    <form method="POST" data-validate="true">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        
                                        <!-- Basic Information -->
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="mb-3">
                                                    <label class="form-label required">Project Name</label>
                                                    <input type="text" name="project_name" class="form-control" 
                                                           value="<?php echo htmlspecialchars($project['project_name']); ?>" 
                                                           required maxlength="200">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Contract Description</label>
                                                    <textarea name="contract_description" class="form-control" rows="4" 
                                                              placeholder="Brief description of the contract..."><?php echo htmlspecialchars($project['contract_description']); ?></textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Scope of Work</label>
                                                    <textarea name="scope_of_work" class="form-control" rows="4" 
                                                              placeholder="Detailed scope of work..."><?php echo htmlspecialchars($project['scope_of_work']); ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Contract Values -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label required">Contract Value (NGN)</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">₦</span>
                                                        <input type="number" name="contract_value_ngn" class="form-control" 
                                                               value="<?php echo $project['contract_value_ngn']; ?>" 
                                                               required min="0" step="0.01">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Contract Value (USD)</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">$</span>
                                                        <input type="number" name="contract_value_usd" class="form-control" 
                                                               value="<?php echo $project['contract_value_usd']; ?>" 
                                                               min="0" step="0.01">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Key Dates -->
                                        <h4 class="mt-4 mb-3">Key Dates</h4>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Notice of Award</label>
                                                    <input type="date" name="notice_of_award" class="form-control" 
                                                           value="<?php echo $project['notice_of_award']; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Contract Signed</label>
                                                    <input type="date" name="contract_signed" class="form-control" 
                                                           value="<?php echo $project['contract_signed']; ?>">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Commencement Date</label>
                                                    <input type="date" name="commencement_date" class="form-control" 
                                                           value="<?php echo $project['commencement_date']; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Proposed Completion</label>
                                                    <input type="date" name="proposed_completion" class="form-control" 
                                                           value="<?php echo $project['proposed_completion']; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Contractual Completion</label>
                                                    <input type="date" name="contractual_completion" class="form-control" 
                                                           value="<?php echo $project['contractual_completion']; ?>">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Form Actions -->
                                        <div class="form-footer">
                                            <div class="btn-list">
                                                <a href="<?php echo $is_edit ? '../project.php?id=' . $project_id : 'dashboard.php'; ?>" class="btn btn-secondary">
                                                    Cancel
                                                </a>
                                                <button type="submit" class="btn btn-primary" data-original-text="<?php echo $is_edit ? 'Update Project' : 'Create Project'; ?>">
                                                    <?php echo $is_edit ? 'Update Project' : 'Create Project'; ?>
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
</body>
</html>
