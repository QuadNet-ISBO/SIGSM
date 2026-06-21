/* ==========================================================================
   SIGSM — Hospital de Clínicas
   script.js — JavaScript del prototipo.

   Usa únicamente lo visto en el curso:
     · Unidad 0 — const/let, objetos, template literals (``, ${ })
     · Unidad 1 (DOM) — querySelector / querySelectorAll, getElementById,
       innerHTML, textContent, createElement, appendChild, prepend, classList
     · Unidad 2 (Eventos) — addEventListener, 'click', 'submit', preventDefault
     · Unidad 4 (Formularios) — inputs, validación y mensajes de error

   Un mismo archivo sirve a las 4 páginas. Antes de actuar, cada bloque
   comprueba con getElementById/querySelector que el elemento exista, así no
   da error en las páginas donde ese elemento no está.
   ========================================================================== */


/* ──────────────────────────────────────────────────────────────────────
   UTILIDADES
   ────────────────────────────────────────────────────────────────────── */

/* Muestra/oculta un elemento agregando o quitando la clase .oculto
   (definida en el CSS como display:none). Devuelve true si quedó visible. */
function alternarVisibilidad(elemento) {
    elemento.classList.toggle('oculto');
    return !elemento.classList.contains('oculto');
}

/* Marca un campo del formulario en rojo y escribe su mensaje de error.
   El <p> del error tiene id "error-" + el id del campo (ej: error-usuario). */
function marcarError(campo, mensaje) {
    const clasePorTipo = {
        TEXTAREA: 'formulario__textarea--error',
        SELECT: 'formulario__select--error'
    };
    campo.classList.add(clasePorTipo[campo.tagName] || 'formulario__input--error');

    const error = document.getElementById('error-' + campo.id);
    if (error) error.textContent = mensaje;
}

/* Quita el rojo y borra el mensaje de error de un campo. */
function limpiarError(campo) {
    campo.classList.remove('formulario__input--error', 'formulario__textarea--error', 'formulario__select--error');
    const error = document.getElementById('error-' + campo.id);
    if (error) error.textContent = '';
}


/* ──────────────────────────────────────────────────────────────────────
   INDEX.HTML — Login (solo funcionarios)
   El hospital pidió no bloquear la cuenta por intentos fallidos: solo se
   valida que los campos no estén vacíos. Las credenciales son simuladas
   porque la primera entrega no tiene backend.
   ────────────────────────────────────────────────────────────────────── */
const formularioLogin = document.querySelector('.formulario--login');
if (formularioLogin) {
    const campoUsuario = document.getElementById('usuario');
    const campoContrasena = document.getElementById('contrasena');

    formularioLogin.addEventListener('submit', function (evento) {
        evento.preventDefault();
        limpiarError(campoUsuario);
        limpiarError(campoContrasena);

        let valido = true;
        if (campoUsuario.value.trim() === '') {
            marcarError(campoUsuario, 'Ingresá tu nombre de usuario.');
            valido = false;
        }
        if (campoContrasena.value.trim() === '') {
            marcarError(campoContrasena, 'Ingresá tu contraseña.');
            valido = false;
        }
        if (!valido) return;

        // Demostración: admin / admin entra; cualquier otra cosa da error.
        if (campoUsuario.value === 'admin' && campoContrasena.value === 'admin') {
            alert('Inicio de sesión correcto. (Simulación: en producción entra al panel principal)');
        } else {
            marcarError(campoContrasena, 'Usuario o contraseña incorrectos. Probá nuevamente.');
        }
    });
}


/* ──────────────────────────────────────────────────────────────────────
   PORTAL-PACIENTE.HTML
   ────────────────────────────────────────────────────────────────────── */

/* Acordeones (preguntas frecuentes y categorías de documentos).
   Cada cabecera abre/cierra el contenido que indica su atributo
   aria-controls, cambia el ícono + / − y actualiza aria-expanded. */
document.querySelectorAll('.acordeon__cabecera').forEach(function (cabecera) {
    cabecera.addEventListener('click', function () {
        const contenido = document.getElementById(cabecera.getAttribute('aria-controls'));
        const icono = cabecera.querySelector('.acordeon__icono');

        contenido.classList.toggle('acordeon__contenido--abierto');
        const abierto = contenido.classList.contains('acordeon__contenido--abierto');

        cabecera.setAttribute('aria-expanded', abierto);
        if (icono) {
            if (abierto) {
                icono.textContent = '−';
            } else {
                icono.textContent = '+';
            }
        }
    });
});

