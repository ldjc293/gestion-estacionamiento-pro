<?php
/**
 * DateHelper - Funciones para formatear fechas en español
 */

/**
 * Obtener nombre del mes en español
 * 
 * @param int $mes Número del mes (1-12)
 * @return string
 */
function getNombreMesEspanol(int $mes): string
{
    $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    
    return $meses[$mes] ?? 'Desconocido';
}

/**
 * Obtener nombre del mes en español (versión corta)
 * 
 * @param int $mes Número del mes (1-12)
 * @return string
 */
function getNombreMesCorto(int $mes): string
{
    $meses = [
        1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
        5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
        9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
    ];
    
    return $meses[$mes] ?? 'Desc';
}

/**
 * Formatear fecha en español
 * 
 * @param string $fecha Fecha en formato Y-m-d o timestamp
 * @param string $formato Formato: 'completo', 'mes_anio', 'corto', 'largo'
 * @return string
 */
function formatearFechaEspanol(string $fecha, string $formato = 'corto'): string
{
    if (empty($fecha)) {
        return '-';
    }
    
    $timestamp = is_numeric($fecha) ? $fecha : strtotime($fecha);
    
    if (!$timestamp) {
        return '-';
    }
    
    $dia = date('d', $timestamp);
    $mes = (int)date('m', $timestamp);
    $anio = date('Y', $timestamp);
    $hora = date('H:i', $timestamp);
    
    $nombreMes = getNombreMesEspanol($mes);
    
    switch ($formato) {
        case 'completo':
            // Ejemplo: "Lunes, 30 de Noviembre de 2025"
            $diaSemana = getDiaSemanaEspanol(date('N', $timestamp));
            return "$diaSemana, $dia de $nombreMes de $anio";
            
        case 'largo':
            // Ejemplo: "30 de Noviembre de 2025"
            return "$dia de $nombreMes de $anio";
            
        case 'mes_anio':
            // Ejemplo: "Noviembre 2025"
            return "$nombreMes $anio";
            
        case 'mes_anio_corto':
            // Ejemplo: "Nov 2025"
            $nombreMesCorto = getNombreMesCorto($mes);
            return "$nombreMesCorto $anio";
            
        case 'con_hora':
            // Ejemplo: "30/11/2025 14:30"
            return "$dia/$mes/$anio $hora";
            
        case 'corto':
        default:
            // Ejemplo: "30/11/2025"
            return "$dia/$mes/$anio";
    }
}

/**
 * Obtener nombre del día de la semana en español
 * 
 * @param int $dia Número del día (1=Lunes, 7=Domingo)
 * @return string
 */
function getDiaSemanaEspanol(int $dia): string
{
    $dias = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        7 => 'Domingo'
    ];
    
    return $dias[$dia] ?? 'Desconocido';
}

/**
 * Formatear fecha para mostrar "mes año" en español
 * Útil para mostrar mensualidades
 * 
 * @param string|int $mes Mes (1-12) o fecha completa
 * @param int|null $anio Año (opcional si $mes es fecha completa)
 * @return string
 */
function formatearMesAnio($mes, ?int $anio = null): string
{
    // Si $mes es una fecha completa (string con guiones o timestamp)
    if (is_string($mes) && (strpos($mes, '-') !== false || strpos($mes, '/') !== false)) {
        $timestamp = strtotime($mes);
        $mes = (int)date('m', $timestamp);
        $anio = (int)date('Y', $timestamp);
    } elseif (is_numeric($mes) && $anio === null) {
        // Si es timestamp
        $timestamp = $mes;
        $mes = (int)date('m', $timestamp);
        $anio = (int)date('Y', $timestamp);
    }
    
    $nombreMes = getNombreMesEspanol((int)$mes);
    return "$nombreMes $anio";
}

/**
 * Obtener tiempo relativo en español (hace X tiempo)
 * 
 * @param string $fecha Fecha en formato Y-m-d H:i:s
 * @return string
 */
function tiempoRelativo(string $fecha): string
{
    $timestamp = strtotime($fecha);
    $diferencia = time() - $timestamp;
    
    if ($diferencia < 60) {
        return 'Hace menos de un minuto';
    } elseif ($diferencia < 3600) {
        $minutos = floor($diferencia / 60);
        return "Hace $minutos " . ($minutos == 1 ? 'minuto' : 'minutos');
    } elseif ($diferencia < 86400) {
        $horas = floor($diferencia / 3600);
        return "Hace $horas " . ($horas == 1 ? 'hora' : 'horas');
    } elseif ($diferencia < 604800) {
        $dias = floor($diferencia / 86400);
        return "Hace $dias " . ($dias == 1 ? 'día' : 'días');
    } elseif ($diferencia < 2592000) {
        $semanas = floor($diferencia / 604800);
        return "Hace $semanas " . ($semanas == 1 ? 'semana' : 'semanas');
    } elseif ($diferencia < 31536000) {
        $meses = floor($diferencia / 2592000);
        return "Hace $meses " . ($meses == 1 ? 'mes' : 'meses');
    } else {
        $anios = floor($diferencia / 31536000);
        return "Hace $anios " . ($anios == 1 ? 'año' : 'años');
    }
}
