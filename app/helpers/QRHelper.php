<?php
/**
 * QRHelper - Generación de códigos QR
 *
 * Utiliza chillerlan/php-qrcode para generar códigos QR en recibos
 */

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class QRHelper
{
    /**
     * Generar código QR como imagen base64
     *
     * @param string $data Datos a codificar
     * @param int $size Tamaño del QR (píxeles)
     * @return string Base64 data URI
     */
    public static function generate(string $data, int $size = 300): string
    {
        try {
            $options = new QROptions([
                'version'      => 5,
                'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
                'eccLevel'     => QRCode::ECC_L,
                'scale'        => 10,
                'imageBase64'  => true,
            ]);

            $qrcode = new QRCode($options);
            return $qrcode->render($data);

        } catch (Exception $e) {
            writeLog("Error al generar QR: " . $e->getMessage(), 'error');
            return '';
        }
    }

    /**
     * Generar QR para recibo de pago
     * Contiene: número de recibo, monto, fecha, hash de verificación
     *
     * @param string $numeroRecibo Número de recibo
     * @param float $montoUSD Monto en USD
     * @param string $fecha Fecha del pago
     * @return string Base64 data URI
     */
    public static function generateForRecibo(string $numeroRecibo, float $montoUSD, string $fecha): string
    {
        $urlVerificacion = url("verificar-recibo/$numeroRecibo");

        // Generar hash de verificación
        $hash = self::generateVerificationHash($numeroRecibo, $montoUSD, $fecha);

        // Datos a codificar (URL de verificación con parámetros)
        $data = $urlVerificacion . "?hash=" . $hash;

        return self::generate($data, 250);
    }

    /**
     * Generar hash de verificación para anti-falsificación
     *
     * @param string $numeroRecibo Número de recibo
     * @param float $montoUSD Monto
     * @param string $fecha Fecha
     * @return string Hash SHA256
     */
    private static function generateVerificationHash(string $numeroRecibo, float $montoUSD, string $fecha): string
    {
        $data = $numeroRecibo . '|' . $montoUSD . '|' . $fecha . '|' . APP_KEY;
        return hash('sha256', $data);
    }

    /**
     * Verificar hash de recibo
     *
     * @param string $numeroRecibo Número de recibo
     * @param float $montoUSD Monto
     * @param string $fecha Fecha
     * @param string $hash Hash a verificar
     * @return bool
     */
    public static function verifyReciboHash(string $numeroRecibo, float $montoUSD, string $fecha, string $hash): bool
    {
        $expectedHash = self::generateVerificationHash($numeroRecibo, $montoUSD, $fecha);
        return hash_equals($expectedHash, $hash);
    }

    /**
     * Guardar QR como archivo PNG
     *
     * @param string $data Datos a codificar
     * @param string $filepath Ruta donde guardar
     * @return bool
     */
    public static function saveToFile(string $data, string $filepath): bool
    {
        try {
            $options = new QROptions([
                'version'    => 5,
                'outputType' => QRCode::OUTPUT_IMAGE_PNG,
                'eccLevel'   => QRCode::ECC_L,
                'scale'      => 10,
            ]);

            $qrcode = new QRCode($options);
            $qrcode->render($data, $filepath);

            return file_exists($filepath);

        } catch (Exception $e) {
            writeLog("Error al guardar QR: " . $e->getMessage(), 'error');
            return false;
        }
    }
}
