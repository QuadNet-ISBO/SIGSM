<?php

require_once __DIR__ . "/../../Backend/config/auth.php";
require_once __DIR__ . "/../../Backend/models/Subcategoria.php";

header('Content-Type: application/json; charset=utf-8');

iniciarSesion();

/*
 * SEGURIDAD
 * Cualquier usuario autenticado puede consultar.
 */

if (!usuarioAutenticado()) {
    http_response_code(403);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'No autorizado.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

/*
 * MÉTODO
 */

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Método no permitido.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

/*
 * LISTAR SUBCATEGORÍAS
 */

try {
    $modelo = new Subcategoria();

    echo json_encode([
        'ok' => true,
        'subcategorias' => $modelo->obtenerTodas()
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('Error al obtener las subcategorías: ' . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al obtener las subcategorías.'
    ], JSON_UNESCAPED_UNICODE);
}

exit;
