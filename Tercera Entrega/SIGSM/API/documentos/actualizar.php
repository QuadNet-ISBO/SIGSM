<?php

require_once __DIR__ . "/../../Backend/config/database.php";
require_once __DIR__ . "/../../Backend/config/auth.php";
require_once __DIR__ . "/../../Backend/models/Documento.php";
require_once __DIR__ . "/../../Backend/config/uploads.php";

header('Content-Type: application/json; charset=utf-8');

iniciarSesion();

if (!usuarioAutenticado()) {
    http_response_code(403);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'No autorizado.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Método no permitido.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Validar el ID.
$idDocumento = filter_var(
    $_POST['id_documento'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$idDocumento || $idDocumento <= 0) {
    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'El ID del documento no es válido.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$titulo = trim($_POST['titulo'] ?? '');

$idSubcategoria = filter_var(
    $_POST['id_subcategoria'] ?? null,
    FILTER_VALIDATE_INT
);

if ($titulo === '' || mb_strlen($titulo) > 200) {
    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'El título es obligatorio y no puede superar los 200 caracteres.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if (!$idSubcategoria || $idSubcategoria <= 0) {
    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'La subcategoría seleccionada no es válida.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {
    $documentoModelo = new Documento();

    // Verificar que el documento exista.
    $documentoActual = $documentoModelo->obtenerPorId($idDocumento);

    if (!$documentoActual) {
        http_response_code(404);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'El documento no existe.'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    // Una baja es definitiva: impedir cambios posteriores incluso por API.
    if ($documentoActual['estado'] !== 'activo') {
        http_response_code(409);
        echo json_encode([
            'ok' => false,
            'mensaje' => 'El documento fue dado de baja y no puede editarse ni reactivarse.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Validar la subcategoría de destino.
    if (!$documentoModelo->subcategoriaDisponible($idSubcategoria)) {
        http_response_code(400);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'La subcategoría seleccionada no existe o no está activa.'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    // Por defecto, conservar el archivo actual.
    $rutaBD = $documentoActual['ruta'];
    $rutaFisicaNueva = null;

    // Solo procesar un archivo si se envió uno.
    if (
        isset($_FILES['archivo']) &&
        $_FILES['archivo']['error'] !== UPLOAD_ERR_NO_FILE
    ) {
        $archivo = $_FILES['archivo'];

        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);

            echo json_encode([
                'ok' => false,
                'mensaje' => 'Ocurrió un error al recibir el nuevo archivo.'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $limiteBytes = 10 * 1024 * 1024;

        if ($archivo['size'] <= 0 || $archivo['size'] > $limiteBytes) {
            http_response_code(400);

            echo json_encode([
                'ok' => false,
                'mensaje' => 'El archivo debe pesar más de 0 bytes y como máximo 10 MB.'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $extension = strtolower(
            pathinfo($archivo['name'], PATHINFO_EXTENSION)
        );

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $tipoArchivo = $finfo->file($archivo['tmp_name']);
        $contenido = file_get_contents($archivo['tmp_name']);

        if (
            $extension !== 'pdf' ||
            $tipoArchivo !== 'application/pdf' ||
            $contenido === false ||
            !str_starts_with($contenido, '%PDF-')
        ) {
            http_response_code(400);

            echo json_encode([
                'ok' => false,
                'mensaje' => 'El archivo enviado no es un PDF válido.'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $directorioDestino = dirname(__DIR__, 2)
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'documentos';

        if (
            !is_dir($directorioDestino) &&
            !mkdir($directorioDestino, 0750, true) &&
            !is_dir($directorioDestino)
        ) {
            throw new RuntimeException(
                'No se pudo preparar el almacenamiento de documentos.'
            );
        }

        
        // Asegura que la carpeta no se pueda abrir directamente por URL.
        protegerCarpetaUploads($directorioDestino);
        

        $nombreArchivo = bin2hex(random_bytes(16)) . '.pdf';

        $rutaFisicaNueva = $directorioDestino
            . DIRECTORY_SEPARATOR . $nombreArchivo;

        $rutaBD = 'uploads/documentos/' . $nombreArchivo;

        if (!move_uploaded_file($archivo['tmp_name'], $rutaFisicaNueva)) {
            throw new RuntimeException(
                'No se pudo guardar el nuevo archivo.'
            );
        }
    }

    // Actualizar los datos del documento.
    $actualizado = $documentoModelo->actualizar(
        $idDocumento,
        $titulo,
        $rutaBD,
        $idSubcategoria
    );

    if (!$actualizado) {
        if ($rutaFisicaNueva !== null && is_file($rutaFisicaNueva)) {
            unlink($rutaFisicaNueva);
        }

        http_response_code(500);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'No se pudo actualizar el documento.'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    // Si se reemplazó el archivo, eliminar el anterior de forma segura.
    if ($rutaFisicaNueva !== null) {
        $directorioBase = realpath(
            dirname(__DIR__, 2)
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'documentos'
        );

        $rutaAnterior = $documentoActual['ruta'];

        if (
            $directorioBase !== false &&
            !str_contains($rutaAnterior, '..') &&
            !str_contains($rutaAnterior, '\\') &&
            str_starts_with($rutaAnterior, 'uploads/documentos/')
        ) {
            $nombreAnterior = substr(
                $rutaAnterior,
                strlen('uploads/documentos/')
            );

            $rutaFisicaAnterior = realpath(
                $directorioBase . DIRECTORY_SEPARATOR . $nombreAnterior
            );

            if (
                $rutaFisicaAnterior !== false &&
                dirname($rutaFisicaAnterior) === $directorioBase &&
                is_file($rutaFisicaAnterior) &&
                $rutaFisicaAnterior !== realpath($rutaFisicaNueva)
            ) {
                if (!unlink($rutaFisicaAnterior)) {
                    error_log(
                        'No se pudo eliminar el PDF anterior del documento '
                        . $idDocumento
                    );
                }
            }
        }
    }

    echo json_encode([
        'ok' => true,
        'mensaje' => 'Documento actualizado correctamente.'
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('Error al actualizar documento: ' . $e->getMessage());

    if (isset($rutaFisicaNueva) && $rutaFisicaNueva !== null
        && is_file($rutaFisicaNueva)) {
        unlink($rutaFisicaNueva);
    }

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Ocurrió un error al actualizar el documento.'
    ], JSON_UNESCAPED_UNICODE);
}

exit;
