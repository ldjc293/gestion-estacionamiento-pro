<?php
/**
 * CacheHelper - Sistema de caché simple
 *
 * Implementa caché en memoria y archivo para mejorar performance
 */

class CacheHelper
{
    private static array $memoryCache = [];
    private static string $cacheDir = __DIR__ . '/../../cache/';

    /**
     * Inicializar directorio de caché
     */
    public static function init(): void
    {
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }
    }

    /**
     * Obtener valor del caché
     *
     * @param string $key Clave del caché
     * @return mixed|null Valor cacheado o null si no existe
     */
    public static function get(string $key)
    {
        // Primero verificar caché en memoria
        if (isset(self::$memoryCache[$key])) {
            return self::$memoryCache[$key]['value'];
        }

        // Verificar caché en archivo
        $file = self::getCacheFile($key);
        if (file_exists($file)) {
            $data = unserialize(file_get_contents($file));
            if ($data && $data['expires'] > time()) {
                // Guardar en memoria para acceso rápido
                self::$memoryCache[$key] = $data;
                return $data['value'];
            } else {
                // Cache expirado, eliminar
                unlink($file);
            }
        }

        return null;
    }

    /**
     * Establecer valor en caché
     *
     * @param string $key Clave del caché
     * @param mixed $value Valor a cachear
     * @param int $ttl Tiempo de vida en segundos (default 1 hora)
     */
    public static function set(string $key, $value, int $ttl = 3600): void
    {
        $data = [
            'value' => $value,
            'expires' => time() + $ttl,
            'created' => time()
        ];

        // Guardar en memoria
        self::$memoryCache[$key] = $data;

        // Guardar en archivo
        $file = self::getCacheFile($key);
        file_put_contents($file, serialize($data));
    }

    /**
     * Verificar si existe una clave en caché
     *
     * @param string $key Clave del caché
     * @return bool
     */
    public static function has(string $key): bool
    {
        return self::get($key) !== null;
    }

    /**
     * Eliminar clave del caché
     *
     * @param string $key Clave del caché
     */
    public static function delete(string $key): void
    {
        // Eliminar de memoria
        unset(self::$memoryCache[$key]);

        // Eliminar archivo
        $file = self::getCacheFile($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * Limpiar todo el caché
     */
    public static function clear(): void
    {
        // Limpiar memoria
        self::$memoryCache = [];

        // Limpiar archivos
        $files = glob(self::$cacheDir . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
    }

    /**
     * Obtener archivo de caché para una clave
     *
     * @param string $key Clave del caché
     * @return string Ruta del archivo
     */
    private static function getCacheFile(string $key): string
    {
        return self::$cacheDir . md5($key) . '.cache';
    }

    /**
     * Obtener estadísticas del caché
     *
     * @return array Estadísticas
     */
    public static function getStats(): array
    {
        $files = glob(self::$cacheDir . '*.cache');
        $fileCount = count($files);
        $totalSize = 0;

        foreach ($files as $file) {
            $totalSize += filesize($file);
        }

        return [
            'memory_items' => count(self::$memoryCache),
            'file_items' => $fileCount,
            'total_size_bytes' => $totalSize,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2)
        ];
    }
}

// Inicializar caché al cargar
CacheHelper::init();