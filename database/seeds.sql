-- ============================================================================
-- SEEDS: Datos iniciales para desarrollo y testing
-- Base de datos: estacionamiento_db
-- Versión: 1.0.0
-- ============================================================================

USE estacionamiento_db;

-- Deshabilitar verificación de claves foráneas temporalmente
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- 1. USUARIOS INICIALES (4 roles)
-- ============================================================================

-- Contraseña para todos: "password123" (debe cambiarse en producción)
-- Hash BCRYPT: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

INSERT INTO usuarios (id, nombre_completo, email, password, telefono, rol, activo, primer_acceso, password_temporal, perfil_completo, exonerado) VALUES
-- Administrador
(1, 'Ing. Miguel Sánchez', 'admin@estacionamiento.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+58 424 1234567', 'administrador', TRUE, FALSE, FALSE, TRUE, FALSE),

-- Operador
(2, 'Carmen Méndez', 'operador@estacionamiento.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+58 414 2345678', 'operador', TRUE, FALSE, FALSE, TRUE, FALSE),

-- Consultor
(3, 'Sr. Alberto Rivas', 'consultor@estacionamiento.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+58 412 3456789', 'consultor', TRUE, FALSE, FALSE, TRUE, FALSE),

-- Clientes de prueba (basados en User Stories)
(4, 'María González', 'maria.gonzalez@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+58 426 4567890', 'cliente', TRUE, FALSE, FALSE, TRUE, FALSE),
(5, 'Roberto Díaz', 'roberto.diaz@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+58 414 5678901', 'cliente', TRUE, TRUE, TRUE, FALSE, FALSE),
(6, 'Laura Morales', 'laura.morales@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+58 424 6789012', 'cliente', TRUE, FALSE, FALSE, TRUE, FALSE),
(7, 'Juan Pérez', 'juan.perez@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+58 412 7890123', 'cliente', TRUE, FALSE, FALSE, TRUE, FALSE),
(8, 'Ana Rodríguez', 'ana.rodriguez@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+58 426 8901234', 'cliente', TRUE, FALSE, FALSE, TRUE, FALSE),
(9, 'Carlos Martínez', 'carlos.martinez@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+58 414 9012345', 'cliente', TRUE, FALSE, FALSE, TRUE, TRUE),
(10, 'Elena Silva', 'elena.silva@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+58 424 0123456', 'cliente', TRUE, FALSE, FALSE, TRUE, FALSE);

-- ============================================================================
-- 2. APARTAMENTOS (Bloques 27-32, varios por bloque)
-- ============================================================================

INSERT INTO apartamentos (id, bloque, escalera, piso, numero_apartamento, activo) VALUES
-- Bloque 27
(1, '27', 'A', 5, '501', TRUE),
(2, '27', 'A', 5, '502', TRUE),
(3, '27', 'B', 3, '301', TRUE),

-- Bloque 28
(4, '28', 'A', 4, '401', TRUE),
(5, '28', 'B', 2, '201', TRUE),

-- Bloque 29
(6, '29', 'A', 5, '501', TRUE),
(7, '29', 'B', 6, '601', TRUE),

-- Bloque 30
(8, '30', 'A', 3, '301', TRUE),
(9, '30', 'C', 4, '402', TRUE),

-- Bloque 31
(10, '31', 'A', 2, '201', TRUE),

-- Bloque 32
(11, '32', 'B', 5, '503', TRUE);

-- ============================================================================
-- 3. APARTAMENTO_USUARIO (Asignación de clientes a apartamentos)
-- ============================================================================

