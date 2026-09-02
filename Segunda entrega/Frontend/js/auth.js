/*
 * ============================================================
 * SEGURIDAD GENERAL DEL SISTEMA
 * ============================================================
 *
 * Este archivo se incluye en las páginas "normales" del sistema
 * (dashboard, traslados, documentos): las que puede ver tanto
 * un Operador como un Administrador. Para el panel exclusivo
 * de administración existe un archivo, admin-auth.js
 * que hace básicamente lo mismo pero exigiendo además el rol
 * de Administrador.
 */

/*
 * ============================================================
 * VERIFICAR SESIÓN
 * ============================================================
 */

async function verificarSesion() {

    try {

        const respuesta =
            await fetch(
                '../../API/usuarios/sesion.php',
                {
                    method: 'GET',

                    // "same-origin" hace que el navegador mande la cookie de sesión (PHPSESSID) junto con
                    // este request. Sin esto, el fetch iría "sin identificarse" y el backend nunca sabría de qué sesión se trata.

                    credentials:
                        'same-origin',

                    // Evita que el navegador use una respuesta guardada en caché.
                    cache:
                        'no-store'
                }
            );


        /*
         * ----------------------------------------------------
         * SESIÓN NO AUTORIZADA
         * ----------------------------------------------------
         *
         * Un 401 significa que sesion.php no encontró una
         * sesión activa (nunca se logueó, o la sesión venció).
         * En ese caso mandamos a la persona de vuelta al login.
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
         *
         * Cualquier otro codigo que no sea "todo OK" (por
         * ejemplo un 500 si el servidor tuvo un problema
         * interno) lo tratamos igual, por las dudas, no dejamos
         * a la persona navegando, la mandamos al login también.
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
         * 
         * Toma el cuerpo de la respuesta HTTP y lo convierte desde JSON a un objeto que JavaScript puede utilizar.
         */

        const datos =
            await respuesta.json();


        /*
         * ----------------------------------------------------
         * VALIDAR RESPUESTA
         * ----------------------------------------------------
         * 
         * Aca esta comprobando que la respuesta tenga la estructura esperada, como;
         * 
         * ok -> no es true
         * autenticado -> no es true
         * no existe usuario
         * 
         * redirigir al login.
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
         *
         * Busca en el HTML de la página actual el elemento
         * con clase "user" dentro del header, y le pone el
         * nombre completo del usuario logueado.
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
         *
         * Recién aca, una vez que se confirmo que la sesión es
         * valida, se activa el boton de "Salir" de la pagina.
         */

        configurarLogout();


        /*
         * ----------------------------------------------------
         * SESIÓN VÁLIDA
         * ----------------------------------------------------
         */

        return true;


    } catch (error) {

        // Si el fetch en si falla (por ejemplo, no hay
        // conexión con el servidor), lo trata como si la
        // sesión no fuera valida.

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
 */

function configurarLogout() {

    const botonLogout =                 // Busca el boton btnLogout
        document.getElementById(
            'btnLogout'
        );


    if (!botonLogout) {

        return;

    }

    if (                                                    // dataset permite acceder a los atributos HTML que comienzan con data-
        botonLogout.dataset.logoutConfigurado ===       
        'true'
    ) {

        return;

    }


    botonLogout.dataset.logoutConfigurado =             // La función sabe que ya fue configurada porque la primera vez guarda una 
                                                        // marca data-logout-configurado="true" en el botón; 
                                                        // cuando vuelve a ejecutarse, revisa esa marca antes de agregar otro evento.
        'true';


    botonLogout.addEventListener(                   // Escucha el evento y ejecuta la funcion
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

    const botonLogout =                         // Busca en el HTML el elemento que tenga al boton
        document.getElementById(
            'btnLogout'
        );


    /*
     * Deshabilitamos el boton mientras se procesa el logout,
     * para que si alguien hace doble click no se disparen dos
     * requests de logout al mismo tiempo.
     */

    if (botonLogout) {              // Comprueba si existe el boton y si existe lo deshabilita

        botonLogout.disabled =
            true;

    }


    try {

        const respuesta =                               // Hace una peticion al backend con metodo POST, sin usar cache, 
                                                        // manteniendo las credenciales asociadas a las cookies
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

        const datos =                                       // Convierte la respuesta en JSON
            await respuesta.json();


        /*
         * ----------------------------------------------------
         * LOGOUT CORRECTO
         * ----------------------------------------------------
         * 
         * Comprueba que la respuesta HTTP sea correcta y que el backned confirme que el logout salio bien
         * entonces manda al usuario al login
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
         *
         * Si por algun motivo el logout no salio bien, se le
         * avisa a la persona y vuelve a habilitar el boton para que pueda intentar de nuevo.
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
     * Si hubo error, vuelve a habilitar
     * el boton.
     */

    if (botonLogout) {

        botonLogout.disabled =
            false;

    }

}
