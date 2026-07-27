-- ============================================================================
-- SCRIPT: Regeneración Completa de Apartamentos y Controles
-- ADVERTENCIA: Este script ELIMINA todos los apartamentos y controles existentes
-- ============================================================================

USE estacionamiento_db;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- 1. LIMPIAR DATOS EXISTENTES
-- ============================================================================

DELETE FROM pago_mensualidad;
DELETE FROM mensualidades;
DELETE FROM controles_estacionamiento;
DELETE FROM apartamento_usuario;
DELETE FROM apartamentos;

-- Resetear auto_increment
ALTER TABLE apartamentos AUTO_INCREMENT = 1;
ALTER TABLE apartamento_usuario AUTO_INCREMENT = 1;
ALTER TABLE controles_estacionamiento AUTO_INCREMENT = 1;

SELECT '✅ Datos anteriores eliminados' AS status;

-- ============================================================================
-- 2. INSERTAR APARTAMENTOS CON ESTRUCTURA CORRECTA
-- ============================================================================

-- BLOQUE 27 - 4 Escaleras (A, B, C, D) - 48 apartamentos
INSERT INTO apartamentos (bloque, escalera, piso, numero_apartamento, activo) VALUES
-- Escalera A
('27', 'A', 1, '101', TRUE), ('27', 'A', 1, '102', TRUE),
('27', 'A', 2, '201', TRUE), ('27', 'A', 2, '202', TRUE),
('27', 'A', 3, '301', TRUE), ('27', 'A', 3, '302', TRUE),
('27', 'A', 4, '401', TRUE), ('27', 'A', 4, '402', TRUE),
('27', 'A', 5, '501', TRUE), ('27', 'A', 5, '502', TRUE),
('27', 'A', 6, '601', TRUE), ('27', 'A', 6, '602', TRUE),
-- Escalera B
('27', 'B', 1, '101', TRUE), ('27', 'B', 1, '102', TRUE),
('27', 'B', 2, '201', TRUE), ('27', 'B', 2, '202', TRUE),
('27', 'B', 3, '301', TRUE), ('27', 'B', 3, '302', TRUE),
('27', 'B', 4, '401', TRUE), ('27', 'B', 4, '402', TRUE),
('27', 'B', 5, '501', TRUE), ('27', 'B', 5, '502', TRUE),
('27', 'B', 6, '601', TRUE), ('27', 'B', 6, '602', TRUE),
-- Escalera C
('27', 'C', 1, '101', TRUE), ('27', 'C', 1, '102', TRUE),
('27', 'C', 2, '201', TRUE), ('27', 'C', 2, '202', TRUE),
('27', 'C', 3, '301', TRUE), ('27', 'C', 3, '302', TRUE),
('27', 'C', 4, '401', TRUE), ('27', 'C', 4, '402', TRUE),
('27', 'C', 5, '501', TRUE), ('27', 'C', 5, '502', TRUE),
('27', 'C', 6, '601', TRUE), ('27', 'C', 6, '602', TRUE),
-- Escalera D
('27', 'D', 1, '101', TRUE), ('27', 'D', 1, '102', TRUE),
('27', 'D', 2, '201', TRUE), ('27', 'D', 2, '202', TRUE),
('27', 'D', 3, '301', TRUE), ('27', 'D', 3, '302', TRUE),
('27', 'D', 4, '401', TRUE), ('27', 'D', 4, '402', TRUE),
('27', 'D', 5, '501', TRUE), ('27', 'D', 5, '502', TRUE),
('27', 'D', 6, '601', TRUE), ('27', 'D', 6, '602', TRUE);

-- BLOQUE 28 - 1 Escalera (A) - 12 apartamentos
INSERT INTO apartamentos (bloque, escalera, piso, numero_apartamento, activo) VALUES
('28', 'A', 1, '101', TRUE), ('28', 'A', 1, '102', TRUE),
('28', 'A', 2, '201', TRUE), ('28', 'A', 2, '202', TRUE),
('28', 'A', 3, '301', TRUE), ('28', 'A', 3, '302', TRUE),
('28', 'A', 4, '401', TRUE), ('28', 'A', 4, '402', TRUE),
('28', 'A', 5, '501', TRUE), ('28', 'A', 5, '502', TRUE),
('28', 'A', 6, '601', TRUE), ('28', 'A', 6, '602', TRUE);

