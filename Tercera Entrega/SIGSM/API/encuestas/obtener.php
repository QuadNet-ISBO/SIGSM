<?php

require_once __DIR__ . "/../../Backend/config/auth.php";
require_once __DIR__ . "/../../Backend/models/Encuesta.php";

header('Content-Type: application/json; charset=utf-8');

iniciarSesion();

if (!usuarioAutenticado()) {
    http_response_code(403);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'No autorizado.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Método no permitido.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {
    $modelo = new Encuesta();
    $encuestas = $modelo->obtenerTodas();

    echo json_encode([
        'ok' => true,
        'encuestas' => $encuestas
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('Error al obtener las encuestas: ' . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al obtener las encuestas.'
    ], JSON_UNESCAPED_UNICODE);
}

exit;