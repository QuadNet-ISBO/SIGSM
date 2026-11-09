<?php

require_once __DIR__ . "/../../Backend/config/database.php";
require_once __DIR__ . "/../../Backend/config/auth.php";
require_once __DIR__ . "/../../Backend/models/Usuario.php";

header('Content-Type: application/json; charset=utf-8');

/*
 * Solo un administrador puede crear usuarios.
 */
exigirAdministrador();

/*
 * Solo acepta solicitudes POST.
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Método no permitido.'
    ]);

    exit;
}

/*
 * Recibe los datos enviados por el frontend.
 */
$datos = json_decode(
    file_get_contents('php://input'),
    true
);

if (!is_array($datos)) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Los datos enviados no son válidos.'
    ]);

    exit;
}

/*
 * Obtiene y limpia los datos.
 */
$ci = trim($datos['ci'] ?? '');
$nombre = trim($datos['nombre'] ?? '');
$apellido = trim($datos['apellido'] ?? '');
$telefono = trim($datos['telefono'] ?? '');
$email = trim($datos['email'] ?? '');
$user = trim($datos['user'] ?? '');
$password = $datos['password'] ?? '';

$idRol = filter_var(
    $datos['id_rol'] ?? null,
    FILTER_VALIDATE_INT
);

/*
 * Validaciones obligatorias.
 */
if (
    $ci === '' ||
    $nombre === '' ||
    $apellido === '' ||
    $email === '' ||
    $user === '' ||
    $password === '' ||
    $idRol === false
) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Todos los campos obligatorios deben estar completos.'
    ]);

    exit;
}

/*
 * Validación básica del correo electrónico.
 */
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'El correo electrónico no es válido.'
    ]);

    exit;
}

/*
 * Validación básica de contraseña.
 */
if (strlen($password) < 8) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'La contraseña debe tener al menos 8 caracteres.'
    ]);

    exit;
}

/*
 * Solo permite roles existentes.
 */
if (!in_array($idRol, [1, 2], true)) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'El rol seleccionado no es válido.'
    ]);

    exit;
}

try {

    $usuarioModel = new Usuario();

    /*
     * Verifica que el nombre de usuario no exista.
     */
    if ($usuarioModel->buscarPorUser($user) !== null) {

        http_response_code(409);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'El nombre de usuario ya está registrado.'
        ]);

        exit;
    }

    /*
     * Crea el usuario.
     * Usuario.php genera el hash de la contraseña.
     */
    $creado = $usuarioModel->crear(
        $ci,
        $nombre,
        $apellido,
        $telefono !== '' ? $telefono : null,
        $email,
        $user,
        $password,
        $idRol
    );

    if (!$creado) {

        http_response_code(500);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'No se pudo crear el usuario.'
        ]);

        exit;
    }

    echo json_encode([
        'ok' => true,
        'mensaje' => 'Usuario creado correctamente.'
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al crear el usuario.'
    ]);
}

exit;