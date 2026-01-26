<?php
require_once '../config/database.php';
require_once '../app/models/Usuario.php';

// Test cambiar rol
$usuarioId = 2; // Cambiar por un ID válido de prueba
$nuevoRol = 'operador';

echo "<h1>Test Cambio de Rol</h1>";

// 1. Obtener usuario
$usuario = Usuario::findById($usuarioId);
if (!$usuario) {
    die("Usuario no encontrado");
}

echo "<h2>Antes del cambio:</h2>";
echo "<p>ID: {$usuario->id}</p>";
echo "<p>Nombre: {$usuario->nombre_completo}</p>";
echo "<p>Rol actual: {$usuario->rol}</p>";

// 2. Intentar cambiar rol
echo "<h2>Intentando cambiar rol a: $nuevoRol</h2>";
$result = $usuario->update(['rol' => $nuevoRol]);
echo "<p>Resultado del update: " . ($result ? 'TRUE' : 'FALSE') . "</p>";

// 3. Verificar en la base de datos
$sql = "SELECT id, nombre_completo, rol FROM usuarios WHERE id = ?";
$dbData = Database::fetchOne($sql, [$usuarioId]);
echo "<h2>Datos en la base de datos después del update:</h2>";
echo "<pre>";
print_r($dbData);
echo "</pre>";

// 4. Volver a cargar el usuario
$usuarioReload = Usuario::findById($usuarioId);
echo "<h2>Usuario recargado desde la BD:</h2>";
echo "<p>Rol: {$usuarioReload->rol}</p>";
?>
