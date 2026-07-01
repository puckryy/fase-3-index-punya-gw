<?php
/**
 * MPL Tournament App - Core Configuration
 * Security Hardened | PDO | Session Protection
 * Path: F:\PROJECT1\htdocs\tournament-app\
 */

// Error handling - production mode
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Session Security - MAXIMUM
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? '1' : '0');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', '1');
ini_set('session.gc_maxlifetime', '1800'); // 30 minutes
ini_set('session.use_only_cookies', '1');

// Start session if not already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerate session ID every 15 minutes
if (!isset($_SESSION['last_regen']) || (time() - $_SESSION['last_regen']) > 900) {
    session_regenerate_id(true);
    $_SESSION['last_regen'] = time();
}

// Database Configuration
$host = 'localhost';
$db   = 'mpl_tournament';
$user = 'root';
$pass = ''; // <-- KOSONGKAN KALAU XAMPP DEFAULT (gak ada password)
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false, // REAL prepared statements
    PDO::ATTR_PERSISTENT         => false // Fresh connection each time
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log("DB Connection failed: " . $e->getMessage());
    die("Sistem sedang maintenance. Silakan coba beberapa saat lagi.");
}

// Global Constants
define('SITE_NAME', 'MPL TOURNAMENT');
define('DEFAULT_STREAM', 'https://www.youtube.com/@Puckryy');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');
define('POSTER_DIR', __DIR__ . '/../assets/posters/');
define('AVATAR_DIR', __DIR__ . '/../assets/avatars/');
define('CSRF_TIMEOUT', 900); // 15 minutes

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Helper: Generate CSRF Token
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token']) || 
        empty($_SESSION['csrf_time']) || 
        (time() - $_SESSION['csrf_time']) > CSRF_TIMEOUT) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

// Helper: Validate CSRF Token
function validateCsrfToken($token) {
    if (empty($token) || empty($_SESSION['csrf_token'])) return false;
    if (!hash_equals($_SESSION['csrf_token'], $token)) return false;
    if ((time() - $_SESSION['csrf_time']) > CSRF_TIMEOUT) return false;
    return true;
}

// Helper: Sanitize Output
function e($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// Helper: Rate Limiting (login attempts) - MORE REASONABLE NOW
function checkRateLimit($identifier, $maxAttempts = 5, $window = 300) {
    $key = 'rate_' . md5($identifier);
    $now = time();

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => $now];
    }

    if (($now - $_SESSION[$key]['first_attempt']) > $window) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => $now];
    }

    if ($_SESSION[$key]['count'] >= $maxAttempts) {
        return false; // Locked out
    }

    return true;
}

function incrementRateLimit($identifier) {
    $key = 'rate_' . md5($identifier);
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
    }
    $_SESSION[$key]['count']++;
}

// Helper: Reset Rate Limit (for admin use or after successful login)
function resetRateLimit($identifier) {
    $key = 'rate_' . md5($identifier);
    unset($_SESSION[$key]);
}

// Helper: Secure Redirect
function secureRedirect($url) {
    $allowed = ['index.php', 'login.php', 'dashboard.php', 'logout.php'];
    $parsed = parse_url($url, PHP_URL_PATH);
    $filename = basename($parsed);

    if (!in_array($filename, $allowed)) {
        $url = 'index.php';
    }

    header("Location: " . $url);
    exit();
}

// Helper: Validate File Upload
function validateUpload($file, $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], $maxSize = MAX_UPLOAD_SIZE) {
    $errors = [];

    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        $errors[] = "File tidak ditemukan";
        return $errors;
    }

    if ($file['size'] > $maxSize) {
        $errors[] = "File terlalu besar (max " . ($maxSize / 1024 / 1024) . "MB)";
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowedTypes)) {
        $errors[] = "Tipe file tidak diizinkan";
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowedExts)) {
        $errors[] = "Ekstensi file tidak diizinkan";
    }

    // Check for PHP content in image
    $content = file_get_contents($file['tmp_name']);
    if (preg_match('/<\?php|<\?=|<\?/i', $content)) {
        $errors[] = "File mencurigakan terdeteksi";
    }

    return $errors;
}

// Helper: Secure File Name
function secureFileName($originalName, $prefix = '') {
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $ext = preg_replace('/[^a-z0-9]/', '', $ext); // Only alphanumeric
    return $prefix . bin2hex(random_bytes(16)) . '_' . time() . '.' . $ext;
}

// Helper: Check Admin Login
function requireAdmin() {
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_logged_in']) || 
        $_SESSION['admin_logged_in'] !== true || empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit();
    }

    // Verify admin still exists in DB
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM admin WHERE id = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$_SESSION['admin_id']]);
    if (!$stmt->fetch()) {
        session_destroy();
        header('Location: login.php?error=session_invalid');
        exit();
    }
}

// Helper: Check User Login
function requireUser() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_logged_in']) || 
        $_SESSION['user_logged_in'] !== true) {
        header('Location: /user/login.php');
        exit();
    }
}

// Helper: Get Current Season
function getCurrentSeason($pdo) {
    $stmt = $pdo->query("SELECT * FROM seasons WHERE status = 'active' ORDER BY id DESC LIMIT 1");
    return $stmt->fetch();
}

// Helper: Log Activity
function logActivity($pdo, $type, $description, $userId = null, $ip = null) {
    if ($ip === null) $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt = $pdo->prepare("INSERT INTO activity_logs (type, description, user_id, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$type, $description, $userId, $ip]);
}
?>
