<?php

require_once __DIR__ . "/../../Backend/config/auth.php";
require_once __DIR__ . "/../../Backend/models/Categoria.php";

header('Content-Type: application/json; charset=utf-8');

iniciarSesion();

// Verificar autenticación y permisos.
if (!usuarioAutenticado() || !esAdministrador()) {
    http_response_code(403);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'No autorizado.'
    ]);

    exit;
}

// Obtener y validar el ID desde la URL.
$id = filter_var(
    $_GET['id_categoria'] ?? null,
    FILTER_VALIDATE_INT
);

if ($id === false || $id === null || $id <= 0) {
    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'ID de categoría inválido.'
    ]);

    exit;
}

try {
    $categoriaModel = new Categoria();

    // Obtener la categoría desde el modelo.
    $categoria = $categoriaModel->obtenerPorId($id);

    if ($categoria === null) {
        http_response_code(404);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'La categoría no existe.'
        ]);

        exit;
    }

    echo json_encode([
        'ok' => true,
        'categoria' => $categoria
    ]);

} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al obtener la categoría.'
    ]);
}

exit;
