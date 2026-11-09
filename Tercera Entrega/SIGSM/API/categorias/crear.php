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
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

// Validar el nombre.
$nombre = trim($datos['nombre'] ?? '');

if ($nombre === '') {
    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'El nombre de la categoría es obligatorio.'
    ]);

    exit;
}

try {
    // Crear el modelo.
    $categoriaModel = new Categoria();

    // Delegar la inserción al modelo.
    $idCategoria = $categoriaModel->crear($nombre);

    echo json_encode([
        'ok' => true,
        'mensaje' => 'Categoría creada correctamente.',
        'id_categoria' => $idCategoria
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
        'mensaje' => 'Error al crear la categoría.'
    ]);
}

exit;
