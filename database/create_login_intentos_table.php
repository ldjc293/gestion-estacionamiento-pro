<?php
/**
 * Script para crear la tabla login_intentos que falta en la base de datos
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "<h1>Creando tabla login_intentos</h1>";

try {
    // SQL para crear la tabla
    $sql = "
    CREATE TABLE IF NOT EXISTS login_intentos (
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
    
    // Ejecutar la consulta
    Database::query($sql);
    
    echo '<p style="color: green;">✅ Tabla login_intentos creada correctamente</p>';
    
    // Verificar que la tabla existe
    $result = Database::fetchAll("SHOW TABLES LIKE 'login_intentos'");
    if (count($result) > 0) {
        echo '<p style="color: green;">✅ Verificación: La tabla login_intentos existe</p>';
    } else {
        echo '<p style="color: red;">❌ Error: La tabla no se pudo crear</p>';
    }
    
} catch (Exception $e) {
    echo '<p style="color: red;">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

echo '<p><a href="/controldepagosestacionamiento/">Ir al sistema</a></p>';