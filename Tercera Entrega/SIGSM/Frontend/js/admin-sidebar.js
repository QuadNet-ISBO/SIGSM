document.addEventListener("DOMContentLoaded", () => {

    const sidebar = document.querySelector(".sidebar");

    if (!sidebar) {
        return;
    }

    /*
     * Determinar la ruta base según la carpeta actual.
     */

    const rutaActual = window.location.pathname;

    const segmentos = rutaActual.split("/").filter(Boolean);

    const paginaActual =
        segmentos[segmentos.length - 1] || "index.html";

    const carpetaActual =
        segmentos[segmentos.length - 2] || "";

    const carpetasFrontend = [
        "admin",
        "dashboard",
        "documentos",
        "encuestas",
        "traslados"
    ];

    const estaEnSubcarpeta =
        carpetasFrontend.includes(carpetaActual);

    const base = estaEnSubcarpeta ? "../" : "./";


    /*
     * Enlaces del menú.
     */

    const enlaces = [

        {
            seccion: "Dashboard",
            nombre: "Dashboard",
            ruta: "dashboard/index.html"
        },

        {
            seccion: "Traslados",
            nombre: "Ver traslados",
            ruta: "traslados/index.html"
        },

        {
            seccion: "Documentos",
            nombre: "Ver documentos",
            ruta: "documentos/index.html"
        },

        {
            seccion: "Indice",
            nombre: "Ver índice",
            ruta: "admin/index.html"
        },

        {
            seccion: "Encuestas",
            nombre: "Ver encuestas",
            ruta: "admin/encuestas.html"
        },

        {
            seccion: "Encuestas",
            nombre: "Crear encuesta",
            ruta: "admin/encuesta_crear.html"
        },

        {
            seccion: "Funcionarios",
            nombre: "Ver funcionarios",
            ruta: "admin/funcionarios.html"
        },

        {
            seccion: "Funcionarios",
            nombre: "Agregar funcionario",
            ruta: "admin/funcionarios_crear.html"
        },

        {
            seccion: "Vehículos",
            nombre: "Ver vehículos",
            ruta: "admin/vehiculos.html"
        },

        {
            seccion: "Vehículos",
            nombre: "Agregar vehículo",
            ruta: "admin/vehiculos_crear.html"
        },

        {
            seccion: "Categorías",
            nombre: "Ver categorías",
            ruta: "admin/categorias.html"
        },

        {
            seccion: "Categorías",
            nombre: "Agregar categoría",
            ruta: "admin/categorias_crear.html"
        },

        {
            seccion: "Categorías",
            nombre: "Agregar subcategoría",
            ruta: "admin/subcategorias_crear.html"
        },

        {
            seccion: "Usuarios",
            nombre: "Gestionar usuarios",
            ruta: "admin/usuarios.html"
        }

    ];


    /*
     * Construir el HTML del menú.
     */

    let html = `
        <h1 class="logo">SIGSM</h1>
        <ul class="menu">
    `;

    let seccionAnterior = "";


    enlaces.forEach(enlace => {

        if (enlace.seccion !== seccionAnterior) {

            html += `
                <li class="menu-section">
                    ${enlace.seccion}
                </li>
            `;

            seccionAnterior = enlace.seccion;

        }


        const rutaCompleta = base + enlace.ruta;


        /*
         * Comparar las rutas completas para marcar
         * únicamente el enlace de la página actual.
         */

        const rutaNormalizada =
            rutaActual.replace(/\/+$/, "");

        const destinoNormalizado = new URL(
            rutaCompleta,
            window.location.href
        ).pathname.replace(/\/+$/, "");

        const activo =
            rutaNormalizada === destinoNormalizado;


        html += `
            <li>
                <a
                    href="${rutaCompleta}"
                    ${activo ? 'class="active"' : ""}
                >
                    ${enlace.nombre}
                </a>
            </li>
        `;

    });


    html += "</ul>";


    /*
     * Insertar el menú en la página.
     */

    sidebar.innerHTML = html;

});