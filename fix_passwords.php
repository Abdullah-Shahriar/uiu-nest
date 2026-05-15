<?php
/**
 * UIU Nest — One-time password fix script.
 * Run once after importing v2_migrations.sql, then DELETE this file.
 */
require_once __DIR__ . '/config/database.php';
$hash = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);
getDB()->prepare("UPDATE users SET password_hash = ?")->execute([$hash]);
echo "<pre>Done! All passwords set to 'admin123'.\nHash: $hash\n\n";
echo "IMPORTANT: Delete this file immediately!</pre>";
