/**
 * confirm-actions.js
 *
 * Manejo de confirmaciones para acciones destructivas
 * - Desactivación de usuarios/apartamentos
 * - Eliminación de registros
 * - Rechazo de comprobantes
 *
 * @version 1.0
 */

/**
 * Confirmar desactivación de usuario
 * @param {string} nombre - Nombre del usuario
 * @returns {boolean}
 */
function confirmarDesactivarUsuario(nombre) {
    return confirm(
        `¿Está seguro de desactivar al usuario "${nombre}"?\n\n` +
        `El usuario no podrá acceder al sistema hasta que sea reactivado.\n\n` +
        `Esta acción puede revertirse posteriormente.`
    );
}

/**
 * Confirmar activación de usuario
 * @param {string} nombre - Nombre del usuario
 * @returns {boolean}
 */
function confirmarActivarUsuario(nombre) {
    return confirm(
        `¿Está seguro de activar al usuario "${nombre}"?\n\n` +
        `El usuario podrá acceder nuevamente al sistema.`
    );
}

/**
 * Confirmar eliminación de usuario (NO RECOMENDADO - usar desactivación)
 * @param {string} nombre - Nombre del usuario
 * @returns {boolean}
 */
function confirmarEliminarUsuario(nombre) {
    return confirm(
        `⚠️ ADVERTENCIA: ¿Está seguro de ELIMINAR permanentemente al usuario "${nombre}"?\n\n` +
        `Esta acción NO puede revertirse.\n` +
        `Se perderán todos los datos asociados al usuario.\n\n` +
        `RECOMENDACIÓN: Use "Desactivar" en lugar de eliminar.`
    );
}

/**
 * Confirmar desactivación de apartamento
 * @param {string} bloque - Bloque del apartamento
 * @param {string} numero - Número de apartamento
 * @returns {boolean}
 */
function confirmarDesactivarApartamento(bloque, numero) {
    return confirm(
        `¿Está seguro de desactivar el apartamento ${bloque}-${numero}?\n\n` +
        `Los controles asociados también serán desactivados.\n\n` +
        `Esta acción puede revertirse posteriormente.`
    );
}

/**
 * Confirmar rechazo de comprobante de pago
 * @param {string} usuario - Nombre del usuario
 * @param {string} monto - Monto del pago
 * @returns {boolean}
 */
function confirmarRechazarComprobante(usuario, monto) {
    return confirm(
        `¿Está seguro de RECHAZAR el comprobante de pago?\n\n` +
        `Usuario: ${usuario}\n` +
        `Monto: ${monto}\n\n` +
        `El usuario será notificado del rechazo y deberá subir un nuevo comprobante.`
    );
}

/**
 * Confirmar bloqueo manual de control
 * @param {string} numeroControl - Número de control
 * @returns {boolean}
 */
function confirmarBloquearControl(numeroControl) {
    return confirm(
        `¿Está seguro de BLOQUEAR el control ${numeroControl}?\n\n` +
        `El control dejará de funcionar inmediatamente.\n` +
        `El usuario deberá pagar la deuda + reconexión para desbloquearlo.`
    );
}

/**
 * Confirmar desbloqueo de control
 * @param {string} numeroControl - Número de control
 * @returns {boolean}
 */
function confirmarDesbloquearControl(numeroControl) {
    return confirm(
        `¿Está seguro de DESBLOQUEAR el control ${numeroControl}?\n\n` +
        `El control volverá a funcionar normalmente.`
    );
}

/**
 * Confirmar eliminación de registro genérico
 * @param {string} tipo - Tipo de registro (ej: "mensualidad", "pago")
 * @param {string} descripcion - Descripción del registro
 * @returns {boolean}
 */
function confirmarEliminarRegistro(tipo, descripcion) {
    return confirm(
        `⚠️ ¿Está seguro de eliminar este ${tipo}?\n\n` +
        `${descripcion}\n\n` +
        `Esta acción NO puede revertirse.`
    );
}

/**
 * Confirmar cambio de rol de usuario
 * @param {string} nombre - Nombre del usuario
 * @param {string} rolActual - Rol actual
 * @param {string} rolNuevo - Rol nuevo
 * @returns {boolean}
 */
function confirmarCambiarRol(nombre, rolActual, rolNuevo) {
    return confirm(
        `¿Está seguro de cambiar el rol del usuario "${nombre}"?\n\n` +
        `Rol actual: ${rolActual}\n` +
        `Rol nuevo: ${rolNuevo}\n\n` +
        `Los permisos del usuario cambiarán inmediatamente.`
    );
}

/**
 * Confirmar reseteo de contraseña
 * @param {string} nombre - Nombre del usuario
 * @returns {boolean}
 */
function confirmarResetearPassword(nombre) {
    return confirm(
        `¿Está seguro de resetear la contraseña de "${nombre}"?\n\n` +
        `Se generará una nueva contraseña temporal.\n` +
        `El usuario deberá cambiarla en su próximo acceso.`
    );
}

/**
 * Prevenir doble envío de formularios
 * Deshabilita el botón submit y muestra mensaje de "Procesando..."
 *
 * @param {HTMLFormElement} form - Formulario
 */
function disableButtonOnSubmit(form) {
    if (!form) return;

    form.addEventListener('submit', function(e) {
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn && !submitBtn.disabled) {
            submitBtn.disabled = true;
            const originalHTML = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Procesando...';

            // Revertir después de 10 segundos como fallback (por si falla el envío)
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalHTML;
            }, 10000);
        }
    });
}

/**
 * Aplicar prevención de doble submit a todos los formularios
 */
document.addEventListener('DOMContentLoaded', function() {
    // Aplicar a todos los formularios de la página
    document.querySelectorAll('form').forEach(function(form) {
        // Solo si NO tiene la clase 'no-auto-disable'
        if (!form.classList.contains('no-auto-disable')) {
            disableButtonOnSubmit(form);
        }
    });

    console.log('✅ Confirmaciones y prevención de doble-submit activadas');
});

/**
 * Confirmar acción genérica
 * @param {string} mensaje - Mensaje de confirmación
 * @returns {boolean}
 */
function confirmar(mensaje) {
    return confirm(mensaje);
}
