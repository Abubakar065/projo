<?php
/**
 * Logout Handler
 * Project Tracking and Reporting Application
 */

define('PTRA_ACCESS', true);
require_once 'inc/config.php';
require_once 'inc/auth.php';

startSecureSession();

// Perform logout
logout();

// Redirect to login page with success message
header('Location: index.php?message=logged_out');
exit();
?>
