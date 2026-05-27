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

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    // Check apartments
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM apartamentos");
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Apartamentos en DB: " . $res['total'] . "\n";
    
    if ($res['total'] > 0) {
        $stmt = $pdo->query("SELECT DISTINCT bloque FROM apartamentos ORDER BY bloque LIMIT 10");
        $bloques = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "Bloques encontrados: " . implode(", ", $bloques) . "\n";
    }

    // Check users
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Usuarios en DB: " . $res['total'] . "\n";
    
} catch (PDOException $e) {
    echo "FAILURE: " . $e->getMessage() . "\n";
}
