<?php
/**
 * Project Tracking and Reporting Application
 * Configuration File
 * Version: 1.0
 */

// Prevent direct access
if (!defined('PTRA_ACCESS')) {
    die('Direct access not permitted');
}

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'project_tracker');

// Application Configuration
define('APP_NAME', 'Project Tracker & Reporting');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/ptra');

// Security Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('CSRF_TOKEN_EXPIRE', 1800); // 30 minutes

// File Upload Configuration
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Pagination
define('RECORDS_PER_PAGE', 10);

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Africa/Lagos');

// Currency Settings
define('DEFAULT_CURRENCY', 'NGN');
define('EXCHANGE_RATE_USD_NGN', 800); // Default rate, should be updated regularly
?>
