-- ============================================================================
-- SCRIPT: Corrección de Estructura de Apartamentos y Generación de Controles
-- Descripción: Actualiza la estructura de bloques/escaleras y genera 500 controles
-- ============================================================================

USE estacionamiento_db;

-- ============================================================================
-- 1. LIMPIAR DATOS EXISTENTES DE APARTAMENTOS (OPCIONAL - COMENTAR SI NO DESEA)
-- ============================================================================

-- ADVERTENCIA: Esto eliminará apartamentos existentes y sus relaciones
-- Descomentar solo si desea empezar desde cero

-- DELETE FROM apartamento_usuario;
-- DELETE FROM apartamentos;
-- ALTER TABLE apartamentos AUTO_INCREMENT = 1;
-- ALTER TABLE apartamento_usuario AUTO_INCREMENT = 1;

-- ============================================================================
-- 2. INSERTAR APARTAMENTOS CON ESTRUCTURA CORRECTA
-- ============================================================================

-- Estructura correcta:
-- Bloque 27: 4 escaleras (A, B, C, D)
-- Bloque 28: 1 escalera (A)
-- Bloque 29: 2 escaleras (A, B)
-- Bloque 30: 1 escalera (A)
-- Bloque 31: 1 escalera (A)
-- Bloque 32: 3 escaleras (A, B, C)

-- Formato: Blq-Esc-Nº apto
-- Ejemplo: 27-A-501 (Bloque 27, Escalera A, Apartamento 501)

-- BLOQUE 27 - 4 Escaleras (A, B, C, D)
-- Asumiendo 6 pisos con 2 apartamentos por piso = 48 apartamentos
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

-- BLOQUE 28 - 1 Escalera (A)
-- 6 pisos con 2 apartamentos por piso = 12 apartamentos
INSERT INTO apartamentos (bloque, escalera, piso, numero_apartamento, activo) VALUES
('28', 'A', 1, '101', TRUE), ('28', 'A', 1, '102', TRUE),
('28', 'A', 2, '201', TRUE), ('28', 'A', 2, '202', TRUE),
('28', 'A', 3, '301', TRUE), ('28', 'A', 3, '302', TRUE),
('28', 'A', 4, '401', TRUE), ('28', 'A', 4, '402', TRUE),
('28', 'A', 5, '501', TRUE), ('28', 'A', 5, '502', TRUE),
('28', 'A', 6, '601', TRUE), ('28', 'A', 6, '602', TRUE);

-- BLOQUE 29 - 2 Escaleras (A, B)
-- 6 pisos con 2 apartamentos por piso = 24 apartamentos
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

-- BLOQUE 30 - 1 Escalera (A)
-- 6 pisos con 2 apartamentos por piso = 12 apartamentos
INSERT INTO apartamentos (bloque, escalera, piso, numero_apartamento, activo) VALUES
('30', 'A', 1, '101', TRUE), ('30', 'A', 1, '102', TRUE),
('30', 'A', 2, '201', TRUE), ('30', 'A', 2, '202', TRUE),
('30', 'A', 3, '301', TRUE), ('30', 'A', 3, '302', TRUE),
('30', 'A', 4, '401', TRUE), ('30', 'A', 4, '402', TRUE),
('30', 'A', 5, '501', TRUE), ('30', 'A', 5, '502', TRUE),
('30', 'A', 6, '601', TRUE), ('30', 'A', 6, '602', TRUE);

-- BLOQUE 31 - 1 Escalera (A)
-- 6 pisos con 2 apartamentos por piso = 12 apartamentos
INSERT INTO apartamentos (bloque, escalera, piso, numero_apartamento, activo) VALUES
('31', 'A', 1, '101', TRUE), ('31', 'A', 1, '102', TRUE),
('31', 'A', 2, '201', TRUE), ('31', 'A', 2, '202', TRUE),
('31', 'A', 3, '301', TRUE), ('31', 'A', 3, '302', TRUE),
('31', 'A', 4, '401', TRUE), ('31', 'A', 4, '402', TRUE),
('31', 'A', 5, '501', TRUE), ('31', 'A', 5, '502', TRUE),
('31', 'A', 6, '601', TRUE), ('31', 'A', 6, '602', TRUE);

-- BLOQUE 32 - 3 Escaleras (A, B, C)
-- 6 pisos con 2 apartamentos por piso = 36 apartamentos
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

