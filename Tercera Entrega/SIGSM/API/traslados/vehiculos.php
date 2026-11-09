<?php

require_once __DIR__ . "/../../Backend/models/Traslado.php";
require_once __DIR__ . "/../../Backend/config/auth.php";



header('Content-Type: application/json; charset=utf-8');

iniciarSesion();


/*
 * ============================================================
 * SEGURIDAD
 * ============================================================
 */

if (!usuarioAutenticado()) {

    http_response_code(403);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'No autorizado.'
    ]);

    exit;
}


/*
 * ============================================================
 * MÉTODO
 * ============================================================
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
 * ============================================================
 * OBTENER VEHÍCULOS
 * ============================================================
 */

try {

    $traslado = new Traslado();
    $vehiculos = $traslado->obtenerVehiculosDisponibles();

    echo json_encode([
        'ok' => true,
        'vehiculos' => $vehiculos
    ]);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al obtener los vehículos disponibles.'
    ]);
}

exit;