<?php
/**
 * includes/db.php
 * 
 * EquiWork Database Connection Module
 * 
 * This file establishes a secure, persistent connection to the MariaDB database
 * using the MySQLi extension. It enforces strict error reporting and UTF-8 
 * encoding to maintain data integrity and prevent SQL injection vulnerabilities 
 * across the platform.
 */

require_once __DIR__ . '/config.php';

// Database connection parameters
$host = DB_HOST;
$db   = DB_NAME;
$user = DB_USER;
$pass = DB_PASS;

/**
 * Configure mysqli to throw exceptions on errors.
 * This modern approach prevents silent failures and allows for robust 
 * try-catch exception handling in transaction blocks.
 */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Instantiate the mysqli connection object
    $conn = new mysqli($host, $user, $pass, $db);
    
    /**
     * Enforce UTF-8 Character Set
     * Critical for supporting diverse user inputs, international characters, 
     * and preventing encoding-based SQL injection attacks.
     */
    $conn->set_charset("utf8mb4");
} catch (\mysqli_sql_exception $e) {
    // In production, do not echo the raw error message to the browser, log it instead.
    // die() gracefully halts execution if the database is unreachable.
    die("Database connection failed. Please check your configuration.");
}
?>
