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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Método no permitido.'
    ]);
    exit;
}

$datos = json_decode(file_get_contents('php://input'), true);

if (!is_array($datos)) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Los datos enviados no son válidos.'
    ]);
    exit;
}

$idCategoria = filter_var(
    $datos['id_categoria'] ?? null,
    FILTER_VALIDATE_INT
);

$nombre = trim($datos['nombre'] ?? '');

if ($idCategoria === false || $idCategoria === null || $idCategoria <= 0) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'La categoría es obligatoria.'
    ]);
    exit;
}

if ($nombre === '') {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'El nombre de la subcategoría es obligatorio.'
    ]);
    exit;
}

try {
    $modelo = new Subcategoria();

    $id = $modelo->crear($idCategoria, $nombre);

    echo json_encode([
        'ok' => true,
        'mensaje' => 'Subcategoría creada correctamente.',
        'id_subcategoria' => $id
    ]);

} catch (DomainException $e) {
    $codigo = $e->getMessage() === 'La categoría seleccionada no existe.'
        ? 404
        : 400;

    http_response_code($codigo);

    echo json_encode([
        'ok' => false,
        'mensaje' => $e->getMessage()
    ]);

} catch (PDOException $e) {
    if (($e->errorInfo[1] ?? null) == 1062) {
        http_response_code(409);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'Ya existe una subcategoría con ese nombre dentro de esta categoría.'
        ]);
        exit;
    }

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al crear la subcategoría.'
    ]);
}

exit;
