<?php
/**
 * Script para ejecutar la corrección de la tabla login_intentos
 * 
 * Este script ejecuta directamente las consultas SQL necesarias para corregir
 * la estructura de la tabla login_intentos sin necesidad de acceder por navegador.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "Iniciando corrección de la tabla login_intentos...\n";

try {
    // Verificar si la tabla existe
    $result = Database::fetchAll("SHOW TABLES LIKE 'login_intentos'");
    
    if (count($result) === 0) {
        echo "La tabla login_intentos no existe. Creándola...\n";
        
        // Crear la tabla con la estructura correcta
        $sql = "
        CREATE TABLE login_intentos (
            id INT PRIMARY KEY AUTO_INCREMENT,
            email VARCHAR(255) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT NULL,
            fecha_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            exitoso BOOLEAN DEFAULT FALSE,
            intentos INT DEFAULT 1,
            ultimo_intento TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            bloqueado_hasta DATETIME NULL,
            
            UNIQUE KEY unique_email (email),
            INDEX idx_email (email),
            INDEX idx_ip_address (ip_address),
            INDEX idx_fecha_hora (fecha_hora),
            INDEX idx_exitoso (exitoso)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Registro de intentos de login para seguridad'
        ";
        
        Database::query($sql);
        echo "✅ Tabla login_intentos creada correctamente\n";
    } else {
        echo "La tabla login_intentos ya existe. Verificando estructura...\n";
        
        // Verificar las columnas actuales
        $columns = Database::fetchAll("SHOW COLUMNS FROM login_intentos");
        $columnNames = array_map(fn($col) => $col['Field'], $columns);
        
        // Verificar si la columna 'ultimo_intento' existe
        if (!in_array('ultimo_intento', $columnNames)) {
            echo "La columna 'ultimo_intento' no existe. Añadiéndola...\n";
            
            // Añadir la columna faltante
            $sql = "ALTER TABLE login_intentos 
                    ADD COLUMN ultimo_intento TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP 
                    AFTER intentos";
            
            Database::query($sql);
            echo "✅ Columna 'ultimo_intento' añadida correctamente\n";
        } else {
            echo "✅ La columna 'ultimo_intento' ya existe\n";
        }
        
        // Verificar si la columna 'bloqueado_hasta' existe
        if (!in_array('bloqueado_hasta', $columnNames)) {
            echo "La columna 'bloqueado_hasta' no existe. Añadiéndola...\n";
            
            // Añadir la columna faltante
            $sql = "ALTER TABLE login_intentos 
                    ADD COLUMN bloqueado_hasta DATETIME NULL 
                    AFTER ultimo_intento";
            
            Database::query($sql);
            echo "✅ Columna 'bloqueado_hasta' añadida correctamente\n";
        } else {
            echo "✅ La columna 'bloqueado_hasta' ya existe\n";
        }
        
        // Verificar si existe el índice único para email
        $indexes = Database::fetchAll("SHOW INDEX FROM login_intentos WHERE Key_name = 'unique_email'");
        if (count($indexes) === 0) {
            echo "El índice 'unique_email' no existe. Añadiéndolo...\n";
            
            // Añadir el índice faltante
            $sql = "ALTER TABLE login_intentos 
                    ADD UNIQUE KEY unique_email (email)";
            
            Database::query($sql);
            echo "✅ Índice 'unique_email' añadido correctamente\n";
        } else {
            echo "✅ El índice 'unique_email' ya existe\n";
        }
    }
    
    // Verificar estructura final
    echo "\nEstructura final de la tabla:\n";
    $finalColumns = Database::fetchAll("SHOW COLUMNS FROM login_intentos");
    
    foreach ($finalColumns as $column) {
        echo "- {$column['Field']} ({$column['Type']})\n";
    }
    
    echo "\n✅ Corrección completada. La tabla login_intentos ahora tiene la estructura correcta.\n";
    echo "Ahora puedes intentar iniciar sesión nuevamente.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}