/* Encuesta de satisfacción: el formulario aparece en línea al tocar el
   botón (se alterna la clase .oculto). Valida departamento y satisfacción. */
const botonAbrirEncuesta = document.getElementById('boton-abrir-encuesta');
const formularioEncuesta = document.getElementById('form-encuesta');

if (botonAbrirEncuesta && formularioEncuesta) {
    botonAbrirEncuesta.addEventListener('click', function () {
        const visible = alternarVisibilidad(formularioEncuesta);
        botonAbrirEncuesta.setAttribute('aria-expanded', visible);
        if (visible) document.getElementById('encuesta-departamento').focus();
    });

    formularioEncuesta.addEventListener('submit', function (evento) {
        evento.preventDefault();
        let valido = true;

        const departamento = document.getElementById('encuesta-departamento');
        limpiarError(departamento);
        if (departamento.value.trim() === '') {
            marcarError(departamento, 'Elegí un departamento.');
            valido = false;
        }

        // Las estrellas de satisfacción son radios: se recorre el grupo
        // buscando el que esté marcado (.checked).
        let satisfaccionElegida = false;
        formularioEncuesta.querySelectorAll('.escala__input').forEach(function (radio) {
            if (radio.checked) satisfaccionElegida = true;
        });
        const errorSatisfaccion = document.getElementById('error-satisfaccion');
        if (!satisfaccionElegida) {
            errorSatisfaccion.textContent = 'Elegí un nivel de satisfacción del 1 al 5.';
            valido = false;
        } else {
            errorSatisfaccion.textContent = '';
        }

        if (!valido) return;

        alert('¡Gracias! Tu respuesta fue enviada de forma anónima.');
        formularioEncuesta.reset();
        formularioEncuesta.classList.add('oculto');
        botonAbrirEncuesta.setAttribute('aria-expanded', false);
    });
}

/* Documentos: "Visualizar" abre el visor (simulado) y "Compartir" arma un
   mensaje de WhatsApp. No hay descarga: por seguridad solo se visualiza.
   Se recorre cada tarjeta de documento y se conectan sus dos botones. */
document.querySelectorAll('.documento').forEach(function (documento) {
    const nombre = documento.querySelector('.documento__nombre').textContent;
    const botonVer = documento.querySelector('.documento__boton');
    const botonCompartir = documento.querySelector('.documento__compartir');

    if (botonVer) {
        botonVer.addEventListener('click', function () {
            alert(`Visualizando: ${nombre}\n(Simulación: en producción se abre el visor de PDF en pantalla).`);
        });
    }
    if (botonCompartir) {
        botonCompartir.addEventListener('click', function () {
            const mensaje = `Te comparto el documento "${nombre}" del Portal del Paciente del Hospital de Clínicas. Entrá escaneando el código QR de la sala.`;
            window.open(`https://wa.me/?text=${encodeURIComponent(mensaje)}`, '_blank');
        });
    }
});

/* Buscador de documentos: filtra las tarjetas por nombre. Las categorías
   con coincidencias se abren solas; las que no tienen, se ocultan. */
const buscadorDocumentos = document.getElementById('buscador-documentos');
const listaCategorias = document.getElementById('lista-categorias-documentos');
const avisoSinResultados = document.getElementById('aviso-sin-resultados');

