<?php
/**
 * API Controller - Endpoints para AJAX
 */

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Control.php';

class ApiController
{
    /**
     * Obtener apartamentos filtrados por bloque, escalera y piso
     */
    public function getApartamentos(): void
    {
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, no-store, must-revalidate'); // Evitar caché

        $bloque = trim($_GET['bloque'] ?? '');
        $escalera = trim($_GET['escalera'] ?? '');
        $piso = trim($_GET['piso'] ?? '');

        if (empty($bloque) || empty($escalera) || ($piso === '' || $piso === null)) {
            echo json_encode([]);
            return;
        }

        $sql = "SELECT numero_apartamento 
                FROM apartamentos 
                WHERE bloque = ? AND escalera = ? AND piso = ?
                ORDER BY numero_apartamento";

        $results = Database::fetchAll($sql, [$bloque, $escalera, $piso]);

        echo json_encode($results);
    }

    /**
     * Verificar disponibilidad de email
     */
    public function checkEmailDisponible(): void
    {
        header('Content-Type: application/json');

        $email = $_GET['email'] ?? '';

        if (empty($email)) {
            echo json_encode(['disponible' => false, 'mensaje' => 'Email requerido']);
            return;
        }

        $usuario = Usuario::findByEmail($email);

        if ($usuario) {
            echo json_encode(['disponible' => false, 'mensaje' => 'Email ya registrado']);
        } else {
            echo json_encode(['disponible' => true, 'mensaje' => 'Email disponible']);
        }
    }

    /**
     * Obtener escaleras disponibles para un bloque
     */
    public function getEscaleras(): void
    {
        header('Content-Type: application/json');

        $bloque = $_GET['bloque'] ?? '';

        if (empty($bloque)) {
            echo json_encode([]);
            return;
        }

        $sql = "SELECT DISTINCT escalera FROM apartamentos WHERE bloque = ? ORDER BY escalera";
        $results = Database::fetchAll($sql, [$bloque]);

        echo json_encode($results);
    }

    /**
     * Obtener pisos disponibles para un bloque y escalera
     */
    public function getPisos(): void
    {
        header('Content-Type: application/json');

        $bloque = $_GET['bloque'] ?? '';
        $escalera = $_GET['escalera'] ?? '';

        if (empty($bloque) || empty($escalera)) {
            echo json_encode([]);
            return;
        }

        $sql = "SELECT DISTINCT piso FROM apartamentos WHERE bloque = ? AND escalera = ? ORDER BY piso";
        $results = Database::fetchAll($sql, [$bloque, $escalera]);

        echo json_encode($results);
    }

    /**
     * Obtener controles disponibles para asignación
     */
    public function controlesDisponibles(): void
    {
        header('Content-Type: application/json');

        $cantidad = intval($_GET['cantidad'] ?? 1);

        if ($cantidad <= 0 || $cantidad > 10) {
            echo json_encode(['success' => false, 'message' => 'Cantidad inválida']);
            return;
        }

        try {
            // Obtener TODOS los controles disponibles ordenados por posición y receptor
            $controlesDisponibles = Control::getVacios();

            // Verificar que haya suficientes controles disponibles
            if (count($controlesDisponibles) < $cantidad) {
                echo json_encode([
                    'success' => false,
                    'message' => "No hay suficientes controles disponibles. Solicitados: {$cantidad}, Disponibles: " . count($controlesDisponibles)
                ]);
                return;
            }

            // Devolver TODOS los controles disponibles (no solo la cantidad solicitada)
            // El frontend se encargará de mostrarlos en las listas desplegables
            echo json_encode([
                'success' => true,
                'controles' => $controlesDisponibles,
                'cantidad_disponible' => count($controlesDisponibles),
                'cantidad_solicitada' => $cantidad
            ]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al obtener controles disponibles']);
        }
    }
}
