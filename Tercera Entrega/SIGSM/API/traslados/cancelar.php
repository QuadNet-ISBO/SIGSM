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

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {

    http_response_code(405);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Método no permitido.'
    ]);

    exit;
}


/*
 * ============================================================
 * DATOS
 * ============================================================
 */

$datos = json_decode(
    file_get_contents('php://input'),
    true
);


$idTraslado = filter_var(
    $datos['id_traslado'] ?? null,
    FILTER_VALIDATE_INT
);


if (!$idTraslado) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'ID de traslado inválido.'
    ]);

    exit;
}


/*
 * ============================================================
 * TRANSACCIÓN
 * ============================================================
 */

try {
    $nuevoEstado = (new Traslado())->cancelar((int) $idTraslado);
    echo json_encode([
        'ok' => true,
        'mensaje' => $nuevoEstado === 'cancelado'
            ? 'Traslado cancelado correctamente.'
            : 'Traslado abortado correctamente.',
        'estado' => $nuevoEstado
    ]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
}

exit;
