# Control de Versiones y Repositorio

El repositorio del equipo es [github.com/QuadNet-ISBO/SIGSM](https://github.com/QuadNet-ISBO/SIGSM), con `main` como rama principal. Ahí está todo: el prototipo, el avance en PHP de Módulo 2, las bases de datos, los diagramas y esta misma documentación, para que cualquiera (un compañero nuevo o el docente) pueda clonarlo y dejarlo andando siguiendo la guía de [Configuración del Entorno](02-Configuracion-Entorno-Desarrollo.md).

## Cómo está organizado

En la raíz del repo separamos lo que es la entrega actual de lo que es trabajo en curso:

```
SIGSM/
├── Primera Entrega/      Prototipo + documentación (lo que se entrega ahora)
├── Modulo1/              Backend y BD de Documentación, en desarrollo
├── Modulo2/              Backend y BD de Traslados, ya funcional en PHP
├── Shared/                Layout y estilos compartidos del backend
└── Diagramas/            DER y Modelo Relacional de ambos módulos
```

## Ramas

Como somos cuatro tocando partes distintas (el prototipo, Módulo 1, Módulo 2), tratamos de no commitear cambios grandes directo a `main`. La idea es:

```bash
git checkout main
git pull
git checkout -b feature/lo-que-estoy-haciendo
```

y recién subir esa rama cuando el cambio está probado, para que quede como Pull Request y alguien más le pegue una mirada antes de mergear:

```bash
git push -u origin feature/lo-que-estoy-haciendo
```

Para cambios chicos (un typo, ajustar un texto) no hace falta tanto trámite, pero para algo que toque varias páginas o la base de datos sí conviene pasarlo por rama.

## Mensajes de commit

Hasta ahora el historial tiene mensajes en español, libres, del tipo "Subo diagramas DER y MR de ambos modulos" o "Actualizo SQL Modulo II". Funcionó, pero de acá en adelante tratamos de ordenarlo un poco más usando un prefijo que diga de qué tipo es el cambio:

- `feat:` algo nuevo (`feat: agregar encuesta de satisfaccion en portal-paciente`)
- `fix:` corrige algo que estaba roto (`fix: filtrar vehiculos no ambulancia en traslado de paciente`)
- `docs:` documentación
- `db:` cambios en el esquema o los datos de la base
- `style:` ajustes visuales que no cambian funcionalidad

No hace falta ser estricto con esto, pero ayuda bastante a la hora de mirar el historial y entender qué pasó sin tener que abrir cada commit.

## Algunas cosas que tratamos de cuidar

- No subir contraseñas ni datos reales de pacientes — los `conexion.php` solo usan el `root` sin contraseña de XAMPP, que es local.
- Que un commit no mezcle cosas sin relación (no juntar un cambio de CSS con uno de la base de datos en el mismo commit).
- Mantener el README al día cuando cambia algo de cómo se instala o se usa el proyecto.