if (buscadorDocumentos && listaCategorias) {
    buscadorDocumentos.addEventListener('input', function () {
        const termino = buscadorDocumentos.value.trim().toLowerCase();
        let hayResultados = false;

        listaCategorias.querySelectorAll('.acordeon__item').forEach(function (categoria) {
            const cabecera = categoria.querySelector('.acordeon__cabecera');
            const contenido = document.getElementById(cabecera.getAttribute('aria-controls'));
            let coincideAlguno = false;

            categoria.querySelectorAll('.documento').forEach(function (documento) {
                const nombre = documento.querySelector('.documento__nombre').textContent.toLowerCase();
                if (termino === '' || nombre.includes(termino)) {
                    documento.classList.remove('oculto');
                    coincideAlguno = true;
                } else {
                    documento.classList.add('oculto');
                }
            });

            // Ocultar la categoría entera si ninguno de sus documentos coincide.
            if (coincideAlguno) {
                categoria.classList.remove('oculto');
                hayResultados = true;
            } else {
                categoria.classList.add('oculto');
            }

            // Abrir las categorías con coincidencias mientras se busca; al
            // borrar el buscador todas vuelven a quedar cerradas (estado normal).
            const abrir = termino !== '' && coincideAlguno;
            const icono = cabecera.querySelector('.acordeon__icono');
            if (abrir) {
                contenido.classList.add('acordeon__contenido--abierto');
                if (icono) icono.textContent = '−';
            } else {
                contenido.classList.remove('acordeon__contenido--abierto');
                if (icono) icono.textContent = '+';
            }
            cabecera.setAttribute('aria-expanded', abrir);
        });

        if (avisoSinResultados) {
            if (hayResultados || termino === '') {
                avisoSinResultados.classList.add('oculto');
            } else {
                avisoSinResultados.classList.remove('oculto');
            }
        }
    });
}


/* ──────────────────────────────────────────────────────────────────────
   AMBULANCIAS.HTML — Solicitud de traslado
   ────────────────────────────────────────────────────────────────────── */

/* Diccionarios para mostrar texto lindo a partir del value de cada <select>. */
const textoMovilidad = {
    'propios-medios': 'Camina por sus propios medios',
    'silla-ruedas': 'Silla de ruedas',
    camilla: 'Camilla'
};
const textoTipo = {
    paciente: 'Paciente',
    organo: 'Órgano',
    equipamiento: 'Equipamiento médico / insumos'
};
const textoVia = {
    'flota-propia': 'Flota propia',
    'sae-105': 'SAE / 105'
};

const formularioTraslado = document.getElementById('form-traslado');
const tipoTraslado = document.getElementById('tipo-traslado');
const grupoDatosPaciente = document.getElementById('grupo-datos-paciente');
const zonaRoja = document.getElementById('zona-roja');
const vehiculoAsignado = document.getElementById('vehiculo-asignado');
const avisoVehiculo = document.getElementById('aviso-restriccion-vehiculo');

/* Datos del paciente: solo se ven si "Qué se traslada" es Paciente. El
   nombre y la edad no se escriben: se buscan por cédula (en producción se
   leerían de la API del hospital; acá se simulan con un objeto de ejemplo). */
const pacientesDemo = {
    12345678: { nombre: 'Lucía Fernández', edad: '34' },
    87654321: { nombre: 'Mateo Silva', edad: '67' },
    11223344: { nombre: 'Rosa Etchegoyen', edad: '81' }
};

if (tipoTraslado && grupoDatosPaciente) {
    tipoTraslado.addEventListener('change', function () {
        if (tipoTraslado.value === 'paciente') {
            grupoDatosPaciente.classList.remove('oculto');
        } else {
            grupoDatosPaciente.classList.add('oculto');
        }
    });
}

const botonBuscarPaciente = document.getElementById('boton-buscar-paciente');
if (botonBuscarPaciente) {
    botonBuscarPaciente.addEventListener('click', function () {
        const cedula = document.getElementById('paciente-ci').value.trim();
        const campoNombre = document.getElementById('paciente-nombre');
        const campoEdad = document.getElementById('paciente-edad');
        const paciente = pacientesDemo[cedula];

        if (paciente) {
            campoNombre.value = paciente.nombre;
            campoEdad.value = paciente.edad;
        } else {
            campoNombre.value = '';
            campoEdad.value = '';
            alert('No se encontró ningún paciente con esa cédula en el registro del hospital.');
        }
    });
}

/* Restricción de vehículo: con paciente en Zona Roja o traslado de órgano,
   solo se habilitan ambulancias (los autos quedan deshabilitados). */
