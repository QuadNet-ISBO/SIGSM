<?php

require_once __DIR__ . "/../../Backend/config/auth.php";
require_once __DIR__ . "/../../Backend/models/Categoria.php";

header('Content-Type: application/json; charset=utf-8');

iniciarSesion();

// Permitir la consulta a cualquier usuario autenticado.
if (!usuarioAutenticado()) {
    http_response_code(403);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'No autorizado.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {
    $categoriaModel = new Categoria();

    $categorias = $categoriaModel->obtenerTodas();

    echo json_encode([
        'ok' => true,
        'categorias' => $categorias
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('Error al obtener las categorías: ' . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al obtener las categorías.'
    ], JSON_UNESCAPED_UNICODE);
}

exit;
