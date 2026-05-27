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
                   AND (
                       m.estado IN ('pendiente', 'vencido') OR
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
    public static function getMensualidadesParaPagoAdelantado(int $usuarioId, int $mesesAdelante = 6): array
    {
        // Generar mensualidades retroactivas para meses pasados sin pago si es necesario
        self::generarMensualidadesRetroactivas($usuarioId);

        // Primero generar mensualidades futuras si no existen
        self::generarMensualidadesFuturas($usuarioId, $mesesAdelante);

        // Obtener todas las mensualidades del usuario ordenadas por fecha de vencimiento
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
                     ORDER BY m.fecha_vencimiento ASC";

        $todasMensualidades = Database::fetchAll($sqlTodas, [$usuarioId]);

        // Encontrar el índice de la primera mensualidad sin pago aprobado
        $indicePrimeraPendiente = -1;
        foreach ($todasMensualidades as $index => $mensualidad) {
            // Verificar si tiene pago aprobado
            $sqlPago = "SELECT COUNT(DISTINCT p.id) as tiene_pago
                        FROM pago_mensualidad pm
                        JOIN pagos p ON p.id = pm.pago_id
                        WHERE pm.mensualidad_id = ?
                          AND p.estado_comprobante IN ('aprobado', 'no_aplica')";

            $resultadoPago = Database::fetchOne($sqlPago, [$mensualidad['id']]);
            $tienePago = $resultadoPago && $resultadoPago['tiene_pago'] > 0;

            // Si no tiene pago aprobado, marcar este índice como el inicio
            if (!$tienePago) {
                $indicePrimeraPendiente = $index;
                break;
            }
        }

        // Si no hay mensualidades pendientes, devolver array vacío
        if ($indicePrimeraPendiente === -1) {
            return [];
        }

        // Devolver las mensualidades consecutivas desde la primera pendiente, limitado al máximo especificado
        $mensualidadesConsecutivas = array_slice($todasMensualidades, $indicePrimeraPendiente, $mesesAdelante);

        return array_map(fn($row) => self::hydrate($row), $mensualidadesConsecutivas);
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
                 GROUP BY u.id
                 HAVING meses_pendientes >= ?
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

            Database::commit();

            // Log
            writeLog("Mensualidades generadas para $mes/$anio: $resultado registros", 'info');

            return $resultado;

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
                SET estado = 'vencida'
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
                    WHERE m.estado = 'vencida'
                      AND au.activo = TRUE
                      AND u.activo = TRUE
                      AND u.exonerado = FALSE
                    GROUP BY au.id
                    HAVING meses_mora >= ?";

            $morosos = Database::fetchAll($sql, [MESES_BLOQUEO]);

            $bloqueados = 0;

            foreach ($morosos as $moroso) {
                // Marcar mensualidades como bloqueadas
                $sqlUpdate = "UPDATE mensualidades
                              SET bloqueado = TRUE
                              WHERE apartamento_usuario_id = ?
                                AND estado = 'vencida'";
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
                GROUP BY m.id
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
    public static function generarMensualidadesFuturas(int $usuarioId, int $mesesAdelante = 3): array
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

            // Generar mensualidades para los próximos meses
            for ($i = 1; $i <= $mesesAdelante; $i++) {
                $mes = $mesActual + $i;
                $anio = $anioActual;

                if ($mes > 12) {
                    $mes = $mes - 12;
                    $anio++;
                }

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

            // Obtener la fecha más antigua de mensualidad existente
            $sqlFechaMinima = "SELECT MIN(make_date(anio, mes, 1)) as fecha_minima
                              FROM mensualidades m
                              JOIN apartamento_usuario au ON au.id = m.apartamento_usuario_id
                              WHERE au.usuario_id = ? AND au.activo = TRUE";

            $resultadoFecha = Database::fetchOne($sqlFechaMinima, [$usuarioId]);
            $fechaInicio = $resultadoFecha && $resultadoFecha['fecha_minima']
                         ? $resultadoFecha['fecha_minima']
                         : date('Y-01-01'); // Inicio del año actual si no hay mensualidades

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

            // Generar mensualidades desde la fecha mínima hacia atrás hasta 3 meses atrás del mes actual
            $fechaActual = strtotime($fechaInicio);
            $fechaLimite = strtotime('-3 months', strtotime(date('Y-m-01'))); // No generar antes de 3 meses atrás

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
}
