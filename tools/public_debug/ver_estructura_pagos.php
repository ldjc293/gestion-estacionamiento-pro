<?php
/**
 * Script para verificar estructura de la tabla pagos
 */

require_once __DIR__ . '/../config/database.php';

echo "<h1>Estructura de la tabla 'pagos'</h1>";
echo "<hr>";

try {
    $sql = "DESCRIBE pagos";
    $columnas = Database::fetchAll($sql);
    
    echo "<h2>Columnas de la tabla 'pagos':</h2>";
    echo "<table border='1' cellpadding='10' style='border-collapse:collapse;'>";
    echo "<tr style='background-color:#f0f0f0;'>";
    echo "<th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th>";
    echo "</tr>";
    
    foreach ($columnas as $col) {
        echo "<tr>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>{$col['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
?>
