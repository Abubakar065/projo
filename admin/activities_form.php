<?php
/**
 * Activities Management Form
 * Project Tracking and Reporting Application
 */

define('PTRA_ACCESS', true);
require_once '../inc/config.php';
require_once '../inc/db.php';
require_once '../inc/auth.php';

startSecureSession();
requireRole('pm'); // PM or Admin can manage activities

$db = getDB();
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$activity_type = isset($_GET['type']) ? $_GET['type'] : 'monthly'; // 'monthly' or 'planned'

if (!$project_id || !in_array($activity_type, ['monthly', 'planned'])) {
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

// Determine month/year based on activity type
$month_year = $activity_type === 'monthly' ? date('Y-m') : date('Y-m', strtotime('+1 month'));
$table_name = $activity_type === 'monthly' ? 'monthly_summary' : 'planned_activities';

$error_message = '';
$success_message = '';

// Get existing activities
$stmt = $db->prepare("SELECT * FROM {$table_name} WHERE project_id = ? AND month_year = ? ORDER BY serial_number");
$stmt->bind_param("is", $project_id, $month_year);
$stmt->execute();
$existing_activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['csrf_token']) && verifyCSRFToken($_POST['csrf_token'])) {
        
        if (isset($_POST['add_activity'])) {
            // Add new activity
            $description = sanitizeInput($_POST['activity_description']);
            $duration = (int)$_POST['duration_weeks'];
            $responsible_party = $activity_type === 'planned' ? sanitizeInput($_POST['responsible_party']) : '';
            $remarks = $activity_type === 'planned' ? sanitizeInput($_POST['remarks']) : '';
            
            if (empty($description) || $duration <= 0) {
                $error_message = 'Activity description and duration are required.';
            } else {
                // Get next serial number
                $stmt = $db->prepare("SELECT COALESCE(MAX(serial_number), 0) + 1 as next_serial FROM {$table_name} WHERE project_id = ? AND month_year = ?");
                $stmt->bind_param("is", $project_id, $month_year);
                $stmt->execute();
                $next_serial = $stmt->get_result()->fetch_assoc()['next_serial'];
                
                if ($activity_type === 'monthly') {
                    $stmt = $db->prepare("INSERT INTO monthly_summary (project_id, month_year, activity_description, duration_weeks, serial_number) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("issii", $project_id, $month_year, $description, $duration, $next_serial);
                } else {
                    $stmt = $db->prepare("INSERT INTO planned_activities (project_id, month_year, activity_description, duration_weeks, responsible_party, remarks, serial_number) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ississi", $project_id, $month_year, $description, $duration, $responsible_party, $remarks, $next_serial);
                }
                
                if ($stmt->execute()) {
                    $success_message = 'Activity added successfully.';
                    // Refresh activities list
                    $stmt = $db->prepare("SELECT * FROM {$table_name} WHERE project_id = ? AND month_year = ? ORDER BY serial_number");
                    $stmt->bind_param("is", $project_id, $month_year);
                    $stmt->execute();
                    $existing_activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                } else {
                    $error_message = 'Error adding activity.';
                }
            }
        } elseif (isset($_POST['delete_activity'])) {
            // Delete activity
            $activity_id = (int)$_POST['activity_id'];
            $stmt = $db->prepare("DELETE FROM {$table_name} WHERE id = ? AND project_id = ?");
            $stmt->bind_param("ii", $activity_id, $project_id);
            
            if ($stmt->execute()) {
                $success_message = 'Activity deleted successfully.';
                // Refresh activities list
                $stmt = $db->prepare("SELECT * FROM {$table_name} WHERE project_id = ? AND month_year = ? ORDER BY serial_number");
                $stmt->bind_param("is", $project_id, $month_year);
                $stmt->execute();
                $existing_activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            } else {
                $error_message = 'Error deleting activity.';
            }
        }
    } else {
        $error_message = 'Security token validation failed.';
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage <?php echo ucfirst($activity_type); ?> Activities - <?php echo htmlspecialchars($project['project_name']); ?> - <?php echo APP_NAME; ?></title>
    
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
                                    <li class="breadcrumb-item active">Manage <?php echo ucfirst($activity_type); ?> Activities</li>
                                </ol>
                            </nav>
                            <h2 class="page-title">Manage <?php echo ucfirst($activity_type); ?> Activities</h2>
                            <div class="page-subtitle">
                                <?php echo $activity_type === 'monthly' ? date('F Y') : date('F Y', strtotime('+1 month')); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Page Body -->
            <div class="page-body">
                <div class="container-xl">
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
                    
                    <div class="row">
                        <!-- Add New Activity -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Add New Activity</h3>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        
                                        <div class="mb-3">
                                            <label class="form-label required">Activity Description</label>
                                            <textarea name="activity_description" class="form-control" rows="3" 
                                                      placeholder="Describe the activity..." required></textarea>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label required">Duration (Weeks)</label>
                                            <input type="number" name="duration_weeks" class="form-control" 
                                                   min="1" max="52" required>
                                        </div>
                                        
                                        <?php if ($activity_type === 'planned'): ?>
                                        <div class="mb-3">
                                            <label class="form-label">Responsible Party</label>
                                            <input type="text" name="responsible_party" class="form-control" 
                                                   placeholder="Who is responsible for this activity?">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Remarks</label>
                                            <textarea name="remarks" class="form-control" rows="2" 
                                                      placeholder="Additional notes or remarks..."></textarea>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <button type="submit" name="add_activity" class="btn btn-primary w-100">
                                            Add Activity
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Existing Activities -->
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        Current <?php echo ucfirst($activity_type); ?> Activities
                                        <span class="badge bg-primary ms-2"><?php echo count($existing_activities); ?></span>
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($existing_activities)): ?>
                                    <div class="text-center text-muted py-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg mb-3" width="48" height="48" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                                            <polyline points="3,7 12,13 21,7"></polyline>
                                        </svg>
                                        <p>No activities added yet.</p>
                                        <p class="small">Use the form on the left to add your first activity.</p>
                                    </div>
                                    <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-vcenter">
                                            <thead>
                                                <tr>
                                                    <th>S/No.</th>
                                                    <th>Description</th>
                                                    <th>Duration</th>
                                                    <?php if ($activity_type === 'planned'): ?>
                                                    <th>Responsible Party</th>
                                                    <th>Remarks</th>
                                                    <?php endif; ?>
                                                    <th class="w-1">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($existing_activities as $activity): ?>
                                                <tr>
                                                    <td><?php echo $activity['serial_number']; ?></td>
                                                    <td>
                                                        <div class="text-wrap" style="max-width: 300px;">
                                                            <?php echo htmlspecialchars($activity['activity_description']); ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info">
                                                            <?php echo $activity['duration_weeks']; ?> week<?php echo $activity['duration_weeks'] > 1 ? 's' : ''; ?>
                                                        </span>
                                                    </td>
                                                    <?php if ($activity_type === 'planned'): ?>
                                                    <td><?php echo htmlspecialchars($activity['responsible_party'] ?? 'Not assigned'); ?></td>
                                                    <td>
                                                        <div class="text-wrap" style="max-width: 200px;">
                                                            <?php echo htmlspecialchars($activity['remarks'] ?? ''); ?>
                                                        </div>
                                                    </td>
                                                    <?php endif; ?>
                                                    <td>
                                                        <form method="POST" style="display: inline;" 
                                                              onsubmit="return confirm('Are you sure you want to delete this activity?');">
                                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                            <input type="hidden" name="activity_id" value="<?php echo $activity['id']; ?>">
                                                            <button type="submit" name="delete_activity" class="btn btn-sm btn-outline-danger">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                                    <line x1="4" y1="7" x2="20" y2="7"></line>
                                                                    <line x1="10" y1="11" x2="10" y2="17"></line>
                                                                    <line x1="14" y1="11" x2="14" y2="17"></line>
                                                                    <path d="m5 7 1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"></path>
                                                                    <path d="m9 7 0 -3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1l0 3"></path>
                                                                </svg>
                                                                Delete
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <a href="../project.php?id=<?php echo $project_id; ?>" class="btn btn-secondary">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="m15 6 -6 6 6 6"></path>
                                        </svg>
                                        Back to Project
                                    </a>
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
