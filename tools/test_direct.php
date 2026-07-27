<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// Try direct host
$host = "db.feoijalccdmdcpufjuda.supabase.co";
$port = "5432";
$db   = "postgres";
$user = "postgres";
$pass = $_ENV['DB_PASS'];

echo "Attempting DIRECT connection to $host:$port as $user...\n";

try {
    // In pgsql, use connect_timeout instead of timeout in DSN
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;connect_timeout=10";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "SUCCESS: Connected DIRECTLY!\n";
} catch (PDOException $e) {
    echo "DIRECT FAILURE: " . $e->getMessage() . "\n";
}
