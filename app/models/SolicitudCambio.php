<?php
/**
 * Modelo SolicitudCambio
 *
 * Maneja operaciones CRUD y lógica de negocio para solicitudes de cambios
 * Incluye: registro de nuevos usuarios, cambio de cantidad de controles, etc.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/Usuario.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';

class SolicitudCambio
{
    // Propiedades
    public $id;
    public $apartamento_usuario_id;
    public $tipo_solicitud;
    public $cantidad_controles_nueva;
    public $control_id;
    public $motivo;
    public $datos_nuevo_usuario;
    public $estado;
    public $fecha_solicitud;
    public $aprobado_por;
    public $fecha_respuesta;
    public $observaciones;

    /**
     * Crear nueva solicitud
     *
     * @param array $data Datos de la solicitud
     * @return int ID de la solicitud creada
     */
    public static function create(array $data): int
    {
        $sql = "INSERT INTO solicitudes_cambios (
            apartamento_usuario_id,
            tipo_solicitud,
            cantidad_controles_nueva,
            control_id,
            motivo,
            datos_nuevo_usuario,
            estado,
            fecha_solicitud
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

        $params = [
            $data['apartamento_usuario_id'] ?? null,
            $data['tipo_solicitud'],
            $data['cantidad_controles_nueva'] ?? null,
            $data['control_id'] ?? null,
            $data['motivo'] ?? '',
            isset($data['datos_nuevo_usuario']) ? json_encode($data['datos_nuevo_usuario']) : null,
            $data['estado'] ?? 'pendiente'
        ];

        $id = Database::insert($sql, $params);

        writeLog("Solicitud creada: ID $id, Tipo: {$data['tipo_solicitud']}", 'info');

        return $id;
    }

    /**
     * Buscar solicitud por ID
     *
     * @param int $id ID de la solicitud
     * @return SolicitudCambio|null
     */
    public static function findById(int $id): ?SolicitudCambio
    {
        $sql = "SELECT * FROM solicitudes_cambios WHERE id = ?";
        $result = Database::fetchOne($sql, [$id]);

        return $result ? self::hydrate($result) : null;
    }

    /**
     * Obtener solicitudes pendientes
     *
     * @param string|null $tipo Filtrar por tipo de solicitud
     * @return array
     */
    public static function getPendientes(?string $tipo = null): array
    {
        $sql = "SELECT * FROM solicitudes_cambios WHERE estado = 'pendiente'";
        $params = [];

        if ($tipo) {
            $sql .= " AND tipo_solicitud = ?";
            $params[] = $tipo;
        }

        $sql .= " ORDER BY fecha_solicitud DESC";

        $results = Database::fetchAll($sql, $params);

        return array_map(fn($row) => self::hydrate($row), $results);
    }

    /**
     * Obtener solo solicitudes de registro de nuevos usuarios
     *
     * @param string $estado Estado de las solicitudes (pendiente, aprobada, rechazada)
     * @return array
     */
    public static function getSolicitudesRegistro(string $estado = 'pendiente'): array
    {
        $sql = "SELECT * FROM solicitudes_cambios 
                WHERE tipo_solicitud = 'registro_nuevo_usuario'
                AND estado = ?
                ORDER BY fecha_solicitud DESC";

        $results = Database::fetchAll($sql, [$estado]);

        return array_map(fn($row) => self::hydrate($row), $results);
    }

    /**
     * Aprobar solicitud
     *
     * @param int $aprobadoPor ID del usuario que aprueba
     * @param string|null $observaciones Observaciones opcionales
     * @return bool
     */
    public function aprobar(int $aprobadoPor, ?string $observaciones = null): bool
    {
        try {
            Database::beginTransaction();

            // Lógica específica según el tipo de solicitud
            $resultadoAccion = true;

            switch ($this->tipo_solicitud) {
                case 'registro_nuevo_usuario':
                    $resultadoAccion = $this->crearUsuarioDesdeRegistro($aprobadoPor);
                    break;

                case 'cambio_cantidad_controles':
                    $resultadoAccion = $this->procesarCambioCantidad();
                    break;

                case 'suspension_control':
                    $resultadoAccion = $this->procesarSuspension($aprobadoPor);
                    break;

                case 'desactivacion_control':
                    $resultadoAccion = $this->procesarDesactivacion($aprobadoPor);
                    break;

                case 'desincorporar_control':
                    $resultadoAccion = $this->procesarDesincorporacion($aprobadoPor);
                    break;

                case 'reportar_perdido':
                    $resultadoAccion = $this->procesarReportePerdido($aprobadoPor);
                    break;

                case 'cambio_estado_control':
                    // Para solicitudes de cambio de estado, solo marcar como aprobada
                    // El administrador decidirá qué acción tomar
                    $resultadoAccion = true;
                    break;

                case 'agregar_control':
                case 'comprar_control':
                    // Para estos casos, asumimos que se aprueba el aumento de la cuota
                    // El operador deberá asignar el control físico manualmente después
                    // O podríamos automatizarlo si tuviéramos lógica de asignación automática
                    // Por ahora, solo aumentamos la cantidad permitida si no se especificó control
                    if (!$this->control_id) {
                        $resultadoAccion = $this->incrementarCantidadControles();
                    }
                    break;
            }

            if (!$resultadoAccion) {
                Database::rollback();
                return false;
            }

            $sql = "UPDATE solicitudes_cambios 
                    SET estado = 'aprobada',
                        aprobado_por = ?,
                        fecha_respuesta = NOW(),
                        observaciones = ?
                    WHERE id = ?";

            $result = Database::execute($sql, [$aprobadoPor, $observaciones, $this->id]) > 0;

            if ($result) {
                Database::commit();
                writeLog("Solicitud aprobada y procesada: ID {$this->id}, Tipo: {$this->tipo_solicitud}", 'info');
                $this->estado = 'aprobada';
                $this->aprobado_por = $aprobadoPor;
                $this->fecha_respuesta = date('Y-m-d H:i:s');
                $this->observaciones = $observaciones;
            } else {
                Database::rollback();
            }

            return $result;

        } catch (Exception $e) {
            Database::rollback();
            writeLog("Error al aprobar solicitud ID {$this->id}: " . $e->getMessage(), 'error');
            return false;
        }
    }

    // Métodos privados para procesar acciones

    private function procesarCambioCantidad(): bool
    {
        if (!$this->cantidad_controles_nueva) return false;

        $sql = "UPDATE apartamento_usuario SET cantidad_controles = ? WHERE id = ?";
        return Database::execute($sql, [$this->cantidad_controles_nueva, $this->apartamento_usuario_id]) > 0;
    }

    private function incrementarCantidadControles(): bool
    {
        $sql = "UPDATE apartamento_usuario SET cantidad_controles = cantidad_controles + 1 WHERE id = ?";
        return Database::execute($sql, [$this->apartamento_usuario_id]) > 0;
    }

    private function procesarSuspension(int $aprobadoPor): bool
    {
        if (!$this->control_id) return false;
        
        require_once __DIR__ . '/Control.php';
        $control = Control::findById($this->control_id);
        
        if (!$control) return false;
        
        return $control->cambiarEstado('suspendido', $this->motivo ?? 'Solicitud de suspensión aprobada', $aprobadoPor);
    }

    private function procesarDesactivacion(int $aprobadoPor): bool
    {
        if (!$this->control_id) return false;
        
        require_once __DIR__ . '/Control.php';
        $control = Control::findById($this->control_id);
        
        if (!$control) return false;
        
        return $control->cambiarEstado('desactivado', $this->motivo ?? 'Solicitud de desactivación aprobada', $aprobadoPor);
    }

    private function procesarDesincorporacion(int $aprobadoPor): bool
    {
        if (!$this->control_id) return false;
        
        require_once __DIR__ . '/Control.php';
        $control = Control::findById($this->control_id);
        
        if (!$control) return false;
        
        // Desasignar el control (vuelve a estar vacío/disponible)
        return $control->desasignar($this->motivo ?? 'Solicitud de desincorporación aprobada', $aprobadoPor);
    }

    private function procesarReportePerdido(int $aprobadoPor): bool
    {
        if (!$this->control_id) return false;
        
        require_once __DIR__ . '/Control.php';
        $control = Control::findById($this->control_id);
        
        if (!$control) return false;
        
        return $control->cambiarEstado('perdido', $this->motivo ?? 'Reporte de pérdida aprobado', $aprobadoPor);
    }

    /**
     * Rechazar solicitud
     *
     * @param int $rechazadoPor ID del usuario que rechaza
     * @param string $motivo Motivo del rechazo
     * @return bool
     */
    public function rechazar(int $rechazadoPor, string $motivo): bool
    {
        $sql = "UPDATE solicitudes_cambios 
                SET estado = 'rechazada',
                    aprobado_por = ?,
                    fecha_respuesta = NOW(),
                    observaciones = ?
                WHERE id = ?";

        $result = Database::execute($sql, [$rechazadoPor, $motivo, $this->id]) > 0;

        if ($result) {
            writeLog("Solicitud rechazada: ID {$this->id}, Motivo: $motivo", 'info');
            $this->estado = 'rechazada';
            $this->aprobado_por = $rechazadoPor;
            $this->fecha_respuesta = date('Y-m-d H:i:s');
            $this->observaciones = $motivo;
        }

        return $result;
    }

    /**
     * Crear usuario desde solicitud de registro aprobada
     *
     * @param int $aprobadoPor ID del usuario que aprueba
     * @return int|null ID del usuario creado o null si falla
     */
    private function crearUsuarioDesdeRegistro(int $aprobadoPor): ?int
    {
        if ($this->tipo_solicitud !== 'registro_nuevo_usuario' || !$this->datos_nuevo_usuario) {
            writeLog("Error: No se puede crear usuario, tipo de solicitud inválido", 'error');
            return null;
        }

        $datos = json_decode($this->datos_nuevo_usuario, true);

        try {
            // 1. Crear usuario
            $usuarioId = Usuario::create([
                'nombre_completo' => $datos['nombre_completo'],
                'email' => $datos['email'],
                'password' => $datos['password'], // Ya viene hasheado (contraseña temporal)
                'telefono' => $datos['telefono'] ?? null,
                'rol' => 'cliente',
                'activo' => true,
                'primer_acceso' => true,
                'password_temporal' => true, // IMPORTANTE: Es contraseña temporal (123456)
                'perfil_completo' => true,
                'is_hashed' => true // IMPORTANTE: Evitar doble hash
            ]);

            // 2. Buscar apartamento
            $sqlApto = "SELECT id FROM apartamentos
                        WHERE bloque = ? AND escalera = ? AND piso = ? AND numero_apartamento = ?";
            $apartamento = Database::fetchOne($sqlApto, [
                $datos['bloque'],
                $datos['escalera'],
                $datos['piso'],
                $datos['apartamento']
            ]);

            if (!$apartamento) {
                throw new Exception("Apartamento no encontrado");
            }

            // 3. Crear relación apartamento-usuario
            $sqlRelacion = "INSERT INTO apartamento_usuario (apartamento_id, usuario_id, cantidad_controles, activo)
                            VALUES (?, ?, ?, TRUE)";
            $apartamentoUsuarioId = Database::insert($sqlRelacion, [
                $apartamento['id'],
                $usuarioId,
                $datos['cantidad_controles']
            ]);

            // 4. Asignar controles automáticamente según cantidad_controles
            if ($datos['cantidad_controles'] > 0) {
                require_once __DIR__ . '/Control.php';
                $controlesVacios = Control::getVacios();

                $asignados = 0;
                foreach ($controlesVacios as $controlData) {
                    if ($asignados >= $datos['cantidad_controles']) {
                        break;
                    }

                    $control = Control::findById($controlData['id']);
                    if ($control && $control->asignar($apartamentoUsuarioId, $aprobadoPor)) {
                        $asignados++;
                    }
                }

                writeLog("Controles asignados automáticamente: $asignados de {$datos['cantidad_controles']} para usuario ID $usuarioId", 'info');
            }

            writeLog("Usuario creado desde solicitud de registro: ID $usuarioId, Email: {$datos['email']}", 'info');

            return $usuarioId;

        } catch (Exception $e) {
            // No hacemos rollback aquí porque la transacción la maneja el método aprobar()
            writeLog("Error creando usuario desde solicitud: " . $e->getMessage(), 'error');
            throw $e; // Re-lanzar excepción para que aprobar() haga rollback
        }
    }

    /**
     * Crear usuario desde solicitud de registro con asignación manual de controles
     *
     * @param int $aprobadoPor ID del usuario que aprueba
     * @param array $datosAsignacion Datos de asignación (cantidad_controles, controles[], bloque, escalera, apartamento, piso)
     * @return int|null ID del usuario creado o null si falla
     */
    public function crearUsuarioConAsignacionManual(int $aprobadoPor, array $datosAsignacion): ?int
    {
        if ($this->tipo_solicitud !== 'registro_nuevo_usuario' || !$this->datos_nuevo_usuario) {
            writeLog("Error: No se puede crear usuario, tipo de solicitud inválido", 'error');
            return null;
        }

        $datosOriginales = json_decode($this->datos_nuevo_usuario, true);

        try {
            Database::beginTransaction();

            // 1. Crear usuario
            $usuarioId = Usuario::create([
                'nombre_completo' => $datosOriginales['nombre_completo'],
                'email' => $datosOriginales['email'],
                'password' => $datosOriginales['password'], // Ya viene hasheado (contraseña temporal)
                'telefono' => $datosOriginales['telefono'] ?? null,
                'rol' => 'cliente',
                'activo' => true,
                'primer_acceso' => true,
                'password_temporal' => true, // IMPORTANTE: Es contraseña temporal (123456)
                'perfil_completo' => true,
                'is_hashed' => true // IMPORTANTE: Evitar doble hash
            ]);

            // 2. Buscar apartamento (usar datos modificados si se proporcionaron)
            $bloque = $datosAsignacion['bloque'] ?? $datosOriginales['bloque'];
            $escalera = $datosAsignacion['escalera'] ?? $datosOriginales['escalera'];
            $piso = $datosAsignacion['piso'] ?? $datosOriginales['piso'];
            $apartamentoNum = $datosAsignacion['apartamento'] ?? $datosOriginales['apartamento'];

            $sqlApto = "SELECT id FROM apartamentos
                        WHERE bloque = ? AND escalera = ? AND piso = ? AND numero_apartamento = ?";
            $apartamento = Database::fetchOne($sqlApto, [$bloque, $escalera, $piso, $apartamentoNum]);

            if (!$apartamento) {
                throw new Exception("Apartamento no encontrado: $bloque-$escalera-$piso-$apartamentoNum");
            }

            // 3. Crear relación apartamento-usuario
            $cantidadControles = intval($datosAsignacion['cantidad_controles'] ?? $datosOriginales['cantidad_controles']);
            $sqlRelacion = "INSERT INTO apartamento_usuario (apartamento_id, usuario_id, cantidad_controles, activo)
                            VALUES (?, ?, ?, TRUE)";
            $apartamentoUsuarioId = Database::insert($sqlRelacion, [
                $apartamento['id'],
                $usuarioId,
                $cantidadControles
            ]);

            // 4. Asignar controles específicos seleccionados
            if (!empty($datosAsignacion['controles']) && is_array($datosAsignacion['controles'])) {
                require_once __DIR__ . '/Control.php';

                $asignados = 0;
                foreach ($datosAsignacion['controles'] as $controlId) {
                    $control = Control::findById(intval($controlId));
                    if ($control && $control->asignar($apartamentoUsuarioId, $aprobadoPor)) {
                        $asignados++;
                    }
                }

                writeLog("Controles asignados manualmente: $asignados de " . count($datosAsignacion['controles']) . " para usuario ID $usuarioId", 'info');
            }

            // 5. Actualizar estado de la solicitud
            $sql = "UPDATE solicitudes_cambios
                    SET estado = 'aprobada',
                        aprobado_por = ?,
                        fecha_respuesta = NOW(),
                        observaciones = ?
                    WHERE id = ?";

            $result = Database::execute($sql, [$aprobadoPor, 'Usuario creado y controles asignados manualmente', $this->id]) > 0;

            if ($result) {
                Database::commit();
                writeLog("Usuario creado con asignación manual de controles: ID $usuarioId, Email: {$datosOriginales['email']}", 'info');
                $this->estado = 'aprobada';
                $this->aprobado_por = $aprobadoPor;
                $this->fecha_respuesta = date('Y-m-d H:i:s');
                return $usuarioId;
            } else {
                Database::rollback();
                return null;
            }

        } catch (Exception $e) {
            Database::rollback();
            writeLog("Error creando usuario con asignación manual: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Hidratar objeto desde array
     *
     * @param array $data Datos de la solicitud
     * @return SolicitudCambio
     */
    private static function hydrate(array $data): SolicitudCambio
    {
        $solicitud = new self();

        foreach ($data as $key => $value) {
            if (property_exists($solicitud, $key)) {
                $solicitud->$key = $value;
            }
        }

        return $solicitud;
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
            'tipo_solicitud' => $this->tipo_solicitud,
            'cantidad_controles_nueva' => $this->cantidad_controles_nueva,
            'control_id' => $this->control_id,
            'motivo' => $this->motivo,
            'datos_nuevo_usuario' => $this->datos_nuevo_usuario,
            'estado' => $this->estado,
            'fecha_solicitud' => $this->fecha_solicitud,
            'aprobado_por' => $this->aprobado_por,
            'fecha_respuesta' => $this->fecha_respuesta,
            'observaciones' => $this->observaciones
        ];
    }

    /**
     * Obtener datos del nuevo usuario decodificados
     *
     * @return array|null
     */
    public function getDatosNuevoUsuario(): ?array
    {
        if (!$this->datos_nuevo_usuario) {
            return null;
        }

        return json_decode($this->datos_nuevo_usuario, true);
    }
}
