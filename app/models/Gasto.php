<?php
/**
 * Modelo Gasto
 *
 * Maneja el registro y consulta de gastos operativos de la junta administrativa
 */

require_once __DIR__ . '/../../config/database.php';

class Gasto
{
    public $id;
    public $nombre;
    public $descripcion;
    public $monto;
    public $moneda;
    public $metodo_pago;
    public $fecha_gasto;
    public $comprobante_ruta;
    public $recibo_ruta;
    public $registrado_por;
    public $fecha_registro;
    public $registrado_por_nombre; // campo extra

    /**
     * Registrar un nuevo gasto
     */
    public static function registrar(array $data): int
    {
        $sql = "INSERT INTO public.gastos (
                    nombre, descripcion, monto, moneda, metodo_pago,
                    fecha_gasto, comprobante_ruta, recibo_ruta, registrado_por
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['nombre'],
            $data['descripcion'] ?? null,
            $data['monto'],
            $data['moneda'],
            $data['metodo_pago'],
            $data['fecha_gasto'],
            $data['comprobante_ruta'] ?? null,
            $data['recibo_ruta'] ?? null,
            $data['registrado_por']
        ];

        return intval(Database::insert($sql, $params));
    }

    /**
     * Buscar gasto por ID
     */
    public static function findById(int $id): ?Gasto
    {
        $sql = "SELECT g.*, u.nombre_completo as registrado_por_nombre
                FROM public.gastos g
                JOIN public.usuarios u ON u.id = g.registrado_por
                WHERE g.id = ?";
        
        $result = Database::fetchOne($sql, [$id]);
        return $result ? self::hydrate($result) : null;
    }

    /**
     * Obtener gastos con filtros
     */
    public static function getAllConFiltros(array $filtros = []): array
    {
        $sql = "SELECT g.*, u.nombre_completo as registrado_por_nombre
                FROM public.gastos g
                JOIN public.usuarios u ON u.id = g.registrado_por
                WHERE 1=1";
        
        $params = [];

        if (!empty($filtros['mes']) && !empty($filtros['anio'])) {
            $sql .= " AND EXTRACT(MONTH FROM g.fecha_gasto) = ? AND EXTRACT(YEAR FROM g.fecha_gasto) = ?";
            $params[] = intval($filtros['mes']);
            $params[] = intval($filtros['anio']);
        } elseif (!empty($filtros['anio'])) {
            $sql .= " AND EXTRACT(YEAR FROM g.fecha_gasto) = ?";
            $params[] = intval($filtros['anio']);
        }

        if (!empty($filtros['moneda'])) {
            $sql .= " AND g.moneda = ?";
            $params[] = $filtros['moneda'];
        }

        if (!empty($filtros['metodo_pago'])) {
            $sql .= " AND g.metodo_pago = ?";
            $params[] = $filtros['metodo_pago'];
        }

        if (!empty($filtros['buscar'])) {
            $sql .= " AND (g.nombre ILIKE ? OR g.descripcion ILIKE ?)";
            $params[] = "%{$filtros['buscar']}%";
            $params[] = "%{$filtros['buscar']}%";
        }

        if (!empty($filtros['fecha_inicio']) && !empty($filtros['fecha_fin'])) {
            $sql .= " AND g.fecha_gasto BETWEEN ? AND ?";
            $params[] = $filtros['fecha_inicio'];
            $params[] = $filtros['fecha_fin'];
        }

        $sql .= " ORDER BY g.fecha_gasto DESC, g.id DESC";

        $results = Database::fetchAll($sql, $params);
        return array_map(fn($row) => self::hydrate($row), $results);
    }

    /**
     * Obtener el resumen financiero en un rango de fechas (Ingresos vs Egresos)
     */
    public static function getResumenFinanciero(string $fechaInicio, string $fechaFin): array
    {
        // Totales de gastos en el rango
        $sqlGastos = "SELECT 
                        SUM(CASE WHEN moneda = 'USD' THEN monto ELSE 0 END) as total_usd,
                        SUM(CASE WHEN moneda = 'Bs' THEN monto ELSE 0 END) as total_bs
                      FROM public.gastos
                      WHERE fecha_gasto BETWEEN ? AND ?";
        
        $resGastos = Database::fetchOne($sqlGastos, [$fechaInicio, $fechaFin]);
        
        // Totales de ingresos en el rango (pagos aprobados)
        $sqlIngresos = "SELECT 
                            SUM(CASE WHEN moneda_pago = 'usd_efectivo' THEN monto_usd ELSE 0 END) as total_usd_efectivo,
                            SUM(CASE WHEN moneda_pago IN ('bs_transferencia', 'bs_pago_movil', 'bs_efectivo') THEN monto_bs ELSE 0 END) as total_bs_digital,
                            SUM(monto_usd) as total_usd_equiv,
                            SUM(monto_bs) as total_bs_equiv
                         FROM public.pagos
                         WHERE estado_comprobante IN ('aprobado', 'no_aplica')
                           AND DATE(fecha_pago) BETWEEN ? AND ?";
        
        $resIngresos = Database::fetchOne($sqlIngresos, [$fechaInicio, $fechaFin]);

        return [
            'egresos' => [
                'USD' => floatval($resGastos['total_usd'] ?? 0),
                'Bs' => floatval($resGastos['total_bs'] ?? 0)
            ],
            'ingresos' => [
                'USD_efectivo' => floatval($resIngresos['total_usd_efectivo'] ?? 0),
                'Bs_digital' => floatval($resIngresos['total_bs_digital'] ?? 0),
                'USD_equiv' => floatval($resIngresos['total_usd_equiv'] ?? 0),
                'Bs_equiv' => floatval($resIngresos['total_bs_equiv'] ?? 0)
            ]
        ];
    }

    /**
     * Hidratar objeto
     */
    private static function hydrate(array $data): Gasto
    {
        $gasto = new self();
        foreach ($data as $key => $value) {
            if (property_exists($gasto, $key)) {
                $gasto->$key = $value;
            }
        }
        return $gasto;
    }
}
