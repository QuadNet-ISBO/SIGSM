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

$idCategoria = filter_input(INPUT_GET, 'id_categoria', FILTER_VALIDATE_INT);
if (!$idCategoria || $idCategoria < 1) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'El servicio seleccionado no es válido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $conexion = Database::getInstancia()->getConexion();
    $sql = "SELECT s.id_subcategoria, s.nombre
            FROM subcategorias s
            INNER JOIN categorias c ON c.id_categoria = s.id_categoria
            
            WHERE s.id_categoria = :id_categoria
              AND s.estado = 'activa'
              AND c.estado = 'activa'
              AND EXISTS (SELECT 1 FROM documentos d WHERE d.id_subcategoria = s.id_subcategoria AND d.estado = 'activo')
            ORDER BY s.nombre ASC";

    $consulta = $conexion->prepare($sql);
    $consulta->execute([':id_categoria' => $idCategoria]);
    $subcategorias = $consulta->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['ok' => true, 'subcategorias' => $subcategorias], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('Portal: error al listar subcategorías: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudieron cargar los subservicios.'], JSON_UNESCAPED_UNICODE);
}
exit;
