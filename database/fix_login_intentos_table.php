<?php
/**
 * Script para corregir el problema de la columna 'ultimo_intento' en la tabla login_intentos
 * 
 * El problema es que el modelo Usuario.php intenta usar la columna 'ultimo_intento'
 * pero en la tabla definida en schema.sql esta columna no existe.
 * 
 * Este script verifica la estructura actual de la tabla y la corrige si es necesario.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "<h1>Corrigiendo tabla login_intentos</h1>";

try {
    // Verificar si la tabla existe
    $result = Database::fetchAll("SHOW TABLES LIKE 'login_intentos'");
    
    if (count($result) === 0) {
        echo '<p style="color: orange;">⚠️ La tabla login_intentos no existe. Creándola...</p>';
        
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
        echo '<p style="color: green;">✅ Tabla login_intentos creada correctamente</p>';
    } else {
        echo '<p style="color: blue;">ℹ️ La tabla login_intentos ya existe. Verificando estructura...</p>';
        
        // Verificar las columnas actuales
        $columns = Database::fetchAll("SHOW COLUMNS FROM login_intentos");
        $columnNames = array_map(fn($col) => $col['Field'], $columns);
        
        // Verificar si la columna 'ultimo_intento' existe
        if (!in_array('ultimo_intento', $columnNames)) {
            echo '<p style="color: orange;">⚠️ La columna \'ultimo_intento\' no existe. Añadiéndola...</p>';
            
            // Añadir la columna faltante
            $sql = "ALTER TABLE login_intentos 
                    ADD COLUMN ultimo_intento TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP 
                    AFTER intentos";
            
            Database::query($sql);
            echo '<p style="color: green;">✅ Columna \'ultimo_intento\' añadida correctamente</p>';
        } else {
            echo '<p style="color: green;">✅ La columna \'ultimo_intento\' ya existe</p>';
        }
        
        // Verificar si la columna 'bloqueado_hasta' existe
        if (!in_array('bloqueado_hasta', $columnNames)) {
            echo '<p style="color: orange;">⚠️ La columna \'bloqueado_hasta\' no existe. Añadiéndola...</p>';
            
            // Añadir la columna faltante
            $sql = "ALTER TABLE login_intentos 
                    ADD COLUMN bloqueado_hasta DATETIME NULL 
                    AFTER ultimo_intento";
            
            Database::query($sql);
            echo '<p style="color: green;">✅ Columna \'bloqueado_hasta\' añadida correctamente</p>';
        } else {
            echo '<p style="color: green;">✅ La columna \'bloqueado_hasta\' ya existe</p>';
        }
        
        // Verificar si existe el índice único para email
        $indexes = Database::fetchAll("SHOW INDEX FROM login_intentos WHERE Key_name = 'unique_email'");
        if (count($indexes) === 0) {
            echo '<p style="color: orange;">⚠️ El índice \'unique_email\' no existe. Añadiéndolo...</p>';
            
            // Añadir el índice faltante
            $sql = "ALTER TABLE login_intentos 
                    ADD UNIQUE KEY unique_email (email)";
            
            Database::query($sql);
            echo '<p style="color: green;">✅ Índice \'unique_email\' añadido correctamente</p>';
        } else {
            echo '<p style="color: green;">✅ El índice \'unique_email\' ya existe</p>';
        }
    }
    
    // Verificar estructura final
    echo '<h2>Estructura final de la tabla:</h2>';
    $finalColumns = Database::fetchAll("SHOW COLUMNS FROM login_intentos");
    
    echo '<table border="1" cellpadding="5" cellspacing="0">';
    echo '<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Por defecto</th><th>Extra</th></tr>';
    
    foreach ($finalColumns as $column) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($column['Field']) . '</td>';
        echo '<td>' . htmlspecialchars($column['Type']) . '</td>';
        echo '<td>' . ($column['Null'] === 'YES' ? 'Sí' : 'No') . '</td>';
        echo '<td>' . htmlspecialchars($column['Key']) . '</td>';
        echo '<td>' . htmlspecialchars($column['Default'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($column['Extra']) . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    
    echo '<p style="color: green;">✅ Corrección completada. La tabla login_intentos ahora tiene la estructura correcta.</p>';
    
} catch (Exception $e) {
    echo '<p style="color: red;">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

echo '<p><a href="/controldepagosestacionamiento/">Ir al sistema</a></p>';