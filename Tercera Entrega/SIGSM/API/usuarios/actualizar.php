<?php

require_once __DIR__ . "/../../Backend/config/database.php";
require_once __DIR__ . "/../../Backend/config/auth.php";
require_once __DIR__ . "/../../Backend/models/Usuario.php";

header('Content-Type: application/json; charset=utf-8');

/*
 * Solo los administradores pueden actualizar usuarios.
 */
exigirAdministrador();

/*
 * Solo acepta solicitudes PUT.
 */
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {

    http_response_code(405);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Método no permitido.'
    ]);

    exit;
}

/*
 * Recibe los datos enviados en formato JSON.
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
 * Obtiene los datos.
 */
$idUsuario = filter_var(
    $datos['id_usuario'] ?? null,
    FILTER_VALIDATE_INT
);

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

$estadoLaboral = trim(
    $datos['estado_laboral'] ?? ''
);

/*
 * Valida el ID.
 */
if (
    $idUsuario === false ||
    $idUsuario === null ||
    $idUsuario <= 0
) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'El ID del usuario no es válido.'
    ]);

    exit;
}

/*
 * Valida campos obligatorios.
 */
if (
    $ci === '' ||
    $nombre === '' ||
    $apellido === '' ||
    $email === '' ||
    $user === '' ||
    $idRol === false ||
    $estadoLaboral === ''
) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Todos los campos obligatorios deben estar completos.'
    ]);

    exit;
}

/*
 * Valida el correo.
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
 * Valida el rol.
 */
if (!in_array($idRol, [1, 2], true)) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'El rol seleccionado no es válido.'
    ]);

    exit;
}

/*
 * Valida el estado laboral.
 */
$estadosPermitidos = [
    'activo',
    'inactivo',
    'desvinculado'
];

if (!in_array($estadoLaboral, $estadosPermitidos, true)) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'El estado laboral seleccionado no es válido.'
    ]);

    exit;
}

/*
 * Si se envía una nueva contraseña,
 * debe cumplir el mínimo de seguridad.
 */
if (
    $password !== '' &&
    strlen($password) < 8
) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'La contraseña debe tener al menos 8 caracteres.'
    ]);

    exit;
}

try {

    $usuarioModel = new Usuario();

    /*
     * Verifica que el usuario exista.
     */
    $usuarioExistente =
        $usuarioModel->obtenerPorId($idUsuario);

    if ($usuarioExistente === null) {

        http_response_code(404);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'Usuario no encontrado.'
        ]);

        exit;
    }

    /*
     * Verifica que el nombre de usuario
     * no pertenezca a otro usuario.
     */
    $usuarioPorNombre =
        $usuarioModel->buscarPorUser($user);

    if (
        $usuarioPorNombre !== null &&
        (int) $usuarioPorNombre['id_usuario']
            !== (int) $idUsuario
    ) {

        http_response_code(409);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'El nombre de usuario ya está registrado.'
        ]);

        exit;
    }

    /*
     * Una persona puede editar sus propios datos (nombre, teléfono,
     * email...), pero no su propio rol ni su propio estado laboral:
     * podría dejarse sin acceso a su propio panel.
     */
    if (
        (int) $idUsuario === (int) ($_SESSION['id_usuario'] ?? 0) &&
        (
            (int) $idRol !== (int) $usuarioExistente['id_rol'] ||
            $estadoLaboral !== $usuarioExistente['estado_laboral']
        )
    ) {

        http_response_code(400);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'No puede modificar su propio rol ni su propio estado laboral.'
        ]);

        exit;
    }

    /*
     * Actualiza los datos.
     *
     * Si password está vacío,
     * conserva la contraseña actual.
     */
    $actualizado = $usuarioModel->actualizar(
        $idUsuario,
        $ci,
        $nombre,
        $apellido,
        $telefono !== '' ? $telefono : null,
        $email,
        $user,
        $idRol,
        $estadoLaboral,
        $password !== '' ? $password : null
    );

    if (!$actualizado) {

        http_response_code(500);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'No se pudo actualizar el usuario.'
        ]);

        exit;
    }

    echo json_encode([
        'ok' => true,
        'mensaje' => 'Usuario actualizado correctamente.'
    ]);

} catch (DomainException $e) {

    /*
     * El modelo avisa que el cambio dejaría al sistema sin
     * ningún administrador activo.
     */
    http_response_code(409);

    echo json_encode([
        'ok' => false,
        'mensaje' => $e->getCode() === Usuario::ULTIMO_ADMINISTRADOR
            ? $e->getMessage()
            : 'No se pudo completar la operación.'
    ]);

} catch (PDOException $e) {

    error_log('Error al actualizar el usuario: ' . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al actualizar el usuario.'
    ]);
}

exit;