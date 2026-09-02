-- =========================================================
-- BASE DE DATOS SIGSM
-- =========================================================
--
-- Este script arma toda la base desde cero. Está ordenado en
-- bloques temáticos (usuarios, funcionarios, documentación,
-- vehículos, traslados) para que sea más fácil ubicarse, aunque
-- en el fondo todas las tablas terminan relacionadas entre sí
-- a través de las FOREIGN KEY.

CREATE DATABASE IF NOT EXISTS sigsm
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sigsm;


-- =========================================================
-- 1. USUARIOS
-- =========================================================

CREATE TABLE roles (
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    ci VARCHAR(20) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    telefono VARCHAR(30),
    email VARCHAR(150) NOT NULL UNIQUE,

    -- El nombre de usuario se guarda con una collation "_bin"
    -- (sensible a mayúsculas/minúsculas), a diferencia del resto
    -- de las columnas de texto. Esto es intencional: así "Admin"
    -- y "admin" se tratan como dos usuarios distintos, no como
    -- el mismo con mayúscula cambiada.
    user VARCHAR(50)
        CHARACTER SET utf8mb4
        COLLATE utf8mb4_bin
        NOT NULL UNIQUE,

    -- Acá nunca va la contraseña real: siempre un hash generado
    -- con password_hash() desde PHP (ver Backend/models/Usuario.php).
    password VARCHAR(255) NOT NULL,

    estado_laboral ENUM(
        'activo',
        'inactivo',
        'desvinculado'
    ) NOT NULL DEFAULT 'activo',

    id_rol INT NOT NULL,

    CONSTRAINT fk_usuarios_rol
        FOREIGN KEY (id_rol)
        REFERENCES roles(id_rol)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);


-- =========================================================
-- 2. FUNCIONARIOS
-- =========================================================

CREATE TABLE funcionarios (
    id_funcionario INT AUTO_INCREMENT PRIMARY KEY,

    ci VARCHAR(20) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    telefono VARCHAR(30),

    estado_laboral ENUM(
        'activo',
        'inactivo',
        'desvinculado'
    ) NOT NULL DEFAULT 'activo',

    estado_operativo ENUM(
        'libre',
        'ocupado'
    ) NOT NULL DEFAULT 'libre',

    puede_ser_chofer BOOLEAN NOT NULL DEFAULT FALSE,
    puede_ser_enfermero BOOLEAN NOT NULL DEFAULT FALSE,

    horario VARCHAR(100)
);


-- =========================================================
-- 3. DOCUMENTACIÓN
-- =========================================================

CREATE TABLE categorias (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    estado VARCHAR(50) NOT NULL
);

