<?php
/**
 * Modelo Mensualidad
 *
 * Maneja mensualidades generadas automáticamente cada mes
 */

require_once __DIR__ . '/../../config/database.php';

class Mensualidad
{
    public $id;
    public $apartamento_usuario_id;
    public $mes;
    public $anio;
    public $cantidad_controles;
    public $monto_usd;
    public $monto_bs;
    public $tasa_cambio_id;
    public $estado;
    public $fecha_vencimiento;
    public $fecha_generacion;
    public $bloqueado;

    // Propiedades adicionales para joins
    public $mes_correspondiente;
    public $apartamento;
    public $tasa_usd_bs;

    /**
     * Buscar mensualidad por ID
     *
     * @param int $id ID de la mensualidad
     * @return Mensualidad|null
     */
    public static function findById(int $id): ?Mensualidad
    {
        $sql = "SELECT * FROM mensualidades WHERE id = ?";
        $result = Database::fetchOne($sql, [$id]);

        return $result ? self::hydrate($result) : null;
    }

    /**
     * Obtener mensualidades de un apartamento_usuario
     *
     * @param int $apartamentoUsuarioId ID de apartamento_usuario
     * @param array $filters Filtros opcionales ['estado', 'anio', 'limit']
     * @return array
     */
    public static function getByApartamentoUsuario(int $apartamentoUsuarioId, array $filters = []): array
    {
        $sql = "SELECT m.*, t.tasa_usd_bs
                FROM mensualidades m
                LEFT JOIN tasa_cambio_bcv t ON t.id = m.tasa_cambio_id
                WHERE m.apartamento_usuario_id = ?";

        $params = [$apartamentoUsuarioId];

        if (isset($filters['estado'])) {
            $sql .= " AND m.estado = ?";
            $params[] = $filters['estado'];
        }

        if (isset($filters['anio'])) {
            $sql .= " AND m.anio = ?";
            $params[] = $filters['anio'];
        }

        $sql .= " ORDER BY m.anio DESC, m.mes DESC";

        if (isset($filters['limit'])) {
            $sql .= " LIMIT ?";
            $params[] = $filters['limit'];
        }

        $results = Database::fetchAll($sql, $params);

        return array_map(fn($row) => self::hydrate($row), $results);
    }

    /**
     * Obtener mensualidades pendientes de un usuario (incluyendo vencidas por fecha)
     *
     * @param int $usuarioId ID del usuario
     * @param bool $generarFuturas Si debe generar mensualidades futuras
     * @return array
     */
    public static function getPendientesByUsuario(int $usuarioId, bool $generarFuturas = true): array
    {
        // Primero generar mensualidades retroactivas si faltan
        self::generarMensualidadesRetroactivas($usuarioId);

        $sql = "SELECT m.*, au.cantidad_controles,
                        CONCAT(a.bloque, '-', a.numero_apartamento) as apartamento,
                        t.tasa_usd_bs,
                        CONCAT(m.anio, '-', LPAD(m.mes::text, 2, '0'), '-01') as mes_correspondiente
                 FROM mensualidades m
                 JOIN apartamento_usuario au ON au.id = m.apartamento_usuario_id
                 JOIN apartamentos a ON a.id = au.apartamento_id
                 LEFT JOIN tasa_cambio_bcv t ON t.id = m.tasa_cambio_id
                 WHERE au.usuario_id = ?
                   AND au.activo = TRUE
                   AND m.estado != 'pagada'
                   AND (
                       m.estado IN ('pendiente', 'vencido', 'vencida') OR
                       (m.fecha_vencimiento <= CURRENT_DATE AND NOT EXISTS(
                           SELECT 1 FROM pago_mensualidad pm
                           JOIN pagos p ON p.id = pm.pago_id
                           WHERE pm.mensualidad_id = m.id
                             AND p.estado_comprobante IN ('aprobado', 'no_aplica')
                       ))
                   )
                 ORDER BY m.fecha_vencimiento ASC";

        $results = Database::fetchAll($sql, [$usuarioId]);
        $mensualidades = array_map(fn($row) => self::hydrate($row), $results);

        // Si no hay suficientes mensualidades futuras, generarlas
        if ($generarFuturas && count($mensualidades) < 3) {
            $futuras = self::generarMensualidadesFuturas($usuarioId, 3 - count($mensualidades));
            $mensualidades = array_merge($mensualidades, $futuras);

            // Ordenar por fecha de vencimiento
            usort($mensualidades, function($a, $b) {
                return strtotime($a->fecha_vencimiento) - strtotime($b->fecha_vencimiento);
            });
        }

        return $mensualidades;
    }

    /**
     * Obtener mensualidades para pagos adelantados (consecutivas desde la más antigua pendiente)
     * El cliente puede pagar múltiples meses consecutivos, pero NO puede saltarse mensualidades
     * Genera mensualidades retroactivas si faltan meses pasados sin pago
     *
     * @param int $usuarioId ID del usuario
     * @param int $mesesAdelante Número máximo de meses consecutivos a permitir
     * @return array
     */
    public static function getMensualidadesParaPagoAdelantado(int $usuarioId, ?int $mesesAdelante = null): array
    {
        // Generar mensualidades retroactivas para meses pasados sin pago si es necesario
        self::generarMensualidadesRetroactivas($usuarioId);

        // Generar mensualidades futuras hasta el final del año siguiente (ej. hasta Dic 2027 si estamos en 2026)
        $hastaAnio = (int)date('Y') + 1;
        self::generarMensualidadesFuturas($usuarioId, $hastaAnio);

        // Obtener todas las mensualidades no pagadas del usuario ordenadas por fecha de vencimiento hasta el fin del año siguiente
        $sqlTodas = "SELECT m.*, au.cantidad_controles,
                            CONCAT(a.bloque, '-', a.numero_apartamento) as apartamento,
                            t.tasa_usd_bs,
                            CONCAT(m.anio, '-', LPAD(m.mes::text, 2, '0'), '-01') as mes_correspondiente
                     FROM mensualidades m
                     JOIN apartamento_usuario au ON au.id = m.apartamento_usuario_id
                     JOIN apartamentos a ON a.id = au.apartamento_id
                     LEFT JOIN tasa_cambio_bcv t ON t.id = m.tasa_cambio_id
                     WHERE au.usuario_id = ?
                       AND au.activo = TRUE
                       AND m.estado != 'pagada'
                       AND m.anio <= ?
                       AND NOT EXISTS (
                           SELECT 1 FROM pago_mensualidad pm
                           JOIN pagos p ON p.id = pm.pago_id
                           WHERE pm.mensualidad_id = m.id
                             AND p.estado_comprobante IN ('aprobado', 'no_aplica')
                       )
                     ORDER BY m.fecha_vencimiento ASC, m.id ASC";

        $todasMensualidades = Database::fetchAll($sqlTodas, [$usuarioId, $hastaAnio]);

        // Si se especifica explícitamente un límite de meses y hay más mensualidades, recortar
        if ($mesesAdelante !== null && $mesesAdelante > 0 && count($todasMensualidades) > $mesesAdelante) {
            $todasMensualidades = array_slice($todasMensualidades, 0, $mesesAdelante);
        }

        return array_map(fn($row) => self::hydrate($row), $todasMensualidades);
    }

