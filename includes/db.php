<?php
// includes/db.php
// EquiWork Database Connection using MySQLi with Prepared Statements support

$host = '127.0.0.1';
$db   = 'equiwork_db';
$user = 'root';     // Default XAMPP username
$pass = '';         // Default XAMPP password is empty

// Enable throwing exceptions for errors to avoid silent failures
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $db);
    // Ensure charset is set to UTF-8 for proper data transfer
    $conn->set_charset("utf8mb4");
} catch (\mysqli_sql_exception $e) {
    // In production, do not echo the raw error message to the browser, log it instead.
    die("Database connection failed. Please check your configuration.");
}
?>