-- BLOQUE 29 - 2 Escaleras (A, B) - 24 apartamentos
INSERT INTO apartamentos (bloque, escalera, piso, numero_apartamento, activo) VALUES
-- Escalera A
('29', 'A', 1, '101', TRUE), ('29', 'A', 1, '102', TRUE),
('29', 'A', 2, '201', TRUE), ('29', 'A', 2, '202', TRUE),
('29', 'A', 3, '301', TRUE), ('29', 'A', 3, '302', TRUE),
('29', 'A', 4, '401', TRUE), ('29', 'A', 4, '402', TRUE),
('29', 'A', 5, '501', TRUE), ('29', 'A', 5, '502', TRUE),
('29', 'A', 6, '601', TRUE), ('29', 'A', 6, '602', TRUE),
-- Escalera B
('29', 'B', 1, '101', TRUE), ('29', 'B', 1, '102', TRUE),
('29', 'B', 2, '201', TRUE), ('29', 'B', 2, '202', TRUE),
('29', 'B', 3, '301', TRUE), ('29', 'B', 3, '302', TRUE),
('29', 'B', 4, '401', TRUE), ('29', 'B', 4, '402', TRUE),
('29', 'B', 5, '501', TRUE), ('29', 'B', 5, '502', TRUE),
('29', 'B', 6, '601', TRUE), ('29', 'B', 6, '602', TRUE);

-- BLOQUE 30 - 1 Escalera (A) - 12 apartamentos
INSERT INTO apartamentos (bloque, escalera, piso, numero_apartamento, activo) VALUES
('30', 'A', 1, '101', TRUE), ('30', 'A', 1, '102', TRUE),
('30', 'A', 2, '201', TRUE), ('30', 'A', 2, '202', TRUE),
('30', 'A', 3, '301', TRUE), ('30', 'A', 3, '302', TRUE),
('30', 'A', 4, '401', TRUE), ('30', 'A', 4, '402', TRUE),
('30', 'A', 5, '501', TRUE), ('30', 'A', 5, '502', TRUE),
('30', 'A', 6, '601', TRUE), ('30', 'A', 6, '602', TRUE);

-- BLOQUE 31 - 1 Escalera (A) - 12 apartamentos
INSERT INTO apartamentos (bloque, escalera, piso, numero_apartamento, activo) VALUES
('31', 'A', 1, '101', TRUE), ('31', 'A', 1, '102', TRUE),
('31', 'A', 2, '201', TRUE), ('31', 'A', 2, '202', TRUE),
('31', 'A', 3, '301', TRUE), ('31', 'A', 3, '302', TRUE),
('31', 'A', 4, '401', TRUE), ('31', 'A', 4, '402', TRUE),
('31', 'A', 5, '501', TRUE), ('31', 'A', 5, '502', TRUE),
('31', 'A', 6, '601', TRUE), ('31', 'A', 6, '602', TRUE);

-- BLOQUE 32 - 3 Escaleras (A, B, C) - 36 apartamentos
INSERT INTO apartamentos (bloque, escalera, piso, numero_apartamento, activo) VALUES
-- Escalera A
('32', 'A', 1, '101', TRUE), ('32', 'A', 1, '102', TRUE),
('32', 'A', 2, '201', TRUE), ('32', 'A', 2, '202', TRUE),
('32', 'A', 3, '301', TRUE), ('32', 'A', 3, '302', TRUE),
('32', 'A', 4, '401', TRUE), ('32', 'A', 4, '402', TRUE),
('32', 'A', 5, '501', TRUE), ('32', 'A', 5, '502', TRUE),
('32', 'A', 6, '601', TRUE), ('32', 'A', 6, '602', TRUE),
-- Escalera B
('32', 'B', 1, '101', TRUE), ('32', 'B', 1, '102', TRUE),
('32', 'B', 2, '201', TRUE), ('32', 'B', 2, '202', TRUE),
('32', 'B', 3, '301', TRUE), ('32', 'B', 3, '302', TRUE),
('32', 'B', 4, '401', TRUE), ('32', 'B', 4, '402', TRUE),
('32', 'B', 5, '501', TRUE), ('32', 'B', 5, '502', TRUE),
('32', 'B', 6, '601', TRUE), ('32', 'B', 6, '602', TRUE),
-- Escalera C
('32', 'C', 1, '101', TRUE), ('32', 'C', 1, '102', TRUE),
('32', 'C', 2, '201', TRUE), ('32', 'C', 2, '202', TRUE),
('32', 'C', 3, '301', TRUE), ('32', 'C', 3, '302', TRUE),
('32', 'C', 4, '401', TRUE), ('32', 'C', 4, '402', TRUE),
('32', 'C', 5, '501', TRUE), ('32', 'C', 5, '502', TRUE),
('32', 'C', 6, '601', TRUE), ('32', 'C', 6, '602', TRUE);

SELECT '✅ 144 apartamentos creados' AS status;

-- ============================================================================
-- 3. GENERAR 500 CONTROLES (250 posiciones × 2 receptores)
-- ============================================================================

DELIMITER //

DROP PROCEDURE IF EXISTS generar_500_controles//

