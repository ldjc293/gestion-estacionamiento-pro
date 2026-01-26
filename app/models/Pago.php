<?php
/**
 * Modelo Pago
 *
 * Maneja registro de pagos y generación de recibos
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../helpers/PDFHelper.php';
require_once __DIR__ . '/../helpers/QRHelper.php';

class Pago
{
    public $id;
    public $apartamento_usuario_id;
    public $numero_recibo;
    public $monto_usd;
    public $monto_bs;
    public $tasa_cambio_id;
    public $moneda_pago;
    public $fecha_pago;
    public $metodo_pago;
    public $estado;
    public $comprobante_ruta;
    public $estado_comprobante;
    public $motivo_rechazo;
    public $registrado_por;
    public $aprobado_por;
    public $fecha_aprobacion;
    public $es_reconexion;
    public $monto_reconexion_usd;
    public $google_sheets_sync;
    public $fecha_sync;
    public $notas;

    /**
     * Buscar pago por ID
     *
     * @param int $id ID del pago
     * @return Pago|null
     */
    public static function findById(int $id): ?Pago
    {
        $sql = "SELECT * FROM pagos WHERE id = ?";
        $result = Database::fetchOne($sql, [$id]);

        return $result ? self::hydrate($result) : null;
    }

    /**
     * Buscar pago por número de recibo
     *
     * @param string $numeroRecibo Número de recibo
     * @return Pago|null
     */
    public static function findByNumeroRecibo(string $numeroRecibo): ?Pago
    {
        $sql = "SELECT * FROM pagos WHERE numero_recibo = ?";
        $result = Database::fetchOne($sql, [$numeroRecibo]);

        return $result ? self::hydrate($result) : null;
    }

    /**
     * Registrar nuevo pago
     *
     * @param array $data Datos del pago
     * @return int ID del pago creado
     */
    public static function registrar(array $data): int
    {
        try {
            Database::beginTransaction();

            // Obtener tarifa actual para cálculos dinámicos
            require_once __DIR__ . '/ConfiguracionTarifa.php';
            $tarifaActual = ConfiguracionTarifa::getTarifaActual();

            if (!$tarifaActual) {
                throw new Exception("No hay tarifa configurada");
            }

            // Obtener cantidad de controles del apartamento
            $sqlControles = "SELECT cantidad_controles FROM apartamento_usuario WHERE id = ?";
            $controlesData = Database::fetchOne($sqlControles, [$data['apartamento_usuario_id']]);
            $cantidadControles = $controlesData ? $controlesData['cantidad_controles'] : 0;

            // Calcular montos basados en tarifa actual y mensualidades seleccionadas
            $cantidadMensualidades = isset($data['mensualidades_ids']) ? count($data['mensualidades_ids']) : 1;
            $montoCalculadoUSD = $tarifaActual->monto_mensual_usd * $cantidadControles * $cantidadMensualidades;

            // Obtener tasa BCV actual
            $sqlTasa = "SELECT id, tasa_usd_bs FROM tasa_cambio_bcv ORDER BY fecha_registro DESC LIMIT 1";
            $tasaData = Database::fetchOne($sqlTasa);
            $tasaBCV = $tasaData ? $tasaData['tasa_usd_bs'] : 36.40;
            $tasaId = $tasaData ? $tasaData['id'] : null;

            $montoCalculadoBS = $montoCalculadoUSD * $tasaBCV;

            // Generar número de recibo
            $numeroRecibo = self::generarNumeroRecibo();

            // Insertar pago con montos calculados dinámicamente
            $sql = "INSERT INTO pagos (
                        apartamento_usuario_id, numero_recibo, monto_usd, monto_bs,
                        tasa_cambio_id, moneda_pago, fecha_pago, comprobante_ruta,
                        estado_comprobante, registrado_por, es_reconexion,
                        monto_reconexion_usd, notas
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            // Determinar estado del comprobante
            if (isset($data['estado_comprobante'])) {
                $estadoComprobante = $data['estado_comprobante'];
            } else {
                $estadoComprobante = 'no_aplica'; // Por defecto para pagos en efectivo

                // Si es transferencia o pago móvil y tiene comprobante, requiere aprobación
                if (in_array($data['moneda_pago'], ['bs_transferencia', 'bs_pago_movil']) && !empty($data['comprobante_ruta'])) {
                    $estadoComprobante = 'pendiente';
                }
            }

            $params = [
                $data['apartamento_usuario_id'],
                $numeroRecibo,
                $montoCalculadoUSD, // Usar monto calculado dinámicamente
                $montoCalculadoBS,   // Usar monto calculado dinámicamente
                $tasaId,
                $data['moneda_pago'],
                $data['fecha_pago'] ?? date('Y-m-d H:i:s'),
                $data['comprobante_ruta'] ?? null,
                $estadoComprobante,
                $data['registrado_por'],
                $data['es_reconexion'] ?? false,
                $data['monto_reconexion_usd'] ?? null,
                $data['notas'] ?? null
            ];

            $pagoId = Database::insert($sql, $params);

            // Asociar pago con mensualidades
            if (isset($data['mensualidades_ids'])) {
                self::asociarMensualidades($pagoId, $data['mensualidades_ids'], $montoCalculadoUSD);
            }

            Database::commit();

            writeLog("Pago registrado con tarifa dinámica: $numeroRecibo (USD: $montoCalculadoUSD, Bs: $montoCalculadoBS)", 'info');

            return $pagoId;

        } catch (Exception $e) {
            Database::rollback();
            writeLog("Error al registrar pago: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Asociar pago con mensualidades
     *
     * @param int $pagoId ID del pago
     * @param array $mensualidadesIds IDs de mensualidades
     * @param float $montoTotal Monto total del pago
     */
    private static function asociarMensualidades(int $pagoId, array $mensualidadesIds, float $montoTotal): void
    {
        $montoRestante = $montoTotal;

        foreach ($mensualidadesIds as $mensualidadId) {
            // Obtener monto de la mensualidad
            $mensualidad = Mensualidad::findById($mensualidadId);

            if (!$mensualidad || $montoRestante <= 0) {
                continue;
            }

            $montoAplicado = min($montoRestante, $mensualidad->monto_usd);

            // Insertar relación pago-mensualidad
            $sql = "INSERT INTO pago_mensualidad (pago_id, mensualidad_id, monto_aplicado_usd)
                    VALUES (?, ?, ?)";
            Database::execute($sql, [$pagoId, $mensualidadId, $montoAplicado]);

            // Marcar mensualidad como pagada si se pagó completa
            if ($montoAplicado >= $mensualidad->monto_usd) {
                $mensualidad->marcarComoPagada();
            }

            $montoRestante -= $montoAplicado;
        }
    }

    /**
     * Marcar mensualidades asociadas como pagadas (para pagos aprobados)
     */
    private function marcarMensualidadesComoPagadas(): void
    {
        $sql = "UPDATE mensualidades m
                JOIN pago_mensualidad pm ON pm.mensualidad_id = m.id
                SET m.estado = 'pagada'
                WHERE pm.pago_id = ? AND m.estado != 'pagada'";

        Database::execute($sql, [$this->id]);

        writeLog("Mensualidades marcadas como pagadas para pago ID: {$this->id}", 'info');
    }

    /**
     * Aprobar comprobante de pago
     *
     * @param int $aprobadoPor ID del usuario que aprueba
     * @return bool
     */
    public function aprobar(int $aprobadoPor): bool
    {
        try {
            Database::beginTransaction();

            $sql = "UPDATE pagos
                    SET estado_comprobante = 'aprobado',
                        aprobado_por = ?,
                        fecha_aprobacion = NOW()
                    WHERE id = ?";

            Database::execute($sql, [$aprobadoPor, $this->id]);

            // Marcar mensualidades asociadas como pagadas
            $this->marcarMensualidadesComoPagadas();

            // Generar recibo PDF
            $this->generarRecibo();

            // Enviar notificación al cliente
            $this->enviarNotificacionAprobacion();

            Database::commit();

            writeLog("Pago aprobado: {$this->numero_recibo}", 'info');

            return true;

        } catch (Exception $e) {
            Database::rollback();
            writeLog("Error al aprobar pago: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Rechazar comprobante de pago
     *
     * @param int $rechazadoPor ID del usuario que rechaza
     * @param string $motivo Motivo del rechazo
     * @return bool
     */
    public function rechazar(int $rechazadoPor, string $motivo): bool
    {
        $sql = "UPDATE pagos
                SET estado_comprobante = 'rechazado',
                    motivo_rechazo = ?,
                    aprobado_por = ?,
                    fecha_aprobacion = NOW()
                WHERE id = ?";

        $result = Database::execute($sql, [$motivo, $rechazadoPor, $this->id]) > 0;

        if ($result) {
            // Enviar notificación de rechazo
            $this->enviarNotificacionRechazo($motivo);
            writeLog("Pago rechazado: {$this->numero_recibo} - Motivo: $motivo", 'info');
        }

        return $result;
    }

    /**
     * Generar recibo PDF
     *
     * @return string|null Path al archivo PDF o null si falla
     */
    public function generarRecibo(): ?string
    {
        try {
            // Obtener datos completos para el recibo
            $datosRecibo = $this->getDatosCompletos();

            // Generar PDF
            $pdfPath = PDFHelper::generateRecibo($datosRecibo);

            writeLog("Recibo PDF generado: {$this->numero_recibo}", 'info');

            return $pdfPath;

        } catch (Exception $e) {
            writeLog("Error al generar recibo PDF: " . $e->getMessage(), 'error');
            return null;
        }
    }

    /**
     * Obtener datos completos del pago para recibo
     *
     * @return array
     */
    private function getDatosCompletos(): array
    {
        $sql = "SELECT
                    p.*,
                    u.nombre_completo as cliente_nombre,
                    CONCAT(a.bloque, '-', a.escalera, '-', a.piso, '-', a.numero_apartamento) as apartamento,
                    t.tasa_usd_bs as tasa_cambio,
                    operador.nombre_completo as operador_nombre,
                    STRING_AGG(
                        m.mes || '/' || m.anio,
                        ', ' ORDER BY m.anio, m.mes
                    ) as meses_pagados,
                    au.cantidad_controles
                FROM pagos p
                JOIN apartamento_usuario au ON au.id = p.apartamento_usuario_id
                JOIN usuarios u ON u.id = au.usuario_id
                JOIN apartamentos a ON a.id = au.apartamento_id
                LEFT JOIN tasa_cambio_bcv t ON t.id = p.tasa_cambio_id
                LEFT JOIN usuarios operador ON operador.id = p.registrado_por
                LEFT JOIN pago_mensualidad pm ON pm.pago_id = p.id
                LEFT JOIN mensualidades m ON m.id = pm.mensualidad_id
                WHERE p.id = ?
                GROUP BY p.id";

        $result = Database::fetchOne($sql, [$this->id]);

        // Obtener controles asociados
        $sqlControles = "SELECT STRING_AGG(numero_control_completo, ', ' ORDER BY numero_control_completo) as controles
                         FROM controles_estacionamiento
                         WHERE apartamento_usuario_id = ?";
        $controles = Database::fetchOne($sqlControles, [$result['apartamento_usuario_id']]);

        $result['controles'] = $controles['controles'] ?? 'N/A';

        return $result;
    }

    /**
     * Enviar notificación de aprobación
     */
    private function enviarNotificacionAprobacion(): void
    {
        $datos = $this->getDatosCompletos();

        // Notificación en el sistema
        $sql = "INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, email_enviado)
                SELECT au.usuario_id, 'pago_aprobado',
                       'Pago Aprobado',
                       CONCAT('Su pago ha sido aprobado. Recibo: ', ?),
                       TRUE
                FROM apartamento_usuario au
                WHERE au.id = ?";
        Database::execute($sql, [$this->numero_recibo, $this->apartamento_usuario_id]);

        // Email (si MailHelper está disponible)
        if (class_exists('MailHelper')) {
            $sqlUsuario = "SELECT u.email, u.nombre_completo
                           FROM usuarios u
                           JOIN apartamento_usuario au ON au.usuario_id = u.id
                           WHERE au.id = ?";
            $usuario = Database::fetchOne($sqlUsuario, [$this->apartamento_usuario_id]);

            if ($usuario) {
                // Obtener mensualidades como array para el email
                $mensualidades = self::getMensualidadesPago($this->id);
                $mesesArray = array_map(function($m) {
                    return ['mes' => $m['mes'], 'anio' => $m['anio']];
                }, $mensualidades);

                MailHelper::sendPaymentApproved(
                    $usuario['email'],
                    $usuario['nombre_completo'],
                    $this->numero_recibo,
                    $this->monto_usd,
                    $mesesArray
                );
            }
        }
    }

    /**
     * Enviar notificación de rechazo
     *
     * @param string $motivo Motivo del rechazo
     */
    private function enviarNotificacionRechazo(string $motivo): void
    {
        // Notificación en el sistema
        $sql = "INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, email_enviado)
                SELECT au.usuario_id, 'comprobante_rechazado',
                       'Comprobante Rechazado',
                       CONCAT('Su comprobante fue rechazado. Motivo: ', ?),
                       TRUE
                FROM apartamento_usuario au
                WHERE au.id = ?";
        Database::execute($sql, [$motivo, $this->apartamento_usuario_id]);

        // Email
        if (class_exists('MailHelper')) {
            $sqlUsuario = "SELECT u.email, u.nombre_completo
                           FROM usuarios u
                           JOIN apartamento_usuario au ON au.usuario_id = u.id
                           WHERE au.id = ?";
            $usuario = Database::fetchOne($sqlUsuario, [$this->apartamento_usuario_id]);

            if ($usuario) {
                MailHelper::sendPaymentRejected(
                    $usuario['email'],
                    $usuario['nombre_completo'],
                    $this->numero_recibo,
                    $this->monto_usd,
                    $motivo
                );
            }
        }
    }

    /**
     * Generar número de recibo único
     *
     * @return string Número de recibo (EST-000001)
     */
    private static function generarNumeroRecibo(): string
    {
        $sql = "SELECT MAX(CAST(SUBSTRING(numero_recibo, 5) AS INTEGER)) as ultimo
                FROM pagos
                WHERE numero_recibo LIKE 'EST-%'";

        $result = Database::fetchOne($sql);
        $ultimo = $result['ultimo'] ?? 0;
        $siguiente = $ultimo + 1;

        return generateReciboNumber($siguiente);
    }

    /**
     * Obtener pagos pendientes de aprobar
     *
     * @return array
     */
    public static function getPendientesAprobar(): array
    {
        $sql = "SELECT p.*,
                        u.nombre_completo as cliente_nombre,
                        CONCAT(a.bloque, '-', a.escalera, '-', a.piso, '-', a.numero_apartamento) as apartamento
                FROM pagos p
                JOIN apartamento_usuario au ON au.id = p.apartamento_usuario_id
                JOIN usuarios u ON u.id = au.usuario_id
                JOIN apartamentos a ON a.id = au.apartamento_id
                WHERE p.estado_comprobante = 'pendiente'
                ORDER BY p.fecha_pago ASC";

        $result = Database::fetchAll($sql);
        return is_array($result) ? $result : [];
    }

    /**
     * Obtener historial de pagos de un usuario
     *
     * @param int $usuarioId ID del usuario
     * @param int $limit Límite de registros
     * @return array
     */
    public static function getHistorialByUsuario(int $usuarioId, int $limit = 20): array
    {
        $sql = "SELECT p.*,
                        CONCAT(a.bloque, '-', a.escalera, '-', a.piso, '-', a.numero_apartamento) as apartamento,
                        t.tasa_usd_bs
                FROM pagos p
                JOIN apartamento_usuario au ON au.id = p.apartamento_usuario_id
                JOIN apartamentos a ON a.id = au.apartamento_id
                LEFT JOIN tasa_cambio_bcv t ON t.id = p.tasa_cambio_id
                WHERE au.usuario_id = ?
                ORDER BY p.fecha_pago DESC
                LIMIT ?";

        return Database::fetchAll($sql, [$usuarioId, $limit]);
    }

    /**
     * Obtener estadísticas de pagos del mes
     *
     * @param int $mes Mes (1-12)
     * @param int $anio Año
     * @return array
     */
    public static function getEstadisticasMes(int $mes, int $anio): array
    {
        $sql = "SELECT
                    COUNT(*) as total_pagos,
                    SUM(monto_usd) as total_usd,
                    SUM(monto_bs) as total_bs,
                    COUNT(CASE WHEN moneda_pago = 'usd_efectivo' THEN 1 END) as pagos_usd,
                    COUNT(CASE WHEN moneda_pago = 'bs_transferencia' THEN 1 END) as pagos_transferencia,
                    COUNT(CASE WHEN moneda_pago = 'bs_efectivo' THEN 1 END) as pagos_bs_efectivo
                FROM pagos
                WHERE EXTRACT(MONTH FROM fecha_pago) = ?
                  AND EXTRACT(YEAR FROM fecha_pago) = ?
                  AND estado_comprobante IN ('aprobado', 'no_aplica')";

        return Database::fetchOne($sql, [$mes, $anio]) ?: [];
    }

    /**
     * Hidratar objeto desde array
     *
     * @param array $data Datos
     * @return Pago
     */
    private static function hydrate(array $data): Pago
    {
        $pago = new self();

        foreach ($data as $key => $value) {
            if (property_exists($pago, $key)) {
                $pago->$key = $value;
            }
        }

        return $pago;
    }

    /**
     * Convertir a array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'numero_recibo' => $this->numero_recibo,
            'monto_usd' => $this->monto_usd,
            'monto_bs' => $this->monto_bs,
            'moneda_pago' => $this->moneda_pago,
            'fecha_pago' => $this->fecha_pago,
            'estado_comprobante' => $this->estado_comprobante,
            'es_reconexion' => $this->es_reconexion
        ];
    }

    /**
     * Obtener pagos de un usuario con filtros (compatible con ClienteController)
     *
     * @param int $usuarioId ID del usuario
     * @param int $limit Límite de resultados
     * @return array de objetos Pago
     */
    public static function getByUsuario(int $usuarioId, int $limit = 20): array
    {
        $sql = "SELECT p.*
                FROM pagos p
                JOIN apartamento_usuario au ON au.id = p.apartamento_usuario_id
                WHERE au.usuario_id = ?
                ORDER BY p.fecha_pago DESC
                LIMIT ?";

        $results = Database::fetchAll($sql, [$usuarioId, $limit]);

        return array_map(fn($row) => self::hydrate($row), $results);
    }

    /**
     * Obtener pagos pendientes de un usuario
     *
     * @param int $usuarioId ID del usuario
     * @return array de objetos Pago
     */
    public static function getPendientesByUsuario(int $usuarioId): array
    {
        $sql = "SELECT p.*
                FROM pagos p
                JOIN apartamento_usuario au ON au.id = p.apartamento_usuario_id
                WHERE au.usuario_id = ? AND p.estado_comprobante = 'pendiente'
                ORDER BY p.fecha_pago DESC";

        $results = Database::fetchAll($sql, [$usuarioId]);

        return array_map(fn($row) => self::hydrate($row), $results);
    }

    /**
     * Obtener pagos con filtros
     *
     * @param int $usuarioId ID del usuario
     * @param array $filtros Filtros (estado, mes, anio)
     * @return array de objetos Pago
     */
    public static function getByUsuarioConFiltros(int $usuarioId, array $filtros = []): array
    {
        $sql = "SELECT p.*
                FROM pagos p
                JOIN apartamento_usuario au ON au.id = p.apartamento_usuario_id
                WHERE au.usuario_id = ?";

        $params = [$usuarioId];

        if (!empty($filtros['estado'])) {
            $sql .= " AND p.estado_comprobante = ?";
            $params[] = $filtros['estado'];
        }

        if (!empty($filtros['mes'])) {
            $sql .= " AND EXTRACT(MONTH FROM p.fecha_pago) = ?";
            $params[] = $filtros['mes'];
        }

        if (!empty($filtros['anio'])) {
            $sql .= " AND EXTRACT(YEAR FROM p.fecha_pago) = ?";
            $params[] = $filtros['anio'];
        }

        $sql .= " ORDER BY p.fecha_pago DESC";

        $results = Database::fetchAll($sql, $params);

        return array_map(fn($row) => self::hydrate($row), $results);
    }

    /**
     * Obtener todas los pagos con filtros (para operadores/admin)
     *
     * @param array $filtros Filtros opcionales
     * @return array de objetos Pago
     */
    public static function getAllConFiltros(array $filtros = []): array
    {
        $sql = "SELECT p.*,
                        u.nombre_completo as cliente_nombre,
                        CONCAT(a.bloque, '-', a.escalera, '-', a.piso, '-', a.numero_apartamento) as apartamento
                FROM pagos p
                JOIN apartamento_usuario au ON au.id = p.apartamento_usuario_id
                LEFT JOIN usuarios u ON u.id = au.usuario_id
                LEFT JOIN apartamentos a ON a.id = au.apartamento_id
                WHERE 1=1";

        $params = [];

        if (!empty($filtros['estado'])) {
            $sql .= " AND p.estado_comprobante = ?";
            $params[] = $filtros['estado'];
        }

        if (!empty($filtros['mes'])) {
            $sql .= " AND EXTRACT(MONTH FROM p.fecha_pago) = ?";
            $params[] = $filtros['mes'];
        }

        if (!empty($filtros['anio'])) {
            $sql .= " AND EXTRACT(YEAR FROM p.fecha_pago) = ?";
            $params[] = $filtros['anio'];
        }

        if (!empty($filtros['cliente'])) {
            $sql .= " AND u.nombre_completo LIKE ?";
            $params[] = "%{$filtros['cliente']}%";
        }

        $sql .= " ORDER BY p.fecha_pago DESC";

        return Database::fetchAll($sql, $params);
    }

    /**
     * Obtener mensualidades asociadas a un pago
     *
     * @param int $pagoId ID del pago
     * @return array
     */
    public static function getMensualidadesPago(int $pagoId): array
    {
        $sql = "SELECT m.*, pm.monto_aplicado_usd
                FROM mensualidades m
                JOIN pago_mensualidad pm ON pm.mensualidad_id = m.id
                WHERE pm.pago_id = ?
                ORDER BY m.anio ASC, m.mes ASC";

        return Database::fetchAll($sql, [$pagoId]);
    }
}
