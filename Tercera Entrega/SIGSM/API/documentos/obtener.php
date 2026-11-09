<?php

require_once __DIR__ . "/../../Backend/config/database.php";
require_once __DIR__ . "/../../Backend/config/auth.php";
require_once __DIR__ . "/../../Backend/models/Documento.php";

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
    http_response_code(405);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Método no permitido.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$idDocumento = filter_input(INPUT_GET, 'id_documento', FILTER_VALIDATE_INT);

if (!$idDocumento || $idDocumento <= 0) {
    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'El ID del documento no es válido.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {
    $documentoModelo = new Documento();
    $documento = $documentoModelo->obtenerPorId($idDocumento);

    if (!$documento) {
        http_response_code(404);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'El documento no existe.'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    echo json_encode([
        'ok' => true,
        'documento' => $documento
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('Error al obtener documento: ' . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al obtener el documento.'
    ], JSON_UNESCAPED_UNICODE);
}

exit;