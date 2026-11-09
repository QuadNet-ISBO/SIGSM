<?php

require_once __DIR__ . "/../../Backend/config/auth.php";

header('Content-Type: application/json; charset=utf-8');

iniciarSesion();


/*
 * ============================================================
 * MÉTODO
 * ============================================================
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
 * ============================================================
 * OBTENER PARÁMETROS DE LA COOKIE
 * ============================================================
 */

$params =
    session_get_cookie_params();


/*
 * ============================================================
 * ELIMINAR VARIABLES DE SESIÓN
 * ============================================================
 */

$_SESSION = [];


/*
 * ============================================================
 * ELIMINAR COOKIE
 * ============================================================
 */

if (
    ini_get('session.use_cookies')
) {

    setcookie(
        session_name(),
        '',
        [
            'expires' =>
                time() - 42000,

            'path' =>
                $params['path'],

            'domain' =>
                $params['domain'],

            'secure' =>
                $params['secure'],

            'httponly' =>
                $params['httponly'],

            'samesite' =>
                $params['samesite'] ?? 'Lax'
        ]
    );
}


/*
 * ============================================================
 * DESTRUIR SESIÓN
 * ============================================================
 */

session_destroy();


/*
 * ============================================================
 * RESPUESTA
 * ============================================================
 */

echo json_encode([
    'ok' => true,
    'autenticado' => false,
    'mensaje' => 'Sesión cerrada correctamente.'
]);

exit;