function actualizarRestriccionVehiculo() {
    if (!vehiculoAsignado) return;
    const soloAmbulancia = (zonaRoja && zonaRoja.checked) || (tipoTraslado && tipoTraslado.value === 'organo');

    vehiculoAsignado.querySelectorAll('option').forEach(function (opcion) {
        if (opcion.getAttribute('data-tipo-vehiculo') === 'auto') {
            opcion.disabled = soloAmbulancia;
            if (soloAmbulancia && opcion.selected) vehiculoAsignado.value = '';
        }
    });

    if (avisoVehiculo) {
        if (soloAmbulancia) {
            avisoVehiculo.classList.remove('oculto');
        } else {
            avisoVehiculo.classList.add('oculto');
        }
    }
}
if (zonaRoja) zonaRoja.addEventListener('change', actualizarRestriccionVehiculo);
if (tipoTraslado) tipoTraslado.addEventListener('change', actualizarRestriccionVehiculo);

if (formularioTraslado) {
    formularioTraslado.addEventListener('submit', function (evento) {
        evento.preventDefault();
        let valido = true;

        // Validar los campos obligatorios. Se recorren todos los campos y se
        // revisa cuáles tienen el atributo required (propiedad .required).
        formularioTraslado.querySelectorAll('.formulario__input, .formulario__select, .formulario__textarea').forEach(function (campo) {
            if (!campo.required) return;
            limpiarError(campo);
            if (campo.value.trim() === '') {
                marcarError(campo, 'Este campo es obligatorio.');
                valido = false;
            }
        });

        // La movilidad son radios: se busca el marcado recorriendo el grupo.
        let movilidadElegida = false;
        formularioTraslado.querySelectorAll('.formulario__radio').forEach(function (radio) {
            if (radio.checked) movilidadElegida = true;
        });
        const errorMovilidad = document.getElementById('error-movilidad');
        if (!movilidadElegida) {
            errorMovilidad.textContent = 'Elegí una opción de movilidad.';
            valido = false;
        } else {
            errorMovilidad.textContent = '';
        }

        if (!valido) return;

        agregarTarjetaTraslado({
            origen: document.getElementById('origen').value,
            destino: `${document.getElementById('destino').value} (Puerta ${document.getElementById('puerta').value})`,
            tipo: textoTipo[tipoTraslado.value],
            via: textoVia[document.getElementById('via-traslado').value],
            diagnostico: document.getElementById('diagnostico').value,
            medico: document.getElementById('medico-receptor').value,
            zonaRoja: zonaRoja.checked
        });

        formularioTraslado.reset();
        grupoDatosPaciente.classList.add('oculto');
        actualizarRestriccionVehiculo();
        alert('Pedido de traslado registrado correctamente.');
    });
}

/* Crea una tarjeta de traslado y la pone primera en la lista. Se arma el
   HTML con un template literal (más legible que concatenar con +). */
function agregarTarjetaTraslado(datos) {
    const lista = document.getElementById('lista-traslados');
    if (!lista) return;

    const tarjeta = document.createElement('article');
    tarjeta.className = 'tarjeta';
    if (datos.zonaRoja) tarjeta.classList.add('tarjeta--zona-roja');

    tarjeta.innerHTML = `
        <h3 class="tarjeta__titulo">${datos.origen} → ${datos.destino}</h3>
        <p class="tarjeta__dato"><span class="tarjeta__etiqueta">Tipo:</span> <span class="badge badge--rol">${datos.tipo}</span></p>
        <p class="tarjeta__dato"><span class="tarjeta__etiqueta">Vía:</span> <span class="badge badge--via">${datos.via}</span></p>
        <p class="tarjeta__dato"><span class="tarjeta__etiqueta">Diagnóstico:</span> ${datos.diagnostico}</p>
        <p class="tarjeta__dato"><span class="tarjeta__etiqueta">Médico que recibe:</span> ${datos.medico}</p>
        <p class="tarjeta__dato"><span class="tarjeta__etiqueta">Estado:</span> <span class="badge badge--pendiente">Pendiente</span></p>
        <div class="tarjeta__acciones"><button class="boton boton--secundario boton--chico" type="button">Ver detalle</button></div>
    `;
    lista.prepend(tarjeta);
}

