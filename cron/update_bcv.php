<?php
/**
 * Cron Task: Actualización diaria de la Tasa BCV
 * 
 * Uso vía CLI: php cron/update_bcv.php
 */

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/app/helpers/logger.php';
require_once ROOT_PATH . '/app/helpers/BCVHelper.php';

echo "[" . date('Y-m-d H:i:s') . "] Ejecutando actualización diaria de Tasa BCV...\n";

$result = BCVHelper::actualizarTasaBCV('Cron Diario');

if ($result['success']) {
    echo "[" . date('Y-m-d H:i:s') . "] ✅ ÉXITO: " . $result['message'] . "\n";
    exit(0);
} else {
    echo "[" . date('Y-m-d H:i:s') . "] ❌ ERROR: " . $result['message'] . "\n";
    exit(1);
}
