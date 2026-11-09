<?php

require_once __DIR__ . "/../../Backend/config/database.php";
require_once __DIR__ . "/../../Backend/config/auth.php";
require_once __DIR__ . "/../../Backend/models/Documento.php";

header('Content-Type: application/json; charset=utf-8');
iniciarSesion();

if (!usuarioAutenticado()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'mensaje' => 'No autorizado.'], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$idDocumento = filter_var($_POST['id_documento'] ?? null, FILTER_VALIDATE_INT);
if (!$idDocumento || $idDocumento <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'El ID del documento no es válido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $modelo = new Documento();
    $documento = $modelo->obtenerPorId($idDocumento);
    if (!$documento) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'mensaje' => 'El documento no existe.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($documento['estado'] !== 'activo') {
        http_response_code(409);
        echo json_encode(['ok' => false, 'mensaje' => 'El documento ya fue dado de baja y no puede reactivarse.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (!$modelo->darDeBaja($idDocumento)) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'mensaje' => 'No se pudo dar de baja el documento; su estado pudo haber cambiado.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    echo json_encode(['ok' => true, 'mensaje' => 'Documento dado de baja correctamente. El PDF se conserva para consulta interna.'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('Error al dar de baja documento: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Ocurrió un error al dar de baja el documento.'], JSON_UNESCAPED_UNICODE);
}
exit;
