<?php
declare(strict_types=1);
session_start();
date_default_timezone_set('Asia/Kuala_Lumpur');
define('APP_NAME', 'Sime Darby Care');
define('BASE_URL', '/sime_darby_care_php');// Update if you rename the project folder.
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads');
define('MAX_UPLOAD_BYTES', 5 * 1024 * 1024);
$host = getenv('DB_HOST') ?: 'sql306.infinityfree.com';
$db   = getenv('DB_NAME') ?: 'if0_43216057_sime_darby_care';
$user = getenv('DB_USER') ?: 'if0_43216057';
$pass = getenv('DB_PASS') ?: 'MioN8482';
try {
    $pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database connection failed. Import database/schema.sql and check includes/config.php.');
}
