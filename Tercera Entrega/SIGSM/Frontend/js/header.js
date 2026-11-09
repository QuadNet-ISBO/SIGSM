
document.addEventListener("DOMContentLoaded", () => {

    "use strict";


    /*
     * ============================================================
     * OBTENER CONTENEDOR DEL HEADER
     * ============================================================
     */

    const derecha = document.querySelector(
        ".header .header-right, .portal-header__preferencias"
    );


    /*
     * ============================================================
     * EVITAR CONTROLES DUPLICADOS
     * ============================================================
     */

    if (document.getElementById("controlesPreferencias")) {
        return;
    }


    /*
     * ============================================================
     * CREAR CONTROLES DE PREFERENCIAS
     * ============================================================
     */

    const controles = document.createElement("div");

    controles.id = "controlesPreferencias";

    controles.className = derecha
        ? "header-preferencias"
        : "preferencias-flotantes";


    controles.innerHTML = `

        <button
            type="button"
            class="btn-preferencia"
            id="btnTema"
            title="Cambiar apariencia"
            aria-label="Cambiar apariencia"
        >
            ☾ Oscuro
        </button>

        <button
            type="button"
            class="btn-preferencia btn-idioma"
            id="btnIdioma"
            title="Change language"
            aria-label="Change language"
        >
            EN
        </button>

    `;


    /*
     * ============================================================
     * UBICAR CONTROLES EN EL HEADER
     * ============================================================
     */

    if (derecha) {

        const botonSalir = derecha.querySelector(
            "#btnLogout, .logout"
        );


        // Colocar controles antes del botón Salir.

        if (botonSalir) {

            derecha.insertBefore(
                controles,
                botonSalir
            );

        } else {

            // Si no existe botón Salir, agregar al final.

            derecha.appendChild(controles);

        }

    } else {

        // Si no existe header, utilizar controles flotantes.

        document.body.appendChild(controles);

    }


    /*
     * ============================================================
     * OBTENER SISTEMA DE PREFERENCIAS
     * ============================================================
     */

    const preferencias = window.SIGSMPreferencias;


    /*
     * ============================================================
     * CAMBIAR TEMA
     * ============================================================
     */

    document.getElementById("btnTema").addEventListener(
        "click",
        () => {

            if (!preferencias) {
                return;
            }


            const temaActual =
                preferencias.temaActual();


            const nuevoTema =
                temaActual === "dark"
                    ? "light"
                    : "dark";


            preferencias.aplicarTema(nuevoTema);

        }
    );


    /*
     * ============================================================
     * CAMBIAR IDIOMA
     * ============================================================
     */

    document.getElementById("btnIdioma").addEventListener(
        "click",
        () => {

            if (!preferencias) {
                return;
            }


            const idiomaActual =
                preferencias.idiomaActual();


            const nuevoIdioma =
                idiomaActual === "es"
                    ? "en"
                    : "es";


            preferencias.aplicarIdioma(nuevoIdioma);

        }
    );


    /*
     * ============================================================
     * APLICAR PREFERENCIAS GUARDADAS
     * ============================================================
     */

    if (preferencias) {

        preferencias.aplicarTema(
            preferencias.temaActual()
        );


        preferencias.aplicarIdioma(
            preferencias.idiomaActual()
        );

    }

});
