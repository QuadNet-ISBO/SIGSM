/*
 * ============================================================
 * SEGURIDAD GENERAL DEL SISTEMA
 * ============================================================
 */


/*
 * ============================================================
 * RUTA DEL LOGIN
 * ============================================================
 *
 * Las páginas protegidas se encuentran dentro de:
 *
 * Frontend/dashboard/
 * Frontend/traslados/
 * Frontend/documentos/
 * Frontend/admin/
 *
 * Por eso desde esas carpetas:
 *
 * ../login.html
 *
 */


/*
 * ============================================================
 * VERIFICAR SESIÓN
 * ============================================================
 *
 * Comprueba si existe una sesión PHP válida.
 *
 * No decide el rol.
 *
 * No restringe por rol.
 *
 * Solamente verifica que el usuario esté autenticado.
 *
 */

async function verificarSesion() {

    try {

        const respuesta =
            await fetch(
                '../../API/usuarios/sesion.php',
                {
                    method: 'GET',

                    credentials:
                        'same-origin',

                    cache:
                        'no-store'
                }
            );


        /*
         * ----------------------------------------------------
         * SESIÓN NO AUTORIZADA
         * ----------------------------------------------------
         */

        if (
            respuesta.status === 401
        ) {

            window.location.replace(
                '../login.html'
            );

            return false;

        }


        /*
         * ----------------------------------------------------
         * OTRO ERROR HTTP
         * ----------------------------------------------------
         */

        if (!respuesta.ok) {

            console.error(
                'Error HTTP al verificar sesión:',
                respuesta.status
            );

            window.location.replace(
                '../login.html'
            );

            return false;

        }


        /*
         * ----------------------------------------------------
         * OBTENER JSON
         * ----------------------------------------------------
         */

        const datos =
            await respuesta.json();


        /*
         * ----------------------------------------------------
         * VALIDAR RESPUESTA
         * ----------------------------------------------------
         */

        if (
            datos.ok !== true ||
            datos.autenticado !== true ||
            !datos.usuario
        ) {

            window.location.replace(
                '../login.html'
            );

            return false;

        }


        /*
         * ----------------------------------------------------
         * MOSTRAR NOMBRE
         * ----------------------------------------------------
         */

        const elementoUsuario =
            document.querySelector(
                '.header .user'
            );


        if (elementoUsuario) {

            elementoUsuario.textContent =
                `${datos.usuario.nombre} ${datos.usuario.apellido}`;

        }


        /*
         * ----------------------------------------------------
         * MOSTRAR ROL
         * ----------------------------------------------------
         */

        const elementoRol =
            document.querySelector(
                '.header .role'
            );


        if (elementoRol) {

            elementoRol.textContent =
                datos.usuario.rol;

        }


        /*
         * ----------------------------------------------------
         * CONFIGURAR LOGOUT
         * ----------------------------------------------------
         */

        configurarLogout();


        /*
         * ----------------------------------------------------
         * SESIÓN VÁLIDA
         * ----------------------------------------------------
         */

        return true;


    } catch (error) {

        console.error(
            'Error al verificar la sesión:',
            error
        );


        window.location.replace(
            '../login.html'
        );


        return false;

    }

}



/*
 * ============================================================
 * CONFIGURAR LOGOUT
 * ============================================================
 *
 * Busca el botón:
 *
 * id="btnLogout"
 *
 * y le asigna la función cerrarSesion().
 *
 */

function configurarLogout() {

    const botonLogout =
        document.getElementById(
            'btnLogout'
        );


    if (!botonLogout) {

        return;

    }


    /*
     * Evita registrar el evento
     * más de una vez.
     */

    if (
        botonLogout.dataset.logoutConfigurado ===
        'true'
    ) {

        return;

    }


    botonLogout.dataset.logoutConfigurado =
        'true';


    botonLogout.addEventListener(
        'click',
        cerrarSesion
    );

}



/*
 * ============================================================
 * CERRAR SESIÓN
 * ============================================================
 */

async function cerrarSesion() {

    const botonLogout =
        document.getElementById(
            'btnLogout'
        );


    /*
     * Evita múltiples clics.
     */

    if (botonLogout) {

        botonLogout.disabled =
            true;

    }


    try {

        const respuesta =
            await fetch(
                '../../API/usuarios/logout.php',
                {
                    method: 'POST',

                    credentials:
                        'same-origin',

                    cache:
                        'no-store'
                }
            );


        /*
         * Intentamos obtener JSON.
         */

        const datos =
            await respuesta.json();


        /*
         * ----------------------------------------------------
         * LOGOUT CORRECTO
         * ----------------------------------------------------
         */

        if (
            respuesta.ok &&
            datos.ok === true
        ) {

            window.location.replace(
                '../login.html'
            );

            return;

        }


        /*
         * ----------------------------------------------------
         * ERROR DEL LOGOUT
         * ----------------------------------------------------
         */

        alert(
            datos.mensaje ||
            'No se pudo cerrar la sesión.'
        );


    } catch (error) {

        console.error(
            'Error al cerrar sesión:',
            error
        );


        alert(
            'No se pudo conectar con el servidor.'
        );

    }


    /*
     * Si hubo error, volvemos a habilitar
     * el botón.
     */

    if (botonLogout) {

        botonLogout.disabled =
            false;

    }

}