# Justificación Tecnológica

El sistema resuelve dos problemas bien distintos del Hospital de Clínicas: la entrega en papel de indicaciones y estudios a pacientes, y el seguimiento manual (básicamente por teléfono) de los traslados en ambulancia. Las tecnologías que elegimos van atadas a esos dos problemas, no a gusto personal del grupo.

## Frontend: HTML, CSS y JavaScript

Esta es la base que estamos viendo en la materia, así que fue la elección natural. JavaScript ya se dio en clase, pero todavía no lo metimos en este prototipo — el acordeón de preguntas frecuentes y documentos del portal, por ejemplo, se resuelve con `<details>`/`<summary>`, que es HTML5 puro y no necesita ni una línea de JS.

El portal del paciente tiene que andar bien en un celular, porque a ese módulo se entra escaneando un QR desde la sala. Para el responsive usamos CSS puro con `flex-wrap` y `grid` (`auto-fit`/`minmax`) más un par de `@media`, que son las herramientas que se dieron en la Unidad 1. No metimos Bootstrap ni nada parecido porque todavía no lo dimos en clase.

El CSS quedó dividido en `layout.css` y `components.css` para todas las pantallas del sistema (login, dashboard, admin, documentos, traslados), comentado por página/sección para que cualquiera del equipo encuentre rápido lo que busca. El portal del paciente tiene su propio `portal.css`, aparte de los otros dos: es una pantalla pública, sin login, con una identidad visual distinta, así que no tenía sentido mezclarla con el resto. Ahí sí usamos BEM (`.portal__header`, `.documento__acciones`, etc.) porque es un único archivo chico y queda prolijo; en el resto del sistema usamos nombres de clase más simples y planos (`.card`, `.btn-primary`) porque son muchas pantallas tocadas por las cuatro personas del equipo y BEM completo en todas hubiese sido más trabajo de mantener que beneficio real.

## Backend: PHP + MySQL/MariaDB

Para el servidor elegimos PHP porque es lo que se viene en el módulo de Full Stack de la carrera y porque se integra directo con Apache, que es el mismo servidor que ya corre en el DTI del hospital (piso 6) — no le estamos pidiendo a una institución pública que sume una pieza nueva a su infraestructura para correr esto. Sumado a eso, PHP tiene soporte nativo para conectarse a MySQL/MariaDB (`mysqli`/`PDO`) sin depender de librerías de terceros para algo tan básico como una consulta.

Para los datos elegimos una base relacional en lugar de, por ejemplo, archivos sueltos o algo tipo NoSQL, porque las relaciones entre las entidades de los dos módulos necesitan integridad real, no solo "guardar cosas":

- En Documentación, un documento queda atado a una categoría y opcionalmente a una subcategoría, a una encuesta con sus preguntas, y a las respuestas que cada paciente fue dejando (`CATEGORIAS`/`SUBCATEGORIAS` → `DOCUMENTOS` → `ENCUESTAS` → `PREGUNTAS` → `RESPUESTAS`). Si se pudiera borrar una pregunta que ya tiene respuestas cargadas, o un documento que sigue asociado a una categoría, quedarían datos huérfanos sueltos por toda la base.
- En Ambulancias/Traslados es todavía más evidente: un traslado tiene un vehículo, uno o más funcionarios con un rol distinto cada uno (`TRASLADO_FUNCIONARIO` guarda ese rol por traslado), un historial de cada cambio de estado (`HISTORIAL_ESTADOS_TRASLADO`) y una carga asociada. Esa cadena —quién hizo qué traslado, en qué vehículo, con qué historial— es la trazabilidad operativa que el sistema necesita, y es exactamente lo que las claves foráneas garantizan desde la base en vez de tener que validarlo a mano en cada pantalla.

No son datos clínicos del paciente en sí (el portal solo expone indicaciones generales, no su historia clínica), pero sí son datos operativos sensibles del hospital, y la misma lógica aplica: mejor que la integridad la garantice la base de datos con sus restricciones que confiar en que nadie se olvide de validar algo en el código. El modelo entidad-relación, el pasaje a tablas y las restricciones de ambos módulos están detallados en [Modelado de Datos](04-Modelado-de-Datos.md).

Esto es la justificación de la elección, no el estado actual: lo que se entrega en esta primera entrega es el prototipo en HTML/CSS de `proto/`, sin backend conectado. El avance real en PHP y MySQL está aparte, en `Modulo1/` y `Modulo2/`, y todavía no está pensado para conectarse con este prototipo.

## Acceso por QR, sin login para el paciente

El relevamiento es claro en esto: el paciente no inicia sesión. Hay un QR único, sin datos personales, que lo lleva directo al portal. Así no le pedimos que instale nada ni que recuerde una contraseña, que para mucha gente (sobre todo gente mayor) sería una barrera más que una ayuda.

## Git, GitHub y VS Code

Somos cuatro personas tocando el mismo proyecto, así que necesitamos historial de cambios y la posibilidad de volver atrás si algo se rompe. Usamos GitHub porque además es donde está el repositorio oficial del equipo (`QuadNet-ISBO/SIGSM`). Cómo manejamos las ramas y los commits está detallado en [Control de Versiones y Repositorio](03-Control-Versiones-Repositorio.md).

Como editor usamos VS Code: es liviano, anda igual en Windows y Linux (el equipo usa los dos) y tiene extensiones para todo lo que necesitamos (PHP, SQL, Git). El detalle de qué extensiones usamos está en [Configuración del Entorno de Desarrollo](02-Configuracion-Entorno-Desarrollo.md).

## Lo que queda para más adelante

Para producción la idea es usar Docker sobre GNU/Linux, con acceso por SSH a los servidores del DTI (piso 6 del hospital). Esto ya lo estamos armando, pero como parte de otra materia, no de esta entrega: empaquetar el backend en un contenedor permite levantar exactamente el mismo entorno ahí que en la máquina de cada uno, evitando el clásico "en mi compu funciona".
