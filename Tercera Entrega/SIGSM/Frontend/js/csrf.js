/* Protección CSRF global de las solicitudes fetch del frontend SIGSM3.
 * Se carga antes de los scripts de cada página. No afecta las API públicas
 * que no usan auth.php ni las solicitudes de lectura GET.
 */
(() => {
    'use strict';
    const originalFetch = window.fetch.bind(window);
    const rutaToken = new URL('../../API/seguridad/csrf.php', document.currentScript.src);
    const metodosProtegidos = new Set(['POST', 'PUT', 'PATCH', 'DELETE']);

    window.fetch = async function (entrada, opciones = {}) {
        const metodo = String(opciones.method || (entrada instanceof Request ? entrada.method : 'GET')).toUpperCase();
        const url = new URL(entrada instanceof Request ? entrada.url : entrada, location.href);
        if (!metodosProtegidos.has(metodo) || url.origin !== location.origin) {
            return originalFetch(entrada, opciones);
        }

        const respuestaToken = await originalFetch(rutaToken.href, {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store'
        });
        if (!respuestaToken.ok) {
            throw new Error('No se pudo obtener el token CSRF.');
        }
        const datos = await respuestaToken.json();
        if (!datos.ok || typeof datos.csrf_token !== 'string') {
            throw new Error('Token CSRF no disponible.');
        }

        const cabeceras = new Headers(entrada instanceof Request ? entrada.headers : undefined);
        if (opciones.headers) {
            new Headers(opciones.headers).forEach((valor, nombre) => cabeceras.set(nombre, valor));
        }
        cabeceras.set('X-CSRF-Token', datos.csrf_token);
        return originalFetch(entrada, { ...opciones, headers: cabeceras });
    };
})();