INSERT INTO apartamento_usuario (id, apartamento_id, usuario_id, cantidad_controles, activo) VALUES
(1, 6, 4, 2, TRUE),   -- María González - Bloque 29, 2 controles (User Story #1)
(2, 7, 5, 1, TRUE),   -- Roberto Díaz - Bloque 29, 1 control (User Story #2)
(3, 8, 6, 2, TRUE),   -- Laura Morales - Bloque 30, 2 controles (User Story #6)
(4, 1, 7, 2, TRUE),   -- Juan Pérez - Bloque 27, 2 controles
(5, 2, 8, 3, TRUE),   -- Ana Rodríguez - Bloque 27, 3 controles
(6, 3, 9, 1, TRUE),   -- Carlos Martínez - Bloque 27, 1 control (EXONERADO)
(7, 4, 10, 2, TRUE);  -- Elena Silva - Bloque 28, 2 controles

-- ============================================================================
-- 4. CONTROLES DE ESTACIONAMIENTO (Generamos los primeros 50 como ejemplo)
-- ============================================================================

-- Función para generar 500 controles automáticamente
-- Controles 1A hasta 250B

-- Controles asignados (primeros 20 controles con receptor A y B)
INSERT INTO controles_estacionamiento (posicion_numero, receptor, numero_control_completo, apartamento_usuario_id, estado, fecha_asignacion) VALUES
-- María González (2 controles)
(15, 'A', '15A', 1, 'activo', '2024-12-01 10:00:00'),
(15, 'B', '15B', 1, 'activo', '2024-12-01 10:00:00'),

-- Roberto Díaz (1 control)
(127, 'A', '127A', 2, 'activo', '2025-01-15 14:30:00'),

-- Laura Morales (2 controles)
(45, 'A', '45A', 3, 'activo', '2024-10-10 09:00:00'),
(45, 'B', '45B', 3, 'activo', '2024-10-10 09:00:00'),

-- Juan Pérez (2 controles)
(30, 'A', '30A', 4, 'activo', '2024-11-05 11:00:00'),
(30, 'B', '30B', 4, 'activo', '2024-11-05 11:00:00'),

-- Ana Rodríguez (3 controles)
(50, 'A', '50A', 5, 'activo', '2024-09-20 15:00:00'),
(50, 'B', '50B', 5, 'activo', '2024-09-20 15:00:00'),
(51, 'A', '51A', 5, 'activo', '2024-09-20 15:00:00'),

-- Carlos Martínez (1 control - EXONERADO)
(60, 'A', '60A', 6, 'activo', '2024-08-15 10:00:00'),

-- Elena Silva (2 controles)
(75, 'A', '75A', 7, 'activo', '2024-12-10 12:00:00'),
(75, 'B', '75B', 7, 'activo', '2024-12-10 12:00:00');

-- Controles vacíos (primeros 20 vacíos como ejemplo)
INSERT INTO controles_estacionamiento (posicion_numero, receptor, numero_control_completo, apartamento_usuario_id, estado) VALUES
(1, 'A', '1A', NULL, 'vacio'),
(1, 'B', '1B', NULL, 'vacio'),
(2, 'A', '2A', NULL, 'vacio'),
(2, 'B', '2B', NULL, 'vacio'),
(3, 'A', '3A', NULL, 'vacio'),
(3, 'B', '3B', NULL, 'vacio'),
(4, 'A', '4A', NULL, 'vacio'),
(4, 'B', '4B', NULL, 'vacio'),
(5, 'A', '5A', NULL, 'vacio'),
(5, 'B', '5B', NULL, 'vacio'),
(6, 'A', '6A', NULL, 'vacio'),
(6, 'B', '6B', NULL, 'vacio'),
(7, 'A', '7A', NULL, 'vacio'),
(7, 'B', '7B', NULL, 'vacio'),
(8, 'A', '8A', NULL, 'vacio'),
(8, 'B', '8B', NULL, 'vacio'),
(9, 'A', '9A', NULL, 'vacio'),
(9, 'B', '9B', NULL, 'vacio'),
(10, 'A', '10A', NULL, 'vacio'),
(10, 'B', '10B', NULL, 'vacio');

-- ============================================================================
-- 5. CONFIGURACIÓN DE TARIFAS
-- ============================================================================

-- Ya existe la tarifa inicial de $1 USD en schema.sql
-- Se puede agregar historial de cambios de tarifas aquí si es necesario

-- ============================================================================
-- 6. TASAS DE CAMBIO BCV (Historial)
-- ============================================================================

-- Ya existe la tasa inicial en schema.sql
-- Agregamos algunas tasas históricas

INSERT INTO tasa_cambio_bcv (tasa_usd_bs, fecha_registro, registrado_por, fuente) VALUES
(36.45, '2025-01-01 10:00:00', 1, 'Manual'),
(36.52, '2025-01-15 10:00:00', 1, 'Manual'),
(36.60, '2025-02-01 10:00:00', 1, 'Manual'),
(36.75, '2025-03-01 10:00:00', 1, 'Manual'),
(36.85, '2025-10-01 10:00:00', 1, 'Manual'),
(37.20, '2025-11-01 10:00:00', 1, 'Manual'),
(37.50, '2025-11-04 09:00:00', 1, 'API'); -- Tasa actual

-- ============================================================================
-- 7. MENSUALIDADES (Generar mensualidades de los últimos 3 meses)
-- ============================================================================

-- Mensualidades de SEPTIEMBRE 2025 (vencidas)
INSERT INTO mensualidades (apartamento_usuario_id, mes, anio, cantidad_controles, monto_usd, monto_bs, tasa_cambio_id, estado, fecha_vencimiento, fecha_generacion) VALUES
(1, 9, 2025, 2, 2.00, 73.70, 5, 'vencido', '2025-09-30', '2025-09-05 00:05:00'),
(2, 9, 2025, 1, 1.00, 36.85, 5, 'vencido', '2025-09-30', '2025-09-05 00:05:00'),
(3, 9, 2025, 2, 2.00, 73.70, 5, 'pagado', '2025-09-30', '2025-09-05 00:05:00'),
(4, 9, 2025, 2, 2.00, 73.70, 5, 'vencido', '2025-09-30', '2025-09-05 00:05:00'),
(5, 9, 2025, 3, 3.00, 110.55, 5, 'pagado', '2025-09-30', '2025-09-05 00:05:00'),
-- Carlos (exonerado) NO genera mensualidad
(7, 9, 2025, 2, 2.00, 73.70, 5, 'vencido', '2025-09-30', '2025-09-05 00:05:00');

-- Mensualidades de OCTUBRE 2025 (vencidas)
INSERT INTO mensualidades (apartamento_usuario_id, mes, anio, cantidad_controles, monto_usd, monto_bs, tasa_cambio_id, estado, fecha_vencimiento, fecha_generacion) VALUES
(1, 10, 2025, 2, 2.00, 74.40, 6, 'vencido', '2025-10-31', '2025-10-05 00:05:00'),
(2, 10, 2025, 1, 1.00, 37.20, 6, 'vencido', '2025-10-31', '2025-10-05 00:05:00'),
(3, 10, 2025, 2, 2.00, 74.40, 6, 'pagado', '2025-10-31', '2025-10-05 00:05:00'),
(4, 10, 2025, 2, 2.00, 74.40, 6, 'vencido', '2025-10-31', '2025-10-05 00:05:00'),
(5, 10, 2025, 3, 3.00, 111.60, 6, 'pagado', '2025-10-31', '2025-10-05 00:05:00'),
(7, 10, 2025, 2, 2.00, 74.40, 6, 'vencido', '2025-10-31', '2025-10-05 00:05:00');

-- Mensualidades de NOVIEMBRE 2025 (pendientes)
INSERT INTO mensualidades (apartamento_usuario_id, mes, anio, cantidad_controles, monto_usd, monto_bs, tasa_cambio_id, estado, fecha_vencimiento, fecha_generacion) VALUES
(1, 11, 2025, 2, 2.00, 75.00, 7, 'pendiente', '2025-11-30', '2025-11-05 00:05:00'),
(2, 11, 2025, 1, 1.00, 37.50, 7, 'pendiente', '2025-11-30', '2025-11-05 00:05:00'),
(3, 11, 2025, 2, 2.00, 75.00, 7, 'pendiente', '2025-11-30', '2025-11-05 00:05:00'),
(4, 11, 2025, 2, 2.00, 75.00, 7, 'pendiente', '2025-11-30', '2025-11-05 00:05:00'),
(5, 11, 2025, 3, 3.00, 112.50, 7, 'pendiente', '2025-11-30', '2025-11-05 00:05:00'),
(7, 11, 2025, 2, 2.00, 75.00, 7, 'pendiente', '2025-11-30', '2025-11-05 00:05:00');

-- ============================================================================
-- 8. PAGOS (Algunos pagos registrados)
-- ============================================================================

-- Pago de María González - Septiembre (User Story #1)
INSERT INTO pagos (id, apartamento_usuario_id, numero_recibo, monto_usd, monto_bs, tasa_cambio_id, moneda_pago, fecha_pago, comprobante_ruta, estado_comprobante, registrado_por, aprobado_por, fecha_aprobacion, google_sheets_sync) VALUES
(1, 1, 'EST-000001', 2.00, 73.70, 5, 'bs_transferencia', '2025-09-15 14:30:00', 'uploads/comprobantes/maria_sept_2025.jpg', 'aprobado', 2, 2, '2025-09-15 15:00:00', TRUE);

-- Pago de Ana Rodríguez - Septiembre (efectivo)
INSERT INTO pagos (apartamento_usuario_id, numero_recibo, monto_usd, monto_bs, tasa_cambio_id, moneda_pago, fecha_pago, comprobante_ruta, estado_comprobante, registrado_por, aprobado_por, fecha_aprobacion, google_sheets_sync) VALUES
(5, 'EST-000002', 3.00, 110.55, 5, 'usd_efectivo', '2025-09-20 10:00:00', NULL, 'no_aplica', 2, NULL, NULL, TRUE);

-- Pago de Laura Morales - Septiembre y Octubre (User Story #6)
INSERT INTO pagos (apartamento_usuario_id, numero_recibo, monto_usd, monto_bs, tasa_cambio_id, moneda_pago, fecha_pago, comprobante_ruta, estado_comprobante, registrado_por, aprobado_por, fecha_aprobacion, google_sheets_sync) VALUES
(3, 'EST-000003', 4.00, 148.80, 6, 'bs_transferencia', '2025-10-25 16:45:00', 'uploads/comprobantes/laura_oct_2025.jpg', 'aprobado', 2, 1, '2025-10-25 17:00:00', TRUE);

-- Pago de Ana Rodríguez - Octubre (efectivo)
INSERT INTO pagos (apartamento_usuario_id, numero_recibo, monto_usd, monto_bs, tasa_cambio_id, moneda_pago, fecha_pago, comprobante_ruta, estado_comprobante, registrado_por, aprobado_por, fecha_aprobacion, google_sheets_sync) VALUES
(5, 'EST-000004', 3.00, 111.60, 6, 'usd_efectivo', '2025-10-22 11:30:00', NULL, 'no_aplica', 2, NULL, NULL, TRUE);

-- ============================================================================
-- 9. PAGO_MENSUALIDAD (Relacionar pagos con mensualidades)
-- ============================================================================

-- Pago EST-000001 cubre septiembre de María
INSERT INTO pago_mensualidad (pago_id, mensualidad_id, monto_aplicado_usd) VALUES
(1, 1, 2.00);

-- Pago EST-000002 cubre septiembre de Ana
INSERT INTO pago_mensualidad (pago_id, mensualidad_id, monto_aplicado_usd) VALUES
(2, 5, 3.00);

-- Pago EST-000003 cubre septiembre y octubre de Laura
INSERT INTO pago_mensualidad (pago_id, mensualidad_id, monto_aplicado_usd) VALUES
(3, 3, 2.00),  -- Septiembre
(3, 9, 2.00);  -- Octubre

-- Pago EST-000004 cubre octubre de Ana
INSERT INTO pago_mensualidad (pago_id, mensualidad_id, monto_aplicado_usd) VALUES
(4, 11, 3.00);

-- ============================================================================
-- 10. SOLICITUDES DE CAMBIOS (1 solicitud pendiente)
-- ============================================================================

INSERT INTO solicitudes_cambios (apartamento_usuario_id, tipo_solicitud, cantidad_controles_nueva, control_id, motivo, estado, fecha_solicitud) VALUES
(4, 'cambio_cantidad_controles', 3, NULL, 'Necesito un control adicional para segundo vehículo', 'pendiente', '2025-11-03 10:00:00');

-- ============================================================================
-- 11. NOTIFICACIONES (Algunas notificaciones de ejemplo)
-- ============================================================================

INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, leido, email_enviado) VALUES
-- Notificación de bienvenida para Roberto (primer acceso)
(5, 'bienvenida', 'Bienvenido al Sistema de Estacionamiento', 'Hola Roberto, bienvenido al sistema. Por favor, cambia tu contraseña temporal y completa tu perfil.', FALSE, TRUE),

-- Alerta de 3 meses para Juan Pérez
(7, 'alerta_3_meses', 'Alerta: 3 Meses de Mora', 'Estimado Juan, tiene 3 mensualidades pendientes. Por favor, realice el pago para evitar bloqueo de controles.', FALSE, TRUE),

-- Comprobante aprobado para María
(4, 'pago_aprobado', 'Pago Aprobado', 'Su pago de Septiembre 2025 ha sido aprobado. Recibo: EST-000001', TRUE, TRUE);

-- ============================================================================
-- 12. LOGS DE ACTIVIDAD (Algunos logs de ejemplo)
-- ============================================================================

INSERT INTO logs_actividad (usuario_id, accion, modulo, tabla_afectada, registro_id, ip_address, fecha_hora) VALUES
(1, 'Login exitoso', 'auth', 'usuarios', 1, '192.168.1.100', '2025-11-04 08:00:00'),
(2, 'Registró pago en efectivo EST-000002', 'pagos', 'pagos', 2, '192.168.1.105', '2025-09-20 10:00:00'),
(1, 'Aprobó comprobante EST-000003', 'pagos', 'pagos', 3, '192.168.1.100', '2025-10-25 17:00:00'),
(4, 'Consultó estado de cuenta', 'cliente', 'mensualidades', NULL, '192.168.1.120', '2025-11-03 14:30:00'),
(7, 'Creó solicitud de cambio de cantidad de controles', 'solicitudes', 'solicitudes_cambios', 1, '192.168.1.125', '2025-11-03 10:00:00');

-- ============================================================================
-- Habilitar verificación de claves foráneas nuevamente
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- RESUMEN DE DATOS INICIALES
-- ============================================================================

/*
USUARIOS:
- 1 Administrador (Ing. Miguel Sánchez)
- 1 Operador (Carmen Méndez)
- 1 Consultor (Sr. Alberto Rivas)
- 7 Clientes

APARTAMENTOS:
- 11 apartamentos en bloques 27-32

CONTROLES:
- 13 controles asignados
- 20 controles vacíos
- Total: 33 controles de ejemplo (de 500 totales)

MENSUALIDADES:
- Septiembre 2025: 6 mensualidades (3 pagadas, 3 vencidas)
- Octubre 2025: 6 mensualidades (2 pagadas, 4 vencidas)
- Noviembre 2025: 6 mensualidades (todas pendientes)

PAGOS:
- 4 pagos registrados (EST-000001 a EST-000004)

MOROSIDAD:
- Roberto Díaz: 3 meses (Septiembre, Octubre, Noviembre) - ALERTA
- Juan Pérez: 3 meses (Septiembre, Octubre, Noviembre) - ALERTA
- Elena Silva: 3 meses (Septiembre, Octubre, Noviembre) - ALERTA

CREDENCIALES DE ACCESO:
Todos los usuarios tienen la contraseña: password123

- admin@estacionamiento.local / password123
- operador@estacionamiento.local / password123
- consultor@estacionamiento.local / password123
- maria.gonzalez@gmail.com / password123
- roberto.diaz@gmail.com / password123 (PRIMER ACCESO)
- laura.morales@gmail.com / password123
*/

SELECT '✅ SEEDS EJECUTADOS CORRECTAMENTE' AS status;
SELECT CONCAT('Total de usuarios creados: ', COUNT(*)) AS usuarios FROM usuarios;
SELECT CONCAT('Total de apartamentos creados: ', COUNT(*)) AS apartamentos FROM apartamentos;
SELECT CONCAT('Total de controles creados: ', COUNT(*)) AS controles FROM controles_estacionamiento;
SELECT CONCAT('Total de mensualidades creadas: ', COUNT(*)) AS mensualidades FROM mensualidades;
SELECT CONCAT('Total de pagos registrados: ', COUNT(*)) AS pagos FROM pagos;
