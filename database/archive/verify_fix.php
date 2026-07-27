<?php
/**
 * Script para verificar si la tabla login_intentos tiene la estructura correcta
 * y mostrar información detallada sobre el estado actual
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "<h1>Verificación de la tabla login_intentos</h1>";

try {
    // Verificar si la tabla existe
    $result = Database::fetchAll("SHOW TABLES LIKE 'login_intentos'");
    
    if (count($result) === 0) {
        echo '<p style="color: red;">❌ La tabla login_intentos no existe.</p>';
        echo '<p>Por favor, ejecuta el script de corrección primero.</p>';
        echo '<p><a href="fix_login_intentos_table.php">Ejecutar script de corrección</a></p>';
        exit;
    }
    
    echo '<p style="color: green;">✅ La tabla login_intentos existe.</p>';
    
    // Verificar las columnas actuales
    $columns = Database::fetchAll("SHOW COLUMNS FROM login_intentos");
    $columnNames = array_map(fn($col) => $col['Field'], $columns);
    
    echo '<h2>Columnas actuales:</h2>';
    echo '<table border="1" cellpadding="5" cellspacing="0">';
    echo '<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Por defecto</th><th>Extra</th><th>Estado</th></tr>';
    
    $requiredColumns = [
        'id' => 'Requerido',
        'email' => 'Requerido',
        'ip_address' => 'Requerido',
        'user_agent' => 'Opcional',
        'fecha_hora' => 'Requerido',
        'exitoso' => 'Requerido',
        'intentos' => 'Requerido',
        'ultimo_intento' => 'Requerido',
        'bloqueado_hasta' => 'Requerido'
    ];
    
    $allColumnsExist = true;
    
    foreach ($columns as $column) {
        $fieldName = $column['Field'];
        $isRequired = array_key_exists($fieldName, $requiredColumns);
        $status = $isRequired ? '<span style="color: green;">✅ Correcta</span>' : '<span style="color: orange;">⚠️ Extra</span>';
        
        if ($isRequired && !in_array($fieldName, $columnNames)) {
            $status = '<span style="color: red;">❌ Faltante</span>';
            $allColumnsExist = false;
        }
        
        echo '<tr>';
        echo '<td>' . htmlspecialchars($fieldName) . '</td>';
        echo '<td>' . htmlspecialchars($column['Type']) . '</td>';
        echo '<td>' . ($column['Null'] === 'YES' ? 'Sí' : 'No') . '</td>';
        echo '<td>' . htmlspecialchars($column['Key']) . '</td>';
        echo '<td>' . htmlspecialchars($column['Default'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($column['Extra']) . '</td>';
        echo '<td>' . $status . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    
    // Verificar columnas faltantes
    $missingColumns = [];
    foreach ($requiredColumns as $column => $type) {
        if (!in_array($column, $columnNames)) {
            $missingColumns[] = $column;
            $allColumnsExist = false;
        }
    }
    
    if (!empty($missingColumns)) {
        echo '<h2 style="color: red;">Columnas faltantes:</h2>';
        echo '<ul>';
        foreach ($missingColumns as $column) {
            echo '<li>' . htmlspecialchars($column) . '</li>';
        }
        echo '</ul>';
        echo '<p style="color: red;">❌ La tabla no tiene todas las columnas necesarias.</p>';
        echo '<p><a href="fix_login_intentos_table.php">Ejecutar script de corrección</a></p>';
    } else {
        echo '<p style="color: green;">✅ Todas las columnas necesarias existen.</p>';
    }
    
    // Verificar índices
    $indexes = Database::fetchAll("SHOW INDEX FROM login_intentos");
    $indexNames = array_map(fn($idx) => $idx['Key_name'], $indexes);
    
    echo '<h2>Índices actuales:</h2>';
    echo '<table border="1" cellpadding="5" cellspacing="0">';
    echo '<tr><th>Nombre</th><th>Columna</th><th>Tipo</th><th>Estado</th></tr>';
    
    $requiredIndexes = [
        'PRIMARY' => 'Requerido',
        'unique_email' => 'Requerido',
        'idx_email' => 'Requerido',
        'idx_ip_address' => 'Requerido',
        'idx_fecha_hora' => 'Requerido',
        'idx_exitoso' => 'Requerido'
    ];
    
    $allIndexesExist = true;
    
    foreach ($indexes as $index) {
        $indexName = $index['Key_name'];
        $isRequired = array_key_exists($indexName, $requiredIndexes);
        $status = $isRequired ? '<span style="color: green;">✅ Correcto</span>' : '<span style="color: orange;">⚠️ Extra</span>';
        
        if ($isRequired && !in_array($indexName, $indexNames)) {
            $status = '<span style="color: red;">❌ Faltante</span>';
            $allIndexesExist = false;
        }
        
        echo '<tr>';
        echo '<td>' . htmlspecialchars($indexName) . '</td>';
        echo '<td>' . htmlspecialchars($index['Column_name']) . '</td>';
        echo '<td>' . ($index['Non_unique'] == 0 ? 'Único' : 'No único') . '</td>';
        echo '<td>' . $status . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    
    // Verificar índices faltantes
    $missingIndexes = [];
    foreach ($requiredIndexes as $index => $type) {
        if (!in_array($index, $indexNames)) {
            $missingIndexes[] = $index;
            $allIndexesExist = false;
        }
    }
    
    if (!empty($missingIndexes)) {
        echo '<h2 style="color: red;">Índices faltantes:</h2>';
        echo '<ul>';
        foreach ($missingIndexes as $index) {
            echo '<li>' . htmlspecialchars($index) . '</li>';
        }
        echo '</ul>';
        echo '<p style="color: red;">❌ La tabla no tiene todos los índices necesarios.</p>';
        echo '<p><a href="fix_login_intentos_table.php">Ejecutar script de corrección</a></p>';
    } else {
        echo '<p style="color: green;">✅ Todos los índices necesarios existen.</p>';
    }
    
    // Verificar si hay datos en la tabla
    $countResult = Database::fetchOne("SELECT COUNT(*) as total FROM login_intentos");
    $totalRecords = $countResult['total'];
    
    echo '<h2>Registros en la tabla:</h2>';
    echo '<p>Total de registros: ' . $totalRecords . '</p>';
    
    if ($totalRecords > 0) {
        echo '<h3>Últimos 5 registros:</h3>';
        echo '<table border="1" cellpadding="5" cellspacing="0">';
        echo '<tr><th>ID</th><th>Email</th><th>IP</th><th>Intentos</th><th>Último intento</th><th>Bloqueado hasta</th></tr>';
        
        $records = Database::fetchAll("SELECT id, email, ip_address, intentos, ultimo_intento, bloqueado_hasta FROM login_intentos ORDER BY id DESC LIMIT 5");
        
        foreach ($records as $record) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($record['id']) . '</td>';
            echo '<td>' . htmlspecialchars($record['email']) . '</td>';
            echo '<td>' . htmlspecialchars($record['ip_address']) . '</td>';
            echo '<td>' . htmlspecialchars($record['intentos']) . '</td>';
            echo '<td>' . htmlspecialchars($record['ultimo_intento']) . '</td>';
            echo '<td>' . htmlspecialchars($record['bloqueado_hasta'] ?? 'N/A') . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
    }
    
    // Resumen final
    echo '<h2>Resumen:</h2>';
    
    if ($allColumnsExist && $allIndexesExist) {
        echo '<p style="color: green;">✅ La tabla login_intentos tiene la estructura correcta.</p>';
        echo '<p>El error de la columna "ultimo_intento" debería estar resuelto.</p>';
        echo '<p>Si sigues teniendo problemas, intenta borrar las cookies de tu navegador y volver a iniciar sesión.</p>';
    } else {
        echo '<p style="color: red;">❌ La tabla login_intentos no tiene la estructura correcta.</p>';
        echo '<p>Por favor, ejecuta el script de corrección para solucionar los problemas.</p>';
        echo '<p><a href="fix_login_intentos_table.php">Ejecutar script de corrección</a></p>';
    }
    
    echo '<p><a href="/controldepagosestacionamiento/">Ir al sistema</a></p>';
    
} catch (Exception $e) {
    echo '<p style="color: red;">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
}