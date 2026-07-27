-- ============================================
-- Script: Generar Solicitud de Pago de Prueba
-- Propósito: Crear un pago pendiente de aprobación para pruebas
-- ============================================

-- Primero, verificar que existan los datos necesarios
SELECT 'Verificando datos necesarios...' as mensaje;

-- Obtener un apartamento_usuario activo
SET @apartamento_usuario_id = (
    SELECT au.id 
    FROM apartamento_usuario au
    JOIN usuarios u ON u.id = au.usuario_id
    WHERE au.activo = TRUE 
    AND u.rol = 'cliente'
    LIMIT 1
);

-- Obtener la tasa BCV más reciente
SET @tasa_cambio_id = (
    SELECT id 
    FROM tasa_cambio_bcv 
    ORDER BY fecha_registro DESC 
    LIMIT 1
);

-- Obtener el valor de la tasa
SET @tasa_bcv = (
    SELECT tasa_usd_bs 
    FROM tasa_cambio_bcv 
    WHERE id = @tasa_cambio_id
);

-- Verificar que tenemos los datos necesarios
SELECT 
    CASE 
        WHEN @apartamento_usuario_id IS NULL THEN 'ERROR: No hay apartamentos con usuarios activos'
        WHEN @tasa_cambio_id IS NULL THEN 'ERROR: No hay tasas BCV registradas'
        ELSE 'OK: Datos encontrados correctamente'
    END as verificacion,
    @apartamento_usuario_id as apartamento_usuario_id,
    @tasa_cambio_id as tasa_cambio_id,
    @tasa_bcv as tasa_bcv;

-- Generar número de recibo único
SET @numero_recibo = CONCAT('TEST-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', LPAD(FLOOR(RAND() * 10000), 4, '0'));

-- Datos del pago de prueba
SET @monto_usd = 5.00; -- Monto en dólares
SET @monto_bs = @monto_usd * @tasa_bcv; -- Calcular monto en bolívares

-- Insertar el pago pendiente de aprobación
INSERT INTO pagos (
    apartamento_usuario_id,
    numero_recibo,
    monto_usd,
    monto_bs,
    tasa_cambio_id,
    moneda_pago,
    metodo_pago,
    referencia_pago,
    fecha_pago,
    comprobante_ruta,
    estado_comprobante,
    registrado_por,
    fecha_registro,
    google_sheets_sync
) VALUES (
    @apartamento_usuario_id,
    @numero_recibo,
    @monto_usd,
    @monto_bs,
    @tasa_cambio_id,
    'Bs',                           -- Moneda de pago
    'transferencia',                -- Método de pago
    'REF-TEST-123456',             -- Referencia de prueba
    NOW(),                          -- Fecha de pago
    '/uploads/comprobantes/test_comprobante.pdf', -- Ruta ficticia
    'pendiente',                    -- ESTADO PENDIENTE (esto es clave)
    @apartamento_usuario_id,        -- Registrado por el mismo usuario
    NOW(),
    FALSE
);

-- Obtener el ID del pago insertado
SET @pago_id = LAST_INSERT_ID();

-- Obtener mensualidades pendientes del apartamento_usuario
SET @mensualidad_id = (
    SELECT id 
    FROM mensualidades 
    WHERE apartamento_usuario_id = @apartamento_usuario_id 
    AND estado = 'pendiente'
    ORDER BY mes_correspondiente ASC
    LIMIT 1
);

-- Asociar el pago con una mensualidad
INSERT INTO pago_mensualidad (pago_id, mensualidad_id)
VALUES (@pago_id, @mensualidad_id);

-- Mostrar resultado
SELECT 
    '✅ Solicitud de pago de prueba creada exitosamente' as resultado,
    @pago_id as pago_id,
    @numero_recibo as numero_recibo,
    @monto_usd as monto_usd,
    @monto_bs as monto_bs,
    'pendiente' as estado,
    'El pago aparecerá en /operador/pagos-pendientes' as nota;

-- Consulta para verificar el pago creado
SELECT 
    p.id,
    p.numero_recibo,
    p.monto_usd,
    p.monto_bs,
    p.estado_comprobante,
    p.fecha_pago,
    u.nombre_completo as cliente,
    u.email,
    CONCAT(a.bloque, '-', a.numero_apartamento) as apartamento
FROM pagos p
JOIN apartamento_usuario au ON au.id = p.apartamento_usuario_id
JOIN usuarios u ON u.id = au.usuario_id
JOIN apartamentos a ON a.id = au.apartamento_id
WHERE p.id = @pago_id;
