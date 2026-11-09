<?php

require_once __DIR__ . "/../../Backend/config/database.php";

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function responder(int $codigo, array $datos): never
{
    http_response_code($codigo);

    echo json_encode(
        $datos,
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');

    responder(405, [
        'ok' => false,
        'mensaje' => 'Método no permitido.'
    ]);
}

$datos = json_decode(
    file_get_contents('php://input'),
    true
);

if (
    !is_array($datos) ||
    !isset($datos['id_encuesta'], $datos['respuestas']) ||
    !is_int($datos['id_encuesta']) ||
    $datos['id_encuesta'] < 1 ||
    !is_array($datos['respuestas']) ||
    count($datos['respuestas']) === 0 ||
    count($datos['respuestas']) > 100
) {
    responder(400, [
        'ok' => false,
        'mensaje' => 'Los datos enviados no son válidos.'
    ]);
}

$idEncuesta = $datos['id_encuesta'];
$respuestas = $datos['respuestas'];

// Límite básico para el portal anónimo: 5 envíos correctos cada 10 minutos
// por IP. No requiere cambios de base de datos ni sesión de usuario.
// Se usa un directorio privado fuera del árbol público del sitio.
$directorioLimites = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sigsm_encuestas_limites';
if (!is_dir($directorioLimites) && !@mkdir($directorioLimites, 0700, true) && !is_dir($directorioLimites)) {
    error_log('Encuestas: no se pudo crear el directorio de límites.');
    responder(503, ['ok' => false, 'mensaje' => 'Servicio temporalmente no disponible.']);
}

$ip = $_SERVER['REMOTE_ADDR'] ?? 'desconocida';
$archivoLimite = $directorioLimites . DIRECTORY_SEPARATOR . hash('sha256', $ip) . '.json';
$manejadorLimite = @fopen($archivoLimite, 'c+');
if ($manejadorLimite === false || !flock($manejadorLimite, LOCK_EX)) {
    if (is_resource($manejadorLimite)) fclose($manejadorLimite);
    error_log('Encuestas: no se pudo bloquear el registro de límites.');
    responder(503, ['ok' => false, 'mensaje' => 'Servicio temporalmente no disponible.']);
}

$ahora = time();
$ventana = 600;
$maximo = 5;
rewind($manejadorLimite);
$registro = json_decode(stream_get_contents($manejadorLimite), true);
$envios = is_array($registro) && isset($registro['envios']) && is_array($registro['envios'])
    ? array_filter($registro['envios'], static fn($fecha) => is_int($fecha) && $fecha > $ahora - $ventana)
    : [];
$envios = array_values($envios);

if (count($envios) >= $maximo) {
    $espera = max(1, $ventana - ($ahora - $envios[0]));
    header('Retry-After: ' . $espera);
    flock($manejadorLimite, LOCK_UN);
    fclose($manejadorLimite);
    responder(429, [
        'ok' => false,
        'mensaje' => 'Se alcanzó el límite de respuestas. Intentá nuevamente más tarde.'
    ]);
}


try {
    $conexion = Database::getInstancia()->getConexion();

    $conexion->beginTransaction();

    // Comprobar que la encuesta exista y siga activa.
    $consulta = $conexion->prepare(
        "SELECT id_encuesta
         FROM encuestas
         WHERE id_encuesta = :id_encuesta
           AND estado = 'activa'
         FOR UPDATE"
    );

    $consulta->execute([
        ':id_encuesta' => $idEncuesta
    ]);

    if (!$consulta->fetch()) {
        $conexion->rollBack();

        responder(409, [
            'ok' => false,
            'mensaje' => 'La encuesta ya no está disponible.'
        ]);
    }

    // Preparar las consultas para validar y guardar.
    $consultaPregunta = $conexion->prepare(
        "SELECT tipo
         FROM preguntas
         WHERE id_pregunta = :id_pregunta
           AND id_encuesta = :id_encuesta"
    );

    $consultaOpcion = $conexion->prepare(
        "SELECT id_opcion
         FROM opciones_pregunta
         WHERE id_opcion = :id_opcion
           AND id_pregunta = :id_pregunta"
    );

    $insertarOpcion = $conexion->prepare(
        "INSERT INTO respuestas (id_pregunta, id_opcion)
         VALUES (:id_pregunta, :id_opcion)"
    );

    $insertarTexto = $conexion->prepare(
        "INSERT INTO respuestas (id_pregunta, texto)
         VALUES (:id_pregunta, :texto)"
    );

    $preguntasProcesadas = [];

    foreach ($respuestas as $respuesta) {
        if (!is_array($respuesta)) {
            throw new InvalidArgumentException(
                'Hay una respuesta con formato incorrecto.'
            );
        }

        $idPregunta = $respuesta['id_pregunta'] ?? null;

        if (
            !is_int($idPregunta) ||
            $idPregunta < 1 ||
            isset($preguntasProcesadas[$idPregunta])
        ) {
            throw new InvalidArgumentException(
                'Hay una pregunta inválida o repetida.'
            );
        }

        $preguntasProcesadas[$idPregunta] = true;

        // Obtener el tipo real desde la base de datos.
        $consultaPregunta->execute([
            ':id_pregunta' => $idPregunta,
            ':id_encuesta' => $idEncuesta
        ]);

        $pregunta = $consultaPregunta->fetch();

        if (!$pregunta) {
            throw new InvalidArgumentException(
                'Una pregunta no pertenece a esta encuesta.'
            );
        }

        $tipo = $pregunta['tipo'];

        if ($tipo === 'opcion_unica') {
            $idOpcion = $respuesta['id_opcion'] ?? null;

            if (!is_int($idOpcion) || $idOpcion < 1) {
                throw new InvalidArgumentException(
                    'Seleccioná una opción válida.'
                );
            }

            $consultaOpcion->execute([
                ':id_opcion' => $idOpcion,
                ':id_pregunta' => $idPregunta
            ]);

            if (!$consultaOpcion->fetch()) {
                throw new InvalidArgumentException(
                    'Una opción no pertenece a su pregunta.'
                );
            }

            $insertarOpcion->execute([
                ':id_pregunta' => $idPregunta,
                ':id_opcion' => $idOpcion
            ]);

        } elseif ($tipo === 'opcion_multiple') {
            $opciones = $respuesta['id_opciones'] ?? null;

            if (
                !is_array($opciones) ||
                count($opciones) === 0 ||
                count($opciones) > 100
            ) {
                throw new InvalidArgumentException(
                    'Seleccioná al menos una opción.'
                );
            }

            $opcionesProcesadas = [];

            foreach ($opciones as $idOpcion) {
                if (
                    !is_int($idOpcion) ||
                    $idOpcion < 1 ||
                    isset($opcionesProcesadas[$idOpcion])
                ) {
                    throw new InvalidArgumentException(
                        'Hay una opción inválida o repetida.'
                    );
                }

                $opcionesProcesadas[$idOpcion] = true;

                $consultaOpcion->execute([
                    ':id_opcion' => $idOpcion,
                    ':id_pregunta' => $idPregunta
                ]);

                if (!$consultaOpcion->fetch()) {
                    throw new InvalidArgumentException(
                        'Una opción no pertenece a su pregunta.'
                    );
                }

                $insertarOpcion->execute([
                    ':id_pregunta' => $idPregunta,
                    ':id_opcion' => $idOpcion
                ]);
            }

        } elseif ($tipo === 'texto_libre') {
            $texto = $respuesta['texto'] ?? null;

            if (
                !is_string($texto) ||
                trim($texto) === '' ||
                mb_strlen($texto) > 2000
            ) {
                throw new InvalidArgumentException(
                    'El texto debe tener entre 1 y 2000 caracteres.'
                );
            }

            $insertarTexto->execute([
                ':id_pregunta' => $idPregunta,
                ':texto' => trim($texto)
            ]);

        } else {
            throw new InvalidArgumentException(
                'La encuesta contiene un tipo de pregunta no compatible.'
            );
        }
    }

    $conexion->commit();

    // Registrar únicamente envíos que se guardaron correctamente.
    $envios[] = time();
    rewind($manejadorLimite);
    ftruncate($manejadorLimite, 0);
    $escrito = fwrite($manejadorLimite, json_encode(['envios' => $envios]));
    fflush($manejadorLimite);
    flock($manejadorLimite, LOCK_UN);
    fclose($manejadorLimite);
    if ($escrito === false) {
        error_log('Encuestas: no se pudo registrar el envío en el límite de frecuencia.');
    }

    responder(200, [
        'ok' => true,
        'mensaje' => 'Tus respuestas se guardaron correctamente.'
    ]);

} catch (InvalidArgumentException $e) {
    if (isset($conexion) && $conexion->inTransaction()) {
        $conexion->rollBack();
    }

    responder(400, [
        'ok' => false,
        'mensaje' => $e->getMessage()
    ]);

} catch (Throwable $e) {
    if (isset($conexion) && $conexion->inTransaction()) {
        $conexion->rollBack();
    }

    error_log(
        'Portal: error al guardar encuesta: ' . $e->getMessage()
    );

    responder(500, [
        'ok' => false,
        'mensaje' => 'No se pudieron guardar las respuestas.'
    ]);
}
