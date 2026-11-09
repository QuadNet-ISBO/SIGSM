<?php
require_once __DIR__ . "/../../Backend/config/database.php";

header("X-Content-Type-Options: nosniff");
header("Cache-Control: no-store");

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    exit("Método no permitido.");
}

// Se admite el identificador del PDF seleccionado. La consulta siempre
// valida que el documento, su subcategoría y su categoría estén activos.
$idDocumento = filter_input(INPUT_GET, "id_documento", FILTER_VALIDATE_INT);
$idSubcategoria = filter_input(INPUT_GET, "id_subcategoria", FILTER_VALIDATE_INT);
if ((!$idDocumento || $idDocumento < 1) && (!$idSubcategoria || $idSubcategoria < 1)) {
    http_response_code(400);
    exit("Documento inválido.");
}

try {
    $conexion = Database::getInstancia()->getConexion();

    // El portal solo puede visualizar documentos activos
    // asociados a una subcategoría y categoría activas.
    $seleccionarPorId = $idDocumento && $idDocumento > 0;
    $consulta = $conexion->prepare(
        "SELECT d.ruta
         FROM documentos d
         INNER JOIN subcategorias s ON s.id_subcategoria = d.id_subcategoria
         INNER JOIN categorias c ON c.id_categoria = s.id_categoria
         WHERE " . ($seleccionarPorId ? "d.id_documento = :id_documento" : "d.id_subcategoria = :id_subcategoria") . "
           AND d.estado = 'activo'
           AND s.estado = 'activa'
           AND c.estado = 'activa'
         ORDER BY d.fecha DESC, d.id_documento DESC
         LIMIT 1"
    );
    $consulta->execute($seleccionarPorId
        ? [':id_documento' => $idDocumento]
        : [':id_subcategoria' => $idSubcategoria]);
    $documento = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$documento) {
        http_response_code(404);
        exit("Documento no encontrado o no disponible.");
    }

    $raizProyecto = dirname(__DIR__, 2);
    $carpetaUploads = realpath($raizProyecto . "/uploads/documentos");
    $rutaRelativa = $documento["ruta"] ?? "";
    $rutaFisica = realpath($raizProyecto . "/" . ltrim($rutaRelativa, "/\\"));

    if (
        $carpetaUploads === false ||
        $rutaFisica === false ||
        !is_file($rutaFisica) ||
        !is_readable($rutaFisica) ||
        strpos($rutaFisica, $carpetaUploads . DIRECTORY_SEPARATOR) !== 0
    ) {
        http_response_code(404);
        exit("El archivo no existe o no está disponible.");
    }

    header("Content-Type: application/pdf");
    header('Content-Disposition: inline; filename="documento.pdf"');
    header("Content-Length: " . filesize($rutaFisica));
    readfile($rutaFisica);
    exit;
} catch (Throwable $e) {
    error_log('Portal: error al visualizar documento: ' . $e->getMessage());
    http_response_code(500);
    exit("No se pudo visualizar el documento.");
}
