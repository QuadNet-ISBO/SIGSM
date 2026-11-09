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

try {
    $conexion = Database::getInstancia()->getConexion();

    // Solo categorías activas que tengan subcategorías activas
    // y al menos un documento activo disponible para el paciente.
    $sql = "SELECT c.id_categoria, c.nombre
            FROM categorias c
            WHERE c.estado = 'activa'
              AND EXISTS (
                  SELECT 1
                  FROM subcategorias s
                  INNER JOIN documentos d ON d.id_subcategoria = s.id_subcategoria
                  WHERE s.id_categoria = c.id_categoria
                    AND s.estado = 'activa'
                    AND d.estado = 'activo'
              )
            ORDER BY c.nombre ASC";

    $categorias = $conexion->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['ok' => true, 'categorias' => $categorias], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('Portal: error al listar categorías: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudieron cargar los servicios.'], JSON_UNESCAPED_UNICODE);
}
exit;
