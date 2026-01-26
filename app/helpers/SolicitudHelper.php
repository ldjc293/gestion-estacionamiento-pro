<?php
/**
 * Helper para manejar tipos de solicitud
 * Centraliza la definición de tipos de solicitud para evitar duplicación
 */

class SolicitudHelper
{
    /**
     * Obtener todos los tipos de solicitud disponibles
     */
    public static function getTiposSolicitud(): array
    {
        return [
            'registro_nuevo_usuario' => 'Registro Nuevo Usuario',
            'cambio_cantidad_controles' => 'Cambio Cantidad Controles',
            'suspension_control' => 'Suspensión Control',
            'desactivacion_control' => 'Desactivación Control',
            'desincorporar_control' => 'Desincorporar Control',
            'reportar_perdido' => 'Reportar Control Perdido',
            'agregar_control' => 'Agregar Control',
            'comprar_control' => 'Comprar Control',
            'solicitud_personalizada' => 'Solicitud Personalizada',
            'cambio_estado_control' => 'Cambio Estado Control'
        ];
    }

    /**
     * Obtener información completa de tipos de solicitud (con iconos y colores)
     */
    public static function getTiposSolicitudCompleto(): array
    {
        return [
            'registro_nuevo_usuario' => [
                'label' => 'Registro Nuevo Usuario',
                'icon' => 'person-plus',
                'color' => 'primary'
            ],
            'cambio_cantidad_controles' => [
                'label' => 'Cambio de Cantidad',
                'icon' => 'arrow-left-right',
                'color' => 'info'
            ],
            'suspension_control' => [
                'label' => 'Suspensión',
                'icon' => 'pause-circle',
                'color' => 'warning'
            ],
            'desactivacion_control' => [
                'label' => 'Desactivación',
                'icon' => 'x-circle',
                'color' => 'danger'
            ],
            'desincorporar_control' => [
                'label' => 'Desincorporar Control',
                'icon' => 'dash-circle',
                'color' => 'danger'
            ],
            'reportar_perdido' => [
                'label' => 'Reportar Perdido',
                'icon' => 'exclamation-triangle',
                'color' => 'warning'
            ],
            'agregar_control' => [
                'label' => 'Añadir Control',
                'icon' => 'plus-circle',
                'color' => 'success'
            ],
            'comprar_control' => [
                'label' => 'Comprar Control',
                'icon' => 'cart-plus',
                'color' => 'primary'
            ],
            'solicitud_personalizada' => [
                'label' => 'Solicitud Personalizada',
                'icon' => 'chat-dots',
                'color' => 'info'
            ],
            'cambio_estado_control' => [
                'label' => 'Cambio Estado Control',
                'icon' => 'arrow-repeat',
                'color' => 'secondary'
            ]
        ];
    }

    /**
     * Obtener el color del badge para un tipo de solicitud
     */
    public static function getBadgeColor(string $tipo): string
    {
        $tipos = self::getTiposSolicitudCompleto();
        return $tipos[$tipo]['color'] ?? 'secondary';
    }

    /**
     * Obtener el label para un tipo de solicitud
     */
    public static function getLabel(string $tipo): string
    {
        $tipos = self::getTiposSolicitud();
        return $tipos[$tipo] ?? ucfirst(str_replace('_', ' ', $tipo));
    }

    /**
     * Obtener información completa para un tipo de solicitud
     */
    public static function getTipoInfo(string $tipo): array
    {
        $tipos = self::getTiposSolicitudCompleto();
        return $tipos[$tipo] ?? [
            'label' => ucfirst(str_replace('_', ' ', $tipo)),
            'icon' => 'tag',
            'color' => 'secondary'
        ];
    }
}
?>