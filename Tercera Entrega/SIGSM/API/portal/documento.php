<?php
require_once __DIR__ . "/../../Backend/config/database.php";

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$idSubcategoria = filter_input(INPUT_GET, 'id_subcategoria', FILTER_VALIDATE_INT);
if (!$idSubcategoria || $idSubcategoria < 1) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'El subservicio seleccionado no es válido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $conexion = Database::getInstancia()->getConexion();
    $sql = "SELECT d.id_documento, d.titulo
            FROM documentos d
            INNER JOIN subcategorias s ON s.id_subcategoria = d.id_subcategoria
            INNER JOIN categorias c ON c.id_categoria = s.id_categoria
            WHERE d.id_subcategoria = :id_subcategoria
              AND d.estado = 'activo'
              AND s.estado = 'activa'
              AND c.estado = 'activa'
            ORDER BY d.titulo ASC, d.id_documento ASC";

    $consulta = $conexion->prepare($sql);
    $consulta->execute([':id_subcategoria' => $idSubcategoria]);
    $documentos = $consulta->fetchAll(PDO::FETCH_ASSOC);

    if (!$documentos) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'mensaje' => 'No hay documentos disponibles para este subservicio.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['ok' => true, 'documentos' => $documentos], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('Portal: error al buscar documento: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudo cargar el documento.'], JSON_UNESCAPED_UNICODE);
}
exit;
