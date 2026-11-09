<?php

require_once __DIR__ . "/../../Backend/config/database.php";
require_once __DIR__ . "/../../Backend/config/auth.php";

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

try {
    $conexion = Database::getInstancia()->getConexion();

    // Obtener todas las encuestas.
    $sqlEncuestas = "SELECT
                        e.id_encuesta,
                        e.titulo,
                        e.estado,
                        s.nombre AS subcategoria,
                        c.nombre AS categoria
                    FROM encuestas e
                    INNER JOIN subcategorias s
                        ON s.id_subcategoria = e.id_subcategoria
                    INNER JOIN categorias c
                        ON c.id_categoria = s.id_categoria
                    ORDER BY e.id_encuesta DESC";

    $consultaEncuestas = $conexion->prepare($sqlEncuestas);
    $consultaEncuestas->execute();

    $encuestas = $consultaEncuestas->fetchAll(PDO::FETCH_ASSOC);

    // Consultas reutilizables para preguntas y opciones.
    $sqlPreguntas = "SELECT
                        id_pregunta,
                        texto,
                        tipo,
                        orden
                     FROM preguntas
                     WHERE id_encuesta = :id_encuesta
                     ORDER BY orden ASC, id_pregunta ASC";

    $consultaPreguntas = $conexion->prepare($sqlPreguntas);

    $sqlOpciones = "SELECT
                        op.id_opcion,
                        op.texto_opcion,
                        op.orden,
                        COUNT(r.id_respuesta) AS votos
                    FROM opciones_pregunta op
                    LEFT JOIN respuestas r
                        ON r.id_opcion = op.id_opcion
                    WHERE op.id_pregunta = :id_pregunta
                    GROUP BY
                        op.id_opcion,
                        op.texto_opcion,
                        op.orden
                    ORDER BY op.orden ASC, op.id_opcion ASC";

    $consultaOpciones = $conexion->prepare($sqlOpciones);

    foreach ($encuestas as &$encuesta) {
        $idEncuesta = (int) $encuesta['id_encuesta'];

        $consultaPreguntas->execute([
            ':id_encuesta' => $idEncuesta
        ]);

        $preguntas = $consultaPreguntas->fetchAll(PDO::FETCH_ASSOC);

        foreach ($preguntas as &$pregunta) {
            $idPregunta = (int) $pregunta['id_pregunta'];

            $consultaOpciones->execute([
                ':id_pregunta' => $idPregunta
            ]);

            $opciones = $consultaOpciones->fetchAll(PDO::FETCH_ASSOC);

            $totalVotos = 0;

            foreach ($opciones as &$opcion) {
                $opcion['votos'] = (int) $opcion['votos'];
                $totalVotos += $opcion['votos'];
            }
            unset($opcion);

            foreach ($opciones as &$opcion) {
                $opcion['porcentaje'] = $totalVotos > 0
                    ? round(($opcion['votos'] / $totalVotos) * 100, 2)
                    : 0;
            }
            unset($opcion);

            $pregunta['id_pregunta'] = $idPregunta;
            $pregunta['total_votos'] = $totalVotos;
            $pregunta['opciones'] = $opciones;
        }
        unset($pregunta);

        $encuesta['id_encuesta'] = $idEncuesta;
        $encuesta['preguntas'] = $preguntas;
    }
    unset($encuesta);

    echo json_encode([
        'ok' => true,
        'encuestas' => $encuestas
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('Error al consultar resultados de encuestas: ' . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'No se pudieron obtener los resultados.'
    ], JSON_UNESCAPED_UNICODE);
}