/* Exportar el pedido a WhatsApp (abre WhatsApp con el mensaje ya escrito). */
const botonWhatsapp = document.getElementById('boton-whatsapp');
if (botonWhatsapp) {
    botonWhatsapp.addEventListener('click', function () {
        const origen = document.getElementById('origen').value || 'sin especificar';
        const destino = document.getElementById('destino').value || 'sin especificar';
        const puerta = document.getElementById('puerta').value || 'sin especificar';
        const diagnostico = document.getElementById('diagnostico').value || 'sin especificar';
        const telefono = document.getElementById('telefono-contacto').value || 'sin especificar';
        const horario = document.getElementById('limite-horario').value || 'sin límite';

        let movilidad = 'sin especificar';
        formularioTraslado.querySelectorAll('.formulario__radio').forEach(function (radio) {
            if (radio.checked) movilidad = textoMovilidad[radio.value];
        });

        const mensaje = `Solicitud de traslado SIGSM
Origen: ${origen}
Destino: ${destino} (Puerta ${puerta})
Diagnóstico: ${diagnostico}
Movilidad: ${movilidad}
Teléfono de contacto: ${telefono}
Límite de horario: ${horario}`;

        window.open(`https://wa.me/?text=${encodeURIComponent(mensaje)}`, '_blank');
    });
}

/* Checklist para habilitar el traslado: se exporta a WhatsApp marcando con
   ✔ / ✘ cada criterio según esté tildado o no. */
const botonChecklist = document.getElementById('boton-whatsapp-checklist');
if (botonChecklist) {
    botonChecklist.addEventListener('click', function () {
        let mensaje = 'Checklist de habilitación de traslado SIGSM\n';

        document.querySelectorAll('#checklist-traslado label').forEach(function (label) {
            const check = label.querySelector('.formulario__checkbox');
            let marca = '✘';
            if (check.checked) marca = '✔';
            mensaje += `${marca} ${label.textContent.trim()}\n`;
        });

        window.open(`https://wa.me/?text=${encodeURIComponent(mensaje)}`, '_blank');
    });
}


/* ──────────────────────────────────────────────────────────────────────
   ADMIN-PANEL.HTML
   ────────────────────────────────────────────────────────────────────── */

/* Visor de documentos: al elegir uno de la lista se marca como activo y se
   muestra su nombre en la barra del visor. */
document.querySelectorAll('.pdf-visor__item').forEach(function (item) {
    item.addEventListener('click', function () {
        document.querySelectorAll('.pdf-visor__item').forEach(function (otro) {
            otro.classList.remove('pdf-visor__item--activo');
        });
        item.classList.add('pdf-visor__item--activo');

        const nombre = item.querySelector('.pdf-visor__item-nombre');
        const barra = document.querySelector('.pdf-visor__nombre-archivo');
        if (nombre && barra) barra.textContent = nombre.textContent.trim();
    });
});

/* Filtro por categoría: muestra solo los documentos de la categoría elegida. */
const filtroCategoria = document.getElementById('filtro-categoria-pdf');
if (filtroCategoria) {
    filtroCategoria.addEventListener('change', function () {
        const categoria = filtroCategoria.value;
        document.querySelectorAll('.pdf-visor__item').forEach(function (item) {
            if (categoria === 'todas' || item.getAttribute('data-categoria') === categoria) {
                item.classList.remove('oculto');
            } else {
                item.classList.add('oculto');
            }
        });
    });
}

/* Crear una nueva categoría: se agrega como opción a los dos <select>. */
const botonNuevaCategoria = document.getElementById('boton-nueva-categoria');
if (botonNuevaCategoria) {
    botonNuevaCategoria.addEventListener('click', function () {
        const nombre = prompt('Nombre de la nueva categoría de documentos:');
        if (!nombre) return;

        [document.getElementById('categoria-nuevo-pdf'), document.getElementById('filtro-categoria-pdf')].forEach(function (select) {
            if (!select) return;
            const opcion = document.createElement('option');
            opcion.value = nombre;
            opcion.textContent = nombre;
            select.appendChild(opcion);
        });
        alert(`Categoría "${nombre}" creada correctamente.`);
    });
}

