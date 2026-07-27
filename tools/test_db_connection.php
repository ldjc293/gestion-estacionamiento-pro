<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Cargar variables de entorno
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "Probando conexión a base de datos...\n";
echo "Driver: " . $_ENV['DB_CONNECTION'] . "\n";
echo "Host: " . $_ENV['DB_HOST'] . "\n";
echo "Port: " . $_ENV['DB_PORT'] . "\n";

try {
    $dsn = sprintf(
        "pgsql:host=%s;port=%s;dbname=%s",
        $_ENV['DB_HOST'],
        $_ENV['DB_PORT'],
        $_ENV['DB_NAME']
    );
    
    $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "¡CONEXIÓN EXITOSA! ✅\n";
    echo "La aplicación puede comunicarse con Supabase correctamente.\n";
    
} catch (PDOException $e) {
    echo "ERROR DE CONEXIÓN ❌\n";
    echo "Mensaje: " . $e->getMessage() . "\n";
    echo "\nPosibles causas:\n";
    echo "1. Credenciales incorrectas en .env\n";
    echo "2. Driver 'pgsql' no habilitado en php.ini\n";
    echo "3. Firewall bloqueando el puerto 5432\n";
}
