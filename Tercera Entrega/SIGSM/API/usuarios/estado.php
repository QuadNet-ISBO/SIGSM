<?php

require_once __DIR__ . "/../../Backend/config/database.php";
require_once __DIR__ . "/../../Backend/config/auth.php";
require_once __DIR__ . "/../../Backend/models/Usuario.php";

header('Content-Type: application/json; charset=utf-8');

/*
 * Solo los administradores pueden cambiar
 * el estado laboral de los usuarios.
 */
exigirAdministrador();

/*
 * Solo acepta solicitudes PATCH.
 */
if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {

    http_response_code(405);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Método no permitido.'
    ]);

    exit;
}

/*
 * Recibe los datos enviados en JSON.
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

$estado = trim(
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
 * Estados permitidos por el sistema.
 */
$estadosPermitidos = [
    'activo',
    'inactivo',
    'desvinculado'
];

/*
 * Valida el estado.
 */
if (!in_array($estado, $estadosPermitidos, true)) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'El estado laboral no es válido.'
    ]);

    exit;
}

try {

    $usuarioModel = new Usuario();

    /*
     * Verifica que el usuario exista.
     */
    $usuario = $usuarioModel->obtenerPorId($idUsuario);

    if ($usuario === null) {

        http_response_code(404);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'Usuario no encontrado.'
        ]);

        exit;
    }

    /*
     * Una persona no puede dejarse a sí misma fuera del sistema:
     * desvincularse o inactivarse a sí misma. (Si necesita
     * hacerlo, que lo haga otro administrador.)
     */
    if (
        $idUsuario === (int) ($_SESSION['id_usuario'] ?? 0) &&
        $estado !== 'activo'
    ) {

        http_response_code(400);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'No puede desvincular ni inactivar su propia cuenta.'
        ]);

        exit;
    }

    /*
     * Actualiza el estado.
     */
    $actualizado = $usuarioModel->cambiarEstado(
        $idUsuario,
        $estado
    );

    if (!$actualizado) {

        http_response_code(500);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'No se pudo actualizar el estado.'
        ]);

        exit;
    }

    echo json_encode([
        'ok' => true,
        'mensaje' => 'Estado del usuario actualizado correctamente.'
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

    error_log('Error al actualizar el estado del usuario: ' . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al actualizar el estado del usuario.'
    ]);
}

exit;