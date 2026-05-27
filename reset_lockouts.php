<?php
require_once __DIR__ . '/config/database.php';

try {
    // 1. Reset login_intentos table
    $sql1 = "DELETE FROM login_intentos";
    $deleted = Database::execute($sql1);
    echo "Eliminados $deleted registros de login_intentos.\n";

    // 2. Reset usuarios table lockout fields
    $sql2 = "UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL";
    $updated = Database::execute($sql2);
    echo "Reseteados $updated registros de usuarios.\n";

    echo "Reseteo de bloqueos completado con éxito.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
