<?php

require_once __DIR__ . "/../../Backend/config/database.php";
require_once __DIR__ . "/../../Backend/config/auth.php";
require_once __DIR__ . "/../../Backend/models/Documento.php";

header("X-Content-Type-Options: nosniff");

iniciarSesion();

if (!usuarioAutenticado()) {
    http_response_code(401);
    exit("Debes iniciar sesión.");
}

$idDocumento = filter_input(
    INPUT_GET,
    "id_documento",
    FILTER_VALIDATE_INT
);

if (!$idDocumento || $idDocumento < 1) {
    http_response_code(400);
    exit("ID de documento inválido.");
}

try {
    $modelo = new Documento();
    $documento = $modelo->obtenerPorId($idDocumento);

    if (!$documento) {
        http_response_code(404);
        exit("Documento no encontrado.");
    }

    // La ruta guardada en la base de datos debe ser relativa
    // y apuntar a un archivo dentro de uploads/documentos.
    $raizProyecto = dirname(__DIR__, 2);
    $carpetaUploads = realpath(
        $raizProyecto . "/uploads/documentos"
    );

    if ($carpetaUploads === false) {
        http_response_code(404);
        exit("Carpeta de documentos no encontrada.");
    }

    $rutaRelativa = $documento["ruta"] ?? "";
    $rutaFisica = realpath(
        $raizProyecto . "/" . ltrim($rutaRelativa, "/\\")
    );

    if (
        $rutaFisica === false ||
        !is_file($rutaFisica) ||
        !is_readable($rutaFisica) ||
        strpos(
            $rutaFisica,
            $carpetaUploads . DIRECTORY_SEPARATOR
        ) !== 0
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
    error_log($e->getMessage());
    http_response_code(500);
    exit("No se pudo visualizar el documento.");
}
