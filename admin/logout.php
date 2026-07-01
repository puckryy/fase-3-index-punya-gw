<?php
require_once '../core/config.php';

// Log logout activity
if (isset($_SESSION['admin_id'])) {
    logActivity($pdo, 'logout', "Admin {$_SESSION['admin_username']} logout", $_SESSION['admin_id']);
}

// Clear all session data
$_SESSION = [];

// Destroy session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}

// Destroy session
session_destroy();

// Redirect to login
header('Location: login.php');
exit();
?>
