<?php
/**
 * Migración: Agregar soporte para registro de nuevos usuarios
 * 
 * Cambios:
 * 1. Actualizar ENUM tipo_solicitud en solicitudes_cambios
 * 2. Agregar columna datos_nuevo_usuario JSON
 * 3. Hacer nullable apartamento_usuario_id en solicitudes_cambios
 */

require_once __DIR__ . '/../../config/database.php';

echo "=== MIGRACIÓN: Soporte para Registro de Usuarios ===\n\n";

try {
    // 1. Hacer nullable la columna apartamento_usuario_id
    echo "1. Modificando columna apartamento_usuario_id...\n";
    $sql = "ALTER TABLE solicitudes_cambios 
            MODIFY COLUMN apartamento_usuario_id INT NULL";
    Database::execute($sql);
    echo "   ✓ Columna apartamento_usuario_id ahora es nullable\n\n";

    // 2. Actualizar ENUM tipo_solicitud
    echo "2. Actualizando ENUM tipo_solicitud...\n";
    $sql = "ALTER TABLE solicitudes_cambios 
            MODIFY COLUMN tipo_solicitud ENUM(
                'registro_nuevo_usuario',
                'cambio_cantidad_controles', 
                'suspension_control', 
                'desactivacion_control'
            ) NOT NULL";
    Database::execute($sql);
    echo "   ✓ ENUM tipo_solicitud actualizado\n\n";

    // 3. Agregar columna datos_nuevo_usuario
    echo "3. Agregando columna datos_nuevo_usuario...\n";
    $sql = "ALTER TABLE solicitudes_cambios 
            ADD COLUMN IF NOT EXISTS datos_nuevo_usuario JSON NULL 
            COMMENT 'Datos del nuevo usuario para solicitudes de registro'";
    Database::execute($sql);
    echo "   ✓ Columna datos_nuevo_usuario agregada\n\n";

    // Verificar cambios
    echo "4. Verificando estructura de la tabla...\n";
    $sql = "DESCRIBE solicitudes_cambios";
    $result = Database::fetchAll($sql);
    
    echo "   Columnas de solicitudes_cambios:\n";
    foreach ($result as $column) {
        if (in_array($column['Field'], ['apartamento_usuario_id', 'tipo_solicitud', 'datos_nuevo_usuario'])) {
            echo "   - {$column['Field']}: {$column['Type']} | Null: {$column['Null']}\n";
        }
    }

    echo "\n✅ MIGRACIÓN COMPLETADA EXITOSAMENTE\n";

} catch (Exception $e) {
    echo "\n❌ ERROR EN MIGRACIÓN: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