CREATE TABLE subcategorias (
    id_subcategoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    estado VARCHAR(50) NOT NULL,
    id_categoria INT NOT NULL,

    CONSTRAINT uq_subcategoria_categoria
        UNIQUE (id_categoria, nombre),

    CONSTRAINT fk_subcategoria_categoria
        FOREIGN KEY (id_categoria)
        REFERENCES categorias(id_categoria)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE TABLE documentos (
    id_documento INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    ruta VARCHAR(500) NOT NULL,
    estado VARCHAR(50) NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_subcategoria INT NOT NULL,

    CONSTRAINT fk_documento_subcategoria
        FOREIGN KEY (id_subcategoria)
        REFERENCES subcategorias(id_subcategoria)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE TABLE encuestas (
    id_encuesta INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    estado VARCHAR(50) NOT NULL,
    id_subcategoria INT NOT NULL UNIQUE,

    CONSTRAINT fk_encuesta_subcategoria
        FOREIGN KEY (id_subcategoria)
        REFERENCES subcategorias(id_subcategoria)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE TABLE preguntas (
    id_pregunta INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(50) NOT NULL,
    texto TEXT NOT NULL,
    id_encuesta INT NOT NULL,

    CONSTRAINT fk_pregunta_encuesta
        FOREIGN KEY (id_encuesta)
        REFERENCES encuestas(id_encuesta)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

CREATE TABLE respuestas (
    id_respuesta INT AUTO_INCREMENT PRIMARY KEY,
    texto TEXT,
    valor VARCHAR(255),
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_pregunta INT NOT NULL,

    CONSTRAINT fk_respuesta_pregunta
        FOREIGN KEY (id_pregunta)
        REFERENCES preguntas(id_pregunta)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);


-- =========================================================
-- 4. VEHÍCULOS
-- =========================================================

CREATE TABLE tipos_vehiculo (
    id_tipo_vehiculo INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE vehiculos (
    id_vehiculo INT AUTO_INCREMENT PRIMARY KEY,

    matricula VARCHAR(20) NOT NULL UNIQUE,

    estado_laboral ENUM(
        'activo',
        'inactivo',
        'desvinculado'
    ) NOT NULL DEFAULT 'activo',

    estado_operativo ENUM(
        'libre',
        'ocupado'
    ) NOT NULL DEFAULT 'libre',

    id_tipo_vehiculo INT NOT NULL,

    CONSTRAINT fk_vehiculo_tipo
        FOREIGN KEY (id_tipo_vehiculo)
        REFERENCES tipos_vehiculo(id_tipo_vehiculo)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);


-- =========================================================
-- 5. TRASLADOS
-- =========================================================
--
-- El campo "estado" define el ciclo de vida completo de un
-- traslado, paso a paso:
--
--   solicitado -> aprobado -> en_curso -> llegado
--   -> regreso -> retornado -> finalizado
--
-- (o bien "cancelado"/"abortado" si en algún punto el
-- traslado no se termina de completar). Cada cambio de estado
-- tiene su columna de fecha/hora correspondiente más abajo,
-- para poder reconstruir la línea de tiempo completa de
-- cualquier traslado.

CREATE TABLE traslados (
    id_traslado INT AUTO_INCREMENT PRIMARY KEY,

    id_vehiculo INT NOT NULL,

    origen VARCHAR(255) NOT NULL,
    destino VARCHAR(255) NOT NULL,
    prioridad VARCHAR(50) NOT NULL,

    estado ENUM(
        'solicitado',
        'aprobado',
        'en_curso',
        'llegado',
        'regreso',
        'retornado',
        'finalizado',
        'cancelado',
        'abortado'

    ) NOT NULL DEFAULT 'solicitado',

    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_hora_destino DATETIME NULL,
    hora_salida DATETIME NULL,
    hora_llegada DATETIME NULL,
    hora_regreso DATETIME NULL,
    hora_retorno DATETIME NULL,
    hora_finalizado DATETIME NULL,

    observaciones TEXT,

    CONSTRAINT fk_traslado_vehiculo
        FOREIGN KEY (id_vehiculo)
        REFERENCES vehiculos(id_vehiculo)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);


-- =========================================================
-- 6. PACIENTE ASOCIADO AL TRASLADO
-- =========================================================

CREATE TABLE paciente (
    id_traslado INT PRIMARY KEY,

    ci VARCHAR(20) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(30),
    quien_recibe VARCHAR(100),
    puerta VARCHAR(50),
    piso VARCHAR(50),
    sala VARCHAR(50),

    CONSTRAINT fk_paciente_traslado
        FOREIGN KEY (id_traslado)
        REFERENCES traslados(id_traslado)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);


-- =========================================================
-- 7. OTRO TIPO DE TRASLADO
-- =========================================================

CREATE TABLE otro (
    id_traslado INT PRIMARY KEY,

    descripcion TEXT NOT NULL,

    CONSTRAINT fk_otro_traslado
        FOREIGN KEY (id_traslado)
        REFERENCES traslados(id_traslado)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);


-- =========================================================
-- 8. FUNCIONARIOS ASIGNADOS A TRASLADOS
-- =========================================================
--
-- Tabla intermedia clásica para resolver una relación de
-- "muchos a muchos": un traslado puede llevar varios
-- funcionarios (un chofer y un enfermero, por ejemplo), y un
-- mismo funcionario a lo largo del tiempo participa en muchos
-- traslados distintos. "rol_en_traslado" es lo que aclara con
-- qué función va cada uno en ESE traslado en particular
-- (podría, en teoría, ser chofer en un traslado y enfermero
-- en otro, si estuviera habilitado para ambas cosas).

CREATE TABLE traslado_funcionario (
    id INT AUTO_INCREMENT PRIMARY KEY,

    id_traslado INT NOT NULL,
    id_funcionario INT NOT NULL,

    rol_en_traslado ENUM(
        'chofer',
        'enfermero'
    ) NOT NULL,

    -- Evita que el mismo funcionario quede asignado dos veces
    -- al mismo traslado (por ejemplo, como chofer Y como
    -- enfermero a la vez en el mismo viaje).
    CONSTRAINT uq_funcionario_traslado
        UNIQUE (id_traslado, id_funcionario),

    CONSTRAINT fk_tf_traslado
        FOREIGN KEY (id_traslado)
        REFERENCES traslados(id_traslado)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_tf_funcionario
        FOREIGN KEY (id_funcionario)
        REFERENCES funcionarios(id_funcionario)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

-- =========================================================
-- DATOS INICIALES (SEED)
-- =========================================================
--
-- Se cargan los dos roles básicos y dos usuarios de prueba
-- (uno de cada rol) para poder entrar al sistema apenas se
-- crea la base, sin tener que insertar nada a mano.
-- Las contraseñas de ambos ya están hasheadas con bcrypt tal
-- como las generaría password_hash() desde PHP: nunca se
-- guardan en texto plano, ni siquiera acá en los datos de
-- prueba.

INSERT INTO roles (nombre)
VALUES
    ('Administrador'),
    ('Operador');

INSERT INTO usuarios (
    ci,
    nombre,
    apellido,
    telefono,
    email,
    user,
    password,
    estado_laboral,
    id_rol
)
VALUES
(
    '50000001',
    'Juan',
    'Pérez',
    '099111111',
    'admin@sigsm.com',
    'Admin',
    '$2y$10$tFjRp9FVFKCCOiauwBgoDO2yzYvsvVtJu67AcSMQX4CWlSzACAiB6', /* user = Admin / password = admin123 */
    'activo',
    1
),
(
    '50000002',
    'Pedro',
    'Gómez',
    '099222222',
    'operador@sigsm.com',
    'Operador',
    '$2y$10$YjOA8pYrS3301C38i8IeyubH.XpEH0wmPl/xjm5NNOxG4nmwsquua', /* user = Operador / password = operador123 */
    'activo',
    2
);
