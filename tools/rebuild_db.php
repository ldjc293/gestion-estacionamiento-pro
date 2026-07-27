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
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => true
    ]);

    echo "Eliminando todas las tablas y vistas...\n";
    
    // Lista de tablas en orden inverso de dependencia
    $tables = [
        'pago_mensualidad',
        'pagos',
        'mensualidades',
        'tasa_cambio_bcv',
        'configuracion_tarifas',
        'solicitudes_cambios',
        'controles_estacionamiento',
        'apartamento_usuario',
        'apartamentos',
        'notificaciones',
        'logs_actividad',
        'password_reset_tokens',
        'login_intentos',
        'configuracion_cron',
        'usuarios'
    ];

    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS $table CASCADE");
    }

    $views = [
        'vista_morosidad',
        'vista_controles_vacios'
    ];

    foreach ($views as $view) {
        $pdo->exec("DROP VIEW IF EXISTS $view CASCADE");
    }

    echo "✅ Base de datos limpia.\n";

    echo "Aplicando schema desde database/supabase_schema.sql...\n";
    $sql = file_get_contents(__DIR__ . '/database/supabase_schema.sql');
    
    // Eliminar comentarios de una sola línea y bloques si es necesario, 
    // pero PDO::exec suele manejarlo si no hay comandos complejos.
    // PostgreSQL permite ejecutar múltiples sentencias en un solo exec() si no son interdependientes en la misma transacción de forma que den error.
    
    $pdo->exec($sql);
    echo "✅ Schema aplicado correctamente.\n";

} catch (PDOException $e) {
    echo "FAILURE: " . $e->getMessage() . "\n";
}
