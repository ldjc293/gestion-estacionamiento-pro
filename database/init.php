<?php
/**
 * Database Initialization Script
 * 
 * This script creates the database and tables based on the schema.sql file.
 * It should be run once during installation or when setting up a new environment.
 */

// Include necessary files
require_once __DIR__ . '/../config/database.php';

// Set headers for proper output
header('Content-Type: text/plain; charset=utf-8');

echo "=== Database Initialization ===\n\n";

try {
    // Get database configuration
    $config = [
        'host'    => $_ENV['DB_HOST'] ?? 'localhost',
        'port'    => $_ENV['DB_PORT'] ?? '3306',
        'dbname'  => $_ENV['DB_NAME'] ?? 'estacionamiento_db',
        'user'    => $_ENV['DB_USER'] ?? 'root',
        'pass'    => $_ENV['DB_PASS'] ?? '',
        'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
    ];
    
    // Connect to MySQL without database name
    $dsn = sprintf(
        "mysql:host=%s;port=%s;charset=%s",
        $config['host'],
        $config['port'],
        $config['charset']
    );
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
    
    echo "✓ Connected to MySQL server\n";
    
    // Check if database exists
    $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
    $stmt->execute([$config['dbname']]);
    $dbExists = $stmt->fetch();
    
    if (!$dbExists) {
        // Create database
        $pdo->exec("CREATE DATABASE `{$config['dbname']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✓ Database '{$config['dbname']}' created\n";
    } else {
        echo "✓ Database '{$config['dbname']}' already exists\n";
    }
    
    // Switch to the database
    $pdo->exec("USE `{$config['dbname']}`");
    echo "✓ Using database '{$config['dbname']}'\n";
    
    // Read schema.sql file
    $schemaFile = __DIR__ . '/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: {$schemaFile}");
    }
    
    $schema = file_get_contents($schemaFile);
    
    // Limpiar el schema de sentencias que no queremos ejecutar aquí
    $schema = preg_replace('/^DROP DATABASE IF EXISTS.*$/m', '', $schema);
    $schema = preg_replace('/^CREATE DATABASE.*$/m', '', $schema);
    $schema = preg_replace('/^USE.*$/m', '', $schema);
    
    // Separar los procedimientos almacenados del resto del schema
    $procedures = [];
    if (preg_match_all('/DELIMITER \/\/(.*?)DELIMITER ;/s', $schema, $matches)) {
        foreach ($matches[1] as $proc) {
            $procedures[] = trim($proc);
        }
        // Remover los procedimientos del string principal del schema
        $schema = preg_replace('/DELIMITER \/\/(.*?)DELIMITER ;/s', '', $schema);
    }
    
    // Split into individual statements
    $statements = preg_split('/;\s*$/m', $schema);
    
    // Execute each statement
    $tableCount = 0;
    $viewCount = 0;
    $procedureCount = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        
        // Skip empty statements and comments
        if (empty($statement) || strpos(ltrim($statement), '--') === 0) {
            continue;
        }
        
        // Execute regular statements
        try {
            $pdo->exec($statement . ';');
            
            // Count different types of objects
            if (strpos($statement, 'CREATE TABLE') === 0) {
                $tableCount++;
                $tableName = preg_match('/CREATE TABLE `?(\w+)`?/', $statement, $matches) ? $matches[1] : 'unknown';
                echo "✓ Created table: {$tableName}\n";
            } elseif (strpos($statement, 'CREATE VIEW') === 0) {
                $viewCount++;
                $viewName = preg_match('/CREATE VIEW `?(\w+)`?/', $statement, $matches) ? $matches[1] : 'unknown';
                echo "✓ Created view: {$viewName}\n";
            } elseif (strpos($statement, 'INSERT INTO') === 0) {
                $tableName = preg_match('/INSERT INTO `?(\w+)`?/', $statement, $matches) ? $matches[1] : 'unknown';
                echo "✓ Inserted initial data into: {$tableName}\n";
            }
        } catch (PDOException $e) {
            // Check if it's a "table already exists" error
            if ($e->getCode() == '42S01' && strpos($e->getMessage(), 'already exists') !== false) {
                $tableName = preg_match('/CREATE TABLE `?(\w+)`?/', $statement, $matches) ? $matches[1] : 'unknown';
                echo "⚠ Table already exists: {$tableName}\n";
            } else {
                echo "✗ Error executing statement: " . $e->getMessage() . "\n";
                echo "Statement: " . substr($statement, 0, 100) . "...\n";
            }
        }
    }
    
    // Ejecutar los procedimientos almacenados
    foreach ($procedures as $procedure) {
        try {
            // Eliminar el delimitador final '//' del procedimiento antes de ejecutarlo.
            $cleanProcedure = preg_replace('/\s*\/\/\s*$/', '', $procedure);
            $pdo->exec($cleanProcedure);
            $procedureCount++;
            $procedureName = preg_match('/CREATE PROCEDURE `?(\w+)`?/', $procedure, $matches) ? $matches[1] : 'unknown';
            echo "✓ Created procedure: {$procedureName}\n";
        } catch (PDOException $e) {
            echo "✗ Error creating procedure: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== Summary ===\n";
    echo "Tables created: {$tableCount}\n";
    echo "Views created: {$viewCount}\n";
    echo "Procedures created: {$procedureCount}\n";
    echo "\n✓ Database initialization completed successfully!\n";
    
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}