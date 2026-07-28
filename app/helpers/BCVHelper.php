<?php
/**
 * BCVHelper - Servicio unificado de consulta y actualización de la Tasa BCV
 */
require_once __DIR__ . '/../../config/database.php';

class BCVHelper
{
    /**
     * Consultar tasa de cambio en vivo desde el sitio oficial del BCV o APIs de respaldo
     *
     * @return float|null Tasa USD/Bs o null si falla
     */
    public static function consultarTasaBCV(): ?float
    {
        // 1. Intentar scraping directo del sitio del BCV
        $tasa = self::consultarBcvOficial();
        if ($tasa && $tasa > 0) {
            return $tasa;
        }

        // 2. Fallback: API pública de tasas BCV
        $tasaApi = self::consultarBcvApiFallback();
        if ($tasaApi && $tasaApi > 0) {
            return $tasaApi;
        }

        return null;
    }

    /**
     * Scraping oficial desde bcv.org.ve
     */
    private static function consultarBcvOficial(): ?float
    {
        try {
            $url = 'https://www.bcv.org.ve/';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

            $html = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200 || !$html) {
                if (function_exists('writeLog')) {
                    writeLog("cURL BCV scraping falló con HTTP Code: $httpCode", 'warning');
                }
                return null;
            }

            $patterns = [
                '/<strong>D[oó]lar.*?<\/strong>.*?<strong[^>]*>\s*([\d,\.]+)\s*<\/strong>/is',
                '/<div[^>]*class="[^"]*moneda[^"]*"[^>]*>.*?USD.*?<strong[^>]*>\s*([\d,\.]+)\s*<\/strong>/is',
                '/USD.*?<strong[^>]*>\s*([\d,\.]+)\s*<\/strong>/is',
                '/<div[^>]*id="dolar"[^>]*>.*?<strong[^>]*>\s*([\d,\.]+)\s*<\/strong>/is',
                '/<td[^>]*>.*?USD.*?<\/td>.*?<td[^>]*>\s*([\d,\.]+)\s*<\/td>/is'
            ];

            foreach ($patterns as $i => $pattern) {
                if (preg_match($pattern, $html, $matches)) {
                    $tasaStr = trim($matches[1]);
                    $tasaStr = str_replace('.', '', $tasaStr);
                    $tasaStr = str_replace(',', '.', $tasaStr);

                    $tasa = floatval($tasaStr);

                    if ($tasa > 0) {
                        if (function_exists('writeLog')) {
                            writeLog("Tasa BCV obtenida exitosamente de bcv.org.ve: $tasa Bs/USD", 'info');
                        }
                        return $tasa;
                    }
                }
            }
        } catch (\Throwable $e) {
            if (function_exists('writeLog')) {
                writeLog("Excepción en scraping de BCV: " . $e->getMessage(), 'error');
            }
        }

        return null;
    }

    /**
     * API Fallback en caso de que bcv.org.ve tenga Cloudflare o esté en mantenimiento
     */
    private static function consultarBcvApiFallback(): ?float
    {
        $apis = [
            'https://pydolarve.org/api/v1/dollar?page=bcv',
            'https://ve.dolarapi.com/v1/dolares/oficial'
        ];

        foreach ($apis as $apiUrl) {
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $apiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode === 200 && $response) {
                    $data = json_decode($response, true);
                    // DolarApi: 'promedio'
                    if (isset($data['promedio']) && is_numeric($data['promedio'])) {
                        return floatval($data['promedio']);
                    }
                    // pydolarve: 'monedas'['usd']['promedio']
                    if (isset($data['monedas']['usd']['promedio']) && is_numeric($data['monedas']['usd']['promedio'])) {
                        return floatval($data['monedas']['usd']['promedio']);
                    }
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * Consultar y actualizar la tasa BCV en la base de datos
     *
     * @param string $fuente Origen de la actualización ('Manual', 'BCV Automático', 'Cron Diario', etc.)
     * @param int|null $usuarioId ID del usuario que solicita (opcional)
     * @return array
     */
    public static function actualizarTasaBCV(string $fuente = 'BCV Automático', ?int $usuarioId = null): array
    {
        $tasa = self::consultarTasaBCV();

        if (!$tasa || $tasa <= 0) {
            return [
                'success' => false,
                'message' => 'No se pudo obtener la tasa del BCV. Verifique su conexión o intente más tarde.'
            ];
        }

        try {
            $sql = "INSERT INTO tasa_cambio_bcv (tasa_usd_bs, fecha_registro, registrado_por, fuente)
                    VALUES (?, NOW(), ?, ?)";
            
            Database::execute($sql, [$tasa, $usuarioId, $fuente]);

            $sqlUltima = "SELECT tasa_usd_bs, fecha_registro, fuente 
                         FROM tasa_cambio_bcv ORDER BY fecha_registro DESC LIMIT 1";
            $ultimaTasa = Database::fetchOne($sqlUltima);

            $fechaFormateada = date('d/m/Y h:i A', strtotime($ultimaTasa['fecha_registro']));

            if (function_exists('writeLog')) {
                writeLog("Tasa BCV actualizada a $tasa Bs/USD ($fuente)", 'info');
            }

            return [
                'success' => true,
                'message' => "Tasa BCV actualizada correctamente a " . number_format($tasa, 2, '.', '') . " Bs/USD",
                'tasa' => $tasa,
                'tasa_formateada' => number_format($tasa, 2, '.', ''),
                'fecha_registro' => $ultimaTasa['fecha_registro'],
                'fecha_formateada' => $fechaFormateada,
                'fuente' => $fuente
            ];

        } catch (\Throwable $e) {
            if (function_exists('writeLog')) {
                writeLog("Error al guardar la tasa BCV: " . $e->getMessage(), 'error');
            }

            return [
                'success' => false,
                'message' => 'Error al guardar la tasa en la base de datos: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verificar si la tasa registrada es de un día anterior y actualizarla en segundo plano
     */
    public static function verificarYActualizarDiario(): void
    {
        try {
            $sql = "SELECT fecha_registro FROM tasa_cambio_bcv ORDER BY fecha_registro DESC LIMIT 1";
            $ultima = Database::fetchOne($sql);

            $debeActualizar = false;

            if (!$ultima || !isset($ultima['fecha_registro'])) {
                $debeActualizar = true;
            } else {
                $fechaTasa = date('Y-m-d', strtotime($ultima['fecha_registro']));
                $hoy = date('Y-m-d');
                if ($fechaTasa < $hoy) {
                    $debeActualizar = true;
                }
            }

            if ($debeActualizar) {
                self::actualizarTasaBCV('Auto Check Diario');
            }

        } catch (\Throwable $e) {
            // Ignorar silenciosamente en peticiones de usuario
        }
    }
}
