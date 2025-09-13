<?php
/**
 * Login Page
 * Project Tracking and Reporting Application
 */

define('PTRA_ACCESS', true);
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/auth.php';

startSecureSession();

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: admin/dashboard.php');
    exit();
}

$error_message = '';
$success_message = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['csrf_token']) && verifyCSRFToken($_POST['csrf_token'])) {
        $username = sanitizeInput($_POST['username']);
        $password = $_POST['password'];
        
        if (empty($username) || empty($password)) {
            $error_message = 'Please enter both username and password.';
        } else {
            if (loginUser($username, $password)) {
                header('Location: admin/dashboard.php');
                exit();
            } else {
                $error_message = 'Invalid username or password.';
            }
        }
    } else {
        $error_message = 'Security token validation failed. Please try again.';
    }
}

// Handle URL parameters
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'session_expired':
            $error_message = 'Your session has expired. Please log in again.';
            break;
        case 'insufficient_permissions':
            $error_message = 'You do not have permission to access that resource.';
            break;
    }
}

if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'logged_out':
            $success_message = 'You have been successfully logged out.';
            break;
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    
    <!-- Tabler CSS -->
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/style.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .login-card {
            max-width: 400px;
            margin: 0 auto;
            margin-top: 8vh;
        }
        
        .brand-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .brand-logo h1 {
            color: white;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .brand-logo p {
            color: rgba(255, 255, 255, 0.8);
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="page page-center">
        <div class="container container-tight py-4">
            <div class="brand-logo">
                <h1><?php echo APP_NAME; ?></h1>
                <p>Infrastructure Project Management System</p>
            </div>
            
            <div class="card login-card">
                <div class="card-body">
                    <h2 class="h2 text-center mb-4">Sign in to your account</h2>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger" role="alert">
                            <div class="d-flex">
                                <div>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <circle cx="12" cy="12" r="9"></circle>
                                        <line x1="12" y1="8" x2="12" y2="12"></line>
                                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                    </svg>
                                </div>
                                <div><?php echo htmlspecialchars($error_message); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success_message): ?>
                        <div class="alert alert-success" role="alert">
                            <div class="d-flex">
                                <div>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <circle cx="12" cy="12" r="9"></circle>
                                        <path d="m9 12 2 2 4 -4"></path>
                                    </svg>
                                </div>
                                <div><?php echo htmlspecialchars($success_message); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" autocomplete="off">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" placeholder="Enter username" 
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                                   required autofocus>
                        </div>
                        
                        <div class="mb-2">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                        </div>
                        
                        <div class="form-footer">
                            <button type="submit" class="btn btn-primary w-100">Sign in</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="text-center text-muted mt-3">
                <small>
                    Default login: <strong>admin</strong> / <strong>admin123</strong><br>
                    <?php echo APP_NAME; ?> v<?php echo APP_VERSION; ?>
                </small>
            </div>
        </div>
    </div>
    
    <!-- Tabler JS -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/js/tabler.min.js"></script>
</body>
</html>
