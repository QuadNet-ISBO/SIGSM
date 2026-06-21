# Justificación Tecnológica

El sistema resuelve dos problemas bien distintos del Hospital de Clínicas: la entrega en papel de indicaciones y estudios a pacientes, y el seguimiento manual (básicamente por teléfono) de los traslados en ambulancia. Las tecnologías que elegimos van atadas a esos dos problemas, no a gusto personal del grupo.

## Frontend: HTML, CSS y JS sin frameworks

El portal del paciente tiene que andar bien en un celular, porque a ese módulo se entra escaneando un QR desde la sala. Para resolver eso usamos CSS puro con `flex-wrap` y `grid` (`auto-fit`/`minmax`), que son las herramientas que se dieron en la Unidad 1 de la materia. No metimos Bootstrap ni nada parecido porque todavía no lo dimos en clase y para esta entrega no hacía falta — con CSS normal y un par de media queries se resuelve el responsive sin problema.

Para que los cuatro pudiéramos tocar el mismo CSS sin pisarnos los nombres de clases, usamos BEM (`.bloque__elemento--modificador`). No es obligatorio pero ayuda bastante cuando el archivo de estilos lo edita más de una persona.

Lo mismo con JavaScript: todo lo que hace falta (mostrar/ocultar secciones, filtrar opciones de un select, validar un formulario, abrir y cerrar la encuesta) se resuelve con el DOM normal, sin librerías. Meter React o algo así para esto sería usar un camión para mover una caja.

## Backend: PHP + MySQL

Esto ya está armado como adelanto en la carpeta `Modulo2` de la raíz del repo (no en esta entrega, que es solo el prototipo). Elegimos PHP porque es lo que se enseña en el módulo de Full Stack y porque se conecta directo con Apache, que es el servidor que ya usa el DTI del hospital. Para la base de datos usamos MySQL/MariaDB: los datos del sistema están bastante relacionados entre sí (un documento tiene una categoría y un funcionario que lo subió, un traslado tiene un vehículo, un chofer y un enfermero asignados), así que una base relacional con claves foráneas evita que queden datos sueltos o inconsistentes. El modelo ya está normalizado a 3FN — los diagramas están en `Diagramas/` en la raíz del repo.

## Acceso por QR, sin login para el paciente

El relevamiento es claro en esto: el paciente no inicia sesión. Hay un QR único, sin datos personales, que lo lleva directo al portal. Así no le pedimos que instale nada ni que recuerde una contraseña, que para mucha gente (sobre todo gente mayor) sería una barrera más que una ayuda.

## Git, GitHub y VS Code

Somos cuatro personas tocando el mismo proyecto, así que necesitamos historial de cambios y la posibilidad de volver atrás si algo se rompe. Usamos GitHub porque además es donde está el repositorio oficial del equipo (`QuadNet-ISBO/SIGSM`). Cómo manejamos las ramas y los commits está detallado en [Control de Versiones y Repositorio](03-Control-Versiones-Repositorio.md).

Como editor usamos VS Code: es liviano, anda igual en Windows y Linux (el equipo usa los dos) y tiene extensiones para todo lo que necesitamos (PHP, SQL, Git). El detalle de qué extensiones usamos está en [Configuración del Entorno de Desarrollo](02-Configuracion-Entorno-Desarrollo.md).

## XAMPP como entorno local

XAMPP empaqueta Apache, PHP y MariaDB en un solo instalador, así que cada uno puede levantar exactamente el mismo entorno en su máquina sin instalar cada cosa por separado. Es además el mismo tipo de stack (LAMP/WAMP) que se va a usar después en el servidor real del hospital.

## Lo que queda para más adelante

Para producción se planea usar Docker sobre GNU/Linux, con acceso por SSH a los servidores del DTI (piso 6 del hospital). Esto es para más adelante: empaquetar el backend en un contenedor permite levantar exactamente el mismo entorno ahí que en la máquina de cada uno, evitando el clásico "en mi compu funciona". No es parte de esta entrega.

## Por qué esta entrega es solo el prototipo

El curso todavía no dio PHP de forma oficial — uno de los integrantes lo aprendió por su cuenta y armó un adelanto funcional, pero la consigna real de la materia pide que para esta primera entrega nos enfoquemos en la parte visual. Por eso lo que se entrega ahora es el prototipo en HTML/CSS/JS (carpeta `proto/`), y el backend que ya tenemos avanzado va a ser la base para conectar con este mismo diseño en la segunda entrega.
