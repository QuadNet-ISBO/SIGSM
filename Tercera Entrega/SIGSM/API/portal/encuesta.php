<?php

require_once __DIR__ . "/../../Backend/config/database.php";
require_once __DIR__ . "/../../Backend/models/Encuesta.php";

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Método no permitido.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$idSubcategoria = filter_input(
    INPUT_GET,
    'id_subcategoria',
    FILTER_VALIDATE_INT
);

if (!$idSubcategoria || $idSubcategoria < 1) {
    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'El subservicio seleccionado no es válido.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {
    $conexion = Database::getInstancia()->getConexion();

    // Verificamos que el subservicio tenga un documento activo
    // y pertenezca a una categoría activa.
    $sql = "SELECT COUNT(*)
            FROM subcategorias s
            INNER JOIN categorias c
                ON c.id_categoria = s.id_categoria
            INNER JOIN documentos d
                ON d.id_subcategoria = s.id_subcategoria
            WHERE s.id_subcategoria = :id_subcategoria
              AND s.estado = 'activa'
              AND c.estado = 'activa'
              AND d.estado = 'activo'";

    $consulta = $conexion->prepare($sql);

    $consulta->execute([
        ':id_subcategoria' => $idSubcategoria
    ]);

    if ((int) $consulta->fetchColumn() === 0) {
        echo json_encode([
            'ok' => true,
            'encuesta' => null,
            'mensaje' => 'No hay una encuesta disponible.'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $modelo = new Encuesta();

    $encuesta = $modelo->obtenerActivaPorSubcategoria(
        $idSubcategoria
    );

    if (!$encuesta) {
        echo json_encode([
            'ok' => true,
            'encuesta' => null,
            'mensaje' => 'No hay una encuesta disponible.'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $preguntas = $modelo->obtenerPreguntas(
        (int) $encuesta['id_encuesta']
    );

    foreach ($preguntas as &$pregunta) {
        $pregunta['opciones'] = $modelo->obtenerOpciones(
            (int) $pregunta['id_pregunta']
        );
    }

    unset($pregunta);

    $encuesta['preguntas'] = $preguntas;

    echo json_encode([
        'ok' => true,
        'encuesta' => $encuesta
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log(
        'Portal: error al obtener encuesta: ' . $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'No se pudo cargar la encuesta.'
    ], JSON_UNESCAPED_UNICODE);
}

exit;
