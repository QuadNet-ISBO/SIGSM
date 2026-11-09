<?php

require_once __DIR__ . "/../../Backend/config/auth.php";

header('Content-Type: application/json; charset=utf-8');

iniciarSesion();


/*
 * ============================================================
 * VERIFICAR SESIÓN
 * ============================================================
 */

if (!usuarioAutenticado()) {

    http_response_code(401);

    echo json_encode([
        'ok' => false,
        'autenticado' => false,
        'autorizado' => false,
        'mensaje' => 'No hay una sesión activa.'
    ]);

    exit;
}


/*
 * ============================================================
 * VERIFICAR DATOS DE SESIÓN
 * ============================================================
 */

if (
    !isset(
        $_SESSION['id_usuario'],
        $_SESSION['user'],
        $_SESSION['nombre'],
        $_SESSION['apellido'],
        $_SESSION['rol']
    )
) {

    http_response_code(401);

    echo json_encode([
        'ok' => false,
        'autenticado' => false,
        'autorizado' => false,
        'mensaje' => 'La sesión no contiene los datos necesarios.'
    ]);

    exit;
}


/*
 * ============================================================
 * ROL SOLICITADO
 * ============================================================
 */

$rolSolicitado =
    $_GET['rol'] ?? null;


if (
    $rolSolicitado !== null &&
    obtenerRolUsuario() !== $rolSolicitado
) {

    http_response_code(403);

    echo json_encode([
        'ok' => false,
        'autenticado' => true,
        'autorizado' => false,
        'mensaje' => 'No tiene permisos para acceder a esta sección.'
    ]);

    exit;
}


/*
 * ============================================================
 * SESIÓN VÁLIDA
 * ============================================================
 */

echo json_encode([

    'ok' => true,

    'autenticado' => true,

    'autorizado' => true,

    'usuario' => [

        'id_usuario' =>
            $_SESSION['id_usuario'],

        'user' =>
            $_SESSION['user'],

        'nombre' =>
            $_SESSION['nombre'],

        'apellido' =>
            $_SESSION['apellido'],

        'rol' =>
            $_SESSION['rol']
    ]

]);

exit;