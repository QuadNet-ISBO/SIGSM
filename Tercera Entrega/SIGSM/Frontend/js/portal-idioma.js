/*
 * Traducción exclusiva de la interfaz pública del Portal SIGSM.
 * No traduce información recibida de la base de datos.
 */
(() => {
    "use strict";

    const traducciones = {
        "Portal del Paciente": "Patient Portal",
        "Hospital de Clínicas": "Hospital de Clínicas",
        "INFORMACIÓN PARA PACIENTES": "INFORMATION FOR PATIENTS",
        "Bienvenido al Portal del Paciente": "Welcome to the Patient Portal",
        "Encontrá de forma rápida y sencilla las indicaciones, recomendaciones y documentos informativos del Hospital de Clínicas.": "Quickly and easily find guidance, recommendations and information documents from Hospital de Clínicas.",
        "No necesitás usuario ni contraseña. Seleccioná un servicio y luego el subservicio que estás buscando.": "No username or password required. Select a service and then the subservice you need.",
        "Consultar información": "View information",
        "Servicios": "Services",
        "Seleccioná el servicio sobre el que necesitás información.": "Select the service you need information about.",
        "Buscar servicio": "Search for a service",
        "Ej.: Cardiología, Imagenología...": "E.g. Cardiology, Medical Imaging...",
        "Cargando servicios...": "Loading services...",
        "No hay servicios disponibles para consultar.": "No services are available.",
        "No se pudieron cargar los servicios. Intentá recargar la página.": "Could not load services. Try reloading the page.",
        "Subservicios": "Subservices",
        "Seleccioná el tipo de información que necesitás consultar.": "Select the type of information you need.",
        "Seleccioná un servicio.": "Select a service.",
        "Cargando subservicios...": "Loading subservices...",
        "Este servicio no tiene documentos disponibles por el momento.": "This service has no documents available at the moment.",
        "No se pudieron cargar los subservicios. Intentá nuevamente.": "Could not load subservices. Please try again.",
        "Servicio": "Service",
        "Subservicio": "Subservice",
        "Documento": "Document",
        "Consultá el documento directamente desde el portal.": "View the document directly on the portal.",
        "Este documento se puede visualizar directamente desde el portal. Por seguridad, no está disponible para descarga.": "This document can be viewed directly on the portal. For security reasons, it cannot be downloaded.",
        "Cargando documento...": "Loading document...",
        "No hay un documento disponible para este subservicio.": "No document is available for this subservice.",
        "Tu opinión nos importa": "Your opinion matters",
        "Luego de consultar el documento, podés completar una breve encuesta.": "After viewing the document, you can complete a short survey.",
        "La encuesta aparecerá aquí después de consultar el documento.": "The survey will appear here after viewing the document.",
        "Cargando encuesta...": "Loading survey...",
        "No hay una encuesta disponible para este subservicio.": "No survey is available for this subservice.",
        "Esta encuesta todavía no tiene preguntas.": "This survey has no questions yet.",
        "No se pudo cargar la encuesta. Intentá nuevamente.": "Could not load the survey. Please try again.",
        "Enviar respuestas": "Submit responses",
        "Enviando...": "Submitting...",
        "Encuesta enviada": "Survey submitted",
        "Reintentar envío": "Retry submission",
        "¡Gracias! Tus respuestas se guardaron correctamente.": "Thank you! Your responses were saved successfully.",
        "Información importante": "Important information",
        "Sin registro": "No registration",
        "No necesitás crear una cuenta para consultar la información.": "You do not need an account to view the information.",
        "Información general": "General information",
        "Los documentos disponibles son informativos y de uso general.": "The available documents provide general information.",
        "Privacidad": "Privacy",
        "El portal no solicita información personal para consultar documentos.": "The portal does not request personal information to view documents.",
        "Hospital de Clínicas — Sistema SIGSM © 2026": "Hospital de Clínicas — SIGSM System © 2026",
        "Cambiar apariencia": "Change appearance",
        "Activar modo claro": "Switch to light mode",
        "Activar modo oscuro": "Switch to dark mode",
        "Change language": "Change language"
    };

    const originales = new WeakMap();
    const atributosOriginales = new WeakMap();
    const excluidos = [
        ".servicio__texto", ".subservicio", ".ruta",
        "#tituloDocumento", "#formularioEncuesta h3",
        "#formularioEncuesta legend", "#formularioEncuesta label",
        "#visorPDF"
    ].join(",");

    function esContenidoDeBaseDeDatos(elemento) {
        return !!elemento.closest(excluidos);
    }

    function idiomaActual() {
        return window.SIGSMPreferencias?.idiomaActual() || "es";
    }

    function traducirNodo(nodo, idioma) {
        const padre = nodo.parentElement;
        if (!padre || padre.closest("script,style,textarea,code,pre") ||
            esContenidoDeBaseDeDatos(padre)) return;

        const texto = nodo.nodeValue;
        const limpio = texto.replace(/\s+/g, " ").trim();
        if (originales.has(nodo)) {
            const previo = originales.get(nodo);
            const inglesPrevio = traducciones[previo.clave];
            if (texto !== previo.texto && texto.trim() !== inglesPrevio) {
                originales.delete(nodo);
            }
        }
        if (!originales.has(nodo)) {
            if (!Object.prototype.hasOwnProperty.call(traducciones, limpio)) return;
            originales.set(nodo, {
                texto,
                clave: limpio
            });
        }
        const original = originales.get(nodo);
        const nuevo = idioma === "en" ? traducciones[original.clave] : original.clave;
        if (!nuevo) return;

        const prefijo = original.texto.match(/^\s*/)?.[0] || "";
        const sufijo = original.texto.match(/\s*$/)?.[0] || "";
        const resultado = idioma === "en"
            ? prefijo + nuevo + sufijo
            : original.texto;
        if (nodo.nodeValue !== resultado) nodo.nodeValue = resultado;
    }

    function traducirAtributos(elemento, idioma) {
        if (esContenidoDeBaseDeDatos(elemento)) return;
        for (const nombre of ["placeholder", "title", "aria-label"]) {
            if (!elemento.hasAttribute(nombre)) continue;
            let mapa = atributosOriginales.get(elemento);
            if (!mapa) {
                mapa = {};
                atributosOriginales.set(elemento, mapa);
            }
            if (!(nombre in mapa)) {
                const valor = elemento.getAttribute(nombre);
                if (!(valor in traducciones)) continue;
                mapa[nombre] = valor;
            }
            const original = mapa[nombre];
            const valor = idioma === "en" ? traducciones[original] : original;
            if (elemento.getAttribute(nombre) !== valor) {
                elemento.setAttribute(nombre, valor);
            }
        }
    }

    let traduciendo = false;
    function traducirInterfaz() {
        if (traduciendo || !document.body) return;
        traduciendo = true;
        try {
            const idioma = idiomaActual();
            const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
            const nodos = [];
            while (walker.nextNode()) nodos.push(walker.currentNode);
            nodos.forEach(nodo => traducirNodo(nodo, idioma));
            document.querySelectorAll("[placeholder],[title],[aria-label]")
                .forEach(elemento => traducirAtributos(elemento, idioma));
        } finally {
            traduciendo = false;
        }
    }

    document.addEventListener("DOMContentLoaded", () => {
        traducirInterfaz();
        let pendiente = false;
        const observador = new MutationObserver(() => {
            if (pendiente) return;
            pendiente = true;
            requestAnimationFrame(() => {
                pendiente = false;
                traducirInterfaz();
            });
        });
        observador.observe(document.body, {
            childList: true, subtree: true, characterData: true
        });
        document.addEventListener("sigsm:idioma", traducirInterfaz);
    });
})();
