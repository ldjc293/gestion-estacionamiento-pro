-- ============================================================================
-- SCRIPT: Cambiar Escaleras de Letras a Números
-- Descripción: Actualiza escaleras de A,B,C,D a 1,2,3,4
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

ALTER TABLE apartamentos AUTO_INCREMENT = 1;
ALTER TABLE apartamento_usuario AUTO_INCREMENT = 1;
ALTER TABLE controles_estacionamiento AUTO_INCREMENT = 1;

SELECT '✅ Datos anteriores eliminados' AS status;

-- ============================================================================
-- 2. INSERTAR APARTAMENTOS CON ESCALERAS NUMERADAS
-- ============================================================================

-- BLOQUE 27 - 4 Escaleras (1, 2, 3, 4) - 48 apartamentos
INSERT INTO apartamentos (bloque, escalera, piso, numero_apartamento, activo) VALUES
-- Escalera 1
('27', '1', 1, '101', TRUE), ('27', '1', 1, '102', TRUE),
('27', '1', 2, '201', TRUE), ('27', '1', 2, '202', TRUE),
('27', '1', 3, '301', TRUE), ('27', '1', 3, '302', TRUE),
('27', '1', 4, '401', TRUE), ('27', '1', 4, '402', TRUE),
('27', '1', 5, '501', TRUE), ('27', '1', 5, '502', TRUE),
('27', '1', 6, '601', TRUE), ('27', '1', 6, '602', TRUE),
-- Escalera 2
('27', '2', 1, '101', TRUE), ('27', '2', 1, '102', TRUE),
('27', '2', 2, '201', TRUE), ('27', '2', 2, '202', TRUE),
('27', '2', 3, '301', TRUE), ('27', '2', 3, '302', TRUE),
('27', '2', 4, '401', TRUE), ('27', '2', 4, '402', TRUE),
('27', '2', 5, '501', TRUE), ('27', '2', 5, '502', TRUE),
('27', '2', 6, '601', TRUE), ('27', '2', 6, '602', TRUE),
-- Escalera 3
('27', '3', 1, '101', TRUE), ('27', '3', 1, '102', TRUE),
('27', '3', 2, '201', TRUE), ('27', '3', 2, '202', TRUE),
('27', '3', 3, '301', TRUE), ('27', '3', 3, '302', TRUE),
('27', '3', 4, '401', TRUE), ('27', '3', 4, '402', TRUE),
('27', '3', 5, '501', TRUE), ('27', '3', 5, '502', TRUE),
('27', '3', 6, '601', TRUE), ('27', '3', 6, '602', TRUE),
-- Escalera 4
('27', '4', 1, '101', TRUE), ('27', '4', 1, '102', TRUE),
('27', '4', 2, '201', TRUE), ('27', '4', 2, '202', TRUE),
('27', '4', 3, '301', TRUE), ('27', '4', 3, '302', TRUE),
('27', '4', 4, '401', TRUE), ('27', '4', 4, '402', TRUE),
('27', '4', 5, '501', TRUE), ('27', '4', 5, '502', TRUE),
('27', '4', 6, '601', TRUE), ('27', '4', 6, '602', TRUE);

-- BLOQUE 28 - 1 Escalera (1) - 12 apartamentos
INSERT INTO apartamentos (bloque, escalera, piso, numero_apartamento, activo) VALUES
('28', '1', 1, '101', TRUE), ('28', '1', 1, '102', TRUE),
('28', '1', 2, '201', TRUE), ('28', '1', 2, '202', TRUE),
('28', '1', 3, '301', TRUE), ('28', '1', 3, '302', TRUE),
('28', '1', 4, '401', TRUE), ('28', '1', 4, '402', TRUE),
('28', '1', 5, '501', TRUE), ('28', '1', 5, '502', TRUE),
('28', '1', 6, '601', TRUE), ('28', '1', 6, '602', TRUE);

-- BLOQUE 29 - 2 Escaleras (1, 2) - 24 apartamentos
INSERT INTO apartamentos (bloque, escalera, piso, numero_apartamento, activo) VALUES
-- Escalera 1
('29', '1', 1, '101', TRUE), ('29', '1', 1, '102', TRUE),
('29', '1', 2, '201', TRUE), ('29', '1', 2, '202', TRUE),
('29', '1', 3, '301', TRUE), ('29', '1', 3, '302', TRUE),
('29', '1', 4, '401', TRUE), ('29', '1', 4, '402', TRUE),
('29', '1', 5, '501', TRUE), ('29', '1', 5, '502', TRUE),
('29', '1', 6, '601', TRUE), ('29', '1', 6, '602', TRUE),
-- Escalera 2
('29', '2', 1, '101', TRUE), ('29', '2', 1, '102', TRUE),
('29', '2', 2, '201', TRUE), ('29', '2', 2, '202', TRUE),
('29', '2', 3, '301', TRUE), ('29', '2', 3, '302', TRUE),
('29', '2', 4, '401', TRUE), ('29', '2', 4, '402', TRUE),
('29', '2', 5, '501', TRUE), ('29', '2', 5, '502', TRUE),
('29', '2', 6, '601', TRUE), ('29', '2', 6, '602', TRUE);

