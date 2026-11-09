<?php

require_once __DIR__ . "/../../Backend/config/database.php";
require_once __DIR__ . "/../../Backend/config/auth.php";
require_once __DIR__ . "/../../Backend/models/Usuario.php";

header('Content-Type: application/json; charset=utf-8');

/*
 * Solo los administradores pueden consultar
 * información de usuarios.
 */
exigirAdministrador();

/*
 * Solo acepta solicitudes GET.
 */
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {

    http_response_code(405);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Método no permitido.'
    ]);

    exit;
}

/*
 * Obtiene el ID enviado mediante la URL.
 */
$idUsuario = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

/*
 * Verifica que el ID sea válido.
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

try {

    $usuarioModel = new Usuario();

    $usuario = $usuarioModel->obtenerPorId($idUsuario);

    /*
     * Usuario inexistente.
     */
    if ($usuario === null) {

        http_response_code(404);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'Usuario no encontrado.'
        ]);

        exit;
    }

    /*
     * Nunca envía la contraseña.
     */
    echo json_encode([
        'ok' => true,
        'usuario' => $usuario
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al obtener el usuario.'
    ]);
}

exit;