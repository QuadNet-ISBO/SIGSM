# S.I.G.S.M. — Primera Entrega

Sistema Informático de Gestión de Servicios Médicos, proyecto de egreso de QuadNet para el Hospital de Clínicas.

Esta carpeta contiene lo que corresponde a la primera entrega: el prototipo de interfaz (sin backend, tal como pide la consigna ya que todavía no dimos PHP en clase) y la documentación técnica pedida.

## Qué hay acá

- **`proto/`** – prototipo en HTML, CSS y JS puro. Cuatro pantallas: login (`index.html`), panel de administración de documentos y usuarios (`admin-panel.html`), gestión de traslados (`ambulancias.html`) y el portal del paciente (`portal-paciente.html`), pensado mobile-first porque a este último se entra escaneando un QR desde el celular, sin login.
- **`Documentacion/`** – justificación tecnológica, cómo armar el entorno de desarrollo y cómo manejamos el repositorio y los commits.

## Cómo ver el prototipo

No hace falta instalar nada. Se puede abrir directo `proto/frontend/html/index.html` en el navegador, aunque para que los enlaces entre páginas funcionen bien conviene abrirlo con la extensión Live Server de VS Code en vez de doble clic.

Las páginas para entrar son `index.html` → `admin-panel.html` / `ambulancias.html`, o directo a `portal-paciente.html` simulando que escaneaste el QR.

## Por qué no hay backend acá

El relevamiento real de la materia dice que para esta entrega solo nos enfocamos en la parte visual. El equipo ya tiene un avance funcional en PHP + MySQL (carpeta `Modulo2` en la raíz del repo), pero eso lo vamos a conectar con este mismo diseño más adelante, no es parte de lo que se entrega ahora.

## Documentación

- [Justificación Tecnológica](Documentacion/01-Justificacion-Tecnologica.md)
- [Configuración del Entorno de Desarrollo](Documentacion/02-Configuracion-Entorno-Desarrollo.md)
- [Control de Versiones y Repositorio](Documentacion/03-Control-Versiones-Repositorio.md)

## Equipo

Guillermo Raffetto (coordinador), Matias Rossello, Thiago Blengini y Luciano Maciel — QuadNet, ISBO 2026.
