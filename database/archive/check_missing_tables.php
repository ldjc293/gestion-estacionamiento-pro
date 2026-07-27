<?php
/**
 * Check if there are any missing tables referenced in the application
 */

// Include necessary files
require_once __DIR__ . '/../config/database.php';

// Set headers for proper output
header('Content-Type: text/plain; charset=utf-8');

echo "=== Checking Missing Tables ===\n\n";

try {
    // Get all tables in the database
    $tables = Database::fetchAll("SHOW TABLES");
    
    $tableNames = [];
    foreach ($tables as $table) {
        $tableName = reset($table);
        $tableNames[] = $tableName;
        echo "✓ Found table: {$tableName}\n";
    }
    
    // Check for tables that might be missing
    $expectedTables = [
        'usuarios',
        'apartamentos',
        'apartamento_usuario',
        'controles_estacionamiento',
        'configuracion_tarifas',
        'tasa_cambio_bcv',
        'mensualidades',
        'pagos',
        'pago_mensualidad',
        'solicitudes_cambios',
        'notificaciones',
        'logs_actividad',
        'password_reset_tokens',
        'login_intentos' // This one is referenced in Usuario model but might not be in schema
    ];
    
    echo "\n=== Checking for Missing Tables ===\n";
    
    foreach ($expectedTables as $expectedTable) {
        if (!in_array($expectedTable, $tableNames)) {
            echo "✗ Missing table: {$expectedTable}\n";
            
            // Create the login_intentos table if it's missing
            if ($expectedTable === 'login_intentos') {
                echo "Creating login_intentos table...\n";
                
                $sql = "CREATE TABLE login_intentos (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    email VARCHAR(255) NOT NULL,
                    intentos INT DEFAULT 1,
                    ultimo_intento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    bloqueado_hasta DATETIME NULL,
                    UNIQUE KEY unique_email (email),
                    INDEX idx_bloqueado_hasta (bloqueado_hasta)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Registro de intentos fallidos de login'";
                
                Database::execute($sql);
                echo "✓ Created login_intentos table\n";
            }
        } else {
            echo "✓ Table exists: {$expectedTable}\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}