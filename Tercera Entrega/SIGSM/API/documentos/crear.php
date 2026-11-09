<?php


require_once __DIR__ . "/../../Backend/config/database.php";
require_once __DIR__ . "/../../Backend/config/auth.php";
require_once __DIR__ . "/../../Backend/config/uploads.php";
require_once __DIR__ . "/../../Backend/models/Documento.php";

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

// Obtener y validar los datos.
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

// Comprobar que se haya enviado un archivo.
if (!isset($_FILES['archivo'])) {
    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Debés seleccionar un archivo PDF.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$archivo = $_FILES['archivo'];

if ($archivo['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);

    $mensaje = match ($archivo['error']) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE =>
            'El archivo supera el tamaño permitido.',
        UPLOAD_ERR_NO_FILE =>
            'Debés seleccionar un archivo PDF.',
        default =>
            'Ocurrió un error al recibir el archivo.'
    };

    echo json_encode([
        'ok' => false,
        'mensaje' => $mensaje
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Limitar el archivo a 10 MB.
$limiteBytes = 10 * 1024 * 1024;

if ($archivo['size'] <= 0 || $archivo['size'] > $limiteBytes) {
    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'El archivo debe pesar más de 0 bytes y como máximo 10 MB.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Validar extensión, MIME y firma del PDF.
$extension = strtolower(
    pathinfo($archivo['name'], PATHINFO_EXTENSION)
);

if ($extension !== 'pdf') {
    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Solo se permiten archivos PDF.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$tipoArchivo = $finfo->file($archivo['tmp_name']);
$archivoTemporal = file_get_contents($archivo['tmp_name']);

if (
    $tipoArchivo !== 'application/pdf' ||
    $archivoTemporal === false ||
    !str_starts_with($archivoTemporal, '%PDF-')
) {
    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'El archivo enviado no es un PDF válido.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Comprobar la subcategoría antes de guardar el archivo.
$documentoModelo = new Documento();

if (!$documentoModelo->subcategoriaDisponible($idSubcategoria)) {
    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'La subcategoría no existe o no está activa.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Crear el directorio de documentos si no existe.
$directorioDestino = dirname(__DIR__, 2)
    . DIRECTORY_SEPARATOR . 'uploads'
    . DIRECTORY_SEPARATOR . 'documentos';

if (
    !is_dir($directorioDestino) &&
    !mkdir($directorioDestino, 0750, true) &&
    !is_dir($directorioDestino)
) {
    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'No se pudo preparar el almacenamiento de documentos.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Asegura que la carpeta no se pueda abrir directamente por URL.
try {
    protegerCarpetaUploads($directorioDestino);
} catch (Throwable $e) {
    error_log('Protección de documentos: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'No se pudo garantizar la protección del documento.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Generar un nombre aleatorio para no usar el nombre original.
try {
    $nombreArchivo = bin2hex(random_bytes(16)) . '.pdf';
} catch (Throwable $e) {
    error_log('Error al generar nombre de documento: ' . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'No se pudo preparar el archivo.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$rutaFisica = $directorioDestino . DIRECTORY_SEPARATOR . $nombreArchivo;
$rutaBD = 'uploads/documentos/' . $nombreArchivo;

try {
    if (!move_uploaded_file($archivo['tmp_name'], $rutaFisica)) {
        throw new RuntimeException('No se pudo mover el archivo subido.');
    }

    $idDocumento = $documentoModelo->crear(
        $titulo,
        $rutaBD,
        $idSubcategoria
    );

    if ($idDocumento === false) {
        if (is_file($rutaFisica)) {
            unlink($rutaFisica);
        }

        http_response_code(400);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'No se pudo registrar el documento. Verificá que la subcategoría siga activa.'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    http_response_code(201);

    echo json_encode([
        'ok' => true,
        'mensaje' => 'Documento subido correctamente.',
        'id_documento' => $idDocumento
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('Error al crear documento: ' . $e->getMessage());

    if (isset($rutaFisica) && is_file($rutaFisica)) {
        unlink($rutaFisica);
    }

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Ocurrió un error al guardar el documento.'
    ], JSON_UNESCAPED_UNICODE);
}

exit;
