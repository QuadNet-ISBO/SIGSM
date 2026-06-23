# Modelado de Datos

El modelo entidad-relación separa los dos módulos del sistema: Documentación (indicaciones, encuestas) y Traslados (ambulancias, funcionarios). Los diagramas están en `.drawio` en la raíz de `Primera Entrega/` para poder abrirlos y editarlos con draw.io.

## Diagrama Entidad-Relación (DER)

- `DER Modulo 1 Primera Entrega.drawio` — Documentación: `USUARIOS`, `ROLES`, `CATEGORIAS`, `SUBCATEGORIAS`, `DOCUMENTOS`, `ENCUESTAS`, `PREGUNTAS`, `RESPUESTAS`.
- `DER Modulo 2 Primera Entrega.drawio` — Traslados: `USUARIOS`, `ROLES`, `FUNCIONARIOS`, `ROLES_FUNCIONARIO`, `VEHICULOS`, `TIPOS_VEHICULO`, `TRASLADOS`, `CARGAS_TRASLADO`, `TIPOS_CARGA`, `HISTORIAL_ESTADOS_TRASLADO`.

## Modelo Relacional (MR)

El paso de DER a tablas está en `MR Modulo 1 Primera Entrega.drawio` y `MR Modulo 2 Primera Entrega.drawio`, con las PK y FK de cada tabla ya marcadas. Las relaciones N:N del DER (funcionario-rol, traslado-funcionario) quedaron resueltas como tablas intermedias (`FUNCIONARIO_ROL`, `TRASLADO_FUNCIONARIO`), que es lo que permite, por ejemplo, que un funcionario tenga un rol distinto en cada traslado en el que participa.

## Restricciones No Estructurales

Además de las PK/FK del modelo, el sistema tiene reglas de negocio que una base de datos relacional no resuelve solo con la estructura de tablas. Estas son las que identificamos como críticas y cómo pensamos implementarlas, tanto del lado del sistema (validación antes de guardar) como del lado de la base (constraints):

| ID | Restricción | Implementación en sistema | Implementación en BD |
|---|---|---|---|
| RNE-01 | Un recurso (funcionario o vehículo) con estado operativo "ocupado" o estado laboral "inactivo/desvinculado" no puede asignarse a un nuevo traslado. | Validar que el recurso esté "disponible" antes de asignarlo; si no, bloquear y mostrar error. Al asignar, cambiar su estado a "ocupado". | CHECK de estados válidos + trigger que impida asignar recursos no disponibles y evite más de un traslado activo por recurso. |
| RNE-02 | Un traslado de paciente tiene que tener sí o sí un enfermero a cargo. | Obligar a seleccionar un enfermero al crear el traslado; si no, no se puede guardar. | Clave foránea NOT NULL hacia el funcionario con rol enfermero en la tabla de traslados. |
| RNE-03 | Coherencia cronológica de los tiempos de un traslado: `fecha_creacion` < `hora_salida` < `hora_llegada` < `hora_retorno` < `hora_finalizado`. | Validar que las fechas/horas respeten ese orden antes de guardar; si no, bloquear. | CHECK que asegure el orden completo entre las cinco columnas. |

Estas tres son las que cubre esta entrega; el resto de las restricciones (unicidad de matrícula, formatos de teléfono, etc.) van quedando documentadas a medida que avanza el desarrollo de `Modulo1` y `Modulo2`.
