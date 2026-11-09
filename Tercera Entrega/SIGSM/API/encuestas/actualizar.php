<?php

require_once __DIR__ . "/../../Backend/config/auth.php";
require_once __DIR__ . "/../../Backend/models/Encuesta.php";

header('Content-Type: application/json; charset=utf-8');

iniciarSesion();

function responder(int $codigo, array $datos): never
{
    http_response_code($codigo);

    echo json_encode(
        $datos,
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

if (!usuarioAutenticado() || !esAdministrador()) {
    responder(403, [
        'ok' => false,
        'mensaje' => 'No autorizado.'
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');

    responder(405, [
        'ok' => false,
        'mensaje' => 'Método no permitido.'
    ]);
}

$datos = json_decode(
    file_get_contents('php://input'),
    true
);

if (!is_array($datos)) {
    responder(400, [
        'ok' => false,
        'mensaje' => 'El cuerpo de la solicitud no contiene JSON válido.'
    ]);
}

$accion = $datos['accion'] ?? '';

try {
    $modelo = new Encuesta();

    // Desactivar una encuesta sin eliminar su historial.
    if ($accion === 'desactivar') {
        $idEncuesta = filter_var(
            $datos['id_encuesta'] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($idEncuesta === false || $idEncuesta === null || $idEncuesta < 1) {
            responder(400, [
                'ok' => false,
                'mensaje' => 'ID de encuesta inválido.'
            ]);
        }

        if (!$modelo->retirar($idEncuesta)) {
            responder(409, [
                'ok' => false,
                'mensaje' => 'La encuesta no existe o ya no está activa.'
            ]);
        }

        responder(200, [
            'ok' => true,
            'mensaje' => 'Encuesta desactivada correctamente.'
        ]);
    }

    // Reemplazar una encuesta activa por una nueva.
    if ($accion === 'reemplazar') {
        $idEncuesta = filter_var(
            $datos['id_encuesta'] ?? null,
            FILTER_VALIDATE_INT
        );

        $idSubcategoria = filter_var(
            $datos['id_subcategoria'] ?? null,
            FILTER_VALIDATE_INT
        );

        $titulo = trim((string) ($datos['titulo'] ?? ''));
        $preguntas = $datos['preguntas'] ?? null;

        if ($idEncuesta === false || $idEncuesta === null || $idEncuesta < 1) {
            responder(400, [
                'ok' => false,
                'mensaje' => 'ID de la encuesta anterior inválido.'
            ]);
        }

        if (
            $idSubcategoria === false ||
            $idSubcategoria === null ||
            $idSubcategoria < 1
        ) {
            responder(400, [
                'ok' => false,
                'mensaje' => 'Subcategoría inválida.'
            ]);
        }

        if ($titulo === '' || mb_strlen($titulo) > 150) {
            responder(400, [
                'ok' => false,
                'mensaje' => 'El título es obligatorio y no puede superar los 150 caracteres.'
            ]);
        }

        if (!is_array($preguntas) || count($preguntas) < 1) {
            responder(400, [
                'ok' => false,
                'mensaje' => 'La encuesta debe tener al menos una pregunta.'
            ]);
        }

        // Validar las preguntas y opciones también en el backend.
        $preguntasValidadas = [];
        $tiposPermitidos = [
            'opcion_unica',
            'opcion_multiple'
        ];

        foreach ($preguntas as $indice => $pregunta) {
            if (!is_array($pregunta)) {
                responder(400, [
                    'ok' => false,
                    'mensaje' => 'Hay una pregunta con formato inválido.'
                ]);
            }

            $texto = trim((string) ($pregunta['texto'] ?? ''));
            $tipo = $pregunta['tipo'] ?? '';
            $opciones = $pregunta['opciones'] ?? null;

            if ($texto === '' || mb_strlen($texto) > 500) {
                responder(400, [
                    'ok' => false,
                    'mensaje' => 'La pregunta ' . ($indice + 1) . ' debe tener texto y no superar los 500 caracteres.'
                ]);
            }

            if (!in_array($tipo, $tiposPermitidos, true)) {
                responder(400, [
                    'ok' => false,
                    'mensaje' => 'El tipo de respuesta de la pregunta ' . ($indice + 1) . ' no es válido.'
                ]);
            }

            if (
                !is_array($opciones) ||
                count($opciones) < 2
            ) {
                responder(400, [
                    'ok' => false,
                    'mensaje' => 'La pregunta ' . ($indice + 1) . ' debe tener al menos dos opciones.'
                ]);
            }

            $opcionesValidadas = [];

            foreach ($opciones as $opcion) {
                if (!is_string($opcion)) {
                    responder(400, [
                        'ok' => false,
                        'mensaje' => 'Las opciones deben ser textos válidos.'
                    ]);
                }

                $opcion = trim($opcion);

                if ($opcion === '' || mb_strlen($opcion) > 255) {
                    responder(400, [
                        'ok' => false,
                        'mensaje' => 'Todas las opciones deben estar completas y no superar los 255 caracteres.'
                    ]);
                }

                $opcionesValidadas[] = $opcion;
            }

            $preguntasValidadas[] = [
                'texto' => $texto,
                'tipo' => $tipo,
                'opciones' => $opcionesValidadas
            ];
        }

        $idNuevaEncuesta = $modelo->reemplazarCompleta(
            (int) $idEncuesta,
            $titulo,
            (int) $idSubcategoria,
            $preguntasValidadas
        );

        if ($idNuevaEncuesta === false) {
            responder(409, [
                'ok' => false,
                'mensaje' => 'No se pudo reemplazar la encuesta. Verificá que la anterior esté activa y que la subcategoría coincida.'
            ]);
        }

        responder(200, [
            'ok' => true,
            'mensaje' => 'Encuesta reemplazada correctamente.',
            'id_encuesta' => $idNuevaEncuesta
        ]);
    }

    responder(400, [
        'ok' => false,
        'mensaje' => 'Acción inválida.'
    ]);

} catch (Throwable $e) {
    error_log(
        'Error al actualizar encuesta: ' . $e->getMessage()
    );

    responder(500, [
        'ok' => false,
        'mensaje' => 'Ocurrió un error al procesar la encuesta.'
    ]);
}