-- =========================================================
-- SIGSM - BASE DE DATOS COMPLETA CON DATOS SEMILLA
-- MariaDB / MySQL
-- IMPORTANTE: importar en una base vacía para evitar duplicados.
-- Incluye estructura y datos semilla en un solo archivo.
-- Accesos de demostración: admin1 / admin123 y operador1 / operador123
-- =========================================================

CREATE DATABASE IF NOT EXISTS sigsm
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sigsm;

-- =========================================================
-- 1. USUARIOS Y ROLES
-- =========================================================

CREATE TABLE roles (
    id_rol INT NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    PRIMARY KEY (id_rol),
    UNIQUE KEY uq_roles_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE usuarios (
    id_usuario INT NOT NULL AUTO_INCREMENT,
    ci VARCHAR(20) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    telefono VARCHAR(30) DEFAULT NULL,
    email VARCHAR(150) NOT NULL,
    user VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    estado_laboral ENUM('activo','inactivo','desvinculado') NOT NULL DEFAULT 'activo',
    id_rol INT NOT NULL,
    PRIMARY KEY (id_usuario),
    UNIQUE KEY uq_usuarios_ci (ci),
    UNIQUE KEY uq_usuarios_email (email),
    UNIQUE KEY uq_usuarios_user (user),
    KEY fk_usuarios_rol (id_rol),
    CONSTRAINT fk_usuarios_rol FOREIGN KEY (id_rol)
        REFERENCES roles(id_rol) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE intentos_login (
    usuario_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    intentos TINYINT UNSIGNED NOT NULL DEFAULT 0,
    ventana_inicio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    bloqueado_hasta DATETIME DEFAULT NULL,
    PRIMARY KEY (usuario_hash)
) ENGINE=InnoDB;


-- =========================================================
-- 2. FUNCIONARIOS
-- =========================================================

CREATE TABLE funcionarios (
    id_funcionario INT NOT NULL AUTO_INCREMENT,
    ci VARCHAR(20) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    telefono VARCHAR(30) DEFAULT NULL,
    estado_laboral ENUM('activo','inactivo','desvinculado') NOT NULL DEFAULT 'activo',
    estado_operativo ENUM('libre','ocupado') NOT NULL DEFAULT 'libre',
    puede_ser_chofer TINYINT(1) NOT NULL DEFAULT 0,
    puede_ser_enfermero TINYINT(1) NOT NULL DEFAULT 0,
    horario VARCHAR(100) DEFAULT NULL,
    PRIMARY KEY (id_funcionario),
    UNIQUE KEY uq_funcionarios_ci (ci)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 3. DOCUMENTACIÓN Y ENCUESTAS
-- =========================================================

CREATE TABLE categorias (
    id_categoria INT NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    estado VARCHAR(50) NOT NULL,
    PRIMARY KEY (id_categoria),
    UNIQUE KEY uq_categorias_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE subcategorias (
    id_subcategoria INT NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    estado VARCHAR(50) NOT NULL,
    id_categoria INT NOT NULL,
    PRIMARY KEY (id_subcategoria),
    UNIQUE KEY uq_subcategoria_categoria (id_categoria, nombre),
    KEY fk_subcategoria_categoria (id_categoria),
    CONSTRAINT fk_subcategoria_categoria FOREIGN KEY (id_categoria)
        REFERENCES categorias(id_categoria) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE documentos (
    id_documento INT NOT NULL AUTO_INCREMENT,
    titulo VARCHAR(200) NOT NULL,
    ruta VARCHAR(500) NOT NULL,
    estado VARCHAR(50) NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_subcategoria INT NOT NULL,
    PRIMARY KEY (id_documento),
    KEY fk_documento_subcategoria (id_subcategoria),
    CONSTRAINT fk_documento_subcategoria FOREIGN KEY (id_subcategoria)
        REFERENCES subcategorias(id_subcategoria) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE encuestas (
    id_encuesta INT NOT NULL AUTO_INCREMENT,
    titulo VARCHAR(200) NOT NULL,
    estado ENUM('activa','retirada') NOT NULL DEFAULT 'activa',
    id_subcategoria INT NOT NULL,
    PRIMARY KEY (id_encuesta),
    KEY idx_encuestas_subcategoria (id_subcategoria),
    CONSTRAINT fk_encuesta_subcategoria FOREIGN KEY (id_subcategoria)
        REFERENCES subcategorias(id_subcategoria) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE preguntas (
    id_pregunta INT NOT NULL AUTO_INCREMENT,
    tipo VARCHAR(50) NOT NULL,
    texto TEXT NOT NULL,
    orden INT NOT NULL DEFAULT 1,
    id_encuesta INT NOT NULL,
    PRIMARY KEY (id_pregunta),
    KEY fk_pregunta_encuesta (id_encuesta),
    CONSTRAINT fk_pregunta_encuesta FOREIGN KEY (id_encuesta)
        REFERENCES encuestas(id_encuesta) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE opciones_pregunta (
    id_opcion INT NOT NULL AUTO_INCREMENT,
    texto_opcion VARCHAR(255) NOT NULL,
    orden INT NOT NULL DEFAULT 1,
    id_pregunta INT NOT NULL,
    PRIMARY KEY (id_opcion),
    KEY fk_opcion_pregunta (id_pregunta),
    CONSTRAINT fk_opcion_pregunta FOREIGN KEY (id_pregunta)
        REFERENCES preguntas(id_pregunta) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE respuestas (
    id_respuesta INT NOT NULL AUTO_INCREMENT,
    texto TEXT DEFAULT NULL,
    valor VARCHAR(255) DEFAULT NULL,
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_pregunta INT NOT NULL,
    id_opcion INT DEFAULT NULL,
    PRIMARY KEY (id_respuesta),
    KEY fk_respuesta_pregunta (id_pregunta),
    KEY fk_respuesta_opcion (id_opcion),
    CONSTRAINT fk_respuesta_opcion FOREIGN KEY (id_opcion)
        REFERENCES opciones_pregunta(id_opcion) ON UPDATE CASCADE,
    CONSTRAINT fk_respuesta_pregunta FOREIGN KEY (id_pregunta)
        REFERENCES preguntas(id_pregunta) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 4. VEHÍCULOS
-- =========================================================

CREATE TABLE tipos_vehiculo (
    id_tipo_vehiculo INT NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    PRIMARY KEY (id_tipo_vehiculo),
    UNIQUE KEY uq_tipos_vehiculo_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE vehiculos (
    id_vehiculo INT NOT NULL AUTO_INCREMENT,
    matricula VARCHAR(20) NOT NULL,
    estado_laboral ENUM('activo','inactivo','desvinculado') NOT NULL DEFAULT 'activo',
    estado_operativo ENUM('libre','ocupado') NOT NULL DEFAULT 'libre',
    id_tipo_vehiculo INT NOT NULL,
    PRIMARY KEY (id_vehiculo),
    UNIQUE KEY uq_vehiculos_matricula (matricula),
    KEY fk_vehiculo_tipo (id_tipo_vehiculo),
    CONSTRAINT fk_vehiculo_tipo FOREIGN KEY (id_tipo_vehiculo)
        REFERENCES tipos_vehiculo(id_tipo_vehiculo) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 5. TRASLADOS
-- =========================================================

CREATE TABLE traslados (
    id_traslado INT NOT NULL AUTO_INCREMENT,
    id_vehiculo INT NOT NULL,
    origen VARCHAR(255) NOT NULL,
    destino VARCHAR(255) NOT NULL,
    prioridad VARCHAR(50) NOT NULL,
    estado ENUM('solicitado','aprobado','en_curso','llegado','regreso','retornado','finalizado','cancelado','abortado')
        NOT NULL DEFAULT 'solicitado',
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_hora_partida DATETIME NULL,
    fecha_hora_destino DATETIME DEFAULT NULL,
    hora_salida DATETIME DEFAULT NULL,
    hora_llegada DATETIME DEFAULT NULL,
    hora_regreso DATETIME DEFAULT NULL,
    hora_retorno DATETIME DEFAULT NULL,
    hora_finalizado DATETIME DEFAULT NULL,
    observaciones TEXT DEFAULT NULL,
    PRIMARY KEY (id_traslado),
    KEY fk_traslado_vehiculo (id_vehiculo),
    CONSTRAINT fk_traslado_vehiculo FOREIGN KEY (id_vehiculo)
        REFERENCES vehiculos(id_vehiculo) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE paciente (
    id_traslado INT NOT NULL,
    ci VARCHAR(20) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(30) DEFAULT NULL,
    quien_recibe VARCHAR(100) DEFAULT NULL,
    puerta VARCHAR(50) DEFAULT NULL,
    piso VARCHAR(50) DEFAULT NULL,
    sala VARCHAR(50) DEFAULT NULL,
    PRIMARY KEY (id_traslado),
    CONSTRAINT fk_paciente_traslado FOREIGN KEY (id_traslado)
        REFERENCES traslados(id_traslado) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE otro (
    id_traslado INT NOT NULL,
    descripcion TEXT NOT NULL,
    PRIMARY KEY (id_traslado),
    CONSTRAINT fk_otro_traslado FOREIGN KEY (id_traslado)
        REFERENCES traslados(id_traslado) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE traslado_funcionario (
    id INT NOT NULL AUTO_INCREMENT,
    id_traslado INT NOT NULL,
    id_funcionario INT NOT NULL,
    rol_en_traslado ENUM('chofer','enfermero') NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_funcionario_traslado (id_traslado, id_funcionario),
    KEY fk_tf_funcionario (id_funcionario),
    CONSTRAINT fk_tf_traslado FOREIGN KEY (id_traslado)
        REFERENCES traslados(id_traslado) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_tf_funcionario FOREIGN KEY (id_funcionario)
        REFERENCES funcionarios(id_funcionario) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 6. DATOS SEMILLA
-- =========================================================

-- 6.1. ROLES
-- El sistema define solamente estos dos roles.
INSERT INTO roles (id_rol, nombre) VALUES
(1, 'Administrador'),
(2, 'Operador');

-- 6.2. USUARIOS
-- Usuario: admin1 Contraseña: admin123 (hash bcrypt).
INSERT INTO usuarios (id_usuario, ci, nombre, apellido, telefono, email, user, password, estado_laboral, id_rol) VALUES
(1, '50000002', 'Admin', 'Sistema', '099000000', 'admin1@sigsm.com', 'admin1', '$2y$10$tFjRp9FVFKCCOiauwBgoDO2yzYvsvVtJu67AcSMQX4CWlSzACAiB6', 'activo', 1);

-- Usuario: operador1 Contraseña: operador123 (bcrypt)
INSERT INTO usuarios
(id_usuario, ci, nombre, apellido, telefono, email, user, password, estado_laboral, id_rol) VALUES
(2, '50000003', 'Operador', 'Sistema', '098000000', 'operador1@sigsm.com', 'operador1', '$2y$12$wJXXgvMgXjkWnCIYO9Gzse2HpXYNwWrdHwqdC6cpeCkcF0s/ei/1K', 'activo', 2);

-- 6.3. FUNCIONARIOS
INSERT INTO funcionarios (id_funcionario, ci, nombre, apellido, telefono, estado_laboral, estado_operativo, puede_ser_chofer, puede_ser_enfermero, horario) VALUES
(1, '51000001', 'Juan', 'Gonzalez', '098100001', 'activo', 'libre', 1, 0, 'Matutino'),
(2, '51000002', 'Sofia', 'Martinez', '098100002', 'activo', 'libre', 0, 1, 'Matutino'),
(3, '51000003', 'Andres', 'Suarez', '098100003', 'activo', 'libre', 1, 1, 'Matutino'),
(4, '51000004', 'Diego', 'Lopez', '098100004', 'activo', 'libre', 1, 0, 'Diurno'),
(5, '51000005', 'Valentina', 'Silva', '098100005', 'activo', 'libre', 0, 1, 'Diurno'),
(6, '51000006', 'Paula', 'Torres', '098100006', 'activo', 'libre', 1, 1, 'Diurno'),
(7, '51000007', 'Nicolas', 'Perez', '098100007', 'activo', 'libre', 1, 0, 'Vespertino'),
(8, '51000008', 'Florencia', 'Diaz', '098100008', 'activo', 'libre', 0, 1, 'Vespertino'),
(9, '51000009', 'Gabriel', 'Castro', '098100009', 'activo', 'libre', 1, 1, 'Vespertino');

-- 6.4. TIPOS DE VEHICULO
INSERT INTO tipos_vehiculo (id_tipo_vehiculo, nombre) VALUES
(1, 'Ambulancia'),
(2, 'Auto de traslado');

-- 6.5. VEHICULOS
INSERT INTO vehiculos (id_vehiculo, matricula, estado_laboral, estado_operativo, id_tipo_vehiculo) VALUES
(1, 'SAA1234', 'activo', 'libre', 1),
(2, 'SAB2345', 'activo', 'libre', 1),
(3, 'SAC3456', 'activo', 'libre', 1),
(4, 'SAD4567', 'activo', 'libre', 2),
(5, 'SAE5678', 'activo', 'libre', 2),
(6, 'SAF6789', 'activo', 'libre', 2);

-- 6.6. CATEGORIAS
INSERT INTO categorias (id_categoria, nombre, estado) VALUES
(1, 'Medicina General', 'activa'),
(2, 'Cardiologia', 'activa'),
(3, 'Neurologia', 'activa'),
(4, 'Nefrologia', 'activa');

-- 6.7. SUBCATEGORIAS
INSERT INTO subcategorias (id_subcategoria, nombre, estado, id_categoria) VALUES
(1, 'Atencion Primaria', 'activa', 1),
(2, 'Medicina Preventiva', 'activa', 1),
(3, 'Hipertension Arterial', 'activa', 2),
(4, 'Enfermedades Coronarias', 'activa', 2),
(5, 'Epilepsia', 'activo', 3),
(6, 'Enfermedades Neurodegenerativas', 'activa', 3),
(7, 'Trastornos Cerebrovasculares', 'activa', 3),
(8, 'Hemodialisis', 'activa', 4);

-- 6.8 DOCUEMENTOS
INSERT INTO `documentos` (`id_documento`, `titulo`, `ruta`, `estado`, `fecha`, `id_subcategoria`) VALUES
(1, 'Atención primaria: puerta de entrada al sistema de salud', 'uploads/documentos/34594ac0aeecfa712e060c8a1ce203eb.pdf', 'activo', '2026-11-08 19:50:01', 1),
(2, 'Preparación para una consulta médica general', 'uploads/documentos/05ceb309b931cee3583d2a2bb10d467a.pdf', 'activo', '2026-11-08 19:50:26', 1),
(3, 'Medicina preventiva y controles de salud', 'uploads/documentos/ad4b7b1d48cc51d566cc485f50778e03.pdf', 'activo', '2026-11-08 19:50:42', 2),
(4, 'Hipertensión arterial: conceptos y seguimiento', 'uploads/documentos/ccceabc9126152131c21d36ce3f21a01.pdf', 'activo', '2026-11-08 19:51:01', 3),
(5, 'Hábitos cardiovasculares y control de la presión', 'uploads/documentos/42fe191e26ac2436da36c2f306bb3988.pdf', 'activo', '2026-11-08 19:51:14', 3),
(6, 'Enfermedad coronaria: prevención y señales de alerta', 'uploads/documentos/15f605332fc3f6ceecb58f004074b007.pdf', 'activo', '2026-11-08 19:51:29', 4),
(7, 'Epilepsia: información básica y acompañamiento', 'uploads/documentos/dc22007d031c56d5e1092c0719784994.pdf', 'activo', '2026-11-08 19:51:58', 6),
(8, 'Enfermedades neurodegenerativas: comprensión y cuidados', 'uploads/documentos/bd36016e1b0f56835cf33dedf2c2b0de.pdf', 'activo', '2026-11-08 19:52:16', 6),
(9, 'Accidente cerebrovascular: reconocimiento temprano', 'uploads/documentos/0dbfd34f1c2791cdc006433112937b2a.pdf', 'activo', '2026-11-08 19:52:40', 7),
(10, 'Rehabilitación y apoyo tras un evento cerebrovascular', 'uploads/documentos/d4285ad0569be5aa609cc60b7475a3b6.pdf', 'activo', '2026-11-08 19:52:56', 7),
(11, 'Hemodiálisis: información para pacientes y acompañantes', 'uploads/documentos/27b3dfcc7d8506bbb67edbce74216f06.pdf', 'activo', '2026-11-08 19:53:10', 8),
(12, 'Preparación y cuidados alrededor de la hemodiálisis', 'uploads/documentos/b63bc95b731c8c04bfcfe6a392b3be93.pdf', 'activo', '2026-11-08 19:53:23', 8);

-- 6.9 ENCUESTAS
INSERT INTO encuestas
(id_encuesta, titulo, estado, id_subcategoria) VALUES
(1, 'Comprensión del material de Atención Primaria', 'activa', 1),
(2, 'Comprensión del material de Hipertensión Arterial', 'activa', 3),
(3, 'Reconocimiento y recuperación ante eventos cerebrovasculares', 'activa', 7),
(4, 'Comprensión del material de Hemodiálisis', 'activa', 8),
(5, 'Hábitos y controles de Medicina Preventiva', 'activa', 2),
(6, 'Prevención y señales de alerta coronaria', 'activa', 4),
(7, 'Información y acompañamiento en Epilepsia', 'activa', 5),
(8, 'Cuidados en enfermedades neurodegenerativas', 'activa', 6);

-- 6.10 PREGUNTAS
INSERT INTO preguntas (id_pregunta, tipo, texto, orden, id_encuesta) VALUES
-- ENCUESTA 1 - ATENCION PRIMARIA
(1, 'opcion_unica', '¿Qué tan clara te resultó la información de esta subcategoría?', 1, 1),
(2, 'opcion_unica', '¿Te resultó útil para comprender los cuidados y controles?', 2, 1),
(3, 'opcion_multiple', '¿Qué aspectos del material te gustaría conocer mejor?', 3, 1),

-- ENCUESTA 2 - HIPERTENSION ARTERIAL
(4, 'opcion_unica', '¿Qué tan clara te resultó la información de esta subcategoría?', 1, 2),
(5, 'opcion_unica', '¿Te resultó útil para comprender los cuidados y controles?', 2, 2),
(6, 'opcion_multiple', '¿Qué aspectos del material te gustaría conocer mejor?', 3, 2),

-- ENCUESTA 3 - TRASTORNOS CEREBROVASCULARES
(7, 'opcion_unica', '¿Reconocés que una dificultad repentina para hablar puede ser una señal de alerta de ACV?', 1, 3),
(8, 'opcion_unica', '¿Qué harías ante una sospecha de ACV?', 2, 3),
(9, 'opcion_multiple', '¿Qué aspectos de la recuperación tras un ACV te interesan?', 3, 3),

-- ENCUESTA 4 - HEMODIALISIS
(10, 'opcion_unica', '¿Qué tan clara te resultó la información de esta subcategoría?', 1, 4),
(11, 'opcion_unica', '¿Te resultó útil para comprender los cuidados y controles?', 2, 4),
(12, 'opcion_multiple', '¿Qué aspectos del material te gustaría conocer mejor?', 3, 4),

-- ENCUESTA 5 - MEDICINA PREVENTIVA
(13, 'opcion_unica', '¿Qué medida preventiva mencionada en la guía considerás más importante para vos?', 1, 5),
(14, 'opcion_multiple', '¿Qué hábitos saludables te gustaría mejorar?', 2, 5),

-- ENCUESTA 6 - ENFERMEDADES CORONARIAS
(15, 'opcion_unica', '¿Qué factor se menciona en el material como riesgo de enfermedad coronaria?', 1, 6),
(16, 'opcion_multiple', '¿Qué temas de prevención coronaria te interesan?', 2, 6),

-- ENCUESTA 7 - EPILEPSIA
(17, 'opcion_unica', '¿Sabías que no todas las crisis epilépticas incluyen convulsiones?', 1, 7),
(18, 'opcion_multiple', '¿Qué aspectos del acompañamiento en epilepsia te interesan?', 2, 7),

-- ENCUESTA 8 - ENFERMEDADES NEURODEGENERATIVAS
(19, 'opcion_multiple', '¿Qué aspectos del cuidado te interesa conocer mejor?', 1, 8),
(20, 'opcion_multiple', '¿Qué aspectos del cuidado cotidiano te gustaría conocer mejor?', 2, 8);

-- 6.11 OPCIONES_PREGUNTA
INSERT INTO opciones_pregunta (id_opcion, texto_opcion, orden, id_pregunta) VALUES
-- PREGUNTA 1 - ATENCION PRIMARIA
(1, 'Muy clara', 1, 1),
(2, 'Clara', 2, 1),
(3, 'Poco clara', 3, 1),
(4, 'Nada clara', 4, 1),

-- PREGUNTA 2
(5, 'Muy útil', 1, 2),
(6, 'Útil', 2, 2),
(7, 'Poco útil', 3, 2),
(8, 'Nada útil', 4, 2),

-- PREGUNTA 3
(9, 'Conceptos generales', 1, 3),
(10, 'Cuidados y recomendaciones', 2, 3),
(11, 'Controles y seguimiento médico', 3, 3),

-- PREGUNTA 4 - HIPERTENSION ARTERIAL
(12, 'Muy clara', 1, 4),
(13, 'Clara', 2, 4),
(14, 'Poco Clara', 3, 4),
(15, 'Nada Clara', 4, 4),

-- PREGUNTA 5
(16, 'Muy útil', 1, 5),
(17, 'Útil', 2, 5),
(18, 'Poco útil', 3, 5),
(19, 'Nada útil', 4, 5),

-- PREGUNTA 6
(20, 'Conceptos generales', 1, 6),
(21, 'Cuidados y recomendaciones', 2, 6),
(22, 'Controles y seguimiento médico', 3, 6),

-- PREGUNTA 7 - ACV
(23, 'Si', 1, 7),
(24, 'No', 2, 7),
(25, 'No estoy seguro/a', 3, 7),

-- PREGUNTA 8
(26, 'Solicitar ayuda médica de emergencia', 1, 8),
(27, 'Esperar a que pase', 2, 8),
(28, 'Dar medicación por cuenta propia', 3, 8),
(29, 'No sé', 4, 8),

-- PREGUNTA 9
(30, 'Rehabilitación física', 1, 9),
(31, 'Recuperación del habla y la comunicación', 2, 9),
(32, 'Apoyo familiar y emocional', 3, 9),

-- PREGUNTA 10 - HEMODIALISIS
(33, 'Muy clara', 1, 10),
(34, 'Clara', 2, 10),
(35, 'Poco clara', 3, 10),
(36, 'Nada clara', 4, 10),

-- PREGUNTA 11
(37, 'Muy útil', 1, 11),
(38, 'Útil', 2, 11),
(39, 'Poco útil', 3, 11),
(40, 'Nada útil', 4, 11),

-- PREGUNTA 12
(41, 'Conceptos generales', 1, 12),
(42, 'Cuidados y recomendaciones', 2, 12),
(43, 'Controles y seguimiento médico', 3, 12),

-- PREGUNTA 13 - MEDICINA PREVENTIVA
(44, 'Controles de salud', 1, 13),
(45, 'Vacunación', 2, 13),
(46, 'Alimentación y actividad física', 3, 13),
(47, 'Todas las anteriores', 4, 13),

-- PREGUNTA 14
(48, 'Alimentación saludable', 1, 14),
(49, 'Actividad física', 2, 14),
(50, 'Controles médicos periódicos', 3, 14),

-- PREGUNTA 15 - ENFERMEDADES CORONARIAS
(51, 'Tabaquismo', 1, 15),
(52, 'Hipertensión', 2, 15),
(53, 'Diabetes', 3, 15),
(54, 'Todos los anteriores', 4, 15),

-- PREGUNTA 16
(55, 'Factores de riesgo cardiovascular', 1, 16),
(56, 'Hábitos para prevenir enfermedades coronarias', 2, 16),
(57, 'Reconocimiento de señales de alerta', 3, 16),

-- PREGUNTA 17 - EPILEPSIA
(58, 'Sí', 1, 17),
(59, 'No', 2, 17),
(60, 'No estoy seguro/a', 3, 17),

-- PREGUNTA 18
(61, 'Cómo actuar ante una crisis', 1, 18),
(62, 'Acompañamiento familiar', 2, 18),
(63, 'Cuidados y seguimiento cotidiano', 3, 18),

-- PREGUNTA 19 - ENFERMEDADES NEURODEGENERATIVAS
(64, 'Memoria y comunicación', 1, 19),
(65, 'Movilidad y autonomía', 2, 19),
(66, 'Apoyo familiar', 3, 19),
(67, 'Seguimiento profesional', 4, 19),

-- PREGUNTA 20
(68, 'Apoyo en las actividades diarias', 1, 20),
(69, 'Comunicación con el paciente', 2, 20),
(70, 'Autonomía y movilidad', 3, 20);

-- =========================================================
-- FIN DEL SCRIPT
-- =========================================================