    /**
     * Obtener mensualidades vencidas (para alertas)
     * Considera mensualidades vencidas por fecha (fecha_vencimiento < CURRENT_DATE) sin pago aprobado
     *
     * @param int $mesesMinimos Mínimo de meses vencidos
     * @return array
     */
    public static function getVencidas(int $mesesMinimos = 3): array
    {
        // Subconsulta para obtener mensualidades vencidas por fecha sin pago aprobado
        $sql = "SELECT u.id as usuario_id, u.nombre_completo, u.email,
                        COUNT(m.id) as meses_pendientes,
                        SUM(m.monto_usd) as total_deuda_usd,
                        SUM(m.monto_bs) as total_deuda_bs,
                        MIN(m.fecha_vencimiento) as primer_mes_vencido
                 FROM mensualidades m
                 JOIN apartamento_usuario au ON au.id = m.apartamento_usuario_id
                 JOIN usuarios u ON u.id = au.usuario_id
                 WHERE m.fecha_vencimiento < CURRENT_DATE
                   AND au.activo = TRUE
                   AND u.activo = TRUE
                   AND u.exonerado = FALSE
                   AND NOT EXISTS (
                       SELECT 1 FROM pago_mensualidad pm
                       JOIN pagos p ON p.id = pm.pago_id
                       WHERE pm.mensualidad_id = m.id
                         AND p.estado_comprobante IN ('aprobado', 'no_aplica')
                   )
                  GROUP BY u.id, u.nombre_completo, u.email
                  HAVING COUNT(m.id) >= ?
                  ORDER BY meses_pendientes DESC";

        return Database::fetchAll($sql, [$mesesMinimos]);
    }

