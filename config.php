<?php
// ============================================================
// config.php — Database connection
// Change DB_USER and DB_PASS to match your local setup
// ============================================================

define('DB_HOST', '127.0.0.1'); // Changed from localhost to force TCP connection
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'online_bookstore');
define('DB_PORT', 3307); // Added your active XAMPP MySQL port

// Added DB_PORT as the 5th parameter to match your XAMPP setup
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Start session safely if it hasn't been started yet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper: redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Helper: is logged in?
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper: is admin?
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Helper: sanitize input
function clean($conn, $data) {
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($data)));
}
?>
