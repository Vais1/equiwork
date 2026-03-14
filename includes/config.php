<?php
// includes/config.php
// Centralized configuration file for EquiWork environment constants and init

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_URL')) {
    define('BASE_URL', '/equiwork/');
}

// Development mode - Error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
