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
 * El frontend debe enviar el estado que muestra en pantalla.
 * Solo se aceptan estados definidos en el flujo de traslados.
 */
$estadoEsperado = is_string($datos['estado_actual'] ?? null)
    ? trim($datos['estado_actual'])
    : '';

$estadosConocidos = [
    'solicitado', 'aprobado', 'en_curso', 'llegado', 'regreso',
    'retornado', 'finalizado', 'cancelado', 'abortado'
];

if (!in_array($estadoEsperado, $estadosConocidos, true)) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Falta el estado actual del traslado.'
    ]);
    exit;
}

/*
 * ============================================================
 * ACTUALIZACIÓN
 * ============================================================
 */
try {
    $resultado = (new Traslado())->avanzarEstado((int) $idTraslado, $estadoEsperado);
    echo json_encode([
        'ok' => true,
        'mensaje' => 'Estado actualizado correctamente.',
        'estado_anterior' => $resultado['estado_anterior'],
        'estado_nuevo' => $resultado['estado_nuevo']
    ]);
} catch (DomainException $e) {
    http_response_code($e->getCode() === 409 ? 409 : 400);
    echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log('Error al avanzar el estado del traslado: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error interno al avanzar el traslado. Intente nuevamente.'
    ]);
}

exit;
