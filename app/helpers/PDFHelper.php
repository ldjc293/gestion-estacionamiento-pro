<?php
/**
 * PDFHelper - Generación de recibos PDF
 *
 * Utiliza DomPDF para generar recibos oficiales con QR
 */

use Dompdf\Dompdf;
use Dompdf\Options;

class PDFHelper
{
    /**
     * Generar recibo de pago en PDF
     *
     * @param array $datosRecibo Datos del recibo
     * @return string Path al archivo PDF generado
     */
    public static function generateRecibo(array $datosRecibo): string
    {
        // Validar datos requeridos
        $requiredFields = ['numero_recibo', 'fecha_pago', 'cliente_nombre', 'apartamento',
                          'monto_usd', 'monto_bs', 'tasa_cambio', 'moneda_pago', 'meses_pagados'];

        foreach ($requiredFields as $field) {
            if (!isset($datosRecibo[$field])) {
                throw new Exception("Falta campo requerido: $field");
            }
        }

        // Generar código QR
        $qrBase64 = QRHelper::generateForRecibo(
            $datosRecibo['numero_recibo'],
            $datosRecibo['monto_usd'],
            $datosRecibo['fecha_pago']
        );

        // Generar HTML del recibo
        $html = self::getReciboHTML($datosRecibo, $qrBase64);

        // Configurar DomPDF
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'helvetica');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        // Guardar PDF
        $filename = 'recibo_' . $datosRecibo['numero_recibo'] . '.pdf';
        $filepath = RECIBOS_PATH . '/' . $filename;

        file_put_contents($filepath, $dompdf->output());

        writeLog("Recibo generado: $filename", 'info');

