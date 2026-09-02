# S.I.G.S.M. — Primera Entrega

Sistema Informático de Gestión de Servicios Médicos, proyecto de egreso de QuadNet para el Hospital de Clínicas.

Esta carpeta contiene lo que corresponde a la primera entrega: el prototipo de interfaz y la documentación técnica pedida.

## Qué hay acá

- **`proto/`** – prototipo en HTML y CSS, sin JavaScript (ya lo dimos en clase, pero todavía no lo metimos en este prototipo). Adentro:
  - `index.html` → `login.html` — pantalla de entrada y login.
  - `dashboard/` — resumen general una vez logueado.
  - `admin/` — alta, edición y listado de categorías, funcionarios, usuarios y vehículos.
  - `documentos/` — gestión de documentos, asociar encuestas y el acceso de prueba al portal del paciente.
  - `traslados/` — listado, solicitud y alta de traslados en ambulancia.
  - `portal-paciente.html` — portal mobile-first al que se entra escaneando un QR, sin login.
  - `css/` — `layout.css` y `components.css` para todas las pantallas internas (login, dashboard, admin, documentos, traslados), y `portal.css` aparte solo para el portal del paciente, porque es una pantalla pública con su propio diseño.
- **`Documentacion/`** – justificación tecnológica, modelado de datos, cómo armar el entorno de desarrollo y cómo manejamos el repositorio y los commits.
- **DER y MR de ambos módulos** (`.drawio`) y `Modelado de Datos.pdf` — diagramas y restricciones de la base, detallados en [Modelado de Datos](Documentacion/04-Modelado-de-Datos.md).

## Cómo ver el prototipo

No hace falta instalar nada. Se puede abrir directo `proto/index.html` en el navegador, aunque para que los enlaces entre páginas funcionen bien conviene abrirlo con la extensión Live Server de VS Code en vez de doble clic.

El recorrido normal es `index.html` → `login.html` → `dashboard/`, `admin/`, `documentos/` o `traslados/`. El portal del paciente se prueba aparte, abriendo directo `portal-paciente.html` como si hubieras escaneado el QR.

## Documentación

- [Justificación Tecnológica](Documentacion/01-Justificacion-Tecnologica.md)
- [Configuración del Entorno de Desarrollo](Documentacion/02-Configuracion-Entorno-Desarrollo.md)
- [Control de Versiones y Repositorio](Documentacion/03-Control-Versiones-Repositorio.md)
- [Modelado de Datos](Documentacion/04-Modelado-de-Datos.md)

## Equipo

Guillermo Raffetto (coordinador), Matias Rossello, Thiago Blengini y Luciano Maciel — QuadNet, ISBO 2026.
