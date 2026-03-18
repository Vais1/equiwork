<?php
// includes/config.php
// Centralized configuration file for EquiWork environment constants and init

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_URL')) {
    define('BASE_URL', '/equiwork/');
}

// Environment constants
if (!defined('APP_ENV')) define('APP_ENV', 'development'); // 'development' or 'production'

// Database Credentials
if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_NAME')) define('DB_NAME', 'equiwork_db');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');

// API Keys
if (!defined('OCR_API_KEY')) define('OCR_API_KEY', 'helloworld');

if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

