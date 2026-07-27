<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

$host = "db.feoijalccdmdcpufjuda.supabase.co";
$port = 5432; // Direct port
$db   = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];

echo "Testing Direct Connection (db.feoijalccdmdcpufjuda.supabase.co:5432)...\n";
try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5 // 5 seconds timeout
    ]);
    echo "✅ Direct Connection Successful!\n";
} catch (PDOException $e) {
    echo "❌ Direct Connection Failed: " . $e->getMessage() . "\n";
}

$host_pooler = "aws-0-us-west-2.pooler.supabase.com";
echo "Testing Pooler Connection ($host_pooler:6543) with username $user...\n";
try {
    $dsn = "pgsql:host=$host_pooler;port=6543;dbname=$db";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5 // 5 seconds timeout
    ]);
    echo "✅ Pooler Connection Successful!\n";
} catch (PDOException $e) {
    echo "❌ Pooler Connection Failed: " . $e->getMessage() . "\n";
}

