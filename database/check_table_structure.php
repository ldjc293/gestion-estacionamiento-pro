<?php
/**
 * Check the structure of the usuarios table
 */

// Include necessary files
require_once __DIR__ . '/../config/database.php';

// Set headers for proper output
header('Content-Type: text/plain; charset=utf-8');

echo "=== Checking Table Structure ===\n\n";

try {
    // Get the structure of the usuarios table
    $columns = Database::fetchAll("DESCRIBE usuarios");
    
    echo "Columns in usuarios table:\n\n";
    
    foreach ($columns as $column) {
        echo "Field: {$column['Field']}\n";
        echo "Type: {$column['Type']}\n";
        echo "Null: {$column['Null']}\n";
        echo "Key: {$column['Key']}\n";
        echo "Default: " . ($column['Default'] ?? 'NULL') . "\n";
        echo "Extra: {$column['Extra']}\n";
        echo "----------------------------------------\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}