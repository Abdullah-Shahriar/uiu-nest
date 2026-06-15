<?php
/**
 * UIU Nest — Database Configuration
 *
 * All sensitive values are loaded from the project root .env file.
 * Never hard-code credentials here.
 */

// ── Load .env ────────────────────────────────────────────────
require_once __DIR__ . '/env.php';
loadEnv(dirname(__DIR__) . '/.env');

// ── Application ──────────────────────────────────────────────
define('APP_NAME', $_ENV['APP_NAME'] ?? 'UIU Nest');
define('APP_URL',  $_ENV['APP_URL']  ?? 'http://localhost');
define('APP_ENV',  $_ENV['APP_ENV']  ?? 'local');
define('APP_ROOT', dirname(__DIR__));

// ── University Coordinates ────────────────────────────────────
define('UIU_LAT', (float)($_ENV['UIU_LAT'] ?? 23.798038));
define('UIU_LNG', (float)($_ENV['UIU_LNG'] ?? 90.449842));

// ── Upload Directories ────────────────────────────────────────
define('UPLOAD_DIR',          APP_ROOT . '/uploads');
define('DOC_UPLOAD_DIR',      UPLOAD_DIR . '/documents');
define('PROPERTY_UPLOAD_DIR', UPLOAD_DIR . '/properties');

// ── Upload Size Limits ────────────────────────────────────────
define('MAX_IMAGE_SIZE', (int)($_ENV['MAX_IMAGE_SIZE_MB'] ?? 5)  * 1024 * 1024);
define('MAX_VIDEO_SIZE', (int)($_ENV['MAX_VIDEO_SIZE_MB'] ?? 30) * 1024 * 1024);

// ── Database ──────────────────────────────────────────────────
define('DB_HOST',    $_ENV['DB_HOST']    ?? 'localhost');
define('DB_NAME',    $_ENV['DB_NAME']    ?? 'uiu_nest');
define('DB_USER',    $_ENV['DB_USER']    ?? 'root');
define('DB_PASS',    $_ENV['DB_PASS']    ?? '');
define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');

// ── PDO Singleton ─────────────────────────────────────────────
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

// ── Session Bootstrap ─────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}
