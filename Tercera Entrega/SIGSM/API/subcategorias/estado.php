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

if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
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

$estado = $datos['estado'] ?? '';

if ($id === false || $id === null || $id <= 0) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'ID de subcategoría inválido.'
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

    if (!$modelo->cambiarEstado($id, $estado)) {
        http_response_code(404);
        echo json_encode([
            'ok' => false,
            'mensaje' => 'La subcategoría no existe.'
        ]);
        exit;
    }

    echo json_encode([
        'ok' => true,
        'mensaje' => $estado === 'activa'
            ? 'Subcategoría activada correctamente.'
            : 'Subcategoría desvinculada correctamente.'
    ]);

} catch (DomainException $e) {
    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => $e->getMessage()
    ]);

} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al cambiar el estado de la subcategoría.'
    ]);
}

exit;
