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

    /**
     * Obtener detalles completos de un pago (AJAX)
     */
    public function getDetallePago(): void
    {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            return;
        }

        $pagoId = (int)($_GET['id'] ?? $_GET['pago_id'] ?? 0);
        if ($pagoId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID de pago inválido']);
            return;
        }

        $sql = "SELECT 
                    p.*,
                    u.nombre_completo as cliente_nombre,
                    u.cedula as cliente_cedula,
                    u.email as cliente_email,
                    CONCAT('Bloque ', a.bloque, ' - Escalera ', a.escalera, ' - Apto ', a.numero_apartamento) as apartamento,
                    t.tasa_usd_bs as tasa_cambio_valor,
                    reg.nombre_completo as registrado_por_nombre,
                    aprob.nombre_completo as aprobado_por_nombre
                FROM pagos p
                JOIN apartamento_usuario au ON au.id = p.apartamento_usuario_id
                JOIN usuarios u ON u.id = au.usuario_id
                JOIN apartamentos a ON a.id = au.apartamento_id
                LEFT JOIN tasa_cambio_bcv t ON t.id = p.tasa_cambio_id
                LEFT JOIN usuarios reg ON reg.id = p.registrado_por
                LEFT JOIN usuarios aprob ON aprob.id = p.aprobado_por
                WHERE p.id = ?";

        $pago = Database::fetchOne($sql, [$pagoId]);

        if (!$pago) {
            echo json_encode(['success' => false, 'message' => 'Pago no encontrado']);
            return;
        }

        // Obtener mensualidades asociadas
        $sqlMens = "SELECT m.mes, m.anio, m.monto_usd, pm.monto_aplicado_usd
                    FROM mensualidades m
                    JOIN pago_mensualidad pm ON pm.mensualidad_id = m.id
                    WHERE pm.pago_id = ?
                    ORDER BY m.anio ASC, m.mes ASC";
        $mensualidades = Database::fetchAll($sqlMens, [$pagoId]);

        $mesesNombres = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];

        $mensualidadesFormateadas = array_map(function($m) use ($mesesNombres) {
            $nombreMes = $mesesNombres[(int)$m['mes']] ?? $m['mes'];
            return [
                'mes' => $m['mes'],
                'anio' => $m['anio'],
                'texto' => $nombreMes . ' ' . $m['anio'],
                'monto_usd' => (float)$m['monto_usd'],
                'monto_aplicado_usd' => (float)$m['monto_aplicado_usd']
            ];
        }, $mensualidades);

        // Nombres legibles para metodo de pago
        $metodos = [
            'usd_efectivo' => 'Efectivo Divisas ($)',
            'bs_transferencia' => 'Transferencia Bs.',
            'bs_pago_movil' => 'Pago Móvil Bs.',
            'bs_efectivo' => 'Efectivo Bs.'
        ];

        $pago['metodo_pago_label'] = $metodos[$pago['moneda_pago']] ?? ucfirst($pago['moneda_pago'] ?? 'N/A');
        $pago['mensualidades'] = $mensualidadesFormateadas;
        $pago['fecha_pago_formateada'] = !empty($pago['fecha_pago']) ? date('d/m/Y H:i', strtotime($pago['fecha_pago'])) : 'N/A';
        $pago['fecha_aprobacion_formateada'] = !empty($pago['fecha_aprobacion']) ? date('d/m/Y H:i', strtotime($pago['fecha_aprobacion'])) : null;

        echo json_encode([
            'success' => true,
            'pago' => $pago
        ]);
    }

    /**
     * Obtener tasa de cambio por fecha (AJAX)
     */
    public function getTasaPorFecha(): void
    {
        header('Content-Type: application/json');
        
        $fecha = $_GET['fecha'] ?? '';
        if (empty($fecha)) {
            echo json_encode(['success' => false, 'message' => 'Fecha es requerida']);
            return;
        }

        // Normalizar fecha a Y-m-d
        $fecha = date('Y-m-d', strtotime($fecha));

        // 1. Intentar obtener la tasa registrada para el mismo día de pago
        $sql = "SELECT tasa_usd_bs FROM tasa_cambio_bcv WHERE DATE(fecha_registro) = DATE(?) ORDER BY fecha_registro DESC LIMIT 1";
        $result = Database::fetchOne($sql, [$fecha]);

        // 2. Si no hay tasa registrada ese mismo día, buscar la tasa más reciente anterior a la fecha de pago
        if (!$result) {
            $sql = "SELECT tasa_usd_bs FROM tasa_cambio_bcv WHERE fecha_registro <= ? ORDER BY fecha_registro DESC LIMIT 1";
            $result = Database::fetchOne($sql, [$fecha . ' 23:59:59']);
        }

        // 3. Fallback: buscar la tasa más reciente en general
        if (!$result) {
            $sql = "SELECT tasa_usd_bs FROM tasa_cambio_bcv ORDER BY fecha_registro DESC LIMIT 1";
            $result = Database::fetchOne($sql);
        }

        echo json_encode([
            'success' => true,
            'tasa' => $result ? floatval($result['tasa_usd_bs']) : 36.50
        ]);
    }
}
