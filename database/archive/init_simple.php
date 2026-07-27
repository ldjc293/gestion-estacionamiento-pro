<?php
/**
 * Simple Database Initialization Script
 * 
 * This script creates the database and tables by executing the schema.sql file
 * using the MySQL command line tool.
 */

// Set headers for proper output
header('Content-Type: text/plain; charset=utf-8');

echo "=== Simple Database Initialization ===\n\n";

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[$key] = $value;
        }
    }
}

// Get database configuration
$config = [
    'host'    => $_ENV['DB_HOST'] ?? 'localhost',
    'port'    => $_ENV['DB_PORT'] ?? '3306',
    'dbname'  => $_ENV['DB_NAME'] ?? 'estacionamiento_db',
    'user'    => $_ENV['DB_USER'] ?? 'root',
    'pass'    => $_ENV['DB_PASS'] ?? '',
];

// Path to MySQL executable
$mysqlPath = 'c:\\xampp\\mysql\\bin\\mysql.exe';

// Path to schema file
$schemaFile = __DIR__ . '/schema.sql';

if (!file_exists($schemaFile)) {
    echo "✗ Schema file not found: {$schemaFile}\n";
    exit(1);
}

if (!file_exists($mysqlPath)) {
    echo "✗ MySQL executable not found: {$mysqlPath}\n";
    exit(1);
}

// Build command to create database if it doesn't exist
$createDbCmd = sprintf(
    '"%s" --host=%s --port=%s --user=%s %s -e "CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"',
    $mysqlPath,
    $config['host'],
    $config['port'],
    $config['user'],
    empty($config['pass']) ? '' : '--password=' . $config['pass'],
    $config['dbname']
);

echo "Creating database...\n";
exec($createDbCmd, $output, $returnVar);
if ($returnVar !== 0) {
    echo "✗ Failed to create database\n";
    exit(1);
}
echo "✓ Database created or already exists\n";

// Build command to import schema
$importCmd = sprintf(
    '"%s" --host=%s --port=%s --user=%s %s %s < "%s"',
    $mysqlPath,
    $config['host'],
    $config['port'],
    $config['user'],
    empty($config['pass']) ? '' : '--password=' . $config['pass'],
    $config['dbname'],
    $schemaFile
);

echo "Importing schema...\n";
exec($importCmd, $output, $returnVar);
if ($returnVar !== 0) {
    echo "✗ Failed to import schema\n";
    exit(1);
}
echo "✓ Schema imported successfully\n";

echo "\n✓ Database initialization completed successfully!\n";