CREATE PROCEDURE generar_500_controles()
BEGIN
    DECLARE v_posicion INT DEFAULT 1;
    
    WHILE v_posicion <= 250 DO
        INSERT INTO controles_estacionamiento 
            (posicion_numero, receptor, numero_control_completo, apartamento_usuario_id, estado)
        VALUES 
            (v_posicion, 'A', CONCAT(v_posicion, 'A'), NULL, 'vacio');
        
        INSERT INTO controles_estacionamiento 
            (posicion_numero, receptor, numero_control_completo, apartamento_usuario_id, estado)
        VALUES 
            (v_posicion, 'B', CONCAT(v_posicion, 'B'), NULL, 'vacio');
        
        SET v_posicion = v_posicion + 1;
    END WHILE;
END//

DELIMITER ;

CALL generar_500_controles();

SELECT '✅ 500 controles generados' AS status;

-- ============================================================================
-- 4. REASIGNAR CLIENTES A APARTAMENTOS (DATOS DE PRUEBA)
-- ============================================================================

-- Asignar clientes a algunos apartamentos para pruebas
INSERT INTO apartamento_usuario (apartamento_id, usuario_id, cantidad_controles, activo) VALUES
(61, 4, 2, TRUE),   -- María González - Bloque 29-A-501
(73, 5, 1, TRUE),   -- Roberto Díaz - Bloque 29-B-501
(85, 6, 2, TRUE),   -- Laura Morales - Bloque 30-A-101
(9, 7, 2, TRUE),    -- Juan Pérez - Bloque 27-A-501
(10, 8, 3, TRUE),   -- Ana Rodríguez - Bloque 27-A-502
(25, 9, 1, TRUE),   -- Carlos Martínez - Bloque 27-B-501
(49, 10, 2, TRUE);  -- Elena Silva - Bloque 28-A-101

SELECT '✅ Clientes reasignados a apartamentos' AS status;

-- ============================================================================
-- 5. ASIGNAR ALGUNOS CONTROLES A CLIENTES
-- ============================================================================

-- María González (2 controles)
UPDATE controles_estacionamiento SET apartamento_usuario_id = 1, estado = 'activo', fecha_asignacion = NOW() WHERE numero_control_completo IN ('15A', '15B');

-- Roberto Díaz (1 control)
UPDATE controles_estacionamiento SET apartamento_usuario_id = 2, estado = 'activo', fecha_asignacion = NOW() WHERE numero_control_completo = '127A';

-- Laura Morales (2 controles)
UPDATE controles_estacionamiento SET apartamento_usuario_id = 3, estado = 'activo', fecha_asignacion = NOW() WHERE numero_control_completo IN ('45A', '45B');

-- Juan Pérez (2 controles)
UPDATE controles_estacionamiento SET apartamento_usuario_id = 4, estado = 'activo', fecha_asignacion = NOW() WHERE numero_control_completo IN ('30A', '30B');

-- Ana Rodríguez (3 controles)
UPDATE controles_estacionamiento SET apartamento_usuario_id = 5, estado = 'activo', fecha_asignacion = NOW() WHERE numero_control_completo IN ('50A', '50B', '51A');

-- Carlos Martínez (1 control)
UPDATE controles_estacionamiento SET apartamento_usuario_id = 6, estado = 'activo', fecha_asignacion = NOW() WHERE numero_control_completo = '60A';

-- Elena Silva (2 controles)
UPDATE controles_estacionamiento SET apartamento_usuario_id = 7, estado = 'activo', fecha_asignacion = NOW() WHERE numero_control_completo IN ('75A', '75B');

SELECT '✅ Controles asignados a clientes' AS status;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- 6. VERIFICACIÓN FINAL
-- ============================================================================

SELECT '📊 RESUMEN FINAL' AS '';

SELECT 
    bloque,
    GROUP_CONCAT(DISTINCT escalera ORDER BY escalera) AS escaleras,
    COUNT(DISTINCT escalera) AS cant_escaleras,
    COUNT(*) AS total_aptos
FROM apartamentos
GROUP BY bloque
ORDER BY bloque;

SELECT 
    COUNT(*) AS total_controles,
    COUNT(DISTINCT posicion_numero) AS posiciones,
    SUM(CASE WHEN receptor = 'A' THEN 1 ELSE 0 END) AS receptor_a,
    SUM(CASE WHEN receptor = 'B' THEN 1 ELSE 0 END) AS receptor_b,
    SUM(CASE WHEN estado = 'vacio' THEN 1 ELSE 0 END) AS vacios,
    SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) AS asignados
FROM controles_estacionamiento;

SELECT '✅ REGENERACIÓN COMPLETADA EXITOSAMENTE' AS status;
