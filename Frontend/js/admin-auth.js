/*
 * ============================================================
 * SEGURIDAD GENERAL DEL PANEL DE ADMINISTRACIÓN
 * ============================================================
 *
 * Hermano de auth.js, pero pensado exclusivamente para las
 * paginas dentro de Frontend/admin/. La diferencia principal
 * es que aca no alcanza con estar logueado: hace falta además
 * tener el rol de Administrador. Un Operador que intente entrar
 * a mano a una URL del panel admin es redirigido al login igual
 * que si no estuviera logueado.
 */


/*
 * Verifica que exista una sesión valida
 * y que el usuario sea Administrador.
 */
async function verificarAdministrador() {

    try {

        // La diferencia clave respecto a auth.js es que se le agrega "?rol=Administrador" a la URL. 
        // Del lado del servidor, sesion.php ve ese parámetro y, si el rol de
        // la sesión no es exactamente ese, responde con un 403
        // en vez de dejar pasar a cualquier usuario logueado.

        const respuesta = await fetch(
            '../../API/usuarios/sesion.php?rol=Administrador',
            {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store'
            }
        );


        if (!respuesta.ok) {

            // Aca entran tanto el 401 (no logueado) como el 403
            // (logueado pero no es Administrador): en los dos
            // casos, esta pagina no es para esa persona, así
            // que se la redirige al login sin distinguir el
            // motivo exacto.
            window.location.replace(
                '../login.html'
            );

            return false;
        }


        const datos =                       // toma el JSON y lo convierte en un objeto de JS
            await respuesta.json();


            // Comprueba si; el backend dijo que la operacion no fue correcta,
            // el usuario NO está autenticado,
            // el usuario esta logueado, pero NO esta autorizado para esta sección
            // y comprueba que realmente exista la información del usuario.
            // Si falla al usuario lo manda al login

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
        * Busca el nombre y rol en el HTML, comprueba que exista el elemento y los muestra
        * Si hay error, muestra y avisa de este y manda al login
        */

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
 *
 * Misma logica que cerrarSesion() en auth.js. Esta duplicada
 * aca para que en vez de compartirse entre los dos archivos,
 * admin-auth.js pueda incluirse solo sin depender de auth.js.
 */

async function cerrarSesion() {

    try {

        const respuesta = await fetch(              // Hace una peticion al backend con metodo POST, sin usar cache, manteniendo las credenciales asociadas a las cookies

            '../../API/usuarios/logout.php',
            {
                method: 'POST',
                credentials: 'same-origin',
                cache: 'no-store'
            }
        );


        const datos =                               // Obtiene el JSON y lo convierte en un objeto de JavaScript.
            await respuesta.json();


        if (datos.ok === true) {                    // Si el backend fue exitoso entra al if -> mand al usuario al login

            /*
             * Reemplaza la página actual por el login.
             */
            window.location.replace(
                '../login.html'
            );

            return;
        }


        alert(                                  // Si no fue exitoso
            datos.mensaje ||                    // Muestra el mensaje enviado por el servidor
            'No se pudo cerrar la sesión.'      // Si no existe, muestra un mensaje de respaldo
        );


    } catch (error) {                           // Si ocurre un error al comunicarse con el servidor,

        console.error(                          // se informa el problema en consola y al usuario.
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
 *
 * A diferencia de auth.js (donde cada pagina HTML decide cuando
 * llamar a verificarSesion() en su propio <script>), aca la
 * verificacion se dispara sola apenas termina de cargar el DOM.
 * Asi, cualquier pagina nueva que se agregue dentro de
 * Frontend/admin/ queda protegida con solo incluir este script,
 * sin tener que escribir codigo adicional en cada una.
 */

document.addEventListener(          // Evento que sucede cuando el HTML termina de cargarse
    'DOMContentLoaded',
    async () => {

        /*
         * Busca en el HTML el elemento que tenga el botón de logout.
         */
        const botonLogout =
            document.getElementById(
                'btnLogout'
            );


        /*
         * Comprueba que existe y si existe conecta el botón con cerrarSesion().
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
