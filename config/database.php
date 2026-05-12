<?php
/**
 * UIU Nest — Database Configuration
 */
define('APP_NAME', 'UIU Nest');
define('APP_URL', 'http://localhost/GitHub/uiu-nest');
define('APP_ROOT', dirname(__DIR__));
define('UIU_LAT', 23.798038);
define('UIU_LNG', 90.449842);
define('UPLOAD_DIR', APP_ROOT . '/uploads');
define('DOC_UPLOAD_DIR', UPLOAD_DIR . '/documents');
define('PROPERTY_UPLOAD_DIR', UPLOAD_DIR . '/properties');
define('MAX_IMAGE_SIZE', 5 * 1024 * 1024);
define('MAX_VIDEO_SIZE', 30 * 1024 * 1024);
define('DB_HOST', 'localhost');
define('DB_NAME', 'uiu_nest');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed.']));
        }
    }
    return $pdo;
}

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}
