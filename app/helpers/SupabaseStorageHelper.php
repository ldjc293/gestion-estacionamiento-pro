<?php
/**
 * Helper para interactuar con Supabase Storage
 */
class SupabaseStorageHelper
{
    /**
     * Sube un archivo a Supabase Storage y retorna la URL pública
     *
     * @param string $fileData Datos binarios del archivo
     * @param string $filename Nombre del archivo en el bucket
     * @param string $mimeType Mime type del archivo (e.g. image/jpeg, application/pdf)
     * @return string|null URL pública del archivo subido o null en caso de error
     */
    public static function upload(string $fileData, string $filename, string $mimeType): ?string
    {
        $supabaseUrl = defined('SUPABASE_URL') ? SUPABASE_URL : 'https://feoijalccdmdcpufjuda.supabase.co';
        $supabaseKey = defined('SUPABASE_KEY') ? SUPABASE_KEY : 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImZlb2lqYWxjY2RtZGNwdWZqdWRhIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjkzODc1MjgsImV4cCI6MjA4NDk2MzUyOH0.t8UfNbbs58ooFBQURhVdchPiRS1iVtfqfBBdRKquQMM';
        $bucketName = 'comprobantes';

        $url = $supabaseUrl . '/storage/v1/object/' . $bucketName . '/' . urlencode($filename);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fileData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $supabaseKey,
            'apikey: ' . $supabaseKey,
            'Content-Type: ' . $mimeType
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 || $httpCode === 201) {
            return $supabaseUrl . '/storage/v1/object/public/' . $bucketName . '/' . $filename;
        }

        writeLog("Error al subir archivo a Supabase: HTTP $httpCode - Response: $response", 'error');
        return null;
    }
}
