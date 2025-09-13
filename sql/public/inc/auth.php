<?php
session_start();

function check_auth($role = null) {
    if (!isset($_SESSION['user'])) {
        header("Location: /login.php");
        exit;
    }
    if ($role && $_SESSION['user']['role'] !== $role) {
        die("Access denied.");
    }
}

// CSRF helpers
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
