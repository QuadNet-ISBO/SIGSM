<?php

require_once __DIR__ . "/../../Backend/config/auth.php";
require_once __DIR__ . "/../../Backend/models/Subcategoria.php";

header('Content-Type: application/json; charset=utf-8');

iniciarSesion();

if (!usuarioAutenticado() || !esAdministrador()) {
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

$id = filter_var(
    $_GET['id'] ?? null,
    FILTER_VALIDATE_INT
);

if ($id === false || $id === null || $id <= 0) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'ID de subcategoría inválido.'
    ]);
    exit;
}

try {
    $modelo = new Subcategoria();

    $subcategoria = $modelo->obtenerPorId($id);

    if (!$subcategoria) {
        http_response_code(404);
        echo json_encode([
            'ok' => false,
            'mensaje' => 'Subcategoría no encontrada.'
        ]);
        exit;
    }

    echo json_encode([
        'ok' => true,
        'subcategoria' => $subcategoria
    ]);

} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al obtener la subcategoría.'
    ]);
}

exit;