/* Subir un PDF: se muestra el nombre del archivo elegido (simulación). */
const inputSubirPdf = document.getElementById('input-subir-pdf');
if (inputSubirPdf) {
    inputSubirPdf.addEventListener('change', function () {
        const archivo = inputSubirPdf.files[0];
        if (!archivo) return;

        const barra = document.querySelector('.pdf-visor__nombre-archivo');
        if (barra) barra.textContent = archivo.name;
        const categoria = document.getElementById('categoria-nuevo-pdf').value;
        alert(`Archivo "${archivo.name}" cargado en la categoría "${categoria}". (Simulación: en producción se guarda en el servidor del DTI)`);
    });
}

/* Alta de funcionario: el formulario aparece en línea (clase .oculto) y
   valida nombre, usuario y rol antes de crear la tarjeta del funcionario. */
const botonAgregarUsuario = document.getElementById('boton-agregar-usuario');
const formularioFuncionario = document.getElementById('form-funcionario');
const botonCancelarFuncionario = document.getElementById('boton-cancelar-funcionario');

if (botonAgregarUsuario && formularioFuncionario) {
    botonAgregarUsuario.addEventListener('click', function () {
        const visible = alternarVisibilidad(formularioFuncionario);
        botonAgregarUsuario.setAttribute('aria-expanded', visible);
        if (visible) document.getElementById('func-nombre').focus();
    });
}

if (botonCancelarFuncionario && formularioFuncionario) {
    botonCancelarFuncionario.addEventListener('click', function () {
        formularioFuncionario.reset();
        formularioFuncionario.classList.add('oculto');
        if (botonAgregarUsuario) botonAgregarUsuario.setAttribute('aria-expanded', false);
    });
}

if (formularioFuncionario) {
    formularioFuncionario.addEventListener('submit', function (evento) {
        evento.preventDefault();
        let valido = true;

        formularioFuncionario.querySelectorAll('.formulario__input, .formulario__select').forEach(function (campo) {
            if (!campo.required) return;
            limpiarError(campo);
            if (campo.value.trim() === '') {
                marcarError(campo, 'Este campo es obligatorio.');
                valido = false;
            }
        });
        if (!valido) return;

        const lista = document.getElementById('lista-usuarios');
        if (lista) {
            const tarjeta = document.createElement('article');
            tarjeta.className = 'tarjeta';
            tarjeta.innerHTML = `
                <h3 class="tarjeta__titulo">${document.getElementById('func-nombre').value}</h3>
                <p class="tarjeta__dato"><span class="tarjeta__etiqueta">Usuario:</span> <span class="id-mono">${document.getElementById('func-usuario').value}</span></p>
                <p class="tarjeta__dato"><span class="tarjeta__etiqueta">Rol:</span> <span class="badge badge--rol">${document.getElementById('func-rol').value}</span></p>
                <div class="tarjeta__acciones">
                    <button class="boton boton--secundario boton--chico" type="button">Resetear Contraseña</button>
                    <button class="boton boton--peligro boton--chico" type="button">Eliminar</button>
                </div>
            `;
            lista.appendChild(tarjeta);
            conectarTarjetaUsuario(tarjeta);
        }

        formularioFuncionario.reset();
        formularioFuncionario.classList.add('oculto');
        if (botonAgregarUsuario) botonAgregarUsuario.setAttribute('aria-expanded', false);
        alert('Funcionario creado correctamente. Se le envió una contraseña temporal a su correo institucional.');
    });
}

/* Conecta los botones "Resetear" (secundario) y "Eliminar" (peligro) de una
   tarjeta de funcionario. Se usa tanto para las tarjetas que ya vienen en el
   HTML como para las que se crean al agregar un funcionario. */
function conectarTarjetaUsuario(tarjeta) {
    const nombre = tarjeta.querySelector('.tarjeta__titulo').textContent;
    const botonResetear = tarjeta.querySelector('.boton--secundario');
    const botonEliminar = tarjeta.querySelector('.boton--peligro');

    if (botonResetear) {
        botonResetear.addEventListener('click', function () {
            alert(`Se generó una contraseña temporal para ${nombre} y se envió a su correo institucional.`);
        });
    }
    if (botonEliminar) {
        botonEliminar.addEventListener('click', function () {
            if (confirm(`¿Eliminar a ${nombre} del sistema?`)) {
                tarjeta.remove();
            }
        });
    }
}

document.querySelectorAll('#lista-usuarios .tarjeta').forEach(conectarTarjetaUsuario);
