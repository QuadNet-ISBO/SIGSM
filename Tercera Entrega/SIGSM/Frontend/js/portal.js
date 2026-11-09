// API pública de solo lectura para el Portal del Paciente.
        const API_PORTAL = '../API/portal/';

        const listaServicios = document.getElementById('listaServicios');
        const listaSubservicios = document.getElementById('listaSubservicios');
        const estadoServicios = document.getElementById('estadoServicios');
        const buscarServicio = document.getElementById('buscarServicio');
        const subservicios = document.getElementById('subservicios');
        const documento = document.getElementById('documento');
        const encuesta = document.getElementById('encuesta');
        const servicioSeleccionado = document.getElementById('servicioSeleccionado');
        const subservicioSeleccionado = document.getElementById('subservicioSeleccionado');
        const tituloDocumento = document.getElementById('tituloDocumento');
        const lienzoPDF = document.getElementById('lienzoPDF');
        const paginaPDF = document.getElementById('paginaPDF');
        const mensajeVisorPDF = document.getElementById('mensajeVisorPDF');
        const controlesPDF = document.getElementById('controlesPDF');
        const contadorPaginasPDF = document.getElementById('contadorPaginasPDF');
        const paginaAnteriorPDF = document.getElementById('paginaAnteriorPDF');
        const paginaSiguientePDF = document.getElementById('paginaSiguientePDF');
        const abrirPDFAlternativo = document.getElementById('abrirPDFAlternativo');
        let pdfActual = null;
        let paginaActual = 1;
        let cargaPDF = 0;
        let tareaRenderPDF = null;
        let bibliotecaPDFPromesa = null;

        function cargarBibliotecaPDF() {
            if (window.pdfjsLib) return Promise.resolve(window.pdfjsLib);
            if (!bibliotecaPDFPromesa) {
                bibliotecaPDFPromesa = new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js';
                    script.onload = () => {
                        if (!window.pdfjsLib) return reject(new Error('PDF.js no está disponible.'));
                        window.pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
                        resolve(window.pdfjsLib);
                    };
                    script.onerror = () => reject(new Error('No se pudo cargar PDF.js. Comprobá la conexión a Internet.'));
                    document.head.appendChild(script);
                }).catch(error => { bibliotecaPDFPromesa = null; throw error; });
            }
            return bibliotecaPDFPromesa;
        }

        function limpiarVisorPDF() {
            cargaPDF++;
            if (tareaRenderPDF) { tareaRenderPDF.cancel(); tareaRenderPDF = null; }
            if (pdfActual) { pdfActual.destroy(); pdfActual = null; }
            paginaActual = 1;
            paginaPDF.hidden = true;
            controlesPDF.hidden = true;
            abrirPDFAlternativo.hidden = true;
            abrirPDFAlternativo.removeAttribute('href');
            mensajeVisorPDF.hidden = false;
            mensajeVisorPDF.textContent = 'Seleccioná un documento para visualizarlo.';
        }

        async function dibujarPaginaPDF(token) {
            if (!pdfActual || token !== cargaPDF) return;
            if (tareaRenderPDF) {
                tareaRenderPDF.cancel();
                try { await tareaRenderPDF.promise; } catch (_) {}
                tareaRenderPDF = null;
            }
            const pdf = pdfActual;
            const pagina = await pdf.getPage(paginaActual);
            if (token !== cargaPDF) return;
            const vistaBase = pagina.getViewport({ scale: 1 });
            const anchoDisponible = Math.max(200, lienzoPDF.clientWidth - 24);
            const escala = Math.min(2, anchoDisponible / vistaBase.width);
            const pixelRatio = Math.min(window.devicePixelRatio || 1, 2);
            const vista = pagina.getViewport({ scale: escala });
            const canvas = paginaPDF;
            canvas.width = Math.floor(vista.width * pixelRatio);
            canvas.height = Math.floor(vista.height * pixelRatio);
            canvas.style.width = Math.floor(vista.width) + 'px';
            canvas.style.height = Math.floor(vista.height) + 'px';
            paginaPDF.hidden = false;
            mensajeVisorPDF.hidden = true;
            contadorPaginasPDF.textContent = `Página ${paginaActual} de ${pdf.numPages}`;
            paginaAnteriorPDF.disabled = paginaActual <= 1;
            paginaSiguientePDF.disabled = paginaActual >= pdf.numPages;
            const contexto = canvas.getContext('2d');
            tareaRenderPDF = pagina.render({
                canvasContext: contexto,
                viewport: vista,
                transform: pixelRatio === 1 ? null : [pixelRatio, 0, 0, pixelRatio, 0, 0]
            });
            try { await tareaRenderPDF.promise; }
            catch (error) { if (error.name !== 'RenderingCancelledException') throw error; }
            finally { tareaRenderPDF = null; }
        }

        async function mostrarPDF(url) {
            limpiarVisorPDF();
            const token = cargaPDF;
            mensajeVisorPDF.textContent = 'Cargando PDF...';
            abrirPDFAlternativo.href = url;
            try {
                const pdfjs = await cargarBibliotecaPDF();
                if (token !== cargaPDF) return;
                const tarea = pdfjs.getDocument({ url, withCredentials: true });
                const pdf = await tarea.promise;
                if (token !== cargaPDF) { await pdf.destroy(); return; }
                pdfActual = pdf;
                controlesPDF.hidden = false;
                await dibujarPaginaPDF(token);
            } catch (error) {
                if (token !== cargaPDF) return;
                console.error('Error del visor PDF:', error);
                mensajeVisorPDF.hidden = false;
                mensajeVisorPDF.textContent = 'No se pudo mostrar el PDF aquí. Podés intentar abrirlo en otra pestaña.';
                paginaPDF.hidden = true;
                controlesPDF.hidden = true;
                abrirPDFAlternativo.hidden = false;
            }
        }

        paginaAnteriorPDF.addEventListener('click', () => {
            if (pdfActual && paginaActual > 1) { paginaActual--; dibujarPaginaPDF(cargaPDF); }
        });
        paginaSiguientePDF.addEventListener('click', () => {
            if (pdfActual && paginaActual < pdfActual.numPages) { paginaActual++; dibujarPaginaPDF(cargaPDF); }
        });
        let temporizadorRedimension;
        window.addEventListener('resize', () => {
            clearTimeout(temporizadorRedimension);
            temporizadorRedimension = setTimeout(() => {
                if (pdfActual) dibujarPaginaPDF(cargaPDF);
            }, 180);
        });

        const estadoDocumento = document.getElementById('estadoDocumento');

        let categorias = [];

        async function obtenerJSON(url) {
            const respuesta = await fetch(url, {
                method: 'GET',
                headers: { 'Accept': 'application/json' },
                cache: 'no-store'
            });

            const datos = await respuesta.json().catch(() => null);

            if (!respuesta.ok || !datos || datos.ok !== true) {
                throw new Error(datos?.mensaje || 'No se pudieron cargar los datos.');
            }

            return datos;
        }

        function mostrarMensaje(contenedor, mensaje) {
            contenedor.replaceChildren();
            const parrafo = document.createElement('p');
            parrafo.textContent = mensaje;
            contenedor.appendChild(parrafo);
        }

        function crearBotonServicio(categoria) {
            const boton = document.createElement('button');
            boton.type = 'button';
            boton.className = 'servicio';

            const icono = document.createElement('span');
            icono.className = 'servicio__icono';
            icono.textContent = '+';

            const texto = document.createElement('span');
            texto.className = 'servicio__texto';
            texto.textContent = categoria.nombre;

            const flecha = document.createElement('span');
            flecha.className = 'servicio__flecha';
            flecha.textContent = '→';

            boton.append(icono, texto, flecha);
            boton.addEventListener('click', () => cargarSubservicios(categoria));
            return boton;
        }

        function renderizarCategorias(lista) {
            listaServicios.replaceChildren();

            if (lista.length === 0) {
                mostrarMensaje(listaServicios, 'No hay servicios disponibles para consultar.');
                return;
            }

            lista.forEach(categoria => listaServicios.appendChild(crearBotonServicio(categoria)));
        }

        async function cargarCategorias() {
            mostrarMensaje(listaServicios, 'Cargando servicios...');

            try {
                const datos = await obtenerJSON(API_PORTAL + 'categorias.php');
                categorias = datos.categorias || [];
                renderizarCategorias(categorias);
            } catch (error) {
                mostrarMensaje(listaServicios, 'No se pudieron cargar los servicios. Intentá recargar la página.');
                console.error(error);
            }
        }

        async function cargarSubservicios(categoria) {
            servicioSeleccionado.textContent = categoria.nombre;
            mostrarMensaje(listaSubservicios, 'Cargando subservicios...');
            subservicios.classList.remove('seccion-oculta');
            documento.classList.add('seccion-oculta');
            encuesta.classList.add('seccion-oculta');
            limpiarVisorPDF();

            subservicios.scrollIntoView({ behavior: 'smooth', block: 'start' });

            try {
                const datos = await obtenerJSON(
                    API_PORTAL + 'subcategorias.php?id_categoria=' + encodeURIComponent(categoria.id_categoria)
                );
                const subcategorias = datos.subcategorias || [];
                listaSubservicios.replaceChildren();

                if (subcategorias.length === 0) {
                    mostrarMensaje(listaSubservicios, 'Este servicio no tiene documentos disponibles por el momento.');
                    return;
                }

                subcategorias.forEach(subcategoria => {
                    const grupo = document.createElement('div');
                    grupo.className = 'subservicio-grupo';

                    const boton = document.createElement('button');
                    boton.type = 'button';
                    boton.className = 'subservicio';
                    boton.setAttribute('aria-expanded', 'false');

                    const nombre = document.createElement('span');
                    nombre.textContent = subcategoria.nombre;

                    const flecha = document.createElement('span');
                    flecha.textContent = '⌄';

                    const listaDocumentos = document.createElement('div');
                    listaDocumentos.className = 'documentos-subservicio';
                    listaDocumentos.hidden = true;

                    boton.append(nombre, flecha);
                    boton.addEventListener('click', async () => {
                        const abrir = listaDocumentos.hidden;
                        listaDocumentos.hidden = !abrir;
                        boton.setAttribute('aria-expanded', String(abrir));
                        flecha.textContent = abrir ? '⌃' : '⌄';

                        if (!abrir || listaDocumentos.dataset.cargada === 'true') return;
                        mostrarMensaje(listaDocumentos, 'Cargando documentos...');
                        try {
                            const respuesta = await obtenerJSON(
                                API_PORTAL + 'documento.php?id_subcategoria='
                                + encodeURIComponent(subcategoria.id_subcategoria)
                            );
                            listaDocumentos.replaceChildren();
                            (respuesta.documentos || []).forEach(doc => {
                                const enlace = document.createElement('button');
                                enlace.type = 'button';
                                enlace.className = 'documento-opcion';
                                const titulo = document.createElement('span');
                                titulo.textContent = doc.titulo;
                                const icono = document.createElement('span');
                                icono.textContent = '→';
                                enlace.append(titulo, icono);
                                enlace.addEventListener('click', () => cargarDocumento(subcategoria, doc));
                                listaDocumentos.appendChild(enlace);
                            });
                            listaDocumentos.dataset.cargada = 'true';
                        } catch (error) {
                            mostrarMensaje(listaDocumentos, 'No se pudieron cargar los documentos. Intentá nuevamente.');
                            console.error(error);
                        }
                    });
                    grupo.append(boton, listaDocumentos);
                    listaSubservicios.appendChild(grupo);
                });
            } catch (error) {
                mostrarMensaje(listaSubservicios, 'No se pudieron cargar los subservicios. Intentá nuevamente.');
                console.error(error);
            }
        }

        
        async function cargarEncuesta(subcategoria) {
            const contenido = document.getElementById('contenidoEncuesta');

            contenido.replaceChildren();

            const mensaje = document.createElement('p');
            mensaje.textContent = 'Cargando encuesta...';
            contenido.appendChild(mensaje);

            try {
                const datos = await obtenerJSON(
                    API_PORTAL + 'encuesta.php?id_subcategoria='
                    + encodeURIComponent(subcategoria.id_subcategoria)
                );

                contenido.replaceChildren();

                if (!datos.encuesta) {
                    mostrarMensaje(
                        contenido,
                        'No hay una encuesta disponible para este subservicio.'
                    );
                    return;
                }

                const formulario = document.createElement('form');
                formulario.id = 'formularioEncuesta';

                const titulo = document.createElement('h3');
                titulo.textContent = datos.encuesta.titulo;
                formulario.appendChild(titulo);

                const preguntas = datos.encuesta.preguntas || [];

                if (preguntas.length === 0) {
                    mostrarMensaje(
                        contenido,
                        'Esta encuesta todavía no tiene preguntas.'
                    );
                    return;
                }

                preguntas.forEach(pregunta => {
                    const grupo = document.createElement('fieldset');

                    const leyenda = document.createElement('legend');
                    leyenda.textContent = pregunta.texto;
                    grupo.appendChild(leyenda);

                    const opciones = pregunta.opciones || [];

                    if (pregunta.tipo === 'opcion_unica') {
                        opciones.forEach(opcion => {
                            const etiqueta = document.createElement('label');
                            const radio = document.createElement('input');

                            radio.type = 'radio';
                            radio.name = 'pregunta_' + pregunta.id_pregunta;
                            radio.value = opcion.id_opcion;
                            radio.required = true;

                            etiqueta.append(
                                radio,
                                document.createTextNode(' ' + opcion.texto_opcion)
                            );

                            grupo.appendChild(etiqueta);
                            grupo.appendChild(document.createElement('br'));
                        });
                    } else if (pregunta.tipo === 'opcion_multiple') {
                        opciones.forEach(opcion => {
                            const etiqueta = document.createElement('label');
                            const casilla = document.createElement('input');

                            casilla.type = 'checkbox';
                            casilla.name = 'pregunta_' + pregunta.id_pregunta;
                            casilla.value = opcion.id_opcion;

                            etiqueta.append(
                                casilla,
                                document.createTextNode(' ' + opcion.texto_opcion)
                            );

                            grupo.appendChild(etiqueta);
                            grupo.appendChild(document.createElement('br'));
                        });
                    } else if (pregunta.tipo === 'texto_libre') {
                        const campo = document.createElement('textarea');

                        campo.name = 'pregunta_' + pregunta.id_pregunta;
                        campo.rows = 3;
                        campo.maxLength = 2000;
                        campo.required = true;

                        grupo.appendChild(campo);
                    } else {
                        const aviso = document.createElement('p');
                        aviso.textContent =
                            'Tipo de pregunta no compatible: ' + pregunta.tipo;

                        grupo.appendChild(aviso);
                    }

                    formulario.appendChild(grupo);
                });

                const botonEnviar = document.createElement('button');
                botonEnviar.type = 'submit';
                botonEnviar.className = 'boton boton--principal';
                botonEnviar.textContent = 'Enviar respuestas';

                const mensajeResultado = document.createElement('p');
                mensajeResultado.id = 'mensajeResultadoEncuesta';
                mensajeResultado.setAttribute('role', 'status');

                formulario.appendChild(botonEnviar);
                formulario.appendChild(mensajeResultado);

                formulario.addEventListener('submit', async function (evento) {
                    evento.preventDefault();

                    if (!formulario.reportValidity()) {
                        return;
                    }

                    botonEnviar.disabled = true;
                    botonEnviar.textContent = 'Enviando...';
                    mensajeResultado.textContent = '';

                    const respuestas = [];

                    for (const pregunta of preguntas) {
                        const nombreCampo = 'pregunta_' + pregunta.id_pregunta;

                        if (pregunta.tipo === 'opcion_unica') {
                            const seleccionada = formulario.querySelector(
                                'input[name="' + nombreCampo + '"]:checked'
                            );

                            if (seleccionada) {
                                respuestas.push({
                                    id_pregunta: Number(pregunta.id_pregunta),
                                    id_opcion: Number(seleccionada.value)
                                });
                            }
                        } else if (pregunta.tipo === 'opcion_multiple') {
                            const seleccionadas = formulario.querySelectorAll(
                                'input[name="' + nombreCampo + '"]:checked'
                            );

                            if (seleccionadas.length > 0) {
                                respuestas.push({
                                    id_pregunta: Number(pregunta.id_pregunta),
                                    id_opciones: Array.from(
                                        seleccionadas,
                                        opcion => Number(opcion.value)
                                    )
                                });
                            }
                        } else if (pregunta.tipo === 'texto_libre') {
                            const campo = formulario.querySelector(
                                '[name="' + nombreCampo + '"]'
                            );

                            if (campo && campo.value.trim() !== '') {
                                respuestas.push({
                                    id_pregunta: Number(pregunta.id_pregunta),
                                    texto: campo.value.trim()
                                });
                            }
                        }
                    }

                    try {
                        const respuesta = await fetch(
                            API_PORTAL + 'responder_encuesta.php',
                            {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    id_encuesta: Number(datos.encuesta.id_encuesta),
                                    respuestas: respuestas
                                })
                            }
                        );

                        const resultado = await respuesta.json();

                        if (!respuesta.ok || resultado.ok !== true) {
                            throw new Error(
                                resultado.mensaje || 'No se pudieron guardar las respuestas.'
                            );
                        }

                        mensajeResultado.textContent =
                            '¡Gracias! Tus respuestas se guardaron correctamente.';

                        formulario.querySelectorAll('input, textarea').forEach(campo => {
                            campo.disabled = true;
                        });

                        botonEnviar.textContent = 'Encuesta enviada';

                    } catch (error) {
                        mensajeResultado.textContent = error.message;
                        botonEnviar.disabled = false;
                        botonEnviar.textContent = 'Reintentar envío';

                        console.error('Error al enviar la encuesta:', error);
                    }
                });

                contenido.appendChild(formulario);

            } catch (error) {
                mostrarMensaje(
                    contenido,
                    'No se pudo cargar la encuesta. Intentá nuevamente.'
                );

                console.error('Error al cargar la encuesta:', error);
            }
        }

        async function cargarDocumento(subcategoria, doc) {
            subservicioSeleccionado.textContent = subcategoria.nombre;
            tituloDocumento.textContent = doc.titulo || subcategoria.nombre;

            documento.classList.remove('seccion-oculta');
            encuesta.classList.add('seccion-oculta');
            document.getElementById('contenidoEncuesta').replaceChildren();
            limpiarVisorPDF();
            estadoDocumento.hidden = false;
            estadoDocumento.textContent = 'Cargando documento...';

            documento.scrollIntoView({ behavior: 'smooth', block: 'start' });

            // Cada opción abre el PDF elegido, no el más reciente de la subcategoría.
            mostrarPDF(API_PORTAL + 'visualizar.php?id_documento='
                + encodeURIComponent(doc.id_documento));
            estadoDocumento.hidden = true;
            estadoDocumento.textContent = '';

            encuesta.classList.remove('seccion-oculta');
            await cargarEncuesta(subcategoria);
        }

        buscarServicio.addEventListener('input', function () {
            const texto = this.value.toLocaleLowerCase('es').trim();
            renderizarCategorias(
                categorias.filter(categoria => categoria.nombre.toLocaleLowerCase('es').includes(texto))
            );
        });

        cargarCategorias();
