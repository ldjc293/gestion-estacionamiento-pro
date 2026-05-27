<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'];
$db   = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];

echo "Attempting to connect to $host:$port as $user...\n";

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "SUCCESS: Connected to database!\n";
} catch (PDOException $e) {
    echo "FAILURE: " . $e->getMessage() . "\n";
    if (strpos($e->getMessage(), 'Tenant or user not found') !== false) {
        echo "Tip: The project ID in the username might be incorrect or the Pooler is not active.\n";
    }
}