        return $filepath;
    }

    /**
     * Generar HTML del recibo
     *
     * @param array $datos Datos del recibo
     * @param string $qrBase64 Código QR en base64
     * @return string HTML completo
     */
    private static function getReciboHTML(array $datos, string $qrBase64): string
    {
        $estacionamiento = ESTACIONAMIENTO_NOMBRE;
        $direccion = ESTACIONAMIENTO_DIRECCION;
        $telefono = ESTACIONAMIENTO_TELEFONO;

        $numeroRecibo = $datos['numero_recibo'];
        $fechaPago = formatDateTime($datos['fecha_pago']);
        $clienteNombre = $datos['cliente_nombre'];
        $apartamento = $datos['apartamento'];
        $montoUSD = formatUSD($datos['monto_usd']);
        $montoBs = formatBs($datos['monto_bs']);
        $tasaCambio = number_format($datos['tasa_cambio'], 2, ',', '.');
        $monedaPago = MONEDAS_PAGO[$datos['moneda_pago']] ?? $datos['moneda_pago'];
        $mesesPagados = $datos['meses_pagados'];
        $controles = $datos['controles'] ?? 'N/A';

        // Datos opcionales
        $esReconexion = $datos['es_reconexion'] ?? false;
        $montoReconexion = $esReconexion ? formatUSD($datos['monto_reconexion_usd']) : null;
        $operador = $datos['operador_nombre'] ?? 'Sistema';
        $notas = $datos['notas'] ?? '';

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo $numeroRecibo</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #1e293b;
        }
        .container {
            width: 100%;
            padding: 30px;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #2563eb;
            font-size: 22pt;
            margin-bottom: 8px;
        }
        .header p {
            font-size: 9pt;
            color: #64748b;
            margin: 3px 0;
        }
        .recibo-title {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
            padding: 15px;
            text-align: center;
            font-size: 18pt;
            font-weight: bold;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-section h3 {
            background: #f1f5f9;
            padding: 8px 12px;
            font-size: 11pt;
            color: #1e40af;
            border-left: 4px solid #2563eb;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table td {
            padding: 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        table td:first-child {
            font-weight: bold;
            width: 40%;
            color: #475569;
        }
        .totales {
            background: #f8fafc;
            padding: 15px;
            border: 2px solid #2563eb;
            border-radius: 5px;
            margin: 20px 0;
        }
        .totales table td {
            border: none;
            padding: 5px;
        }
        .total-final {
            font-size: 14pt;
            font-weight: bold;
            color: #1e40af;
            background: #dbeafe;
            padding: 10px !important;
        }
        .qr-section {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background: #f8fafc;
            border-radius: 5px;
        }
        .qr-section img {
            width: 150px;
            height: 150px;
            margin: 10px auto;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
            text-align: center;
            font-size: 9pt;
            color: #64748b;
        }
        .firma-section {
            margin-top: 50px;
            display: table;
            width: 100%;
        }
        .firma {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding: 20px;
        }
        .firma-line {
            border-top: 1px solid #000;
            margin-top: 60px;
            padding-top: 5px;
        }
        .reconexion-badge {
            background: #fef2f2;
            border: 2px solid #ef4444;
            color: #991b1b;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
            margin: 15px 0;
        }
        .notas {
            background: #fffbeb;
            border-left: 4px solid #f59e0b;
            padding: 10px;
            margin: 15px 0;
            font-size: 9pt;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>$estacionamiento</h1>
            <p>$direccion</p>
            <p>Teléfono: $telefono</p>
        </div>

        <!-- Título del recibo -->
        <div class="recibo-title">
            RECIBO DE PAGO<br>
            $numeroRecibo
        </div>

        <!-- Información del cliente -->
        <div class="info-section">
            <h3>Datos del Cliente</h3>
            <table>
                <tr>
                    <td>Nombre:</td>
                    <td>$clienteNombre</td>
                </tr>
                <tr>
                    <td>Apartamento:</td>
                    <td>$apartamento</td>
                </tr>
                <tr>
                    <td>Controles:</td>
                    <td>$controles</td>
                </tr>
                <tr>
                    <td>Fecha de Pago:</td>
                    <td>$fechaPago</td>
                </tr>
            </table>
        </div>

        <!-- Información del pago -->
        <div class="info-section">
            <h3>Detalles del Pago</h3>
            <table>
                <tr>
                    <td>Meses Pagados:</td>
                    <td>$mesesPagados</td>
                </tr>
                <tr>
                    <td>Forma de Pago:</td>
                    <td>$monedaPago</td>
                </tr>
                <tr>
                    <td>Tasa de Cambio BCV:</td>
                    <td>$tasaCambio Bs/USD</td>
                </tr>
                <tr>
                    <td>Operador:</td>
                    <td>$operador</td>
                </tr>
            </table>
        </div>

        <!-- Reconexión (si aplica) -->
HTML;

        if ($esReconexion) {
            $html .= <<<HTML
        <div class="reconexion-badge">
            ⚠️ INCLUYE CARGO DE RECONEXIÓN: $montoReconexion
        </div>
HTML;
        }

        $html .= <<<HTML
        <!-- Totales -->
        <div class="totales">
            <table>
                <tr>
                    <td>Monto en USD:</td>
                    <td style="text-align: right; font-size: 12pt;">$montoUSD</td>
                </tr>
                <tr>
                    <td>Monto en Bs:</td>
                    <td style="text-align: right; font-size: 12pt;">$montoBs</td>
                </tr>
HTML;

        if ($esReconexion) {
            $html .= <<<HTML
                <tr>
                    <td colspan="2" style="font-size: 9pt; color: #64748b; padding-top: 10px;">
                        * Incluye cargo de reconexión por bloqueo previo
                    </td>
                </tr>
HTML;
        }

        $html .= <<<HTML
            </table>
        </div>

        <!-- Notas -->
HTML;

        if (!empty($notas)) {
            $html .= <<<HTML
        <div class="notas">
            <strong>Notas:</strong> $notas
        </div>
HTML;
        }

        $html .= <<<HTML
        <!-- Código QR -->
        <div class="qr-section">
            <p style="font-size: 9pt; margin-bottom: 10px;">
                Escanea para verificar la autenticidad de este recibo
            </p>
            <img src="$qrBase64" alt="QR Code">
        </div>

        <!-- Firmas -->
        <div class="firma-section">
            <div class="firma">
                <div class="firma-line">Recibido por</div>
            </div>
            <div class="firma">
                <div class="firma-line">Entregado por</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Este recibo fue generado electrónicamente y es válido sin firma.</p>
            <p>Número de Recibo: $numeroRecibo | Fecha de Emisión: $fechaPago</p>
            <p style="margin-top: 10px; font-size: 8pt;">
                Para verificar la autenticidad de este recibo, escanee el código QR o visite nuestro sistema.
            </p>
        </div>
    </div>
</body>
</html>
HTML;

        return $html;
    }

    /**
     * Forzar descarga de PDF
     *
     * @param string $filepath Ruta al archivo PDF
     * @param string $filename Nombre para la descarga
     */
    public static function download(string $filepath, string $filename): void
    {
        if (!file_exists($filepath)) {
            throw new Exception("Archivo PDF no encontrado: $filepath");
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        readfile($filepath);
        exit;
    }

    /**
     * Mostrar PDF en navegador (inline)
     *
     * @param string $filepath Ruta al archivo PDF
     */
    public static function display(string $filepath): void
    {
        if (!file_exists($filepath)) {
            throw new Exception("Archivo PDF no encontrado: $filepath");
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($filepath) . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        readfile($filepath);
        exit;
    }
}
