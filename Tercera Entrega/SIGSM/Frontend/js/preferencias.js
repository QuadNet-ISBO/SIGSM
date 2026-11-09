(function () {
    "use strict";

    const CLAVES = {
        tema: "sigsm_tema",
        idioma: "sigsm_idioma"
    };

    const traducciones = {
    "Salir": "Log out",
    "Panel Administrador": "Administrator Panel",
    "Panel de Administración": "Administration Panel",
    "Panel de administración": "Administration panel",
    "Dashboard": "Dashboard",
    "Traslados": "Transfers",
    "Ver traslados": "View transfers",
    "Documentos": "Documents",
    "Ver documentos": "View documents",
    "Ver índice": "View index",
    "Encuestas": "Surveys",
    "Ver encuestas": "View surveys",
    "Crear encuesta": "Create survey",
    "Crear Encuesta": "Create Survey",
    "Funcionarios": "Staff",
    "Ver funcionarios": "View staff",
    "Agregar funcionario": "Add staff member",
    "Agregar Funcionario": "Add Staff Member",
    "Vehículos": "Vehicles",
    "Ver vehículos": "View vehicles",
    "Agregar vehículo": "Add vehicle",
    "Agregar Vehículo": "Add Vehicle",
    "Categorías": "Categories",
    "Ver categorías": "View categories",
    "Crear categoría": "Create category",
    "Crear Categoría": "Create Category",
    "Crear subcategoría": "Create subcategory",
    "Crear Subcategoría": "Create Subcategory",
    "Subcategoría": "Subcategory",
    "Subcategorías": "Subcategories",
    "Usuarios": "Users",
    "Gestionar usuarios": "Manage users",
    "Gestionar Usuarios": "Manage Users",
    "Gestionar": "Manage",
    "Editar": "Edit",
    "Volver": "Back",
    "Cancelar": "Cancel",
    "Guardar cambios": "Save changes",
    "Nombre": "Name",
    "Apellido": "Last name",
    "Estado": "Status",
    "Estado laboral": "Employment status",
    "Estado operativo": "Operational status",
    "Tipo": "Type",
    "Teléfono": "Phone",
    "Email": "Email",
    "Usuario": "User",
    "Rol": "Role",
    "Contraseña": "Password",
    "Matrícula": "License plate",
    "Nombre del documento": "Document name",
    "Origen": "Origin",
    "Destino": "Destination",
    "Prioridad": "Priority",
    "Activo": "Active",
    "Inactivo": "Inactive",
    "Activa": "Active",
    "Desvinculado": "Disassociated",
    "Desvinculada": "Disassociated",
    "Retirada": "Withdrawn",
    "Finalizado": "Completed",
    "Regreso": "Return",
    "Chofer": "Driver",
    "Enfermero": "Nurse",
    "Administrador": "Administrator",
    "Operador": "Operator",
    "Matutino": "Morning",
    "Diurno": "Daytime",
    "Vespertino": "Evening",
    "Baja": "Low",
    "Media": "Medium",
    "Alta": "High",
    "Todos los estados": "All statuses",
    "Seleccione un rol": "Select a role",
    "Seleccione un horario": "Select a shift",
    "Seleccione un tipo": "Select a type",
    "Seleccioná una categoría primero": "Select a category first",
    "Seleccioná una prioridad": "Select a priority",
    "Seleccionar categoría": "Select category",
    "Buscar categoría": "Search category",
    "Seleccionar categoría...": "Select category...",
    "Seleccionar subcategoría...": "Select subcategory...",
    "Categoría": "Category",
    "Categoría:": "Category:",
    "Subcategoría:": "Subcategory:",
    "Título": "Title",
    "Título:": "Title:",
    "Título de la encuesta": "Survey title",
    "ID": "ID",
    "CI": "ID number",
    "Horario": "Shift",
    "Encuestas registradas": "Registered surveys",
    "Buscar encuestas": "Search surveys",
    "Resultados": "Results",
    "Importante:": "Important:",
    "Volver a encuestas": "Back to surveys",
    "Cargando...": "Loading...",
    "Cargando categorías...": "Loading categories...",
    "Cargando encuestas...": "Loading surveys...",
    "Cargando funcionarios...": "Loading staff...",
    "Cargando usuarios...": "Loading users...",
    "Cargando vehículos...": "Loading vehicles...",
    "Cargando tipos...": "Loading types...",
    "Cargando choferes...": "Loading drivers...",
    "Cargando enfermeros...": "Loading nurses...",
    "Cargando resultados...": "Loading results...",
    "Cargando datos...": "Loading data...",
    "Preparando el resumen del día...": "Preparing today's summary...",
    "Bienvenido": "Welcome",
    "Actualizar": "Refresh",
    "Solicitudes pendientes": "Pending requests",
    "Esperando aprobación": "Awaiting approval",
    "Traslados en proceso": "Transfers in progress",
    "Aprobados y en ejecución": "Approved and in progress",
    "Finalizados hoy": "Completed today",
    "Completados durante el día": "Completed today",
    "Prioridad alta": "High priority",
    "Pendientes o en proceso": "Pending or in progress",
    "Disponibles en el portal": "Available on the portal",
    "Visibles para los pacientes": "Visible to patients",
    "Fuera del portal": "Off the portal",
    "No visibles para el público": "Not visible to the public",
    "Categorías activas": "Active categories",
    "Grupos principales": "Main groups",
    "Subcategorías activas": "Active subcategories",
    "Organización de documentos": "Document organization",
    "Crear y editar usuarios del sistema": "Create and edit system users",
    "Choferes y enfermeros": "Drivers and nurses",
    "Ambulancias y estado": "Ambulances and status",
    "Categorías y subcategorías": "Categories and subcategories",
    "Crear traslado": "Create transfer",
    "Observaciones": "Notes",
    "OBSERVACIONES": "NOTES",
    "ORIGEN": "ORIGIN",
    "DESTINO": "DESTINATION",
    "FECHA Y HORA": "DATE AND TIME",
    "CHOFER": "DRIVER",
    "ENFERMERO": "NURSE",
    "PRIORIDAD": "PRIORITY",
    "BOTONES": "BUTTONS",
    "VEHÍCULO": "VEHICLE",
    "Vehículo": "Vehicle",
    "Fecha y hora prevista de destino": "Expected date and time of arrival",
    "Datos generales": "General information",
    "Nueva encuesta": "New survey",
    "Completá los datos y agregá las preguntas con sus opciones de respuesta.": "Complete the information and add questions with their answer options.",
    "Preguntas": "Questions",
    "Agregar pregunta": "Add question",
    "Todavía no agregaste preguntas.": "You haven't added any questions yet.",
    "Cada pregunta debe tener al menos dos opciones de respuesta.": "Each question must have at least two answer options.",
    "Primero seleccioná una categoría": "Select a category first",
    "Cada subcategoría puede tener una sola encuesta activa.": "Each subcategory can have only one active survey.",
    "Consultar encuesta": "View survey",
    "Consultá el estado, la categoría y la subcategoría de la encuesta seleccionada.": "View the status, category and subcategory of the selected survey.",
    "Datos de la encuesta": "Survey information",
    "Acciones disponibles": "Available actions",
    "Reemplazar encuesta": "Replace survey",
    "Retirar encuesta": "Withdraw survey",
    "Seleccionar encuesta": "Select survey",
    "Lista de funcionarios": "Staff list",
    "Flota de vehículos": "Vehicle fleet",
    "Usuarios del sistema": "System users",
    "Crear usuario": "Create user",
    "Crear Usuario": "Create User",
    "Crear funcionario": "Create staff member",
    "Crear vehículo": "Create vehicle",
    "Editar categoría": "Edit category",
    "Editar Funcionario": "Edit Staff Member",
    "Editar Subcategoría": "Edit Subcategory",
    "Editar Usuario": "Edit User",
    "Editar Vehículo": "Edit Vehicle",
    "Editar Documento": "Edit Document",
    "Subir Documento": "Upload Document",
    "Editar subcategorías": "Edit subcategories",
    "Desvincular": "Disassociate",
    "Desvincular categoría": "Disassociate category",
    "Desvincular una subcategoría no elimina sus datos. Puede volver a activarse posteriormente.": "Disassociating a subcategory does not delete its data. It can be reactivated later.",
    "+ Agregar funcionario": "+ Add staff member",
    "+ Agregar vehículo": "+ Add vehicle",
    "+ Crear categoría": "+ Create category",
    "+ Crear encuesta": "+ Create survey",
    "+ Crear subcategoría": "+ Create subcategory",
    "+ Crear usuario": "+ Create user",
    "+ Nuevo traslado": "+ New transfer",
    "+ Nuevo traslado de paciente": "+ New patient transfer",
    "+ Subir documento": "+ Upload document",
    "Administración del Sistema": "System Administration",
    "Ambulancia": "Ambulance",
    "Ambulancias": "Ambulances",
    "Abortado": "Aborted",
    "Aprobado": "Approved",
    "Archivo PDF": "PDF file",
    "Archivo actual": "Current file",
    "Avanzar": "Continue",
    "Aviso": "Notice",
    "Bienvenido al Portal del Paciente": "Welcome to the Patient Portal",
    "Buscador": "Search",
    "Buscar documentos": "Search documents",
    "Buscar servicio": "Search service",
    "Cancelado": "Cancelled",
    "Cancelar / Abortar": "Cancel / Abort",
    "Cargando ambulancias...": "Loading ambulances...",
    "Cargando documento...": "Loading document...",
    "Cargando documentos...": "Loading documents...",
    "Cargando servicios...": "Loading services...",
    "Cargando traslados...": "Loading transfers...",
    "Categorías del sistema": "System categories",
    "Cerrar visor": "Close viewer",
    "Chofer y Enfermero": "Driver and Nurse",
    "Consultar encuestas": "View surveys",
    "Consultar información": "View information",
    "Consultá el documento directamente desde el portal.": "View the document directly from the portal.",
    "Creado": "Created",
    "Crear traslado de carga / otro": "Create cargo / other transfer",
    "Crear traslado de paciente": "Create patient transfer",
    "Cédula del paciente": "Patient ID number",
    "Descripción": "Description",
    "Datos de la categoría": "Category information",
    "Detalle de la encuesta": "Survey details",
    "Documento": "Document",
    "El portal no solicita información personal para consultar documentos.": "The portal does not request personal information to view documents.",
    "Eliminar": "Delete",
    "En curso": "In progress",
    "Fecha": "Date",
    "Fecha prevista": "Expected date",
    "Filtrar por estado": "Filter by status",
    "Filtros": "Filters",
    "Gestionar Encuestas": "Manage Surveys",
    "Gestionar categorías": "Manage categories",
    "Gestión de Encuestas": "Survey Management",
    "Gestión de Traslados": "Transfer Management",
    "Gestión de Usuarios": "User Management",
    "Gestión de encuestas": "Survey management",
    "Información": "Information",
    "Información de encuesta": "Survey information",
    "Información general": "General information",
    "Información importante": "Important information",
    "Ingresar": "Sign in",
    "Ingresar como Administrador": "Sign in as Administrator",
    "Ingresar como Operador": "Sign in as Operator",
    "La encuesta aparecerá aquí después de consultar el documento.": "The survey will appear here after viewing the document.",
    "Llegada": "Arrival",
    "Llegado": "Arrived",
    "Los documentos disponibles son informativos y de uso general.": "The available documents are for general informational use.",
    "Luego de consultar el documento, podés completar una breve encuesta.": "After viewing the document, you can complete a short survey.",
    "No necesitás crear una cuenta para consultar la información.": "You do not need an account to view the information.",
    "Nombre completo": "Full name",
    "Paciente": "Patient",
    "Piso": "Floor",
    "Portal del Paciente": "Patient Portal",
    "Privacidad": "Privacy",
    "Quién recibe": "Recipient",
    "Reemplazar archivo PDF (opcional)": "Replace PDF file (optional)",
    "Resultados de encuestas": "Survey results",
    "Retornado": "Returned",
    "Retorno": "Return",
    "Sala": "Room",
    "Salida": "Departure",
    "Seleccioná el servicio sobre el que necesitás información.": "Select the service you need information about.",
    "Seleccioná el tipo de información que necesitás consultar.": "Select the type of information you need.",
    "Seleccioná un archivo PDF de hasta 10 MB.": "Select a PDF file up to 10 MB.",
    "Seleccioná un servicio.": "Select a service.",
    "Servicio": "Service",
    "Servicios": "Services",
    "Si no seleccionás otro archivo, se conserva el actual.": "If you do not select another file, the current one will be kept.",
    "Sin registro": "No record",
    "Solicitado": "Requested",
    "Subir documento": "Upload document",
    "Subservicio": "Subservice",
    "Subservicios": "Subservices",
    "Tabla de documentos": "Documents table",
    "Todas las categorías": "All categories",
    "Todas las subcategorías": "All subcategories",
    "Todos": "All",
    "Tu opinión nos importa": "Your opinion matters",
    "Ver PDF": "View PDF",
    "Ver portal": "View portal",
    "Visor PDF": "PDF viewer",
    "Visualizar documento": "View document"
};

    function obtenerPreferencia(clave, defecto) {
        try { return localStorage.getItem(clave) || defecto; }
        catch (error) { return defecto; }
    }

    function aplicarTema(tema) {
        const valor = tema === "dark" ? "dark" : "light";
        document.documentElement.dataset.theme = valor;
        document.documentElement.style.colorScheme = valor;
        try { localStorage.setItem(CLAVES.tema, valor); } catch (error) {}
        actualizarBotonTema();
    }

    function idiomaActual() {
        return obtenerPreferencia(CLAVES.idioma, "es");
    }

    function traducirTexto(texto) {
        const limpio = texto.replace(/\s+/g, " ").trim();
        if (!limpio || idiomaActual() !== "en") return texto;
        const inversas = window.SIGSMPreferencias && window.SIGSMPreferencias.inversas;
        const tabla = idiomaActual() === "en" ? traducciones : (inversas || {});
        const traduccion = tabla[limpio];
        if (!traduccion) return texto;
        const inicio = texto.search(/\S|$/);
        const final = texto.search(/\s*$/);
        return texto.slice(0, inicio) + traduccion + textSuffix(texto, final);
    }

    function textSuffix(texto, final) {
        return texto.slice(final);
    }

    function aplicarIdioma(idioma) {
        const valor = idioma === "en" ? "en" : "es";
        try { localStorage.setItem(CLAVES.idioma, valor); } catch (error) {}
        document.documentElement.lang = valor;
        if (!document.querySelector(".portal-header")) traducirPagina();
        actualizarBotonIdioma();
        document.dispatchEvent(new CustomEvent("sigsm:idioma", { detail: { idioma: valor } }));
    }

    function traducirPagina() {
        if (!document.body) return;
        const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
        const nodos = [];
        while (walker.nextNode()) nodos.push(walker.currentNode);
        nodos.forEach(nodo => {
            if (nodo.parentElement && !nodo.parentElement.closest("script,style,textarea,code,pre")) {
                const nuevoTexto = traducirTexto(nodo.nodeValue);
                if (nuevoTexto !== nodo.nodeValue) nodo.nodeValue = nuevoTexto;
            }
        });

        document.querySelectorAll("[placeholder],[title],[aria-label]").forEach(elemento => {
            ["placeholder", "title", "aria-label"].forEach(atributo => {
                if (elemento.hasAttribute(atributo)) {
                    const original = elemento.getAttribute(atributo);
                    const tabla = idiomaActual() === "en" ? traducciones : inversas;
                    const traducido = tabla[original];
                    if (traducido) elemento.setAttribute(atributo, traducido);
                }
            });
        });
    }

    function actualizarBotonTema() {
        const boton = document.getElementById("btnTema");
        if (!boton) return;
        const oscuro = document.documentElement.dataset.theme === "dark";
        boton.textContent = oscuro ? "☀ " + (idiomaActual() === "en" ? "Light" : "Claro")
                                  : "☾ " + (idiomaActual() === "en" ? "Dark" : "Oscuro");
        boton.setAttribute("aria-label", oscuro ? "Activar modo claro" : "Activar modo oscuro");
    }

    function actualizarBotonIdioma() {
        const boton = document.getElementById("btnIdioma");
        if (boton) boton.textContent = idiomaActual() === "en" ? "ES" : "EN";
    }

    const inversas = {};
    Object.entries(traducciones).forEach(([es, en]) => {
        if (!inversas[en]) inversas[en] = es;
    });

    window.SIGSMPreferencias = {
        inversas,
        aplicarTema,
        aplicarIdioma,
        traducirPagina,
        idiomaActual,
        temaActual: () => document.documentElement.dataset.theme || "light"
    };

    const temaInicial = obtenerPreferencia(CLAVES.tema, "light");
    const idiomaInicial = obtenerPreferencia(CLAVES.idioma, "es");
    document.documentElement.dataset.theme = temaInicial;
    document.documentElement.style.colorScheme = temaInicial;
    document.documentElement.lang = idiomaInicial;

    document.addEventListener("DOMContentLoaded", () => {
        aplicarTema(temaInicial);
        aplicarIdioma(idiomaInicial);

        // Traduce también textos que se agregan dinámicamente desde JavaScript.
        let programado = false;
        const observador = new MutationObserver(() => {
            if (programado) return;
            programado = true;
            requestAnimationFrame(() => {
                traducirPagina();
                programado = false;
            });
        });
        if (!document.querySelector(".portal-header")) {
            observador.observe(document.body, { childList: true, subtree: true, characterData: true });
        }
    });
})();
