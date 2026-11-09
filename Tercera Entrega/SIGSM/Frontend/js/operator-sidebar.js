
document.addEventListener("DOMContentLoaded", () => {

    const sidebar = document.querySelector(".sidebar");

    if (!sidebar) {
        return;
    }

    const enlaces = [
        {
            nombre: "Dashboard",
            ruta: "dashboard/index.html"
        },
        {
            nombre: "Traslados",
            ruta: "traslados/index.html"
        },
        {
            nombre: "Documentos",
            ruta: "documentos/index.html"
        },
        {
            nombre: "Panel Administrador",
            ruta: "admin/index.html"
        }
    ];

    const rutaActual = window.location.pathname;
    const segmentos = rutaActual.split("/").filter(Boolean);
    const carpetaActual = segmentos[segmentos.length - 2] || "";

    const base = [
        "admin",
        "dashboard",
        "documentos",
        "traslados"
    ].includes(carpetaActual) ? "../" : "./";

    let html = `
        <h1 class="logo">SIGSM</h1>
        <ul class="menu">
    `;

    enlaces.forEach(enlace => {

        const rutaCompleta = base + enlace.ruta;

        const rutaDestino = new URL(
            rutaCompleta,
            window.location.href
        ).pathname.replace(/\/+$/, "");

        const rutaPagina = rutaActual.replace(/\/+$/, "");
        const activo = rutaPagina === rutaDestino;

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

    sidebar.innerHTML = html;

});
