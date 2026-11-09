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
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
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

// Validar los datos recibidos.
$id = filter_var(
    $datos['id_categoria'] ?? null,
    FILTER_VALIDATE_INT
);

$nombre = trim($datos['nombre'] ?? '');
$estado = $datos['estado'] ?? '';

if ($id === false || $id === null || $id <= 0 ||
    $nombre === '' || !is_string($estado) || $estado === '') {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Datos obligatorios incompletos o inválidos.'
    ]);

    exit;
}

// Validar los estados permitidos.
if (!in_array($estado, ['activa', 'desvinculada'], true)) {
    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Estado de categoría inválido.'
    ]);

    exit;
}

try {
    $categoriaModel = new Categoria();

    $actualizada = $categoriaModel->actualizar(
        $id,
        $nombre,
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
        'mensaje' => 'Categoría actualizada correctamente.'
    ]);

} catch (PDOException $e) {
    // 1062 = nombre de categoría duplicado.
    if (($e->errorInfo[1] ?? null) == 1062) {
        http_response_code(409);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'Ya existe una categoría con ese nombre.'
        ]);

        exit;
    }

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al actualizar la categoría.'
    ]);
}

exit;
