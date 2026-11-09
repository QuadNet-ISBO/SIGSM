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

// Verificar el método HTTP.
if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    http_response_code(405);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Método no permitido.'
    ]);

    exit;
}

// Obtener los datos enviados en formato JSON.
$datos = json_decode(
    file_get_contents('php://input'),
    true
);

if (!is_array($datos)) {
    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Los datos enviados no son válidos.'
    ]);

    exit;
}

// Validar el ID.
$id = filter_var(
    $datos['id_categoria'] ?? null,
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

// Validar el estado.
$estado = $datos['estado'] ?? '';

if (!is_string($estado) ||
    !in_array($estado, ['activa', 'desvinculada'], true)) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Estado inválido.'
    ]);

    exit;
}

try {
    $categoriaModel = new Categoria();

    $actualizada = $categoriaModel->cambiarEstado(
        $id,
        $estado
    );

    if (!$actualizada) {
        http_response_code(404);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'La categoría no existe.'
        ]);

        exit;
    }

    echo json_encode([
        'ok' => true,
        'mensaje' => $estado === 'desvinculada'
            ? 'Categoría y sus subcategorías desvinculadas correctamente.'
            : 'Categoría activada correctamente.'
    ]);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al cambiar el estado de la categoría.'
    ]);
}

exit;