-- ============================================================================
-- 3. GENERAR 500 CONTROLES (250 posiciones × 2 receptores A/B)
-- ============================================================================

-- Limpiar controles existentes si es necesario
-- DELETE FROM controles_estacionamiento;
-- ALTER TABLE controles_estacionamiento AUTO_INCREMENT = 1;

-- Procedimiento para generar los 500 controles
DELIMITER //

DROP PROCEDURE IF EXISTS generar_500_controles//

CREATE PROCEDURE generar_500_controles()
BEGIN
    DECLARE v_posicion INT DEFAULT 1;
    
    -- Generar controles para posiciones 1 a 250
    WHILE v_posicion <= 250 DO
        -- Receptor A
        INSERT INTO controles_estacionamiento 
            (posicion_numero, receptor, numero_control_completo, apartamento_usuario_id, estado)
        VALUES 
            (v_posicion, 'A', CONCAT(v_posicion, 'A'), NULL, 'vacio');
        
        -- Receptor B
        INSERT INTO controles_estacionamiento 
            (posicion_numero, receptor, numero_control_completo, apartamento_usuario_id, estado)
        VALUES 
            (v_posicion, 'B', CONCAT(v_posicion, 'B'), NULL, 'vacio');
        
        SET v_posicion = v_posicion + 1;
    END WHILE;
    
    SELECT CONCAT('✅ Generados ', (v_posicion - 1) * 2, ' controles (', (v_posicion - 1), ' posiciones × 2 receptores)') AS resultado;
END//

DELIMITER ;

-- Ejecutar el procedimiento
CALL generar_500_controles();

-- ============================================================================
-- 4. VERIFICACIÓN DE RESULTADOS
-- ============================================================================

SELECT '📊 RESUMEN DE ESTRUCTURA' AS '';

SELECT 
    CONCAT('Total de apartamentos: ', COUNT(*)) AS apartamentos,
    CONCAT('Bloque 27: ', SUM(CASE WHEN bloque = '27' THEN 1 ELSE 0 END), ' apartamentos (4 escaleras)') AS bloque_27,
    CONCAT('Bloque 28: ', SUM(CASE WHEN bloque = '28' THEN 1 ELSE 0 END), ' apartamentos (1 escalera)') AS bloque_28,
    CONCAT('Bloque 29: ', SUM(CASE WHEN bloque = '29' THEN 1 ELSE 0 END), ' apartamentos (2 escaleras)') AS bloque_29,
    CONCAT('Bloque 30: ', SUM(CASE WHEN bloque = '30' THEN 1 ELSE 0 END), ' apartamentos (1 escalera)') AS bloque_30,
    CONCAT('Bloque 31: ', SUM(CASE WHEN bloque = '31' THEN 1 ELSE 0 END), ' apartamentos (1 escalera)') AS bloque_31,
    CONCAT('Bloque 32: ', SUM(CASE WHEN bloque = '32' THEN 1 ELSE 0 END), ' apartamentos (3 escaleras)') AS bloque_32
FROM apartamentos;

SELECT 
    CONCAT('Total de controles: ', COUNT(*)) AS total_controles,
    CONCAT('Posiciones únicas: ', COUNT(DISTINCT posicion_numero)) AS posiciones,
    CONCAT('Receptor A: ', SUM(CASE WHEN receptor = 'A' THEN 1 ELSE 0 END)) AS receptor_a,
    CONCAT('Receptor B: ', SUM(CASE WHEN receptor = 'B' THEN 1 ELSE 0 END)) AS receptor_b,
    CONCAT('Controles vacíos: ', SUM(CASE WHEN estado = 'vacio' THEN 1 ELSE 0 END)) AS vacios,
    CONCAT('Controles asignados: ', SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END)) AS asignados
FROM controles_estacionamiento;

-- Verificar escaleras por bloque
SELECT 
    bloque,
    GROUP_CONCAT(DISTINCT escalera ORDER BY escalera) AS escaleras,
    COUNT(DISTINCT escalera) AS cantidad_escaleras,
    COUNT(*) AS total_apartamentos
FROM apartamentos
GROUP BY bloque
ORDER BY bloque;

SELECT '✅ SCRIPT EJECUTADO CORRECTAMENTE' AS status;
