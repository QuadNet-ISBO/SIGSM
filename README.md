# S.I.G.S.M. — Sistema Informático de Gestión de Servicios Médicos

Proyecto de Egreso — BT Tecnologías de la Información 2026
Cliente: **Hospital de Clínicas** | Empresa desarrolladora: **QuadNet**
Institución: I.S.B.O. Instituto Superior Brazo Oriental
---

## ¿Qué es S.I.G.S.M.?

Plataforma a medida que transforma la ineficiencia documental y la logística de traslados del Hospital de Clínicas en **transparencia operativa y accesibilidad inmediata** para todos los usuarios.

El sistema se compone de dos módulos integrados al panel institucional existente del hospital (piso 6, servidores del DTI), accesibles con las credenciales habituales del personal.

---

## Módulos del sistema

### 📄 Módulo 1 — Gestión digital de documentación para pacientes

Permite a funcionarios administrativos cargar y gestionar documentos en el servidor. Los pacientes acceden a ellos escaneando un **código QR** desde su celular, sin necesidad de recibir copias impresas ni instalar aplicaciones.

Tipos de documentos contemplados:
- Indicaciones médicas (IVE, prostatectomía, warfarina, trasplante, etc.)
- Preparación para estudios imagenológicos y diagnósticos
- Indicaciones de enfermería (nefrología, ostomizados, trasplantados)
- **Encuestas de satisfacción anónimas** (almacenadas para análisis de indicadores)

### 🚑 Módulo 2 — Trazabilidad de ambulancias y traslados

Gestión y seguimiento del transporte mediante ambulancias y otros vehículos del hospital. Registra cada solicitud de traslado e incluye:

- Conductor, enfermero responsable y paciente/elemento a trasladar
- Origen, destino, hora de salida y hora de llegada
- Datos del paciente consumidos desde API externa por C.I.
- Gestión de rutas en el circuito nacional
- Reglas de negocio: no todos los traslados son válidos para cualquier vehículo
- Seguimiento en tiempo real del estado del traslado

> Workflow del hospital: https://drive.google.com/file/d/19_1YgPe1Mb0ivRz6KpNEd70nGylTIFI0/view?usp=sharing

---

## Propuesta de valor

| Impacto | Cifra |
|---------|-------|
| Hojas eliminadas/año | **105.600 hojas** |
| Ahorro estimado en papel e insumos | **~USD 4.520/año** |
| Horas administrativas liberadas/año | **660 horas** (seguimiento telefónico de traslados) |
| Encuestas digitalizadas/mes | **+8.800 encuestas** en papel reemplazadas |
| Tiempo evitado por re-consultas | **~29 horas administrativas/mes** |

**Diferenciadores clave:**
- Diseñado sobre el workflow real del hospital
- Acceso con credenciales institucionales existentes (cero curva de adopción)
- Reglas de negocio incorporadas (impide errores operativos)
- Acceso del paciente por QR: sin apps, sin cuentas

---

## Stack tecnológico

| Capa | Tecnología |
|------|-----------|
| Frontend | HTML · CSS |
| Backend | PHP |
| Base de datos | MySQL |
| Infraestructura | GNU/Linux · Docker · SSH |
| Control de versiones | Git · GitHub |
| Entorno de desarrollo | VS Code · XAMPP 8.x · VirtualBox 7.x |

---

## Equipo QuadNet

| Rol | Nombre | GitHub |
|-----|--------|--------|
| Coordinador | Guillermo Raffetto | [@guillermoraffetto](https://github.com/guillermoraffetto) |
| Subcoordinador | Matias Rossello | [@elmandress](https://github.com/elmandress) |
| Integrante | Thiago Blengini | [@Snufkhin](https://github.com/Snufkhin) |
| Integrante | Luciano Maciel | — |

📧 quadnet.contacto@gmail.com

---

## Misión y Visión

**MISIÓN:** Digitalizar y optimizar los procesos administrativos y logísticos del Hospital de Clínicas, asegurando que cada paciente y funcionario disponga de acceso inmediato a información crítica en tiempo real. Transformar la ineficiencia documental y de traslados en transparencia y control operacional.

**VISIÓN:** Ser la solución de software institucional de referencia para la gestión de la salud pública en Uruguay, escalable a otros centros asistenciales y reconocida por maximizar la eficiencia de los recursos hospitalarios mediante innovación tecnológica continua.

---

## Cronograma de entregas

| Entrega | Fecha |
|---------|-------|
| Conformación de grupos | 11/05/2026 |
| **Primera entrega** | 24/06/2026 |
| **Segunda entrega** | 02/09/2026 |
| **Documentación final** | 09/11/2026 |
| Instalación del proyecto | 11/11/2026 |
| Defensa de proyecto | 20–24/11/2026 |

---

## Estado del proyecto

- [x] Conformación del equipo
- [x] Propuesta de valor
- [x] Repositorio configurado
- [x] Relevamiento de requerimientos (ESRE)
- [ ] Justificación tecnológica
- [ ] Prototipo UI/UX (Figma)
- [x] Modelo Entidad-Relación
- [ ] Implementación Módulo Documentación
- [ ] Implementación Módulo Ambulancias
- [ ] Script de administración del servidor
- [ ] Docker Compose deploy
- [ ] Deploy en servidores del DTI

---

## Recursos institucionales

- 🌐 Institución: https://isbo.utu.edu.uy/bt-tecnologias-de-la-informacion
- 📋 Workflow de traslados: https://drive.google.com/file/d/19_1YgPe1Mb0ivRz6KpNEd70nGylTIFI0/view?usp=sharing

---

*Proyecto académico — I.S.B.O. UTU 2026.*
