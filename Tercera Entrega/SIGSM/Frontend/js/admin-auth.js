/*
 * ============================================================
 * SEGURIDAD GENERAL DEL PANEL DE ADMINISTRACIÓN
 * ============================================================
 */


/*
 * Verifica que exista una sesión válida
 * y que el usuario sea Administrador.
 */
async function verificarAdministrador() {

    try {

        const respuesta = await fetch(
            '../../API/usuarios/sesion.php?rol=Administrador',
            {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store'
            }
        );


        if (!respuesta.ok) {

            window.location.replace(
                '../login.html'
            );

            return false;
        }


        const datos =
            await respuesta.json();


        if (
            !datos.ok ||
            datos.autenticado !== true ||
            datos.autorizado !== true ||
            !datos.usuario
        ) {

            window.location.replace(
                '../login.html'
            );

            return false;
        }


        /*
         * Nombre del usuario.
         */
        const elementoUsuario =
            document.querySelector(
                '.header .user'
            );


        /*
         * Rol del usuario.
         */
        const elementoRol =
            document.querySelector(
                '.header .role'
            );


        if (elementoUsuario) {

            elementoUsuario.textContent =
                `${datos.usuario.nombre} ${datos.usuario.apellido}`;

        }


        if (elementoRol) {

            elementoRol.textContent =
                datos.usuario.rol;

        }


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
 * CERRAR SESIÓN
 * ============================================================
 */

async function cerrarSesion() {

    try {

        const respuesta = await fetch(
            '../../API/usuarios/logout.php',
            {
                method: 'POST',
                credentials: 'same-origin',
                cache: 'no-store'
            }
        );


        const datos =
            await respuesta.json();


        if (datos.ok === true) {

            /*
             * Reemplaza la página actual por el login.
             */
            window.location.replace(
                '../login.html'
            );

            return;
        }


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

}


/*
 * ============================================================
 * INICIALIZACIÓN
 * ============================================================
 */

document.addEventListener(
    'DOMContentLoaded',
    async () => {

        /*
         * Busca el botón de logout.
         */
        const botonLogout =
            document.getElementById(
                'btnLogout'
            );


        /*
         * Conecta el botón con cerrarSesion().
         */
        if (botonLogout) {

            botonLogout.addEventListener(
                'click',
                cerrarSesion
            );

        }


        /*
         * Verifica la sesión.
         */
        await verificarAdministrador();

    }
);