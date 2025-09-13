<?php
/**
 * User Create/Edit Form
 * Project Tracking and Reporting Application
 */

define('PTRA_ACCESS', true);
require_once '../inc/config.php';
require_once '../inc/db.php';
require_once '../inc/auth.php';

startSecureSession();
requireRole('admin'); // Only admins can manage users

$db = getDB();
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_edit = $user_id > 0;

$user = [
    'username' => '',
    'email' => '',
    'full_name' => '',
    'role' => 'viewer',
    'is_active' => 1
];

$error_message = '';
$success_message = '';

// Load existing user data for editing
if ($is_edit) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('Location: users.php?error=user_not_found');
        exit();
    }
    
    $user = $result->fetch_assoc();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['csrf_token']) && verifyCSRFToken($_POST['csrf_token'])) {
        // Sanitize and validate input
        $username = sanitizeInput($_POST['username']);
        $email = sanitizeInput($_POST['email']);
        $full_name = sanitizeInput($_POST['full_name']);
        $role = sanitizeInput($_POST['role']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Validation
        $errors = [];
        
        if (empty($username)) {
            $errors[] = 'Username is required.';
        } elseif (strlen($username) < 3) {
            $errors[] = 'Username must be at least 3 characters long.';
        }
        
        if (empty($email) || !isValidEmail($email)) {
            $errors[] = 'Valid email address is required.';
        }
        
        if (empty($full_name)) {
            $errors[] = 'Full name is required.';
        }
        
        if (!in_array($role, ['admin', 'pm', 'viewer'])) {
            $errors[] = 'Invalid role selected.';
        }
        
        // Password validation (required for new users, optional for editing)
        if (!$is_edit) {
            if (empty($password)) {
                $errors[] = 'Password is required for new users.';
            } elseif (strlen($password) < 6) {
                $errors[] = 'Password must be at least 6 characters long.';
            } elseif ($password !== $confirm_password) {
                $errors[] = 'Passwords do not match.';
            }
        } elseif (!empty($password)) {
            if (strlen($password) < 6) {
                $errors[] = 'Password must be at least 6 characters long.';
            } elseif ($password !== $confirm_password) {
                $errors[] = 'Passwords do not match.';
            }
        }
        
        // Check for duplicate username/email
        if ($is_edit) {
            $stmt = $db->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $stmt->bind_param("ssi", $username, $email, $user_id);
        } else {
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $email);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = 'Username or email already exists.';
        }
        
        // Prevent admin from changing their own role or deactivating themselves
        if ($is_edit && $user_id === $_SESSION['user_id']) {
            if ($role !== $_SESSION['role']) {
                $errors[] = 'You cannot change your own role.';
            }
            if (!$is_active) {
                $errors[] = 'You cannot deactivate your own account.';
            }
        }
        
        if (empty($errors)) {
            if ($is_edit) {
                // Update existing user
                if (!empty($password)) {
                    $hashed_password = hashPassword($password);
                    $stmt = $db->prepare("
                        UPDATE users SET 
                            username = ?, email = ?, full_name = ?, role = ?, 
                            password = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP
                        WHERE id = ?
                    ");
                    $stmt->bind_param("ssssiii", $username, $email, $full_name, $role, $hashed_password, $is_active, $user_id);
                } else {
                    $stmt = $db->prepare("
                        UPDATE users SET 
                            username = ?, email = ?, full_name = ?, role = ?, 
                            is_active = ?, updated_at = CURRENT_TIMESTAMP
                        WHERE id = ?
                    ");
                    $stmt->bind_param("sssiii", $username, $email, $full_name, $role, $is_active, $user_id);
                }
                
                if ($stmt->execute()) {
                    $success_message = 'User updated successfully.';
                    
                    // Update session if user edited themselves
                    if ($user_id === $_SESSION['user_id']) {
                        $_SESSION['username'] = $username;
                        $_SESSION['email'] = $email;
                        $_SESSION['full_name'] = $full_name;
                    }
                } else {
                    $error_message = 'Error updating user.';
                }
            } else {
                // Create new user
                $hashed_password = hashPassword($password);
                $stmt = $db->prepare("
                    INSERT INTO users (username, email, full_name, role, password, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("sssssi", $username, $email, $full_name, $role, $hashed_password, $is_active);
                
                if ($stmt->execute()) {
                    header('Location: users.php?success=user_created');
                    exit();
                } else {
                    $error_message = 'Error creating user.';
                }
            }
        } else {
            $error_message = implode(' ', $errors);
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
    <title><?php echo $is_edit ? 'Edit' : 'Add'; ?> User - <?php echo APP_NAME; ?></title>
    
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
                                            <path d="m5 12 0 7a2 2 0 0 0 2 2l0 -7"></path>
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
                            <li class="nav-item">
                                <a class="nav-link active" href="users.php">
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
                                    <li class="breadcrumb-item"><a href="users.php">Users</a></li>
                                    <li class="breadcrumb-item active"><?php echo $is_edit ? 'Edit' : 'Add'; ?> User</li>
                                </ol>
                            </nav>
                            <h2 class="page-title"><?php echo $is_edit ? 'Edit' : 'Add New'; ?> User</h2>
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
                                    <h3 class="card-title">User Information</h3>
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
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label required">Username</label>
                                                    <input type="text" name="username" class="form-control" 
                                                           value="<?php echo htmlspecialchars($user['username']); ?>" 
                                                           required minlength="3" maxlength="50">
                                                    <div class="form-hint">Must be unique and at least 3 characters long</div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label required">Email Address</label>
                                                    <input type="email" name="email" class="form-control" 
                                                           value="<?php echo htmlspecialchars($user['email']); ?>" 
                                                           required maxlength="100">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label required">Full Name</label>
                                                    <input type="text" name="full_name" class="form-control" 
                                                           value="<?php echo htmlspecialchars($user['full_name']); ?>" 
                                                           required maxlength="100">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label required">Role</label>
                                                    <select name="role" class="form-select" required 
                                                            <?php echo ($is_edit && $user_id === $_SESSION['user_id']) ? 'disabled' : ''; ?>>
                                                        <option value="viewer" <?php echo $user['role'] === 'viewer' ? 'selected' : ''; ?>>
                                                            Viewer - Can view projects and reports
                                                        </option>
                                                        <option value="pm" <?php echo $user['role'] === 'pm' ? 'selected' : ''; ?>>
                                                            Project Manager - Can create and edit projects
                                                        </option>
                                                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>
                                                            Administrator - Full system access
                                                        </option>
                                                    </select>
                                                    <?php if ($is_edit && $user_id === $_SESSION['user_id']): ?>
                                                    <input type="hidden" name="role" value="<?php echo $user['role']; ?>">
                                                    <div class="form-hint text-warning">You cannot change your own role</div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Password Section -->
                                        <h4 class="mt-4 mb-3"><?php echo $is_edit ? 'Change Password (Optional)' : 'Password'; ?></h4>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label <?php echo !$is_edit ? 'required' : ''; ?>">
                                                        <?php echo $is_edit ? 'New Password' : 'Password'; ?>
                                                    </label>
                                                    <input type="password" name="password" class="form-control" 
                                                           <?php echo !$is_edit ? 'required' : ''; ?> minlength="6">
                                                    <div class="form-hint">
                                                        <?php echo $is_edit ? 'Leave blank to keep current password. ' : ''; ?>
                                                        Minimum 6 characters
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label <?php echo !$is_edit ? 'required' : ''; ?>">
                                                        Confirm Password
                                                    </label>
                                                    <input type="password" name="confirm_password" class="form-control" 
                                                           <?php echo !$is_edit ? 'required' : ''; ?> minlength="6">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Status -->
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="mb-3">
                                                    <label class="form-check">
                                                        <input type="checkbox" name="is_active" class="form-check-input" 
                                                               <?php echo $user['is_active'] ? 'checked' : ''; ?>
                                                               <?php echo ($is_edit && $user_id === $_SESSION['user_id']) ? 'disabled' : ''; ?>>
                                                        <span class="form-check-label">Active User</span>
                                                    </label>
                                                    <?php if ($is_edit && $user_id === $_SESSION['user_id']): ?>
                                                    <input type="hidden" name="is_active" value="1">
                                                    <div class="form-hint text-warning">You cannot deactivate your own account</div>
                                                    <?php else: ?>
                                                    <div class="form-hint">Inactive users cannot log in to the system</div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Form Actions -->
                                        <div class="form-footer">
                                            <div class="btn-list">
                                                <a href="users.php" class="btn btn-secondary">
                                                    Cancel
                                                </a>
                                                <button type="submit" class="btn btn-primary" data-original-text="<?php echo $is_edit ? 'Update User' : 'Create User'; ?>">
                                                    <?php echo $is_edit ? 'Update User' : 'Create User'; ?>
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