-- BLOQUE 30 - 1 Escalera (1) - 12 apartamentos
INSERT INTO apartamentos (bloque, escalera, piso, numero_apartamento, activo) VALUES
('30', '1', 1, '101', TRUE), ('30', '1', 1, '102', TRUE),
('30', '1', 2, '201', TRUE), ('30', '1', 2, '202', TRUE),
('30', '1', 3, '301', TRUE), ('30', '1', 3, '302', TRUE),
('30', '1', 4, '401', TRUE), ('30', '1', 4, '402', TRUE),
('30', '1', 5, '501', TRUE), ('30', '1', 5, '502', TRUE),
('30', '1', 6, '601', TRUE), ('30', '1', 6, '602', TRUE);

-- BLOQUE 31 - 1 Escalera (1) - 12 apartamentos
INSERT INTO apartamentos (bloque, escalera, piso, numero_apartamento, activo) VALUES
('31', '1', 1, '101', TRUE), ('31', '1', 1, '102', TRUE),
('31', '1', 2, '201', TRUE), ('31', '1', 2, '202', TRUE),
('31', '1', 3, '301', TRUE), ('31', '1', 3, '302', TRUE),
('31', '1', 4, '401', TRUE), ('31', '1', 4, '402', TRUE),
('31', '1', 5, '501', TRUE), ('31', '1', 5, '502', TRUE),
('31', '1', 6, '601', TRUE), ('31', '1', 6, '602', TRUE);

-- BLOQUE 32 - 3 Escaleras (1, 2, 3) - 36 apartamentos
INSERT INTO apartamentos (bloque, escalera, piso, numero_apartamento, activo) VALUES
-- Escalera 1
('32', '1', 1, '101', TRUE), ('32', '1', 1, '102', TRUE),
('32', '1', 2, '201', TRUE), ('32', '1', 2, '202', TRUE),
('32', '1', 3, '301', TRUE), ('32', '1', 3, '302', TRUE),
('32', '1', 4, '401', TRUE), ('32', '1', 4, '402', TRUE),
('32', '1', 5, '501', TRUE), ('32', '1', 5, '502', TRUE),
('32', '1', 6, '601', TRUE), ('32', '1', 6, '602', TRUE),
-- Escalera 2
('32', '2', 1, '101', TRUE), ('32', '2', 1, '102', TRUE),
('32', '2', 2, '201', TRUE), ('32', '2', 2, '202', TRUE),
('32', '2', 3, '301', TRUE), ('32', '2', 3, '302', TRUE),
('32', '2', 4, '401', TRUE), ('32', '2', 4, '402', TRUE),
('32', '2', 5, '501', TRUE), ('32', '2', 5, '502', TRUE),
('32', '2', 6, '601', TRUE), ('32', '2', 6, '602', TRUE),
-- Escalera 3
('32', '3', 1, '101', TRUE), ('32', '3', 1, '102', TRUE),
('32', '3', 2, '201', TRUE), ('32', '3', 2, '202', TRUE),
('32', '3', 3, '301', TRUE), ('32', '3', 3, '302', TRUE),
('32', '3', 4, '401', TRUE), ('32', '3', 4, '402', TRUE),
('32', '3', 5, '501', TRUE), ('32', '3', 5, '502', TRUE),
('32', '3', 6, '601', TRUE), ('32', '3', 6, '602', TRUE);

SELECT '✅ 144 apartamentos creados con escaleras numeradas' AS status;

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

INSERT INTO apartamento_usuario (apartamento_id, usuario_id, cantidad_controles, activo) VALUES
(61, 4, 2, TRUE),   -- María González - Bloque 29-1-501
(73, 5, 1, TRUE),   -- Roberto Díaz - Bloque 29-2-501
(85, 6, 2, TRUE),   -- Laura Morales - Bloque 30-1-101
(9, 7, 2, TRUE),    -- Juan Pérez - Bloque 27-1-501
(10, 8, 3, TRUE),   -- Ana Rodríguez - Bloque 27-1-502
(25, 9, 1, TRUE),   -- Carlos Martínez - Bloque 27-2-501
(49, 10, 2, TRUE);  -- Elena Silva - Bloque 28-1-101

SELECT '✅ Clientes reasignados a apartamentos' AS status;

-- ============================================================================
-- 5. ASIGNAR ALGUNOS CONTROLES A CLIENTES
-- ============================================================================

UPDATE controles_estacionamiento SET apartamento_usuario_id = 1, estado = 'activo', fecha_asignacion = NOW() WHERE numero_control_completo IN ('15A', '15B');
UPDATE controles_estacionamiento SET apartamento_usuario_id = 2, estado = 'activo', fecha_asignacion = NOW() WHERE numero_control_completo = '127A';
UPDATE controles_estacionamiento SET apartamento_usuario_id = 3, estado = 'activo', fecha_asignacion = NOW() WHERE numero_control_completo IN ('45A', '45B');
UPDATE controles_estacionamiento SET apartamento_usuario_id = 4, estado = 'activo', fecha_asignacion = NOW() WHERE numero_control_completo IN ('30A', '30B');
UPDATE controles_estacionamiento SET apartamento_usuario_id = 5, estado = 'activo', fecha_asignacion = NOW() WHERE numero_control_completo IN ('50A', '50B', '51A');
UPDATE controles_estacionamiento SET apartamento_usuario_id = 6, estado = 'activo', fecha_asignacion = NOW() WHERE numero_control_completo = '60A';
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

SELECT '✅ ACTUALIZACIÓN COMPLETADA - ESCALERAS CON NÚMEROS' AS status;
