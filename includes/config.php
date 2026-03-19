<?php
// includes/config.php
// Centralized configuration file for EquiWork environment constants and init

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_URL')) {
    define('BASE_URL', '/equiwork/');
}

// Load .env and .env.local if they exist
$envFiles = [__DIR__ . '/../.env', __DIR__ . '/../.env.local'];
foreach ($envFiles as $envFile) {
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            list($name, $value) = explode('=', $line, 2) + [null, null];
            if ($name !== null && $value !== null) {
                $name = trim($name);
                $value = trim($value, " \t\n\r\0\x0B\"'");
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// Environment constants
if (!defined('APP_ENV')) define('APP_ENV', 'production'); // 'development' or 'production'

// Database Credentials
if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_NAME')) define('DB_NAME', 'equiwork_db');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');

// API Keys
if (!defined('OCR_API_KEY')) define('OCR_API_KEY', getenv('OCR_API_KEY') ?: '');
if (!defined('GEMINI_API_KEY')) define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: '');
if (!defined('ALLOW_EXTERNAL_OCR')) define('ALLOW_EXTERNAL_OCR', false);

if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

