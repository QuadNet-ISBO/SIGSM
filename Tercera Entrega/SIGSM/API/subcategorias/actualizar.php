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

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
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

$id = filter_var(
    $datos['id_subcategoria'] ?? null,
    FILTER_VALIDATE_INT
);

$nombre = trim($datos['nombre'] ?? '');
$estado = $datos['estado'] ?? '';

if ($id === false || $id === null || $id <= 0 ||
    $nombre === '' || $estado === '') {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Datos obligatorios incompletos.'
    ]);
    exit;
}

if (!in_array($estado, ['activa', 'desvinculada'], true)) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Estado inválido.'
    ]);
    exit;
}

try {
    $modelo = new Subcategoria();

    if (!$modelo->actualizar($id, $nombre, $estado)) {
        http_response_code(404);
        echo json_encode([
            'ok' => false,
            'mensaje' => 'La subcategoría no existe.'
        ]);
        exit;
    }

    echo json_encode([
        'ok' => true,
        'mensaje' => 'Subcategoría actualizada correctamente.'
    ]);

} catch (DomainException $e) {
    http_response_code(400);

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
        'mensaje' => 'Error al actualizar la subcategoría.'
    ]);
}

exit;
