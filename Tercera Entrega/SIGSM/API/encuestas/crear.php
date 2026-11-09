<?php

require_once __DIR__ . "/../../Backend/config/auth.php";
require_once __DIR__ . "/../../Backend/models/Encuesta.php";

header('Content-Type: application/json; charset=utf-8');

iniciarSesion();

if (!usuarioAutenticado() || !esAdministrador()) {
    http_response_code(403);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'No autorizado.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Método no permitido.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$contenido = file_get_contents('php://input');
$datos = json_decode($contenido, true);

if (!is_array($datos)) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'El cuerpo de la solicitud no contiene JSON válido.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$titulo = trim((string) ($datos['titulo'] ?? ''));
$idSubcategoria = filter_var(
    $datos['id_subcategoria'] ?? null,
    FILTER_VALIDATE_INT
);
$preguntas = $datos['preguntas'] ?? null;

if ($titulo === '' || mb_strlen($titulo) > 150) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'El título es obligatorio y no puede superar los 150 caracteres.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($idSubcategoria === false || $idSubcategoria === null || $idSubcategoria < 1) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Seleccioná una subcategoría válida.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!is_array($preguntas) || count($preguntas) < 1) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'La encuesta debe tener al menos una pregunta.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$preguntasValidadas = [];
$tiposPermitidos = ['opcion_unica', 'opcion_multiple'];

foreach ($preguntas as $indice => $pregunta) {
    if (!is_array($pregunta)) {
        http_response_code(400);
        echo json_encode([
            'ok' => false,
            'mensaje' => 'Hay una pregunta con formato inválido.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $texto = trim((string) ($pregunta['texto'] ?? ''));
    $tipo = $pregunta['tipo'] ?? '';
    $opciones = $pregunta['opciones'] ?? null;

    if ($texto === '' || mb_strlen($texto) > 500) {
        http_response_code(400);
        echo json_encode([
            'ok' => false,
            'mensaje' => 'La pregunta ' . ($indice + 1) . ' debe tener texto y no superar los 500 caracteres.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!in_array($tipo, $tiposPermitidos, true)) {
        http_response_code(400);
        echo json_encode([
            'ok' => false,
            'mensaje' => 'El tipo de respuesta de la pregunta ' . ($indice + 1) . ' no es válido.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!is_array($opciones) || count($opciones) < 2) {
        http_response_code(400);
        echo json_encode([
            'ok' => false,
            'mensaje' => 'La pregunta ' . ($indice + 1) . ' debe tener al menos dos opciones.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $opcionesValidadas = [];
    foreach ($opciones as $opcion) {
        $opcion = trim((string) $opcion);

        if ($opcion === '' || mb_strlen($opcion) > 255) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Todas las opciones de la pregunta ' . ($indice + 1) . ' deben estar completas y no superar los 255 caracteres.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $opcionesValidadas[] = $opcion;
    }

    $preguntasValidadas[] = [
        'texto' => $texto,
        'tipo' => $tipo,
        'opciones' => $opcionesValidadas
    ];
}

try {
    $modelo = new Encuesta();
    $idEncuesta = $modelo->crearCompleta(
        $titulo,
        (int) $idSubcategoria,
        $preguntasValidadas
    );

    if ($idEncuesta === false) {
        http_response_code(409);
        echo json_encode([
            'ok' => false,
            'mensaje' => 'La subcategoría no está activa o ya tiene una encuesta activa.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'ok' => true,
        'mensaje' => 'Encuesta creada correctamente.',
        'id_encuesta' => $idEncuesta
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {

    error_log('Error al crear la encuesta: ' . $e->getMessage());

    // El detalle del error queda en el log del servidor (arriba);
    // a la persona se le muestra un mensaje genérico, sin el texto
    // interno del error ni el archivo o la línea donde falló.
    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error interno al crear la encuesta. Intente nuevamente.'
    ], JSON_UNESCAPED_UNICODE);
}

exit;
