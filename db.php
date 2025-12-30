<?php
declare(strict_types=1);

/**
 * db.php
 * Central PDO connection file.
 * Put this file in: C:\xampp\htdocs\iqss-check\db.php
 * Then all pages can: require __DIR__ . "/db.php";
 */

$dbHost = "localhost";
$dbName = "iqss-check";
$dbUser = "root";
$dbPass = "";

try {
    $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";

    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo "Database connection failed. Check db.php settings.";
    exit();
}
