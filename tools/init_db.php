<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';

try {
    $pdo = getDB();
    
    // Clear login attempts
    $pdo->exec("UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL");
    
    // Bypass forced password resets for test users
    $pdo->exec("UPDATE usuarios SET primer_acceso = FALSE, password_temporal = FALSE WHERE email LIKE '%@test.com'");
    
    echo "Bypassed password reset for all test users ending in @test.com.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
