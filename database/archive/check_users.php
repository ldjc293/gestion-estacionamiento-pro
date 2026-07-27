<?php
/**
 * Check if there are any users in the database
 */

// Include necessary files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/Usuario.php';

// Set headers for proper output
header('Content-Type: text/plain; charset=utf-8');

echo "=== Checking Users ===\n\n";

try {
    // Get all users
    $users = Database::fetchAll("SELECT * FROM usuarios");
    
    if (empty($users)) {
        echo "No users found in the database.\n";
        
        // Create a default admin user
        echo "Creating default admin user...\n";
        
        $adminData = [
            'nombre_completo' => 'Administrador',
            'email' => 'admin@estacionamiento.local',
            'password' => 'admin123',
            'rol' => 'administrador',
            'activo' => true,
            'primer_acceso' => false,
            'password_temporal' => false,
            'perfil_completo' => true
        ];
        
        // Insert user directly using SQL since the table doesn't have cedula column
        $passwordHash = password_hash($adminData['password'], PASSWORD_BCRYPT);
        
        $sql = "INSERT INTO usuarios (
                    nombre_completo, email, password, rol,
                    activo, primer_acceso, password_temporal, perfil_completo
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $adminData['nombre_completo'],
            $adminData['email'],
            $passwordHash,
            $adminData['rol'],
            $adminData['activo'] ? 1 : 0,
            $adminData['primer_acceso'] ? 1 : 0,
            $adminData['password_temporal'] ? 1 : 0,
            $adminData['perfil_completo'] ? 1 : 0
        ];
        
        $adminId = Database::insert($sql, $params);
        
        echo "✓ Default admin user created with ID: {$adminId}\n";
        echo "Email: admin@estacionamiento.local\n";
        echo "Password: admin123\n";
    } else {
        echo "Found " . count($users) . " users in the database:\n\n";
        
        foreach ($users as $user) {
            echo "ID: {$user['id']}\n";
            echo "Name: {$user['nombre_completo']}\n";
            echo "Email: {$user['email']}\n";
            echo "Role: {$user['rol']}\n";
            echo "Active: " . ($user['activo'] ? 'Yes' : 'No') . "\n";
            echo "First Access: " . ($user['primer_acceso'] ? 'Yes' : 'No') . "\n";
            echo "Temporary Password: " . ($user['password_temporal'] ? 'Yes' : 'No') . "\n";
            echo "Profile Complete: " . ($user['perfil_completo'] ? 'Yes' : 'No') . "\n";
            echo "Exempt: " . ($user['exonerado'] ? 'Yes' : 'No') . "\n";
            echo "----------------------------------------\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}