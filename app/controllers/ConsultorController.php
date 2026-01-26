<?php
/**
 * ConsultorController - Funcionalidades para consultores
 *
 * Reportes, estadísticas, consultas (solo lectura)
 */

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Pago.php';
require_once __DIR__ . '/../models/Mensualidad.php';
require_once __DIR__ . '/../models/Control.php';
require_once __DIR__ . '/../models/Apartamento.php';

class ConsultorController
{
    /**
     * Verificar que el usuario esté autenticado como consultor
     */
    private function checkAuth(): ?Usuario
    {
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_rol'], ['consultor', 'administrador'])) {
            redirect('auth/login');
            return null;
        }

        $usuario = Usuario::findById($_SESSION['user_id']);

        if (!$usuario || !$usuario->activo) {
            session_destroy();
            redirect('auth/login');
            return null;
        }

        return $usuario;
    }

    /**
     * Dashboard del consultor
     */
    public function dashboard(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        // Estadísticas generales
        $estadisticasGenerales = $this->getEstadisticasGenerales();

        // Estadísticas del mes actual
        $estadisticasMes = Pago::getEstadisticasMes(date('n'), date('Y'));

        // Morosidad
        $morosidad = $this->getEstadisticasMorosidad();

        // Controles
        $estadisticasControles = Control::getEstadisticas();

        require_once __DIR__ . '/../views/consultor/dashboard.php';
    }

    /**
     * Reporte de morosidad
     */
    /**
     * Reporte de morosidad
     */
    public function reporteMorosidad(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        // Filtros
        $mesesMinimos = intval($_GET['meses_min'] ?? 1);
        $bloque = sanitize($_GET['torre'] ?? '');
        $orden = sanitize($_GET['orden'] ?? 'meses_desc');

        $sql = "SELECT
                    u.id,
                    u.nombre_completo,
                    u.email,
                    u.telefono,
                    u.cedula,
                    a.bloque as torre,
                    a.numero_apartamento as apartamento,
                    COUNT(m.id) as meses_vencidos,
                    SUM(m.monto_usd) as deuda_total,
                    MIN(CONCAT(m.anio, '-', LPAD(m.mes, 2, '0'), '-01')) as primera_mensualidad_vencida,
                    MAX(CONCAT(m.anio, '-', LPAD(m.mes, 2, '0'), '-01')) as ultima_mensualidad,
                    (SELECT COUNT(*) 
                     FROM controles_estacionamiento ce 
                     JOIN apartamento_usuario au2 ON au2.id = ce.apartamento_usuario_id 
                     WHERE au2.usuario_id = u.id AND au2.activo = 1) as total_controles
                FROM usuarios u
                JOIN apartamento_usuario au ON au.usuario_id = u.id AND au.activo = TRUE
                JOIN apartamentos a ON a.id = au.apartamento_id
                JOIN mensualidades m ON m.apartamento_usuario_id = au.id AND m.estado IN ('pendiente', 'vencido')
                WHERE 1=1";

        $params = [];

        if ($bloque) {
            $sql .= " AND a.bloque = ?";
            $params[] = $bloque;
        }

        $sql .= " GROUP BY u.id, a.id
                  HAVING COUNT(m.id) >= ?";

        $params[] = $mesesMinimos;

        // Ordenamiento
        switch ($orden) {
            case 'meses_asc':
                $sql .= " ORDER BY meses_vencidos ASC";
                break;
            case 'deuda_desc':
                $sql .= " ORDER BY deuda_total DESC";
                break;
            case 'deuda_asc':
                $sql .= " ORDER BY deuda_total ASC";
                break;
            case 'meses_desc':
            default:
                $sql .= " ORDER BY meses_vencidos DESC";
                break;
        }

        $morosos = Database::fetchAll($sql, $params);

        // Resumen para la vista
        $totalDeuda = 0;
        $totalMensualidades = 0;
        $bloqueados = 0;

        foreach ($morosos as $m) {
            $totalDeuda += $m['deuda_total'];
            $totalMensualidades += $m['meses_vencidos'];
            if ($m['meses_vencidos'] >= 4) { // Assuming 4 is blockade threshold or use constant if available
                $bloqueados++;
            }
        }
        
        // Si existe la constante MESES_BLOQUEO, usémosla
        if (!defined('MESES_BLOQUEO')) define('MESES_BLOQUEO', 4);

        // Obtener bloques para filtro
        $bloques = Apartamento::getBloques();

        require_once __DIR__ . '/../views/consultor/reporte_morosidad.php';
    }

    /**
     * Reporte de pagos
     */
    /**
     * Reporte de pagos
     */
    public function reportePagos(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        // Filtros
        $mesInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
        $mesFin = $_GET['fecha_fin'] ?? date('Y-m-t');
        $estado = $_GET['estado'] ?? null;
        $moneda = $_GET['moneda'] ?? null;
        $torre = $_GET['torre'] ?? null;

        $sql = "SELECT
                    p.*,
                    u.nombre_completo as cliente_nombre,
                    u.cedula as cliente_cedula,
                    op.nombre_completo as operador_nombre,
                    a.bloque as torre,
                    a.numero_apartamento as apartamento
                FROM pagos p
                JOIN apartamento_usuario au ON au.id = p.apartamento_usuario_id
                JOIN usuarios u ON u.id = au.usuario_id
                JOIN apartamentos a ON a.id = au.apartamento_id
                LEFT JOIN usuarios op ON op.id = p.aprobado_por
                WHERE DATE(p.fecha_pago) BETWEEN ? AND ?";

        $params = [$mesInicio, $mesFin];

        if ($estado) {
            $sql .= " AND p.estado_comprobante = ?"; // Fixed field name
            $params[] = $estado;
        }

        if ($moneda) {
            $sql .= " AND p.moneda_pago LIKE ?"; // Fixed flexibility for usd_efectivo etc
            $params[] = $moneda . '%';
        }

        if ($torre) {
            $sql .= " AND a.bloque = ?";
            $params[] = $torre;
        }

        $sql .= " ORDER BY p.fecha_pago DESC, p.id DESC";

        $pagos = Database::fetchAll($sql, $params);

        // Estadísticas del período
        $estadisticas = [
            'total_pagos' => count($pagos),
            'total_usd' => 0,
            'total_bs' => 0,
            'aprobados' => 0,
            'rechazados' => 0,
            'pendientes' => 0
        ];

        foreach ($pagos as $pago) {
            if ($pago['estado_comprobante'] === 'aprobado') {
                if (strpos($pago['moneda_pago'], 'usd') !== false) {
                    $estadisticas['total_usd'] += $pago['monto_usd'];
                } else {
                    $estadisticas['total_bs'] += $pago['monto_bs'];
                }
                $estadisticas['aprobados']++;
            } elseif ($pago['estado_comprobante'] === 'rechazado') {
                $estadisticas['rechazados']++;
            } else {
                $estadisticas['pendientes']++;
            }
        }



        // Obtener bloques para filtro
        $bloques = Apartamento::getBloques();

        require_once __DIR__ . '/../views/consultor/reporte_pagos.php';
    }

    /**
     * Reporte de controles
     */
    /**
     * Reporte de controles
     */
    public function reporteControles(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        // Filtros
        $estado = $_GET['estado'] ?? null;
        $receptor = $_GET['receptor'] ?? null;
        $torre = $_GET['torre'] ?? null;
        $posicion = $_GET['posicion'] ?? null;

        $filtros = [];
        if ($estado) $filtros['estado'] = $estado;
        if ($receptor) $filtros['receptor'] = $receptor;
        if ($posicion) $filtros['posicion_numero'] = $posicion;

        // Usar getControlesConPropietarios para obtener bloque y otros datos detallados
        $filtrosBusqueda = $filtros;
        if ($torre) $filtrosBusqueda['bloque'] = $torre;
        
        $controlesRaw = Control::getControlesConPropietarios($filtrosBusqueda);
        
        // Mapear para la vista
        $controles = array_map(function($c) {
            $c['residente_nombre'] = $c['propietario_nombre'];
            $c['codigo_control'] = $c['numero_control_completo'];
            $c['motivo_bloqueo'] = $c['motivo_estado'];
            $c['torre'] = $c['bloque']; // Control::getControlesConPropietarios devuelve 'bloque'
            $c['apartamento'] = isset($c['apartamento']) ? explode('-', $c['apartamento'])[1] ?? '' : '';
            return $c;
        }, $controlesRaw);

        // Estadísticas Generales
        $statsRaw = Control::getEstadisticas();
        $estadisticas = [
            'total' => $statsRaw['total'] ?? 0,
            'asignados' => $statsRaw['activos'] ?? 0,
            'porcentaje_asignados' => ($statsRaw['total'] ?? 0) > 0 ? round(($statsRaw['activos'] / $statsRaw['total']) * 100, 1) : 0,
            'bloqueados' => $statsRaw['bloqueados'] ?? 0,
            'disponibles' => $statsRaw['vacios'] ?? 0
        ];

        // Distribución por Torre (Calculada manualmente ya que no hay método en modelo)
        $distribucionTorres = [];
        // Inicializar torres 27-32 logic should match view loop
        // Para simplificar, haremos una query ad-hoc o procesaremos en memoria si no son muchos datos.
        // Mejor query ad-hoc para rendimiento
        $sqlTorres = "SELECT a.bloque, 
                             COUNT(c.id) as total,
                             COUNT(CASE WHEN c.estado = 'activo' THEN 1 END) as asignados,
                             COUNT(CASE WHEN c.estado = 'bloqueado' THEN 1 END) as bloqueados
                      FROM controles_estacionamiento c
                      JOIN apartamento_usuario au ON au.id = c.apartamento_usuario_id
                      JOIN apartamentos a ON a.id = au.apartamento_id
                      GROUP BY a.bloque
                      ORDER BY a.bloque";
        $torresStats = Database::fetchAll($sqlTorres);
        
        // Reorganizar stats por torre
        $torresMap = [];
        foreach($torresStats as $t) $torresMap[$t['bloque']] = $t;

        // Reorganizar stats por torre
        $torresMap = [];
        foreach($torresStats as $t) $torresMap[$t['bloque']] = $t;

        $bloques = Apartamento::getBloques();
        foreach ($bloques as $t) {
            $data = $torresMap[$t] ?? ['total' => 0, 'asignados' => 0, 'bloqueados' => 0];
            
            $distribucionTorres[] = [
                'torre' => $t,
                'total' => $data['total'],
                'asignados' => $data['asignados'],
                'bloqueados' => $data['bloqueados'],
                'disponibles' => 0, 
                'porcentaje' => ($data['total'] > 0) ? round(($data['asignados'] / $data['total']) * 100, 1) : 0
            ];
        }

        require_once __DIR__ . '/../views/consultor/reporte_controles.php';
    }

    /**
     * Reporte de apartamentos
     */
    /**
     * Reporte de apartamentos
     */
    public function reporteApartamentos(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        // Filtros
        $torre = $_GET['torre'] ?? null;
        $estadoResidente = $_GET['estado_residente'] ?? null; // activo, inactivo
        $conMorosidad = $_GET['con_morosidad'] ?? null; // si, no

        // Query principal para lista de apartamentos
        $sql = "SELECT 
                    a.id,
                    a.bloque as torre,
                    a.escalera,
                    a.numero_apartamento,
                    u.nombre_completo as residente_nombre,
                    u.cedula,
                    u.email,
                    u.telefono,
                    u.activo as usuario_activo,
                    (SELECT COUNT(*) 
                     FROM controles_estacionamiento c 
                     JOIN apartamento_usuario au2 ON au2.id = c.apartamento_usuario_id 
                     WHERE au2.apartamento_id = a.id AND au2.activo = 1) as total_controles,
                    (SELECT COUNT(*) 
                     FROM mensualidades m 
                     JOIN apartamento_usuario au3 ON au3.id = m.apartamento_usuario_id 
                     WHERE au3.apartamento_id = a.id AND au3.activo = 1 AND m.estado = 'vencida') as mensualidades_vencidas,
                    (SELECT COALESCE(SUM(m2.monto_usd), 0)
                     FROM mensualidades m2 
                     JOIN apartamento_usuario au4 ON au4.id = m2.apartamento_usuario_id 
                     WHERE au4.apartamento_id = a.id AND au4.activo = 1 AND m2.estado = 'vencida') as deuda_total
                FROM apartamentos a
                LEFT JOIN apartamento_usuario au ON au.apartamento_id = a.id AND au.activo = 1
                LEFT JOIN usuarios u ON u.id = au.usuario_id
                WHERE a.activo = 1";
        
        $params = [];

        if ($torre) {
            $sql .= " AND a.bloque = ?";
            $params[] = $torre;
        }

        if ($estadoResidente === 'activo') {
            $sql .= " AND u.activo = 1";
        } elseif ($estadoResidente === 'inactivo') {
            $sql .= " AND (u.activo = 0 OR u.id IS NULL)";
        }

        // Para filtrar por morosidad, usamos HAVING ya que son columnas calculadas o agregadas
        // Pero HAVING funciona después del GROUP BY. Aquí no hay GROUP BY porque las subqueries van por fila.
        // Podemos usar una clausula WHERE sobre las subqueries, pero MySQL a veces requiere sintaxis especifica.
        // Filtramos en PHP o hacemos tabla derivada. Filtraremos en PHP para simplificar la query compleja.

        $sql .= " ORDER BY a.bloque, a.escalera, a.piso, a.numero_apartamento";

        $apartamentosRaw = Database::fetchAll($sql, $params);
        $apartamentos = [];

        foreach ($apartamentosRaw as $apto) {
            // Filtrado post-query para morosidad
            if ($conMorosidad === 'si' && $apto['mensualidades_vencidas'] == 0) continue;
            if ($conMorosidad === 'no' && $apto['mensualidades_vencidas'] > 0) continue;
            
            $apartamentos[] = $apto;
        }

        // Estadísticas Generales
        $estadisticas = [
            'total_apartamentos' => count($apartamentosRaw), // Total sin filtros
            'con_residentes' => 0,
            'con_morosidad' => 0,
            'sin_residentes' => 0
        ];

        // Recalcular estadísticas sobre el total sin filtro de vista (pero con filtros query)
        // Lo ideal sería una query separada para stats globales, pero usaremos el array raw
        foreach ($apartamentosRaw as $a) {
            if (!empty($a['residente_nombre'])) $estadisticas['con_residentes']++;
            else $estadisticas['sin_residentes']++;

            if ($a['mensualidades_vencidas'] > 0) $estadisticas['con_morosidad']++;
        }

        // Resumen por Torre
        $resumenTorres = [];
        $torresMap = [];
        
        // Iterar sobre todos los apartamentos (sin filtrar por torre especifica si no se solicitó, o solo sobre la filtrada)
        // Para el resumen "por torre" usualmente se quiere ver todas las torres en la tabla resumen,
        // independiente del filtro de la lista principal. Haremos una query específica para el resumen.
        
        $sqlResumen = "SELECT 
                        a.bloque as torre,
                        COUNT(a.id) as total_aptos,
                        COUNT(au.id) as con_residentes,
                        SUM(CASE WHEN (SELECT COUNT(*) FROM mensualidades m WHERE m.apartamento_usuario_id = au.id AND m.estado = 'vencida') > 0 THEN 1 ELSE 0 END) as con_morosidad,
                        (SELECT COUNT(*) FROM controles_estacionamiento c JOIN apartamento_usuario au5 ON au5.id = c.apartamento_usuario_id WHERE au5.apartamento_id IN (SELECT id FROM apartamentos WHERE bloque = a.bloque)) as controles_asignados_aprox 
                       FROM apartamentos a
                       LEFT JOIN apartamento_usuario au ON au.apartamento_id = a.id AND au.activo = 1
                       GROUP BY a.bloque
                       ORDER BY a.bloque";
                       
        // La subquery de controles_asignados_aprox es compleja en group by. Mejor obtener asignados aparte.
        // Simplificamos:
        $sqlResumenSimple = "SELECT a.bloque as torre, COUNT(*) as total_aptos FROM apartamentos a GROUP BY a.bloque";
        $dataTorres = Database::fetchAll($sqlResumenSimple);
        
        foreach ($dataTorres as $t) {
            $torre = $t['torre'];
            // Obtener datos especificos
            $sqlDetalle = "SELECT 
                           COUNT(DISTINCT au.id) as con_residentes,
                           (SELECT COUNT(*) FROM controles_estacionamiento c 
                            JOIN apartamento_usuario au2 ON au2.id = c.apartamento_usuario_id 
                            JOIN apartamentos a2 ON a2.id = au2.apartamento_id 
                            WHERE a2.bloque = ?) as controles_asignados
                           FROM apartamentos a 
                           LEFT JOIN apartamento_usuario au ON au.apartamento_id = a.id AND au.activo = 1
                           WHERE a.bloque = ?";
            
            $detalle = Database::fetchOne($sqlDetalle, [$torre, $torre]);
            
            // Morosidad torre
            $sqlMorosidadTorre = "SELECT COUNT(DISTINCT au.id) as morosos 
                                  FROM apartamentos a
                                  JOIN apartamento_usuario au ON au.apartamento_id = a.id AND au.activo = 1
                                  JOIN mensualidades m ON m.apartamento_usuario_id = au.id
                                  WHERE a.bloque = ? AND m.estado = 'vencida'";
            $morosidadT = Database::fetchOne($sqlMorosidadTorre, [$torre]);

            $resumenTorres[] = [
                'torre' => $torre,
                'total_aptos' => $t['total_aptos'],
                'con_residentes' => $detalle['con_residentes'],
                'controles_asignados' => $detalle['controles_asignados'],
                'con_morosidad' => $morosidadT['morosos'],
                'porcentaje_ocupacion' => ($t['total_aptos'] > 0) ? round(($detalle['con_residentes'] / $t['total_aptos']) * 100, 1) : 0
            ];
        }

        // Obtener bloques para filtro
        $bloques = Apartamento::getBloques();

        require_once __DIR__ . '/../views/consultor/reporte_apartamentos.php';
    }

    /**
     * Exportar apartamentos
     */
    public function exportApartamentos(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        $formato = $_GET['formato'] ?? 'excel';

        // Filtros
        $torre = $_GET['torre'] ?? null;
        $estadoResidente = $_GET['estado_residente'] ?? null;
        $conMorosidad = $_GET['con_morosidad'] ?? null;

        $sql = "SELECT 
                    a.id,
                    a.bloque as torre,
                    a.escalera,
                    a.numero_apartamento,
                    u.nombre_completo as residente_nombre,
                    u.cedula,
                    u.email,
                    u.telefono,
                    u.activo as usuario_activo,
                    (SELECT COUNT(*) FROM controles_estacionamiento c JOIN apartamento_usuario au2 ON au2.id = c.apartamento_usuario_id WHERE au2.apartamento_id = a.id AND au2.activo = 1) as total_controles,
                    (SELECT COUNT(*) FROM mensualidades m JOIN apartamento_usuario au3 ON au3.id = m.apartamento_usuario_id WHERE au3.apartamento_id = a.id AND au3.activo = 1 AND m.estado = 'vencida') as mensualidades_vencidas,
                    (SELECT COALESCE(SUM(m2.monto_usd), 0) FROM mensualidades m2 JOIN apartamento_usuario au4 ON au4.id = m2.apartamento_usuario_id WHERE au4.apartamento_id = a.id AND au4.activo = 1 AND m2.estado = 'vencida') as deuda_total
                FROM apartamentos a
                LEFT JOIN apartamento_usuario au ON au.apartamento_id = a.id AND au.activo = 1
                LEFT JOIN usuarios u ON u.id = au.usuario_id
                WHERE a.activo = 1";
        
        $params = [];

        if ($torre) {
            $sql .= " AND a.bloque = ?";
            $params[] = $torre;
        }

        if ($estadoResidente === 'activo') {
            $sql .= " AND u.activo = 1";
        } elseif ($estadoResidente === 'inactivo') {
            $sql .= " AND (u.activo = 0 OR u.id IS NULL)";
        }

        $sql .= " ORDER BY a.bloque, a.escalera, a.piso, a.numero_apartamento";

        $apartamentosRaw = Database::fetchAll($sql, $params);
        $apartamentos = [];

        foreach ($apartamentosRaw as $apto) {
            if ($conMorosidad === 'si' && $apto['mensualidades_vencidas'] == 0) continue;
            if ($conMorosidad === 'no' && $apto['mensualidades_vencidas'] > 0) continue;
            $apartamentos[] = $apto;
        }

        if ($formato === 'pdf') {
            require_once __DIR__ . '/../views/consultor/print_reporte_apartamentos.php';
            return;
        }

        // CSV con delimitador punto y coma
        $filename = "reporte_apartamentos_" . date('Ymd_His') . ".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, ['Bloque', 'Escalera', 'Apartamento', 'Residente', 'Cedula', 'Telefono', 'Email', 'Controles', 'Meses Vencidos', 'Deuda Total', 'Estado'], ';');

        foreach ($apartamentos as $apto) {
            $estado = $apto['usuario_activo'] ? ($apto['mensualidades_vencidas'] > 0 ? 'Moroso' : 'Activo') : 'Inactivo';
            fputcsv($output, [
                $apto['torre'],
                $apto['escalera'],
                $apto['numero_apartamento'],
                $apto['residente_nombre'],
                $apto['cedula'],
                $apto['telefono'],
                $apto['email'],
                $apto['total_controles'],
                $apto['mensualidades_vencidas'],
                number_format($apto['deuda_total'], 2, '.', ''),
                $estado
            ], ';');
        }
        
        fclose($output);
        exit;
    }

    /**
     * Reporte financiero mensual
     */
    /**
     * Reporte financiero mensual
     */
    public function reporteFinanciero(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        // Filtros y Período
        $periodo = $_GET['periodo'] ?? 'mes_actual';
        
        switch ($periodo) {
            case 'mes_anterior':
                $inicio = date('Y-m-01', strtotime('first day of last month'));
                $fin = date('Y-m-t', strtotime('last day of last month'));
                break;
            case 'trimestre':
                $inicio = date('Y-m-01', strtotime('-3 months'));
                $fin = date('Y-m-t');
                break;
            case 'semestre':
                $inicio = date('Y-m-01', strtotime('-6 months'));
                $fin = date('Y-m-t');
                break;
            case 'anio':
                $inicio = date('Y-01-01');
                $fin = date('Y-12-31');
                break;
            case 'personalizado':
                $inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
                $fin = $_GET['fecha_fin'] ?? date('Y-m-t');
                break;
            case 'mes_actual':
            default:
                $inicio = date('Y-m-01');
                $fin = date('Y-m-t');
                break;
        }

        // 1. Finanzas y Totales
        $sqlFinanzas = "SELECT 
                            COUNT(*) as total_pagos,
                            SUM(CASE WHEN moneda_pago LIKE 'usd%' THEN monto_usd ELSE 0 END) as ingresos_usd,
                            SUM(CASE WHEN moneda_pago LIKE 'bs%' THEN monto_bs ELSE 0 END) as ingresos_bs
                        FROM pagos 
                        WHERE DATE(fecha_pago) BETWEEN ? AND ? 
                          AND estado_comprobante = 'aprobado'";
        
        $finanzasData = Database::fetchOne($sqlFinanzas, [$inicio, $fin]);
        
        // Calcular deuda pendiente (total sistema, no depende del periodo temporal de pagos, es snapshot)
        $sqlDeuda = "SELECT SUM(monto_usd) as deuda FROM mensualidades WHERE estado = 'vencida'";
        $deudaData = Database::fetchOne($sqlDeuda);
        
        // Mensualidades pagadas en el periodo (aproximación por fecha pago)
        $sqlMensualidades = "SELECT COUNT(*) as pagadas 
                             FROM pago_mensualidad pm
                             JOIN pagos p ON p.id = pm.pago_id
                             WHERE DATE(p.fecha_pago) BETWEEN ? AND ? AND p.estado_comprobante = 'aprobado'";
        $mensualidadesData = Database::fetchOne($sqlMensualidades, [$inicio, $fin]);

        $finanzas = [
            'ingresos_usd' => $finanzasData['ingresos_usd'] ?? 0,
            'ingresos_bs' => $finanzasData['ingresos_bs'] ?? 0,
            'total_pagos' => $finanzasData['total_pagos'] ?? 0,
            'deuda_pendiente' => $deudaData['deuda'] ?? 0,
            'mensualidades_pagadas' => $mensualidadesData['pagadas'] ?? 0
        ];

        // 2. Desglose por Método de Pago
        $sqlMetodos = "SELECT
                            moneda_pago as metodo,
                            COUNT(*) as cantidad,
                            SUM(monto_usd) as total_usd,
                            SUM(monto_bs) as total_bs
                       FROM pagos
                       WHERE DATE(fecha_pago) BETWEEN ? AND ?
                         AND estado_comprobante = 'aprobado'
                       GROUP BY moneda_pago";
        
        $metodosData = Database::fetchAll($sqlMetodos, [$inicio, $fin]);
        
        $desgloseMetodos = [];
        $totalIngresosRef = max($finanzas['ingresos_usd'], 1); // Evitar div por 0
        
        foreach ($metodosData as $m) {
            $m['porcentaje'] = round(($m['total_usd'] / $totalIngresosRef) * 100, 1);
            $desgloseMetodos[] = $m;
        }

        // 3. Tasa de Cobro (Mensualidades generadas vs pagadas que corresponden a meses dentro del rango)
        // Esto es complejo porque 'periodo' aplica a fecha pago, pero tasa cobro suele ser por 'mes de deuda'.
        // Asumiremos tasa de cobro sobre las mensualidades CUYA FECHA (mes/año) cae en el rango.
        
        // Extraer rango meses/años
        $anioInicio = date('Y', strtotime($inicio));
        $mesInicio = date('n', strtotime($inicio));
        $anioFin = date('Y', strtotime($fin));
        $mesFin = date('n', strtotime($fin));
        
        // Simplificación: Total mensualidades con fecha_vencimiento o similar en rango
        $sqlTasa = "SELECT 
                        COUNT(*) as generadas,
                        COUNT(CASE WHEN estado = 'pagada' THEN 1 END) as pagadas
                    FROM mensualidades
                    WHERE CONCAT(anio, '-', LPAD(mes, 2, '0'), '-01') BETWEEN ? AND ?";
        
        $tasaData = Database::fetchOne($sqlTasa, [$inicio, $fin]);
        
        $tasaCobro = [
            'generadas' => $tasaData['generadas'] ?? 0,
            'pagadas' => $tasaData['pagadas'] ?? 0,
            'porcentaje' => ($tasaData['generadas'] > 0) ? round(($tasaData['pagadas'] / $tasaData['generadas']) * 100, 1) : 0
        ];

        // 4. Top Clientes
        $sqlTop = "SELECT 
                        u.nombre_completo,
                        a.bloque as torre,
                        a.numero_apartamento as apartamento,
                        COUNT(p.id) as total_pagos,
                        SUM(p.monto_usd) as monto_total,
                        MAX(p.fecha_pago) as ultimo_pago
                   FROM pagos p
                   JOIN apartamento_usuario au ON au.id = p.apartamento_usuario_id
                   JOIN usuarios u ON u.id = au.usuario_id
                   JOIN apartamentos a ON a.id = au.apartamento_id
                   WHERE DATE(p.fecha_pago) BETWEEN ? AND ?
                     AND p.estado_comprobante = 'aprobado'
                   GROUP BY u.id
                   ORDER BY total_pagos DESC, monto_total DESC
                   LIMIT 10";
                   
        $topClientes = Database::fetchAll($sqlTop, [$inicio, $fin]);

        require_once __DIR__ . '/../views/consultor/reporte_financiero.php';
    }

    /**
     * Exportar reporte financiero
     */
    public function exportFinanciero(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        $formato = $_GET['formato'] ?? 'excel';

        // Filtros y Período (misma lógica que reporteFinanciero)
        $periodo = $_GET['periodo'] ?? 'mes_actual';
        
        switch ($periodo) {
            case 'mes_anterior':
                $inicio = date('Y-m-01', strtotime('first day of last month'));
                $fin = date('Y-m-t', strtotime('last day of last month'));
                break;
            case 'trimestre':
                $inicio = date('Y-m-01', strtotime('-3 months'));
                $fin = date('Y-m-t');
                break;
            case 'semestre':
                $inicio = date('Y-m-01', strtotime('-6 months'));
                $fin = date('Y-m-t');
                break;
            case 'anio':
                $inicio = date('Y-01-01');
                $fin = date('Y-12-31');
                break;
            case 'personalizado':
                $inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
                $fin = $_GET['fecha_fin'] ?? date('Y-m-t');
                break;
            case 'mes_actual':
            default:
                $inicio = date('Y-m-01');
                $fin = date('Y-m-t');
                break;
        }

        // Obtener datos financieros
        $sqlFinanzas = "SELECT 
                            COUNT(*) as total_pagos,
                            SUM(CASE WHEN moneda_pago LIKE 'usd%' THEN monto_usd ELSE 0 END) as ingresos_usd,
                            SUM(CASE WHEN moneda_pago LIKE 'bs%' THEN monto_bs ELSE 0 END) as ingresos_bs
                        FROM pagos 
                        WHERE DATE(fecha_pago) BETWEEN ? AND ? 
                          AND estado_comprobante = 'aprobado'";
        
        $finanzasData = Database::fetchOne($sqlFinanzas, [$inicio, $fin]);

        // Desglose por método de pago
        $sqlMetodos = "SELECT
                            moneda_pago as metodo,
                            COUNT(*) as cantidad,
                            SUM(monto_usd) as total_usd,
                            SUM(monto_bs) as total_bs
                       FROM pagos
                       WHERE DATE(fecha_pago) BETWEEN ? AND ?
                         AND estado_comprobante = 'aprobado'
                       GROUP BY moneda_pago";
        
        $metodosData = Database::fetchAll($sqlMetodos, [$inicio, $fin]);

        // Top clientes
        $sqlTop = "SELECT 
                        u.nombre_completo,
                        a.bloque as torre,
                        a.numero_apartamento as apartamento,
                        COUNT(p.id) as total_pagos,
                        SUM(p.monto_usd) as monto_total
                   FROM pagos p
                   JOIN apartamento_usuario au ON au.id = p.apartamento_usuario_id
                   JOIN usuarios u ON u.id = au.usuario_id
                   JOIN apartamentos a ON a.id = au.apartamento_id
                   WHERE DATE(p.fecha_pago) BETWEEN ? AND ?
                     AND p.estado_comprobante = 'aprobado'
                   GROUP BY u.id
                   ORDER BY monto_total DESC
                   LIMIT 10";
                   
        $topClientes = Database::fetchAll($sqlTop, [$inicio, $fin]);

        if ($formato === 'pdf') {
            // Renderizar vista de impresión
            $finanzas = [
                'ingresos_usd' => $finanzasData['ingresos_usd'] ?? 0,
                'ingresos_bs' => $finanzasData['ingresos_bs'] ?? 0,
                'total_pagos' => $finanzasData['total_pagos'] ?? 0
            ];
            $desgloseMetodos = $metodosData;
            require_once __DIR__ . '/../views/consultor/print_reporte_financiero.php';
            return;
        }

        // CSV
        $filename = "reporte_financiero_" . date('Ymd_His') . ".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Resumen
        fputcsv($output, ['RESUMEN FINANCIERO'], ';');
        fputcsv($output, ['Periodo', date('d/m/Y', strtotime($inicio)) . ' al ' . date('d/m/Y', strtotime($fin))], ';');
        fputcsv($output, ['Total Pagos', $finanzasData['total_pagos'] ?? 0], ';');
        fputcsv($output, ['Ingresos USD', number_format($finanzasData['ingresos_usd'] ?? 0, 2, '.', '')], ';');
        fputcsv($output, ['Ingresos Bs', number_format($finanzasData['ingresos_bs'] ?? 0, 2, '.', '')], ';');
        fputcsv($output, [], ';');

        // Desglose por método
        fputcsv($output, ['DESGLOSE POR METODO DE PAGO'], ';');
        fputcsv($output, ['Metodo', 'Cantidad', 'Total USD', 'Total Bs'], ';');
        foreach ($metodosData as $m) {
            fputcsv($output, [
                ucfirst(str_replace('_', ' ', $m['metodo'])),
                $m['cantidad'],
                number_format($m['total_usd'], 2, '.', ''),
                number_format($m['total_bs'], 2, '.', '')
            ], ';');
        }
        fputcsv($output, [], ';');

        // Top clientes
        fputcsv($output, ['TOP 10 CLIENTES'], ';');
        fputcsv($output, ['Cliente', 'Ubicacion', 'Total Pagos', 'Monto Total USD'], ';');
        foreach ($topClientes as $cliente) {
            fputcsv($output, [
                $cliente['nombre_completo'],
                "Blq {$cliente['torre']}-{$cliente['apartamento']}",
                $cliente['total_pagos'],
                number_format($cliente['monto_total'], 2, '.', '')
            ], ';');
        }
        
        fclose($output);
        exit;
    }

    /**
     * Exportar reporte a Excel
     */
    public function exportarExcel(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        $tipo = $_GET['tipo'] ?? '';

        switch ($tipo) {
            case 'morosidad':
                $this->exportarMorosidadExcel();
                break;
            case 'pagos':
                $this->exportarPagosExcel();
                break;
            case 'controles':
                $this->exportarControlesExcel();
                break;
            default:
                $_SESSION['error'] = 'Tipo de reporte inválido';
                redirect('consultor/dashboard');
        }
    }

    /**
     * Buscar cliente/apartamento
     */
    public function buscar(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        $criterio = sanitize($_GET['q'] ?? '');
        $resultados = [];

        if (strlen($criterio) >= 2) {
            // Buscar clientes
            $clientes = Usuario::buscarClientes($criterio);

            // Buscar apartamentos
            $apartamentos = Apartamento::buscar($criterio);

            // Buscar controles
            $controles = Control::buscar($criterio);

            $resultados = [
                'clientes' => $clientes,
                'apartamentos' => $apartamentos,
                'controles' => $controles
            ];
        }

        require_once __DIR__ . '/../views/consultor/buscar.php';
    }

    /**
     * Ver detalle de cliente (solo lectura)
     */
    public function verCliente(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        $clienteId = intval($_GET['id'] ?? 0);

        if (!$clienteId) {
            redirect('consultor/dashboard');
            return;
        }

        $cliente = Usuario::findById($clienteId);

        if (!$cliente || $cliente->rol !== 'cliente') {
            $_SESSION['error'] = 'Cliente no encontrado';
            redirect('consultor/dashboard');
            return;
        }

        // Información del cliente
        $deudaInfo = Mensualidad::calcularDeudaTotal($clienteId);
        $mensualidades = Mensualidad::getAllByUsuario($clienteId);
        $pagos = Pago::getByUsuario($clienteId);
        $controles = $this->getControlesUsuario($clienteId);

        require_once __DIR__ . '/../views/consultor/ver_cliente.php';
    }

    // ==================== HELPERS ====================

    /**
     * Obtener estadísticas generales
     */
    private function getEstadisticasGenerales(): array
    {
        $sql = "SELECT
                    (SELECT COUNT(*) FROM usuarios WHERE rol = 'cliente' AND activo = TRUE) as total_clientes,
                    (SELECT COUNT(*) FROM apartamentos WHERE activo = TRUE) as total_apartamentos,
                    (SELECT COUNT(*) FROM controles_estacionamiento WHERE estado = 'activo') as controles_activos,
                    (SELECT COUNT(*) FROM controles_estacionamiento WHERE estado = 'bloqueado') as controles_bloqueados,
                    (SELECT COUNT(*) FROM mensualidades WHERE estado = 'vencida') as mensualidades_vencidas,
                    (SELECT COUNT(*) FROM pagos WHERE estado_comprobante = 'pendiente') as pagos_pendientes";

        return Database::fetchOne($sql) ?: [];
    }

    /**
     * Obtener estadísticas de morosidad
     */
    private function getEstadisticasMorosidad(): array
    {
        $sql = "SELECT COUNT(DISTINCT usuario_id) as total_morosos,
                       SUM(total_deuda_usd) as deuda_total
                FROM vista_morosidad
                WHERE meses_pendientes >= 1";

        return Database::fetchOne($sql) ?: [];
    }

    /**
     * Obtener controles del usuario
     */
    private function getControlesUsuario(int $usuarioId): array
    {
        $sql = "SELECT c.*, a.bloque, a.numero_apartamento
                FROM controles_estacionamiento c
                JOIN apartamento_usuario au ON au.id = c.apartamento_usuario_id
                JOIN apartamentos a ON a.id = au.apartamento_id
                WHERE au.usuario_id = ? AND au.activo = TRUE
                ORDER BY c.posicion_numero, c.receptor";

        return Database::fetchAll($sql, [$usuarioId]);
    }

    /**
     * Exportar morosidad a Excel
     */
    /**
     * Exportar morosidad
     */
    public function exportMorosidad(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;
        
        $formato = $_GET['formato'] ?? 'excel';

        if (!defined('MESES_BLOQUEO')) define('MESES_BLOQUEO', 4);

        // Filtros
        $mesesMin = $_GET['meses_min'] ?? null;
        $torre = $_GET['torre'] ?? null;
        $orden = $_GET['orden'] ?? 'meses_desc';

        $sql = "SELECT 
                    u.nombre_completo,
                    u.cedula,
                    u.email,
                    u.telefono,
                    a.bloque as torre,
                    a.numero_apartamento as apartamento,
                    (SELECT COUNT(*) FROM controles_estacionamiento c JOIN apartamento_usuario au2 ON au2.id = c.apartamento_usuario_id WHERE au2.apartamento_id = a.id AND au2.activo = 1) as total_controles,
                    (SELECT COUNT(*) FROM mensualidades m JOIN apartamento_usuario au3 ON au3.id = m.apartamento_usuario_id WHERE au3.apartamento_id = a.id AND au3.activo = 1 AND m.estado = 'vencida') as meses_vencidos,
                    (SELECT COALESCE(SUM(m2.monto_usd), 0) FROM mensualidades m2 JOIN apartamento_usuario au4 ON au4.id = m2.apartamento_usuario_id WHERE au4.apartamento_id = a.id AND au4.activo = 1 AND m2.estado = 'vencida') as deuda_total,
                    (SELECT MAX(CONCAT(m3.anio, '-', LPAD(m3.mes, 2, '0'))) FROM mensualidades m3 JOIN apartamento_usuario au5 ON au5.id = m3.apartamento_usuario_id WHERE au5.apartamento_id = a.id AND au5.activo = 1 AND m3.estado = 'vencida') as ultima_mensualidad
                FROM apartamentos a
                JOIN apartamento_usuario au ON au.apartamento_id = a.id AND au.activo = 1
                JOIN usuarios u ON u.id = au.usuario_id
                WHERE a.activo = 1";
        
        $params = [];

        if ($torre) {
            $sql .= " AND a.bloque = ?";
            $params[] = $torre;
        }

        // Subquery para morosidad filter
        $sql .= " HAVING meses_vencidos > 0";

        if ($mesesMin) {
            $sql .= " AND meses_vencidos >= ?";
            $params[] = intval($mesesMin);
        }

        // Ordenamiento
        switch ($orden) {
            case 'meses_asc': $sql .= " ORDER BY meses_vencidos ASC"; break;
            case 'deuda_desc': $sql .= " ORDER BY deuda_total DESC"; break;
            case 'deuda_asc': $sql .= " ORDER BY deuda_total ASC"; break;
            case 'meses_desc': default: $sql .= " ORDER BY meses_vencidos DESC"; break;
        }

        $morososRaw = Database::fetchAll($sql, $params);
        // Filtrar nulos si usuario no tiene morosidad real (aunque having deberia capturarlo, a veces por left join extras...)
        // En este caso usamos JOIN directo con usuarios y HAVING > 0, deberia estar bien.
        $morosos = $morososRaw;

        if ($formato === 'pdf') {
            require_once __DIR__ . '/../views/consultor/print_reporte_morosidad.php';
            return;
        }

        // CSV con delimitador punto y coma para Excel en español
        $filename = "reporte_morosidad_" . date('Ymd_His') . ".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // BOM UTF-8 para Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Encabezados
        fputcsv($output, ['Cliente', 'Cedula', 'Bloque', 'Apartamento', 'Controles', 'Meses Vencidos', 'Deuda Total', 'Ultima Mensualidad', 'Email', 'Telefono'], ';');

        foreach ($morosos as $m) {
            fputcsv($output, [
                $m['nombre_completo'],
                $m['cedula'],
                $m['torre'],
                $m['apartamento'],
                $m['total_controles'],
                $m['meses_vencidos'],
                number_format($m['deuda_total'], 2, '.', ''),
                $m['ultima_mensualidad'],
                $m['email'],
                $m['telefono']
            ], ';');
        }
        
        fclose($output);
        exit;
    }

    /**
     * Exportar pagos
     */
    public function exportPagos(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        $formato = $_GET['formato'] ?? 'excel';

        // if ($formato === 'pdf') { ... } // Eliminamos el bloque de redirección temprana para procesar los datos primero

        // --- Lógica de filtrado idéntica a reportePagos ---
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
        $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
        $estado = $_GET['estado'] ?? null;
        $moneda = $_GET['moneda'] ?? null;
        $torre = $_GET['torre'] ?? null;

        $sql = "SELECT 
                    p.id,
                    p.fecha_pago,
                    p.monto_bs,
                    p.monto_usd,
                    p.estado_comprobante,
                    p.notas,
                    p.fecha_aprobacion,
                    p.aprobado_por,
                    u.nombre_completo as cliente_nombre,
                    u.cedula as cliente_cedula,
                    op.nombre_completo as operador_nombre,
                    a.bloque as torre,
                    a.numero_apartamento as apartamento,
                    p.moneda_pago
                FROM pagos p
                JOIN apartamento_usuario au ON au.id = p.apartamento_usuario_id
                JOIN usuarios u ON u.id = au.usuario_id
                JOIN apartamentos a ON a.id = au.apartamento_id
                LEFT JOIN usuarios op ON op.id = p.aprobado_por
                WHERE DATE(p.fecha_pago) BETWEEN ? AND ?";
        
        $params = [$fechaInicio, $fechaFin];

        if ($estado) {
            $sql .= " AND p.estado_comprobante = ?";
            $params[] = $estado;
        }

        if ($moneda) {
            if ($moneda === 'USD') {
                $sql .= " AND (p.moneda_pago LIKE '%usd%' OR p.moneda_pago = 'zelle' OR p.moneda_pago = 'efectivo')";
            } elseif ($moneda === 'Bs') {
                $sql .= " AND (p.moneda_pago = 'bolivares' OR p.moneda_pago = 'pago_movil')";
            }
        }

        if ($torre) {
            $sql .= " AND a.bloque = ?";
            $params[] = $torre;
        }

        $sql .= " ORDER BY p.fecha_pago DESC, p.id DESC";

        $pagos = Database::fetchAll($sql, $params);

        if ($formato === 'pdf') {
            // Renderizar vista de impresión
            require_once __DIR__ . '/../views/consultor/print_reporte_pagos.php';
            return;
        }

        // --- Generar CSV con delimitador punto y coma ---
        $filename = "reporte_pagos_" . date('Ymd_His') . ".csv";
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // BOM para Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Encabezados
        fputcsv($output, ['ID', 'Fecha', 'Cliente', 'Cedula', 'Bloque', 'Apartamento', 'Monto USD', 'Monto Bs', 'Metodo', 'Estado', 'Aprobado Por', 'Notas'], ';');

        foreach ($pagos as $pago) {
            fputcsv($output, [
                $pago['id'],
                $pago['fecha_pago'],
                $pago['cliente_nombre'],
                $pago['cliente_cedula'],
                $pago['torre'],
                $pago['apartamento'],
                number_format($pago['monto_usd'], 2, '.', ''),
                number_format($pago['monto_bs'], 2, '.', ''),
                ucfirst(str_replace('_', ' ', $pago['moneda_pago'])),
                ucfirst($pago['estado_comprobante']),
                $pago['operador_nombre'] ?? '-',
                $pago['notas']
            ], ';');
        }
        
        fclose($output);
        exit;
    }

    /**
     * Exportar controles
     */
    public function exportControles(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        $formato = $_GET['formato'] ?? 'excel';

        // Filtros
        $estado = $_GET['estado'] ?? null;
        $torre = $_GET['torre'] ?? null;
        $posicion = $_GET['posicion'] ?? null;

        $sql = "SELECT 
                    c.id,
                    c.numero_control_completo,
                    c.receptor,
                    c.estado,
                    c.posicion_numero,
                    c.fecha_asignacion,
                    c.motivo_estado,
                    a.bloque as torre,
                    a.escalera,
                    a.numero_apartamento as apartamento,
                    u.nombre_completo as residente_nombre
                FROM controles_estacionamiento c
                LEFT JOIN apartamento_usuario au ON au.id = c.apartamento_usuario_id 
                LEFT JOIN usuarios u ON u.id = au.usuario_id
                LEFT JOIN apartamentos a ON a.id = au.apartamento_id
                WHERE 1=1";
        
        $params = [];

        if ($estado) {
            $sql .= " AND c.estado = ?";
            $params[] = $estado;
        }

        if ($torre) {
            // Filtrar controles por torre del apartamento asignado
            // Nota: controles libres no tienen torre, por lo que no saldrán si se filtra por torre
            $sql .= " AND a.bloque = ?";
            $params[] = $torre;
        }

        if ($posicion) {
            $sql .= " AND c.posicion_numero = ?";
            $params[] = $posicion;
        }

        $sql .= " ORDER BY c.posicion_numero ASC";
        
        $controles = Database::fetchAll($sql, $params);

        if ($formato === 'pdf') {
            require_once __DIR__ . '/../views/consultor/print_reporte_controles.php';
            return;
        }

        // CSV con delimitador punto y coma
        $filename = "reporte_controles_" . date('Ymd_His') . ".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));


        fputcsv($output, ['ID', 'Posicion', 'Receptor', 'Codigo', 'Estado', 'Bloque', 'Escalera', 'Apartamento', 'Residente', 'Fecha Asignacion', 'Motivo'], ';');

        foreach ($controles as $c) {
            fputcsv($output, [
                $c['id'],
                $c['posicion_numero'],
                $c['receptor'],
                $c['numero_control_completo'],
                ucfirst($c['estado']),
                $c['torre'],
                $c['escalera'],
                $c['apartamento'],
                $c['residente_nombre'],
                $c['fecha_asignacion'],
                $c['motivo_estado']
            ], ';');
        }
        
        fclose($output);
        exit;
    }

    // ==================== GESTIÓN DE PERFIL ====================

    /**
     * Ver perfil del consultor
     */
    public function perfil(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        // Obtener información del apartamento (si tiene)
        $sql = "SELECT a.id as apartamento_id, a.bloque, a.escalera, a.piso, a.numero_apartamento
                FROM apartamento_usuario au
                JOIN apartamentos a ON a.id = au.apartamento_id
                WHERE au.usuario_id = ? AND au.activo = 1
                LIMIT 1";
        $apartamento = Database::fetchOne($sql, [$usuario->id]);

        // Obtener controles asignados (si tiene)
        $sql = "SELECT ce.numero_control_completo, ce.estado, ce.fecha_asignacion
                FROM apartamento_usuario au
                LEFT JOIN controles_estacionamiento ce ON ce.apartamento_usuario_id = au.id
                WHERE au.usuario_id = ? AND au.activo = 1
                ORDER BY ce.numero_control_completo";
        $controles = Database::fetchAll($sql, [$usuario->id]);

        // Filtrar controles válidos (que no sean NULL)
        $controles = array_filter($controles, function($c) {
            return !empty($c['numero_control_completo']);
        });

        // Obtener todos los apartamentos disponibles para el selector
        $sql = "SELECT id, bloque, escalera, piso, numero_apartamento
                FROM apartamentos
                WHERE activo = 1
                ORDER BY bloque, escalera, piso, numero_apartamento";
        $apartamentosDisponibles = Database::fetchAll($sql);

        require_once __DIR__ . '/../views/consultor/perfil.php';
    }

    /**
     * Actualizar perfil del consultor
     */
    public function updatePerfil(): void
    {
        $usuario = $this->checkAuth();
        if (!$usuario) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('consultor/perfil');
            return;
        }

        // Validar CSRF
        if (!ValidationHelper::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            redirect('consultor/perfil');
            return;
        }

        $nombreCompleto = sanitize($_POST['nombre_completo'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $telefono = sanitize($_POST['telefono'] ?? '');
        $direccion = sanitize($_POST['direccion'] ?? '');
        $apartamentoId = intval($_POST['apartamento_id'] ?? 0);

        // Validar nombre completo
        if (empty($nombreCompleto) || strlen($nombreCompleto) < 3) {
            $_SESSION['error'] = 'El nombre completo debe tener al menos 3 caracteres';
            redirect('consultor/perfil');
            return;
        }

        // Validar email
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Formato de email inválido';
            redirect('consultor/perfil');
            return;
        }

        // Verificar si el email ya está en uso por otro usuario
        if ($email !== $usuario->email) {
            $existingUser = Usuario::findByEmail($email);
            if ($existingUser && $existingUser->id !== $usuario->id) {
                $_SESSION['error'] = 'El email ya está en uso por otro usuario';
                redirect('consultor/perfil');
                return;
            }
        }

        // Validar teléfono
        if (!empty($telefono) && !ValidationHelper::validatePhone($telefono)) {
            $_SESSION['error'] = 'Formato de teléfono inválido';
            redirect('consultor/perfil');
            return;
        }

        try {
            // Actualizar datos personales
            $usuario->update([
                'nombre_completo' => $nombreCompleto,
                'email' => $email,
                'telefono' => $telefono,
                'direccion' => $direccion
            ]);

            // Actualizar sesión si cambió el email o nombre
            if ($email !== $_SESSION['user_email']) {
                $_SESSION['user_email'] = $email;
            }

            if ($nombreCompleto !== $_SESSION['user_nombre']) {
                $_SESSION['user_nombre'] = $nombreCompleto;
            }

            // Manejar cambio de apartamento
            $sql = "SELECT id, apartamento_id FROM apartamento_usuario
                    WHERE usuario_id = ? AND activo = 1 LIMIT 1";
            $asignacionActual = Database::fetchOne($sql, [$usuario->id]);

            if ($apartamentoId > 0) {
                if ($asignacionActual) {
                    if ($asignacionActual['apartamento_id'] != $apartamentoId) {
                        $sql = "UPDATE apartamento_usuario SET activo = 0 WHERE id = ?";
                        Database::execute($sql, [$asignacionActual['id']]);

                        $sql = "INSERT INTO apartamento_usuario (usuario_id, apartamento_id, activo, fecha_asignacion)
                                VALUES (?, ?, 1, NOW())";
                        Database::execute($sql, [$usuario->id, $apartamentoId]);

                        writeLog("Apartamento cambiado para usuario {$usuario->email} (ID: {$usuario->id})", 'info');
                    }
                } else {
                    $sql = "INSERT INTO apartamento_usuario (usuario_id, apartamento_id, activo, fecha_asignacion)
                            VALUES (?, ?, 1, NOW())";
                    Database::execute($sql, [$usuario->id, $apartamentoId]);

                    writeLog("Apartamento asignado a usuario {$usuario->email} (ID: {$usuario->id})", 'info');
                }
            } else {
                if ($asignacionActual) {
                    $sql = "UPDATE apartamento_usuario SET activo = 0 WHERE id = ?";
                    Database::execute($sql, [$asignacionActual['id']]);

                    writeLog("Apartamento desasignado de usuario {$usuario->email} (ID: {$usuario->id})", 'info');
                }
            }

            $_SESSION['success'] = 'Perfil actualizado correctamente';

        } catch (Exception $e) {
            writeLog("Error al actualizar perfil de consultor: " . $e->getMessage(), 'error');
            $_SESSION['error'] = 'Error al actualizar el perfil. Intente nuevamente.';
        }

        redirect('consultor/perfil');
    }
}