    /**
     * Generar mensualidades del mes actual
     * Se ejecuta automáticamente el día 5 de cada mes vía CRON
     *
     * @return int Número de mensualidades generadas
     */
    public static function generarMensualidadesMes(): int
    {
        try {
            Database::beginTransaction();

            // Obtener mes y año actual
            $mes = (int)date('n');
            $anio = (int)date('Y');

            // Obtener última tasa BCV
            $sqlTasa = "SELECT id, tasa_usd_bs FROM tasa_cambio_bcv
                        ORDER BY fecha_registro DESC LIMIT 1";
            $tasa = Database::fetchOne($sqlTasa);

            if (!$tasa) {
                throw new Exception("No hay tasa de cambio BCV registrada");
            }

            // Obtener tarifa vigente
            $sqlTarifa = "SELECT monto_mensual_usd FROM configuracion_tarifas
                          WHERE activo = TRUE
                          AND fecha_vigencia_inicio <= CURRENT_DATE
                          ORDER BY fecha_vigencia_inicio DESC LIMIT 1";
            $tarifa = Database::fetchOne($sqlTarifa);

            if (!$tarifa) {
                throw new Exception("No hay tarifa configurada");
            }

            $tarifaUSD = $tarifa['monto_mensual_usd'];
            $tasaCambioId = $tasa['id'];
            $tasaBCV = $tasa['tasa_usd_bs'];

            // Obtener fecha de vencimiento (último día del mes)
            $fechaVencimiento = date('Y-m-t');

            // Insertar mensualidades para usuarios activos no exonerados
            $sqlInsert = "INSERT INTO mensualidades (
                            apartamento_usuario_id, mes, anio,
                            cantidad_controles, monto_usd, monto_bs,
                            tasa_cambio_id, fecha_vencimiento, estado
                          )
                          SELECT
                            au.id,
                            ?,
                            ?,
                            au.cantidad_controles,
                            (au.cantidad_controles::numeric * ?::numeric),
                            (au.cantidad_controles::numeric * ?::numeric * ?::numeric),
                            ?,
                            ?,
                            'pendiente'
                          FROM apartamento_usuario au
                          JOIN usuarios u ON u.id = au.usuario_id
                          WHERE au.activo = TRUE
                            AND u.activo = TRUE
                            AND u.exonerado = FALSE
                            AND au.cantidad_controles > 0
                            AND NOT EXISTS (
                                SELECT 1 FROM mensualidades m2
                                WHERE m2.apartamento_usuario_id = au.id
                                  AND m2.mes = ?
                                  AND m2.anio = ?
                            )";

            $params = [
                $mes, $anio,
                $tarifaUSD, $tarifaUSD, $tasaBCV,
                $tasaCambioId, $fechaVencimiento,
                $mes, $anio
            ];

            $resultado = Database::execute($sqlInsert, $params);

            // Rectificar mensualidades existentes no pagadas para el mes actual si cambió la tarifa o cantidad de controles
            $sqlUpdate = "UPDATE mensualidades m
                          SET cantidad_controles = au.cantidad_controles,
                              monto_usd = (au.cantidad_controles::numeric * ?::numeric),
                              monto_bs = (au.cantidad_controles::numeric * ?::numeric * ?::numeric),
                              tasa_cambio_id = ?
                          FROM apartamento_usuario au
                          JOIN usuarios u ON u.id = au.usuario_id
                          WHERE m.apartamento_usuario_id = au.id
                            AND m.mes = ?
                            AND m.anio = ?
                            AND m.estado = 'pendiente'
                            AND au.activo = TRUE
                            AND u.activo = TRUE
                            AND u.exonerado = FALSE
                            AND au.cantidad_controles > 0
                            AND (m.cantidad_controles != au.cantidad_controles OR m.monto_usd != (au.cantidad_controles::numeric * ?::numeric) OR m.tasa_cambio_id != ?)";

            $updateParams = [
                $tarifaUSD, $tarifaUSD, $tasaBCV,
                $tasaCambioId, $mes, $anio,
                $tarifaUSD, $tasaCambioId
            ];

            $rectificadas = Database::execute($sqlUpdate, $updateParams);

            // Eliminar mensualidades pendientes del mes actual si el usuario ya no tiene controles, está exonerado o inactivo
            $sqlDelete = "DELETE FROM mensualidades m
                          USING apartamento_usuario au
                          JOIN usuarios u ON u.id = au.usuario_id
                          WHERE m.apartamento_usuario_id = au.id
                            AND m.mes = ?
                            AND m.anio = ?
                            AND m.estado = 'pendiente'
                            AND (au.cantidad_controles = 0 OR u.exonerado = TRUE OR au.activo = FALSE OR u.activo = FALSE)";
            $eliminadas = Database::execute($sqlDelete, [$mes, $anio]);

            Database::commit();

            // Log
            writeLog("Mensualidades generadas/rectificadas para $mes/$anio: $resultado creadas, $rectificadas actualizadas, $eliminadas removidas", 'info');

            return $resultado + $rectificadas;

        } catch (Exception $e) {
            Database::rollback();
            writeLog("Error al generar mensualidades: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Marcar mensualidad como pagada
     *
     * @return bool
     */
    public function marcarComoPagada(): bool
    {
        $sql = "UPDATE mensualidades SET estado = 'pagada' WHERE id = ?";
        return Database::execute($sql, [$this->id]) > 0;
    }

    /**
     * Marcar mensualidades vencidas
     * Se ejecuta diariamente vía CRON
     *
     * @return int Número de mensualidades marcadas como vencidas
     */
    public static function marcarVencidas(): int
    {
        $sql = "UPDATE mensualidades
                SET estado = 'vencido'
                WHERE estado = 'pendiente'
                  AND fecha_vencimiento < CURRENT_DATE";

        $resultado = Database::execute($sql);

        writeLog("Mensualidades marcadas como vencidas: $resultado", 'info');

        return $resultado;
    }

    /**
     * Verificar y bloquear controles por morosidad (4+ meses)
     * Se ejecuta diariamente vía CRON
     *
     * @return int Número de apartamento_usuario bloqueados
     */
    public static function verificarBloqueos(): int
    {
        try {
            Database::beginTransaction();

            // Obtener apartamento_usuario con 4+ meses de mora
            $sql = "SELECT au.id, au.usuario_id, COUNT(m.id) as meses_mora
                    FROM apartamento_usuario au
                    JOIN mensualidades m ON m.apartamento_usuario_id = au.id
                    JOIN usuarios u ON u.id = au.usuario_id
                    WHERE m.estado IN ('vencido', 'vencida')
                      AND au.activo = TRUE
                      AND u.activo = TRUE
                      AND u.exonerado = FALSE
                    GROUP BY au.id, au.usuario_id
                    HAVING COUNT(m.id) >= ?";

            $morosos = Database::fetchAll($sql, [MESES_BLOQUEO]);

            $bloqueados = 0;

            foreach ($morosos as $moroso) {
                // Marcar mensualidades como bloqueadas
                $sqlUpdate = "UPDATE mensualidades
                              SET bloqueado = TRUE
                              WHERE apartamento_usuario_id = ?
                                AND estado IN ('vencido', 'vencida')";
                Database::execute($sqlUpdate, [$moroso['id']]);

                // Bloquear controles
                $sqlBloquear = "UPDATE controles_estacionamiento
                                SET estado = 'bloqueado',
                                    motivo_estado = 'Bloqueado por morosidad (4+ meses)',
                                    fecha_estado = NOW()
                                WHERE apartamento_usuario_id = ?
                                  AND estado = 'activo'";
                Database::execute($sqlBloquear, [$moroso['id']]);

                $bloqueados++;
            }

            Database::commit();

            writeLog("Controles bloqueados por morosidad: $bloqueados", 'info');

            return $bloqueados;

        } catch (Exception $e) {
            Database::rollback();
            writeLog("Error al verificar bloqueos: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Verificar y desbloquear controles automáticamente si el usuario ya no tiene mora (menos de 4 meses)
     *
     * @param int $apartamentoUsuarioId
     * @return bool
     */
    public static function verificarDesbloqueosPorApartamentoUsuario(int $apartamentoUsuarioId): bool
    {
        try {
            // Contar meses de mora del usuario (fecha_vencimiento anterior a la fecha actual y sin pago aprobado)
            $sqlMora = "SELECT COUNT(m.id) as meses_mora
                        FROM mensualidades m
                        WHERE m.apartamento_usuario_id = ?
                          AND m.fecha_vencimiento < CURRENT_DATE
                          AND m.estado != 'pagada'
                          AND NOT EXISTS (
                              SELECT 1 FROM pago_mensualidad pm
                              JOIN pagos p ON p.id = pm.pago_id
                              WHERE pm.mensualidad_id = m.id
                                AND p.estado_comprobante IN ('aprobado', 'no_aplica')
                          )";

            $res = Database::fetchOne($sqlMora, [$apartamentoUsuarioId]);
            $mesesMora = $res ? (int)$res['meses_mora'] : 0;
            $limiteBloqueo = defined('MESES_BLOQUEO') ? MESES_BLOQUEO : 4;

            // Si el usuario debe menos de 4 meses (o 0), desbloquear controles bloqueados por morosidad
            if ($mesesMora < $limiteBloqueo) {
                // Desbloquear mensualidades
                $sqlUnblockMens = "UPDATE mensualidades
                                   SET bloqueado = FALSE
                                   WHERE apartamento_usuario_id = ?";
                Database::execute($sqlUnblockMens, [$apartamentoUsuarioId]);

                // Desbloquear controles
                $sqlDesbloquear = "UPDATE controles_estacionamiento
                                    SET estado = 'activo',
                                        motivo_estado = NULL,
                                        fecha_estado = NOW()
                                    WHERE apartamento_usuario_id = ?
                                      AND estado = 'bloqueado'
                                      AND (motivo_estado ILIKE '%morosidad%' OR motivo_estado IS NULL)";
                $desbloqueados = Database::execute($sqlDesbloquear, [$apartamentoUsuarioId]);

                writeLog("Controles desbloqueados automáticamente para apartamento_usuario_id $apartamentoUsuarioId (Mora: $mesesMora): $desbloqueados", 'info');
                return true;
            }

            return false;

        } catch (Exception $e) {
            writeLog("Error al verificar desbloqueos: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Calcular total adeudado de un usuario
     *
     * @param int $usuarioId ID del usuario
     * @return array ['total_usd', 'total_bs', 'meses_count']
     */
    public static function calcularDeudaTotal(int $usuarioId): array
    {
        // Primero obtener todas las mensualidades del usuario
        $sqlTodas = "SELECT m.*
                     FROM mensualidades m
                     JOIN apartamento_usuario au ON au.id = m.apartamento_usuario_id
                     WHERE au.usuario_id = ?
                       AND au.activo = TRUE
                     ORDER BY m.fecha_vencimiento";

        $todasMensualidades = Database::fetchAll($sqlTodas, [$usuarioId]);

        $totalUsd = 0;
        $totalBs = 0;
        $mesesCount = 0;

        foreach ($todasMensualidades as $mensualidad) {
            // Si el estado en BD es pagada, ignorar inmediatamente
            if (($mensualidad['estado'] ?? '') === 'pagada') {
                continue;
            }

            // Verificar si tiene pago aprobado
            $sqlPago = "SELECT COUNT(DISTINCT p.id) as tiene_pago
                        FROM pago_mensualidad pm
                        JOIN pagos p ON p.id = pm.pago_id
                        WHERE pm.mensualidad_id = ?
                          AND p.estado_comprobante IN ('aprobado', 'no_aplica')";

            $resultadoPago = Database::fetchOne($sqlPago, [$mensualidad['id']]);
            $tienePago = $resultadoPago && $resultadoPago['tiene_pago'] > 0;

            // Si no tiene pago aprobado Y está vencida (fecha de vencimiento pasada), contar como deuda
            $fechaVencimiento = strtotime($mensualidad['fecha_vencimiento']);
            $estaVencida = $fechaVencimiento < time();

            if (!$tienePago && $estaVencida) {
                $totalUsd += $mensualidad['monto_usd'];
                $totalBs += $mensualidad['monto_bs'];
                $mesesCount++;
            }
        }

        return [
            'total_usd' => (float)$totalUsd,
            'total_bs' => (float)$totalBs,
            'meses_count' => (int)$mesesCount,
            // Alias para compatibilidad con vistas
            'deuda_total_usd' => (float)$totalUsd,
            'total_vencidas' => (int)$mesesCount
        ];
    }

    /**
     * Obtener historial de mensualidades de un usuario
     *
     * @param int $usuarioId ID del usuario
     * @param int $limit Límite de registros
     * @return array
     */
    public static function getHistorialByUsuario(int $usuarioId, int $limit = 12): array
    {
        $sql = "SELECT m.*,
                       CONCAT(a.bloque, '-', a.numero_apartamento) as apartamento,
                       t.tasa_usd_bs
                FROM mensualidades m
                JOIN apartamento_usuario au ON au.id = m.apartamento_usuario_id
                JOIN apartamentos a ON a.id = au.apartamento_id
                LEFT JOIN tasa_cambio_bcv t ON t.id = m.tasa_cambio_id
                WHERE au.usuario_id = ?
                ORDER BY m.anio DESC, m.mes DESC
                LIMIT ?";

        return Database::fetchAll($sql, [$usuarioId, $limit]);
    }

    /**
     * Obtener todas las mensualidades de un usuario
     *
     * @param int $usuarioId ID del usuario
     * @return array
     */
    public static function getAllByUsuario(int $usuarioId): array
    {
        $sql = "SELECT m.*,
                       CONCAT(a.bloque, '-', a.numero_apartamento) as apartamento,
                       t.tasa_usd_bs,
                       MAX(p.fecha_pago) as fecha_pago
                FROM mensualidades m
                JOIN apartamento_usuario au ON au.id = m.apartamento_usuario_id
                JOIN apartamentos a ON a.id = au.apartamento_id
                LEFT JOIN tasa_cambio_bcv t ON t.id = m.tasa_cambio_id
                LEFT JOIN pago_mensualidad pm ON pm.mensualidad_id = m.id
                LEFT JOIN pagos p ON p.id = pm.pago_id AND p.estado_comprobante = 'aprobado'
                WHERE au.usuario_id = ?
                GROUP BY m.id, a.bloque, a.numero_apartamento, t.tasa_usd_bs
                ORDER BY m.anio DESC, m.mes DESC";

        return Database::fetchAll($sql, [$usuarioId]);
    }

    /**
     * Obtener nombre del mes
     *
     * @param int $mes Número del mes (1-12)
     * @return string
     */
    public static function getNombreMes(int $mes): string
    {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];

        return $meses[$mes] ?? 'Desconocido';
    }

    /**
     * Hidratar objeto desde array
     *
     * @param array $data Datos
     * @return Mensualidad
     */
    private static function hydrate(array $data): Mensualidad
    {
        $mensualidad = new self();

        foreach ($data as $key => $value) {
            if (property_exists($mensualidad, $key)) {
                $mensualidad->$key = $value;
            }
        }

        return $mensualidad;
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
            'apartamento_usuario_id' => $this->apartamento_usuario_id,
            'mes' => $this->mes,
            'mes_nombre' => self::getNombreMes($this->mes),
            'anio' => $this->anio,
            'cantidad_controles' => $this->cantidad_controles,
            'monto_usd' => $this->monto_usd,
            'monto_bs' => $this->monto_bs,
            'estado' => $this->estado,
            'fecha_vencimiento' => $this->fecha_vencimiento,
            'bloqueado' => $this->bloqueado
        ];
    }

    /**
     * Generar mensualidades futuras para un usuario
     *
     * @param int $usuarioId ID del usuario
     * @param int $mesesAdelante Número de meses a generar (por defecto 3)
     * @return array Mensualidades generadas
     */
    public static function generarMensualidadesFuturas(int $usuarioId, ?int $limiteParam = null): array
    {
        try {
            Database::beginTransaction();

            // Obtener datos del apartamento del usuario
            $sql = "SELECT au.id, au.cantidad_controles, a.bloque, a.numero_apartamento
                    FROM apartamento_usuario au
                    JOIN apartamentos a ON a.id = au.apartamento_id
                    WHERE au.usuario_id = ? AND au.activo = TRUE
                    LIMIT 1";

            $apartamentoUsuario = Database::fetchOne($sql, [$usuarioId]);

            if (!$apartamentoUsuario) {
                throw new Exception("El usuario no tiene un apartamento activo");
            }

            // Obtener última tasa BCV
            $sqlTasa = "SELECT id, tasa_usd_bs FROM tasa_cambio_bcv
                        ORDER BY fecha_registro DESC LIMIT 1";
            $tasa = Database::fetchOne($sqlTasa);

            if (!$tasa) {
                throw new Exception("No hay tasa de cambio BCV registrada");
            }

            // Obtener tarifa vigente
            $sqlTarifa = "SELECT monto_mensual_usd FROM configuracion_tarifas
                          WHERE activo = TRUE
                          AND fecha_vigencia_inicio <= CURRENT_DATE
                          ORDER BY fecha_vigencia_inicio DESC LIMIT 1";
            $tarifa = Database::fetchOne($sqlTarifa);

            if (!$tarifa) {
                throw new Exception("No hay tarifa configurada");
            }

            $mensualidadesGeneradas = [];
            $mesActual = (int)date('n');
            $anioActual = (int)date('Y');

            $anioObjetivo = ($limiteParam && $limiteParam > 2000) ? $limiteParam : ($anioActual + 1);
            $cursor = strtotime(sprintf('%04d-%02d-01', $anioActual, $mesActual));
            $finObjetivo = strtotime(sprintf('%04d-12-01', $anioObjetivo));

            while ($cursor <= $finObjetivo) {
                $mes = (int)date('n', $cursor);
                $anio = (int)date('Y', $cursor);

                // Verificar si ya existe la mensualidad
                $sqlExiste = "SELECT id FROM mensualidades
                             WHERE apartamento_usuario_id = ? AND mes = ? AND anio = ?";
                $existe = Database::fetchOne($sqlExiste, [$apartamentoUsuario['id'], $mes, $anio]);

                if (!$existe) {
                    // Calcular montos multiplicados por cantidad de controles
                    $montoUsd = $tarifa['monto_mensual_usd'] * $apartamentoUsuario['cantidad_controles'];
                    $montoBs = $montoUsd * $tasa['tasa_usd_bs'];

                    // Fecha de vencimiento (último día del mes)
                    $fechaVencimiento = date('Y-m-t', strtotime("$anio-$mes-01"));

                    // Insertar mensualidad
                    $sqlInsert = "INSERT INTO mensualidades
                                  (apartamento_usuario_id, mes, anio, cantidad_controles,
                                   monto_usd, monto_bs, tasa_cambio_id, estado, fecha_vencimiento)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente', ?)";

                    $params = [
                        $apartamentoUsuario['id'],
                        $mes,
                        $anio,
                        $apartamentoUsuario['cantidad_controles'],
                        $montoUsd,
                        $montoBs,
                        $tasa['id'],
                        $fechaVencimiento
                    ];

                    $mensualidadId = Database::execute($sqlInsert, $params);

                    if ($mensualidadId) {
                        // Crear objeto mensualidad para retornar
                        $mensualidad = new self();
                        $mensualidad->id = $mensualidadId;
                        $mensualidad->apartamento_usuario_id = $apartamentoUsuario['id'];
                        $mensualidad->mes = $mes;
                        $mensualidad->anio = $anio;
                        $mensualidad->cantidad_controles = $apartamentoUsuario['cantidad_controles'];
                        $mensualidad->monto_usd = $montoUsd;
                        $mensualidad->monto_bs = $montoBs;
                        $mensualidad->tasa_cambio_id = $tasa['id'];
                        $mensualidad->estado = 'pendiente';
                        $mensualidad->fecha_vencimiento = $fechaVencimiento;
                        $mensualidad->mes_correspondiente = "$anio-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01";
                        $mensualidad->apartamento = $apartamentoUsuario['bloque'] . '-' . $apartamentoUsuario['numero_apartamento'];
                        $mensualidad->tasa_usd_bs = $tasa['tasa_usd_bs'];

                        $mensualidadesGeneradas[] = $mensualidad;
                    }
                }
                $cursor = strtotime('+1 month', $cursor);
            }

            Database::commit();

            writeLog("Generadas " . count($mensualidadesGeneradas) . " mensualidades futuras para usuario ID: $usuarioId", 'info');

            return $mensualidadesGeneradas;

        } catch (Exception $e) {
            Database::rollback();
            writeLog("Error generando mensualidades futuras: " . $e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * Generar mensualidades retroactivas para meses pasados sin pago
     * Se ejecuta cuando un usuario intenta pagar para asegurar que no pueda saltarse meses
     *
     * @param int $usuarioId ID del usuario
     * @return int Número de mensualidades generadas
     */
    public static function generarMensualidadesRetroactivas(int $usuarioId): int
    {
        try {
            Database::beginTransaction();

            // Obtener datos del apartamento y fecha de registro del usuario
            $sql = "SELECT au.id, au.cantidad_controles, a.bloque, a.numero_apartamento, u.fecha_registro
                    FROM apartamento_usuario au
                    JOIN apartamentos a ON a.id = au.apartamento_id
                    JOIN usuarios u ON u.id = au.usuario_id
                    WHERE au.usuario_id = ? AND au.activo = TRUE
                    LIMIT 1";

            $apartamentoUsuario = Database::fetchOne($sql, [$usuarioId]);

            if (!$apartamentoUsuario) {
                throw new Exception("El usuario no tiene un apartamento activo");
            }

            // La fecha límite mínima hacia atrás NUNCA debe ser anterior al mes en que se registró el usuario
            $fechaRegistroUser = $apartamentoUsuario['fecha_registro'] ?? date('Y-m-d');
            $mesReg = (int)date('n', strtotime($fechaRegistroUser));
            $anioReg = (int)date('Y', strtotime($fechaRegistroUser));
            $fechaRegistroLimite = strtotime("$anioReg-$mesReg-01");

            // Obtener la fecha más antigua de mensualidad existente
            $sqlFechaMinima = "SELECT MIN(make_date(anio, mes, 1)) as fecha_minima
                              FROM mensualidades m
                              JOIN apartamento_usuario au ON au.id = m.apartamento_usuario_id
                              WHERE au.usuario_id = ? AND au.activo = TRUE";

            $resultadoFecha = Database::fetchOne($sqlFechaMinima, [$usuarioId]);
            $fechaInicio = $resultadoFecha && $resultadoFecha['fecha_minima']
                         ? $resultadoFecha['fecha_minima']
                         : date('Y-m-01'); // Inicio del año actual si no hay mensualidades

            // Obtener última tasa BCV
            $sqlTasa = "SELECT id, tasa_usd_bs FROM tasa_cambio_bcv
                        ORDER BY fecha_registro DESC LIMIT 1";
            $tasa = Database::fetchOne($sqlTasa);

            if (!$tasa) {
                throw new Exception("No hay tasa de cambio BCV registrada");
            }

            // Obtener tarifa vigente
            $sqlTarifa = "SELECT monto_mensual_usd FROM configuracion_tarifas
                          WHERE activo = TRUE
                          AND fecha_vigencia_inicio <= CURRENT_DATE
                          ORDER BY fecha_vigencia_inicio DESC LIMIT 1";
            $tarifa = Database::fetchOne($sqlTarifa);

            if (!$tarifa) {
                throw new Exception("No hay tarifa configurada");
            }

            $mensualidadesGeneradas = 0;
            $mesActual = (int)date('n');
            $anioActual = (int)date('Y');

            // Generar mensualidades desde la fecha mínima hacia atrás sin superar la fecha de registro del usuario ni 3 meses atrás
            $fechaActual = strtotime($fechaInicio);
            $fechaLimiteCalculada = strtotime('-3 months', strtotime(date('Y-m-01')));
            $fechaLimite = max($fechaLimiteCalculada, $fechaRegistroLimite);

            while ($fechaActual >= $fechaLimite) {
                $mes = (int)date('n', $fechaActual);
                $anio = (int)date('Y', $fechaActual);

                // Verificar si ya existe la mensualidad
                $sqlExiste = "SELECT id FROM mensualidades
                             WHERE apartamento_usuario_id = ? AND mes = ? AND anio = ?";
                $existe = Database::fetchOne($sqlExiste, [$apartamentoUsuario['id'], $mes, $anio]);

                if (!$existe) {
                    // Calcular montos multiplicados por cantidad de controles
                    $montoUsd = $tarifa['monto_mensual_usd'] * $apartamentoUsuario['cantidad_controles'];
                    $montoBs = $montoUsd * $tasa['tasa_usd_bs'];

                    // Fecha de vencimiento (último día del mes)
                    $fechaVencimiento = date('Y-m-t', strtotime("$anio-$mes-01"));

                    // Insertar mensualidad
                    $sqlInsert = "INSERT INTO mensualidades
                                  (apartamento_usuario_id, mes, anio, cantidad_controles,
                                   monto_usd, monto_bs, tasa_cambio_id, estado, fecha_vencimiento)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente', ?)";

                    $params = [
                        $apartamentoUsuario['id'],
                        $mes,
                        $anio,
                        $apartamentoUsuario['cantidad_controles'],
                        $montoUsd,
                        $montoBs,
                        $tasa['id'],
                        $fechaVencimiento
                    ];

                    $mensualidadId = Database::execute($sqlInsert, $params);

                    if ($mensualidadId) {
                        $mensualidadesGeneradas++;
                    }
                }

                // Retroceder un mes
                $fechaActual = strtotime('-1 month', $fechaActual);
            }

            Database::commit();

            if ($mensualidadesGeneradas > 0) {
                writeLog("Generadas $mensualidadesGeneradas mensualidades retroactivas para usuario ID: $usuarioId", 'info');
            }

            return $mensualidadesGeneradas;

        } catch (Exception $e) {
            Database::rollback();
            writeLog("Error generando mensualidades retroactivas: " . $e->getMessage(), 'error');
            return 0;
        }
    }

    /**
     * Cargar deudas históricas / mensualidades anteriores desde un mes/año especificado hasta el mes actual
     *
     * @param int $usuarioId ID del usuario
     * @param int $mesInicio Mes de inicio (1-12)
     * @param int $anioInicio Año de inicio (ej. 2025)
     * @return array ['success' => bool, 'message' => string, 'generadas' => int]
     */
    public static function cargarDeudaHistorica(int $usuarioId, int $mesInicio, int $anioInicio, ?int $mesFin = null, ?int $anioFin = null): array
    {
        try {
            Database::beginTransaction();

            // Obtener apartamento activo del usuario
            $sql = "SELECT au.id as apartamento_usuario_id, au.cantidad_controles, a.bloque, a.numero_apartamento
                    FROM apartamento_usuario au
                    JOIN apartamentos a ON a.id = au.apartamento_id
                    WHERE au.usuario_id = ? AND au.activo = TRUE
                    LIMIT 1";

            $apartamentoUsuario = Database::fetchOne($sql, [$usuarioId]);

            if (!$apartamentoUsuario) {
                throw new Exception("El usuario no tiene un apartamento activo asignado");
            }

            $aptUserId = (int)$apartamentoUsuario['apartamento_usuario_id'];
            $cantidadControles = max(1, (int)$apartamentoUsuario['cantidad_controles']);

            // Obtener última tasa BCV
            $sqlTasa = "SELECT id, tasa_usd_bs FROM tasa_cambio_bcv ORDER BY fecha_registro DESC LIMIT 1";
            $tasa = Database::fetchOne($sqlTasa);

            if (!$tasa) {
                throw new Exception("No hay tasa de cambio BCV registrada en el sistema");
            }

            // Obtener tarifa vigente
            $sqlTarifa = "SELECT monto_mensual_usd FROM configuracion_tarifas
                          WHERE activo = TRUE AND fecha_vigencia_inicio <= CURRENT_DATE
                          ORDER BY fecha_vigencia_inicio DESC LIMIT 1";
            $tarifa = Database::fetchOne($sqlTarifa);
            $tarifaUSD = $tarifa ? (float)$tarifa['monto_mensual_usd'] : 1.00;

            $montoUsd = $tarifaUSD * $cantidadControles;
            $montoBs = round($montoUsd * (float)$tasa['tasa_usd_bs'], 2);

            $fechaInicioTimestamp = strtotime(sprintf('%04d-%02d-01', $anioInicio, $mesInicio));
            
            if ($anioFin && $mesFin && $mesFin >= 1 && $mesFin <= 12) {
                $fechaFinTimestamp = strtotime(sprintf('%04d-%02d-01', $anioFin, $mesFin));
            } else {
                $fechaFinTimestamp = max(strtotime(date('Y-m-01')), $fechaInicioTimestamp);
            }

            if ($fechaInicioTimestamp > $fechaFinTimestamp) {
                $fechaFinTimestamp = $fechaInicioTimestamp;
            }

            // PASO 1: Eliminar mensualidades SIN pagos aprobados en el rango a corregir.
            // Esto permite reemplazar mensualidades erróneas (montos viejos, meses fuera de orden, etc.)
            $mensualidadesEliminadas = 0;
            $cursorTime = $fechaInicioTimestamp;
            while ($cursorTime <= $fechaFinTimestamp) {
                $mes  = (int)date('n', $cursorTime);
                $anio = (int)date('Y', $cursorTime);

                // Solo eliminar si NO tiene pagos aprobados
                $sqlBuscar = "SELECT m.id, m.estado FROM mensualidades m
                              WHERE m.apartamento_usuario_id = ? AND m.anio = ? AND m.mes = ?";
                $mensualidad = Database::fetchOne($sqlBuscar, [$aptUserId, $anio, $mes]);

                if ($mensualidad) {
                    $mId = (int)$mensualidad['id'];
                    $sqlPago = "SELECT 1 FROM pago_mensualidad pm
                                JOIN pagos p ON p.id = pm.pago_id
                                WHERE pm.mensualidad_id = ?
                                  AND p.estado_comprobante IN ('aprobado', 'no_aplica')
                                LIMIT 1";
                    $tienePagoAprobado = Database::fetchOne($sqlPago, [$mId]);

                    if (!$tienePagoAprobado) {
                        Database::execute("DELETE FROM pago_mensualidad WHERE mensualidad_id = ?", [$mId]);
                        Database::execute("DELETE FROM mensualidades WHERE id = ?", [$mId]);
                        $mensualidadesEliminadas++;
                    }
                }

                $cursorTime = strtotime('+1 month', $cursorTime);
            }

            // PASO 2: Recrear todas las mensualidades en el rango (con tarifa y montos actualizados)
            $mensualidadesGeneradas = 0;
            $cursorTime = $fechaInicioTimestamp;

            while ($cursorTime <= $fechaFinTimestamp) {
                $mes  = (int)date('n', $cursorTime);
                $anio = (int)date('Y', $cursorTime);

                // Verificar si ya existe (protégida porque tenía pago aprobado)
                $sqlExiste = "SELECT id FROM mensualidades
                              WHERE apartamento_usuario_id = ? AND anio = ? AND mes = ?";
                $existe = Database::fetchOne($sqlExiste, [$aptUserId, $anio, $mes]);

                if (!$existe) {
                    // Fecha vencimiento: día 5 del mes correspondiente
                    $fechaVencimiento = date('Y-m-05', $cursorTime);

                    $sqlInsert = "INSERT INTO mensualidades (
                                    apartamento_usuario_id, mes, anio, cantidad_controles,
                                    monto_usd, monto_bs, tasa_cambio_id, estado, fecha_vencimiento
                                  ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente', ?)";

                    $params = [
                        $aptUserId,
                        $mes,
                        $anio,
                        $cantidadControles,
                        $montoUsd,
                        $montoBs,
                        $tasa['id'],
                        $fechaVencimiento
                    ];

                    Database::execute($sqlInsert, $params);
                    $mensualidadesGeneradas++;
                }

                // Avanzar un mes
                $cursorTime = strtotime('+1 month', $cursorTime);
            }

            // Marcar vencidas y verificar bloqueos
            self::marcarVencidas();
            self::verificarBloqueos();

            Database::commit();

            $detalleMsg = "Se generaron $mensualidadesGeneradas mensualidades";
            if ($mensualidadesEliminadas > 0) {
                $detalleMsg .= " (se reemplazaron $mensualidadesEliminadas con montos actualizados)";
            }
            $detalleMsg .= " desde $mesInicio/$anioInicio hasta el mes actual.";

            writeLog("Cargadas/reemplazadas mensualidades históricas para usuario ID: $usuarioId desde $mesInicio/$anioInicio. Eliminadas: $mensualidadesEliminadas, Generadas: $mensualidadesGeneradas", 'info');

            return [
                'success'   => true,
                'message'   => $detalleMsg,
                'generadas' => $mensualidadesGeneradas,
                'eliminadas' => $mensualidadesEliminadas
            ];

        } catch (Exception $e) {
            Database::rollback();
            writeLog("Error al cargar deuda histórica para usuario ID $usuarioId: " . $e->getMessage(), 'error');
            return [
                'success'   => false,
                'message'   => $e->getMessage(),
                'generadas' => 0,
                'eliminadas' => 0
            ];
        }
    }

    /**
     * Revertir / Eliminar mensualidades históricas sin pagos aprobados para un usuario
     */
    public static function revertirDeudaHistorica(int $usuarioId, int $mesInicio, int $anioInicio, ?int $mesFin = null, ?int $anioFin = null): array
    {
        try {
            Database::beginTransaction();

            // Buscar apartamento asignado activo
            $sqlApt = "SELECT au.id as apartamento_usuario_id 
                       FROM apartamento_usuario au
                       WHERE au.usuario_id = ? AND au.activo = TRUE 
                       LIMIT 1";
            $apartamentoUsuario = Database::fetchOne($sqlApt, [$usuarioId]);

            if (!$apartamentoUsuario) {
                throw new Exception("El usuario no tiene un apartamento asignado activo");
            }

            $aptUserId = (int)$apartamentoUsuario['apartamento_usuario_id'];

            $fechaInicioTimestamp = strtotime(sprintf('%04d-%02d-01', $anioInicio, $mesInicio));
            
            if ($mesFin && $anioFin) {
                $fechaFinTimestamp = strtotime(sprintf('%04d-%02d-01', $anioFin, $mesFin));
            } else {
                $fechaFinTimestamp = strtotime(date('Y-m-01'));
            }

            if ($fechaInicioTimestamp > $fechaFinTimestamp) {
                throw new Exception("El mes de inicio no puede ser posterior al mes de fin");
            }

            $mensualidadesEliminadas = 0;
            $mensualidadesProtegidas = 0;
            $cursorTime = $fechaInicioTimestamp;

            while ($cursorTime <= $fechaFinTimestamp) {
                $mes = (int)date('n', $cursorTime);
                $anio = (int)date('Y', $cursorTime);

                // Buscar mensualidad
                $sqlBuscar = "SELECT m.id, m.estado FROM mensualidades m
                              WHERE m.apartamento_usuario_id = ? AND m.anio = ? AND m.mes = ?";
                $mensualidad = Database::fetchOne($sqlBuscar, [$aptUserId, $anio, $mes]);

                if ($mensualidad) {
                    $mId = (int)$mensualidad['id'];

                    // Verificar si tiene pagos aprobados o si está pagada
                    $sqlPago = "SELECT 1 FROM pago_mensualidad pm
                                JOIN pagos p ON p.id = pm.pago_id
                                WHERE pm.mensualidad_id = ?
                                  AND p.estado_comprobante IN ('aprobado', 'no_aplica')
                                LIMIT 1";
                    $tienePagoAprobado = Database::fetchOne($sqlPago, [$mId]);

                    if ($mensualidad['estado'] === 'pagada' || $mensualidad['estado'] === 'pagado' || $tienePagoAprobado) {
                        $mensualidadesProtegidas++;
                    } else {
                        // Eliminar referencias pendientes en pago_mensualidad
                        Database::execute("DELETE FROM pago_mensualidad WHERE mensualidad_id = ?", [$mId]);
                        // Eliminar mensualidad
                        Database::execute("DELETE FROM mensualidades WHERE id = ?", [$mId]);
                        $mensualidadesEliminadas++;
                    }
                }

                $cursorTime = strtotime('+1 month', $cursorTime);
            }

            // Recalcular morosidad y desbloquear si corresponde
            self::recalcularMorosidadYDesbloquear($aptUserId);

            Database::commit();

            writeLog("Revertidas $mensualidadesEliminadas mensualidades para usuario ID: $usuarioId desde $mesInicio/$anioInicio (Protegidas: $mensualidadesProtegidas)", 'info');

            $msg = "Se eliminaron $mensualidadesEliminadas mensualidades no pagadas.";
            if ($mensualidadesProtegidas > 0) {
                $msg .= " Se conservaron $mensualidadesProtegidas mensualidades por contar con pagos registrados.";
            }

            return [
                'success' => true,
                'message' => $msg,
                'eliminadas' => $mensualidadesEliminadas,
                'protegidas' => $mensualidadesProtegidas
            ];

        } catch (Exception $e) {
            Database::rollback();
            writeLog("Error al revertir deuda histórica para usuario ID $usuarioId: " . $e->getMessage(), 'error');
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'eliminadas' => 0
            ];
        }
    }

    /**
     * Recalcular la morosidad de un usuario y reactivar sus controles si ya no cumple la regla de bloqueo
     */
    public static function recalcularMorosidadYDesbloquear(int $aptUserId): void
    {
        $sqlConteo = "SELECT COUNT(id) as total_vencidas
                      FROM mensualidades
                      WHERE apartamento_usuario_id = ?
                        AND estado IN ('vencido', 'vencida')";
        $res = Database::fetchOne($sqlConteo, [$aptUserId]);
        $totalVencidas = (int)($res['total_vencidas'] ?? 0);

        if ($totalVencidas < MESES_BLOQUEO) {
            Database::execute("UPDATE mensualidades SET bloqueado = FALSE WHERE apartamento_usuario_id = ?", [$aptUserId]);

            $sqlReactivar = "UPDATE controles_estacionamiento
                             SET estado = 'activo',
                                 motivo_estado = NULL,
                                 fecha_estado = NOW()
                             WHERE apartamento_usuario_id = ?
                               AND estado = 'bloqueado'
                               AND motivo_estado LIKE '%morosidad%'";
            Database::execute($sqlReactivar, [$aptUserId]);
        }
    }
}
