-- ============================================================================
-- BASE DE DATOS: Sistema de Control de Pagos - Estacionamiento Bloques 27-32
-- Versión: 1.0.0
-- Fecha: 2025-11-04
-- Descripción: Schema completo con 12 tablas para gestión de pagos mensuales
-- ============================================================================

DROP DATABASE IF EXISTS estacionamiento_db;
CREATE DATABASE estacionamiento_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE estacionamiento_db;

-- ============================================================================
-- TABLA: usuarios
-- Descripción: Almacena todos los usuarios del sistema (4 roles)
-- ============================================================================
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre_completo VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL COMMENT 'Hash BCRYPT',
    telefono VARCHAR(20),
    rol ENUM('cliente', 'operador', 'consultor', 'administrador') NOT NULL DEFAULT 'cliente',
    activo BOOLEAN DEFAULT TRUE,

    -- Control de acceso y seguridad
    intentos_fallidos INT DEFAULT 0 COMMENT 'Máximo 5 intentos',
    bloqueado_hasta DATETIME NULL COMMENT 'Bloqueo temporal por intentos fallidos',

    -- Flags para primer acceso (User Story #2)
    primer_acceso BOOLEAN DEFAULT TRUE COMMENT 'TRUE si es primera vez que ingresa',
    password_temporal BOOLEAN DEFAULT TRUE COMMENT 'TRUE si password fue generado por admin',
    perfil_completo BOOLEAN DEFAULT FALSE COMMENT 'TRUE cuando completa datos personales',

    -- Exoneraciones
    exonerado BOOLEAN DEFAULT FALSE COMMENT 'Usuarios exonerados no pagan mensualidad',
    motivo_exoneracion TEXT NULL,

    -- Auditoría
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso DATETIME NULL,

    INDEX idx_email (email),
    INDEX idx_rol (rol),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Usuarios del sistema con 4 roles';

-- ============================================================================
-- TABLA: apartamentos
-- Descripción: Almacena los apartamentos por bloque, escalera, piso
-- ============================================================================
CREATE TABLE apartamentos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bloque VARCHAR(10) NOT NULL COMMENT 'Bloques: 27, 28, 29, 30, 31, 32',
    escalera VARCHAR(5) NOT NULL COMMENT 'Escaleras: A, B, C',
    piso INT NOT NULL COMMENT 'Piso del apartamento',
    numero_apartamento VARCHAR(10) NOT NULL COMMENT 'Número del apto (Ej: 501, 502)',
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_apartamento (bloque, escalera, piso, numero_apartamento),
    INDEX idx_bloque (bloque),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Apartamentos de los bloques 27-32';

-- ============================================================================
-- TABLA: apartamento_usuario
-- Descripción: Relación N:N entre apartamentos y usuarios (un apto puede tener varios residentes)
-- ============================================================================
CREATE TABLE apartamento_usuario (
    id INT PRIMARY KEY AUTO_INCREMENT,
    apartamento_id INT NOT NULL,
    usuario_id INT NOT NULL,
    cantidad_controles INT DEFAULT 0 COMMENT 'Número de controles asignados',
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE COMMENT 'FALSE cuando se muda o transfiere',

    FOREIGN KEY (apartamento_id) REFERENCES apartamentos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_apartamento (apartamento_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Relación apartamentos-usuarios';

-- ============================================================================
-- TABLA: controles_estacionamiento
-- Descripción: 250 posiciones × 2 receptores (A/B) = 500 controles totales
-- ============================================================================
CREATE TABLE controles_estacionamiento (
    id INT PRIMARY KEY AUTO_INCREMENT,
    apartamento_usuario_id INT NULL COMMENT 'NULL = control vacío/sin asignar',

    -- Identificación del control
    posicion_numero INT NOT NULL COMMENT 'Posición física: 1-250',
    receptor ENUM('A', 'B') NOT NULL COMMENT 'Receptor A o B',
    numero_control_completo VARCHAR(10) NOT NULL UNIQUE COMMENT 'Ej: 1A, 15B, 250A',

    -- Estado del control
    estado ENUM('activo', 'suspendido', 'desactivado', 'perdido', 'bloqueado', 'vacio') DEFAULT 'vacio',
    motivo_estado TEXT NULL COMMENT 'Motivo de suspensión, desactivación, etc.',
    fecha_estado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Aprobaciones
    aprobado_por INT NULL COMMENT 'Usuario que aprobó cambio de estado',
    fecha_asignacion DATETIME NULL COMMENT 'Fecha cuando se asignó a un usuario',

    FOREIGN KEY (apartamento_usuario_id) REFERENCES apartamento_usuario(id) ON DELETE SET NULL,
    FOREIGN KEY (aprobado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    UNIQUE KEY unique_posicion_receptor (posicion_numero, receptor),
    INDEX idx_estado (estado),
    INDEX idx_posicion (posicion_numero),
    INDEX idx_receptor (receptor)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='500 controles totales (250×2 receptores)';

-- ============================================================================
-- TABLA: configuracion_tarifas
-- Descripción: Tarifas mensuales en USD por control
-- ============================================================================
CREATE TABLE configuracion_tarifas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    monto_mensual_usd DECIMAL(10, 2) NOT NULL DEFAULT 1.00 COMMENT 'Tarifa en USD por control',
    fecha_vigencia_inicio DATE NOT NULL,
    fecha_vigencia_fin DATE NULL COMMENT 'NULL = vigencia actual',
    activo BOOLEAN DEFAULT TRUE,
    creado_por INT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_vigencia (fecha_vigencia_inicio, fecha_vigencia_fin),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Tarifas mensuales configurables';

-- ============================================================================
-- TABLA: tasa_cambio_bcv
-- Descripción: Historial de tasas de cambio USD → Bs (BCV)
-- ============================================================================
CREATE TABLE tasa_cambio_bcv (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tasa_usd_bs DECIMAL(10, 4) NOT NULL COMMENT 'Tasa oficial BCV (Ej: 36.5000)',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    registrado_por INT NULL COMMENT 'Usuario que actualizó la tasa',
    fuente VARCHAR(100) DEFAULT 'Manual' COMMENT 'Manual o API',

    FOREIGN KEY (registrado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_fecha_registro (fecha_registro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Historial de tasas BCV';

-- ============================================================================
-- TABLA: mensualidades
-- Descripción: Mensualidades generadas automáticamente el día 5 de cada mes
-- ============================================================================
CREATE TABLE mensualidades (
    id INT PRIMARY KEY AUTO_INCREMENT,
    apartamento_usuario_id INT NOT NULL,

    -- Período
    mes INT NOT NULL COMMENT '1-12',
    anio INT NOT NULL COMMENT 'Año de la mensualidad',

    -- Snapshot al momento de generar
    cantidad_controles INT NOT NULL COMMENT 'Cantidad de controles al generar',
    monto_usd DECIMAL(10, 2) NOT NULL COMMENT 'Monto total en USD',
    monto_bs DECIMAL(12, 2) NOT NULL COMMENT 'Monto total en Bs',
    tasa_cambio_id INT NOT NULL COMMENT 'Tasa BCV usada para conversión',

    -- Estado
    estado ENUM('pendiente', 'pagado', 'vencido') DEFAULT 'pendiente',
    fecha_vencimiento DATE NOT NULL COMMENT 'Último día del mes',
    fecha_generacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Día 5 del mes',
    bloqueado BOOLEAN DEFAULT FALSE COMMENT 'TRUE con 4+ meses sin pagar',

    FOREIGN KEY (apartamento_usuario_id) REFERENCES apartamento_usuario(id) ON DELETE CASCADE,
    FOREIGN KEY (tasa_cambio_id) REFERENCES tasa_cambio_bcv(id),
    UNIQUE KEY unique_mensualidad (apartamento_usuario_id, mes, anio),
    INDEX idx_estado (estado),
    INDEX idx_fecha_vencimiento (fecha_vencimiento),
    INDEX idx_mes_anio (mes, anio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Mensualidades generadas automáticamente';

-- ============================================================================
-- TABLA: pagos
-- Descripción: Registro de todos los pagos (efectivo, transferencia)
-- ============================================================================
CREATE TABLE pagos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    apartamento_usuario_id INT NOT NULL,

    -- Recibo oficial
    numero_recibo VARCHAR(20) NOT NULL UNIQUE COMMENT 'Formato: EST-000001',

    -- Montos
    monto_usd DECIMAL(10, 2) NOT NULL,
    monto_bs DECIMAL(12, 2) NOT NULL,
    tasa_cambio_id INT NOT NULL,
    moneda_pago ENUM('usd_efectivo', 'bs_transferencia', 'bs_efectivo') NOT NULL,

    -- Fechas
    fecha_pago TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Comprobante (solo para transferencias)
    comprobante_ruta VARCHAR(255) NULL COMMENT 'Path al archivo de comprobante',
    estado_comprobante ENUM('pendiente', 'aprobado', 'rechazado', 'no_aplica') DEFAULT 'no_aplica',
    motivo_rechazo TEXT NULL,

    -- Aprobación
    registrado_por INT NOT NULL COMMENT 'Usuario que registró el pago',
    aprobado_por INT NULL COMMENT 'Usuario que aprobó el comprobante',
    fecha_aprobacion DATETIME NULL,

    -- Reconexión
    es_reconexion BOOLEAN DEFAULT FALSE,
    monto_reconexion_usd DECIMAL(10, 2) NULL DEFAULT 2.00,

    -- Sincronización
    google_sheets_sync BOOLEAN DEFAULT FALSE,
    fecha_sync DATETIME NULL,

    -- Notas adicionales
    notas TEXT NULL,

    FOREIGN KEY (apartamento_usuario_id) REFERENCES apartamento_usuario(id) ON DELETE CASCADE,
    FOREIGN KEY (tasa_cambio_id) REFERENCES tasa_cambio_bcv(id),
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id) ON DELETE RESTRICT,
    FOREIGN KEY (aprobado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_numero_recibo (numero_recibo),
    INDEX idx_fecha_pago (fecha_pago),
    INDEX idx_estado_comprobante (estado_comprobante),
    INDEX idx_moneda_pago (moneda_pago)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Registro completo de pagos';

-- ============================================================================
-- TABLA: pago_mensualidad
-- Descripción: Relación N:N entre pagos y mensualidades (un pago puede cubrir varios meses)
-- ============================================================================
CREATE TABLE pago_mensualidad (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pago_id INT NOT NULL,
    mensualidad_id INT NOT NULL,
    monto_aplicado_usd DECIMAL(10, 2) NOT NULL COMMENT 'Monto aplicado a esta mensualidad',
    fecha_aplicacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (pago_id) REFERENCES pagos(id) ON DELETE CASCADE,
    FOREIGN KEY (mensualidad_id) REFERENCES mensualidades(id) ON DELETE CASCADE,
    UNIQUE KEY unique_pago_mensualidad (pago_id, mensualidad_id),
    INDEX idx_pago (pago_id),
    INDEX idx_mensualidad (mensualidad_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Relación pagos-mensualidades';

-- ============================================================================
-- TABLA: solicitudes_cambios
-- Descripción: Solicitudes de cambios en controles (suspensión, cambio cantidad, etc.)
-- ============================================================================
CREATE TABLE solicitudes_cambios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    apartamento_usuario_id INT NOT NULL,

    -- Tipo de solicitud
    tipo_solicitud ENUM('cambio_cantidad_controles', 'suspension_control', 'desactivacion_control') NOT NULL,

    -- Datos específicos según tipo
    cantidad_controles_nueva INT NULL COMMENT 'Solo para cambio_cantidad_controles',
    control_id INT NULL COMMENT 'Solo para suspension/desactivacion',
    motivo TEXT NOT NULL,

    -- Estado
    estado ENUM('pendiente', 'aprobada', 'rechazada') DEFAULT 'pendiente',
    fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Respuesta
    aprobado_por INT NULL,
    fecha_respuesta DATETIME NULL,
    observaciones TEXT NULL COMMENT 'Comentarios del operador/admin',

    FOREIGN KEY (apartamento_usuario_id) REFERENCES apartamento_usuario(id) ON DELETE CASCADE,
    FOREIGN KEY (control_id) REFERENCES controles_estacionamiento(id) ON DELETE SET NULL,
    FOREIGN KEY (aprobado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_estado (estado),
    INDEX idx_tipo_solicitud (tipo_solicitud),
    INDEX idx_fecha_solicitud (fecha_solicitud)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Solicitudes de cambios en controles';

-- ============================================================================
-- TABLA: notificaciones
-- Descripción: Sistema de notificaciones internas y por email
-- ============================================================================
CREATE TABLE notificaciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,

    -- Tipo de notificación
    tipo ENUM(
        'alerta_3_meses',
        'alerta_bloqueo',
        'comprobante_rechazado',
        'pago_aprobado',
        'solicitud_aprobada',
        'solicitud_rechazada',
        'bienvenida',
        'password_cambiado'
    ) NOT NULL,

    -- Contenido
    titulo VARCHAR(255) NOT NULL,
    mensaje TEXT NOT NULL,

    -- Estado
    leido BOOLEAN DEFAULT FALSE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_lectura DATETIME NULL,

    -- Email
    email_enviado BOOLEAN DEFAULT FALSE,
    fecha_email DATETIME NULL,

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario_leido (usuario_id, leido),
    INDEX idx_tipo (tipo),
    INDEX idx_fecha_creacion (fecha_creacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Notificaciones del sistema';

-- ============================================================================
-- TABLA: logs_actividad
-- Descripción: Registro completo de actividad del sistema (auditoría)
-- ============================================================================
CREATE TABLE logs_actividad (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NULL COMMENT 'NULL para acciones del sistema',

    -- Acción
    accion VARCHAR(255) NOT NULL COMMENT 'Descripción de la acción',
    modulo VARCHAR(50) NULL COMMENT 'pagos, usuarios, controles, etc.',

    -- Datos afectados
    tabla_afectada VARCHAR(50) NULL,
    registro_id INT NULL COMMENT 'ID del registro afectado',
    datos_anteriores JSON NULL COMMENT 'Estado anterior (UPDATE/DELETE)',
    datos_nuevos JSON NULL COMMENT 'Estado nuevo (INSERT/UPDATE)',

    -- Información de sesión
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    fecha_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_usuario (usuario_id),
    INDEX idx_modulo (modulo),
    INDEX idx_tabla (tabla_afectada),
    INDEX idx_fecha_hora (fecha_hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Logs de auditoría completos';

-- ============================================================================
-- TABLA: password_reset_tokens
-- Descripción: Códigos de verificación para recuperación de contraseña (User Story #6)
-- ============================================================================
CREATE TABLE password_reset_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,

    -- Código de verificación
    codigo VARCHAR(6) NOT NULL COMMENT 'Código de 6 dígitos',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion TIMESTAMP NULL COMMENT 'Expira en 15 minutos',

    -- Control de uso
    usado BOOLEAN DEFAULT FALSE COMMENT 'TRUE después de usar el código',
    intentos_validacion INT DEFAULT 0 COMMENT 'Máximo 3 intentos',

    -- Seguridad
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_codigo (codigo),
    INDEX idx_email (email),
    INDEX idx_fecha_expiracion (fecha_expiracion),
    INDEX idx_usado (usado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Tokens para recuperación de contraseña';

-- ============================================================================
-- TABLA: login_intentos
-- Descripción: Registro de intentos de login fallidos para seguridad
-- ============================================================================
CREATE TABLE login_intentos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    fecha_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    exitoso BOOLEAN DEFAULT FALSE,
    intentos INT DEFAULT 1,
    ultimo_intento TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    bloqueado_hasta DATETIME NULL,
    
    UNIQUE KEY unique_email (email),
    INDEX idx_email (email),
    INDEX idx_ip_address (ip_address),
    INDEX idx_fecha_hora (fecha_hora),
    INDEX idx_exitoso (exitoso)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Registro de intentos de login para seguridad';

-- ============================================================================
-- TABLA: configuracion_cron
-- Descripción: Configuración y estado de las tareas programadas (CRON jobs)
-- ============================================================================
CREATE TABLE configuracion_cron (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre_tarea VARCHAR(100) NOT NULL UNIQUE COMMENT 'Nombre único de la tarea (ej: generar_mensualidades)',
    descripcion TEXT NULL,
    script_path VARCHAR(255) NOT NULL COMMENT 'Ruta relativa al script PHP',
    activo BOOLEAN DEFAULT TRUE,
    frecuencia VARCHAR(100) NOT NULL COMMENT 'Ej: "Diario", "Mensual"',
    hora_ejecucion TIME NULL COMMENT 'Hora preferida de ejecución (HH:MM:SS)',
    dia_mes INT NULL COMMENT 'Día del mes para tareas mensuales (1-31)',
    ultima_ejecucion DATETIME NULL,
    ultimo_resultado ENUM('exitoso', 'fallido', 'pendiente') DEFAULT 'pendiente',
    ultimo_mensaje TEXT NULL,

    INDEX idx_nombre_tarea (nombre_tarea),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Configuración y estado de tareas CRON';


-- ============================================================================
-- VISTAS ÚTILES
-- ============================================================================

-- Vista: Morosidad por usuario
CREATE VIEW vista_morosidad AS
SELECT
    u.id AS usuario_id,
    u.nombre_completo,
    u.email,
    u.telefono,
    CONCAT(a.bloque, '-', a.numero_apartamento) AS apartamento,
    au.cantidad_controles,
    COUNT(m.id) AS meses_pendientes,
    SUM(m.monto_usd) AS total_deuda_usd,
    SUM(m.monto_bs) AS total_deuda_bs,
    MIN(m.fecha_vencimiento) AS primer_mes_pendiente,
    MAX(m.fecha_vencimiento) AS ultimo_mes_pendiente,
    CASE
        WHEN COUNT(m.id) >= 4 THEN 'Bloqueado'
        WHEN COUNT(m.id) >= 3 THEN 'Alerta'
        ELSE 'Normal'
    END AS estado_morosidad
FROM usuarios u
JOIN apartamento_usuario au ON au.usuario_id = u.id AND au.activo = TRUE
JOIN apartamentos a ON a.id = au.apartamento_id
JOIN mensualidades m ON m.apartamento_usuario_id = au.id AND m.estado IN ('pendiente', 'vencido')
WHERE u.activo = TRUE AND u.exonerado = FALSE
GROUP BY u.id, u.nombre_completo, u.email, u.telefono, a.bloque, a.numero_apartamento, au.cantidad_controles;

-- Vista: Controles vacíos disponibles
CREATE VIEW vista_controles_vacios AS
SELECT
    c.id,
    c.posicion_numero,
    c.receptor,
    c.numero_control_completo,
    c.estado
FROM controles_estacionamiento c
WHERE c.estado = 'vacio' AND c.apartamento_usuario_id IS NULL
ORDER BY c.posicion_numero, c.receptor;

-- ============================================================================
-- DATOS INICIALES (mínimos)
-- ============================================================================

-- Tarifa inicial: $1 USD por control
INSERT INTO configuracion_tarifas (monto_mensual_usd, fecha_vigencia_inicio, fecha_vigencia_fin, activo)
VALUES (1.00, '2025-01-01', NULL, TRUE);

-- Tasa BCV inicial (debe actualizarse regularmente)
INSERT INTO tasa_cambio_bcv (tasa_usd_bs, registrado_por, fuente)
VALUES (36.50, NULL, 'Inicial');

-- Tareas CRON iniciales
INSERT INTO configuracion_cron (nombre_tarea, descripcion, script_path, activo, frecuencia, hora_ejecucion, dia_mes) VALUES
('generar_mensualidades', 'Genera las mensualidades para todos los clientes activos el día 5 de cada mes.', 'cron/generar_mensualidades.php', TRUE, 'Mensual', '00:05:00', 5),
('verificar_bloqueos', 'Verifica clientes con 4+ meses de mora y bloquea sus controles.', 'cron/verificar_bloqueos.php', TRUE, 'Diario', '01:00:00', NULL),
('enviar_notificaciones', 'Envía notificaciones pendientes por email (alertas de mora, etc.).', 'cron/enviar_notificaciones.php', TRUE, 'Diario', '09:00:00', NULL),
('actualizar_tasa_bcv', 'Intenta actualizar la tasa de cambio desde el BCV automáticamente.', 'cron/actualizar_tasa_bcv.php', TRUE, 'Diario', '10:00:00', NULL),
('backup_database', 'Realiza un backup completo de la base de datos.', 'cron/backup_database.php', TRUE, 'Diario', '02:00:00', NULL);

-- ============================================================================
-- STORED PROCEDURES ÚTILES
-- ============================================================================

DELIMITER //

-- Procedimiento: Generar mensualidades del mes actual
CREATE PROCEDURE sp_generar_mensualidades_mes()
BEGIN
    DECLARE v_mes INT;
    DECLARE v_anio INT;
    DECLARE v_tasa_id INT;
    DECLARE v_tasa_valor DECIMAL(10,4);
    DECLARE v_tarifa DECIMAL(10,2);

    -- Obtener mes y año actual
    SET v_mes = MONTH(CURDATE());
    SET v_anio = YEAR(CURDATE());

    -- Obtener última tasa BCV
    SELECT id, tasa_usd_bs INTO v_tasa_id, v_tasa_valor
    FROM tasa_cambio_bcv
    ORDER BY fecha_registro DESC
    LIMIT 1;

    -- Obtener tarifa vigente
    SELECT monto_mensual_usd INTO v_tarifa
    FROM configuracion_tarifas
    WHERE activo = TRUE AND fecha_vigencia_inicio <= CURDATE()
    ORDER BY fecha_vigencia_inicio DESC
    LIMIT 1;

    -- Insertar mensualidades para usuarios activos no exonerados
    INSERT INTO mensualidades (
        apartamento_usuario_id,
        mes,
        anio,
        cantidad_controles,
        monto_usd,
        monto_bs,
        tasa_cambio_id,
        fecha_vencimiento,
        estado
    )
    SELECT
        au.id,
        v_mes,
        v_anio,
        au.cantidad_controles,
        (au.cantidad_controles * v_tarifa),
        (au.cantidad_controles * v_tarifa * v_tasa_valor),
        v_tasa_id,
        LAST_DAY(CURDATE()),
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
            AND m2.mes = v_mes
            AND m2.anio = v_anio
      );

    SELECT ROW_COUNT() AS mensualidades_generadas;
END //

DELIMITER ;

-- ============================================================================
-- ÍNDICES ADICIONALES PARA PERFORMANCE
-- ============================================================================

-- Índice compuesto para búsquedas de morosidad
CREATE INDEX idx_mensualidad_estado_fecha ON mensualidades(estado, fecha_vencimiento);

-- Índice para búsqueda rápida de controles por apartamento
CREATE INDEX idx_control_apartamento ON controles_estacionamiento(apartamento_usuario_id, estado);

-- ============================================================================
-- COMENTARIOS FINALES
-- ============================================================================

/*
RESUMEN DE TABLAS:
1. usuarios (clientes, operadores, consultores, administradores)
2. apartamentos (bloques 27-32)
3. apartamento_usuario (relación N:N)
4. controles_estacionamiento (500 controles: 250×2 receptores)
5. configuracion_tarifas (tarifa mensual en USD)
6. tasa_cambio_bcv (historial de tasas)
7. mensualidades (generadas día 5 de cada mes)
8. pagos (registro de todos los pagos)
9. pago_mensualidad (relación N:N pagos-mensualidades)
10. solicitudes_cambios (solicitudes de usuarios)
11. notificaciones (alertas internas y emails)
12. logs_actividad (auditoría completa)
13. password_reset_tokens (recuperación de contraseña)

TOTAL: 13 TABLAS + 2 VISTAS + 1 STORED PROCEDURE
*/
