<?php

require_once __DIR__ . "/../../Backend/models/Traslado.php";
require_once __DIR__ . "/../../Backend/config/auth.php";



header('Content-Type: application/json; charset=utf-8');

iniciarSesion();

if (!usuarioAutenticado()) {

    http_response_code(403);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'No autorizado.'
    ]);

    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {

    http_response_code(405);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Método no permitido.'
    ]);

    exit;
}

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'ID de traslado inválido.'
    ]);

    exit;
}

try {

    $traslado = (new Traslado())->obtenerPorId($id);

    if (!$traslado) {

        http_response_code(404);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'Traslado no encontrado.'
        ]);

        exit;
    }


    echo json_encode([
        'ok' => true,
        'traslado' => $traslado
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al obtener el traslado.'
    ]);
}

exit;