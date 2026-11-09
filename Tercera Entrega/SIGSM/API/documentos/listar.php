<?php

require_once __DIR__ . "/../../Backend/config/database.php";
require_once __DIR__ . "/../../Backend/config/auth.php";
require_once __DIR__ . "/../../Backend/models/Documento.php";

header('Content-Type: application/json; charset=utf-8');

iniciarSesion();

/*
 * ============================================================
 * SEGURIDAD
 * ============================================================
 */

if (!usuarioAutenticado()) {

    http_response_code(403);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'No autorizado.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
 * ============================================================
 * MÉTODO
 * ============================================================
 */

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {

    header('Allow: GET');
    http_response_code(405);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Método no permitido.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
 * ============================================================
 * LISTAR DOCUMENTOS
 * ============================================================
 */

try {

    /*
     * FILTROS
     */

    $filtros = [];

    // Buscar por título.
    $buscar = trim($_GET['buscar'] ?? '');

    if ($buscar !== '') {
        $filtros['buscar'] = $buscar;
    }


    // Filtrar por categoría.
    if (isset($_GET['id_categoria']) && $_GET['id_categoria'] !== '') {

        $idCategoria = filter_var(
            $_GET['id_categoria'],
            FILTER_VALIDATE_INT
        );

        if ($idCategoria === false || $idCategoria < 1) {

            http_response_code(400);

            echo json_encode([
                'ok' => false,
                'mensaje' => 'El ID de categoría no es válido.'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $filtros['id_categoria'] = $idCategoria;
    }


    // Filtrar por subcategoría.
    if (isset($_GET['id_subcategoria']) && $_GET['id_subcategoria'] !== '') {

        $idSubcategoria = filter_var(
            $_GET['id_subcategoria'],
            FILTER_VALIDATE_INT
        );

        if ($idSubcategoria === false || $idSubcategoria < 1) {

            http_response_code(400);

            echo json_encode([
                'ok' => false,
                'mensaje' => 'El ID de subcategoría no es válido.'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $filtros['id_subcategoria'] = $idSubcategoria;
    }


    // Filtrar por estado.
    $estado = trim($_GET['estado'] ?? '');

    if ($estado !== '') {
        $filtros['estado'] = $estado;
    }


    /*
     * CONSULTAR DOCUMENTOS
     */

    $documento = new Documento();

    $documentos = $documento->obtenerTodos($filtros);


    /*
     * RESPUESTA
     */

    http_response_code(200);

    echo json_encode([
        'ok' => true,
        'documentos' => $documentos
    ], JSON_UNESCAPED_UNICODE);


} catch (Throwable $e) {

    error_log(
        'Error al listar documentos: ' . $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al obtener los documentos.'
    ], JSON_UNESCAPED_UNICODE);

}

exit;
