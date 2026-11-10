<?php
// config.php - Database connection & session start
declare(strict_types=1);

date_default_timezone_set('Asia/Manila');

session_start();

// Database credentials
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_NAME', 'nicole_flower');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Site settings
define('SITE_NAME', 'Nicole\'s Flowers & Greens');
define('SITE_URL', 'http://localhost/nicole_flower');

// Connect to MySQL using PDO
try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Helper: redirect
function redirect(string $url): void {
    header("Location: " . $url);
    exit;
}

// Helper: check if user is logged in
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

// Helper: require login (redirect if not logged in)
function requireLogin(): void {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';
        redirect('login.php');
    }
}

// Helper: require owner role
function requireOwner(): void {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';
        redirect(SITE_URL . '/owner_login.php');
    }
    if (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'owner') {
        return;
    }
    setFlash('error', 'Owner access required.');
    redirect(SITE_URL . '/index.php');
}

// Helper: flash message
function getFlash(string $key): ?string {
    if (!empty($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function setFlash(string $key, string $msg): void {
    $_SESSION['flash'][$key] = $msg;
}

// Helper: CSRF token
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES) . '">';
}

function verifyCsrf(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
