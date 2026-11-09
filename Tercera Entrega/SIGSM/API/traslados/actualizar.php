<?php

require_once __DIR__ . "/../../Backend/models/Traslado.php";
require_once __DIR__ . "/../../Backend/config/auth.php";



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
    ]);

    exit;
}


if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {

    http_response_code(405);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Método no permitido.'
    ]);

    exit;
}


/*
 * ============================================================
 * DATOS
 * ============================================================
 */

$datos = json_decode(
    file_get_contents('php://input'),
    true
);

$idTraslado = filter_var(
    $datos['id_traslado'] ?? null,
    FILTER_VALIDATE_INT
);

$origen = trim(
    $datos['origen'] ?? ''
);

$destino = trim(
    $datos['destino'] ?? ''
);

$fechaHoraPartida = trim($datos['fecha_hora_partida'] ?? '');

$fechaHoraDestino = trim(
    $datos['fecha_hora_destino'] ?? ''
);

$prioridad = trim(
    $datos['prioridad'] ?? ''
);

$observaciones = trim(
    $datos['observaciones'] ?? ''
);

$estado = trim(
    $datos['estado'] ?? ''
);


if (!$idTraslado) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'ID de traslado inválido.'
    ]);

    exit;
}


if ($origen === '' || $destino === '') {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Origen y destino son obligatorios.'
    ]);

    exit;
}


if ($fechaHoraPartida === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'La fecha y hora de partida son obligatorias.']);
    exit;
}

if ($fechaHoraDestino === '') {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'La fecha y hora de destino son obligatorias.'
    ]);

    exit;
}


try {

    $fechaPartida = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $fechaHoraPartida);
    if (!$fechaPartida || $fechaPartida->format('Y-m-d\TH:i') !== $fechaHoraPartida) {
        throw new Exception('Partida inválida');
    }
    $fechaDestino = new DateTime($fechaHoraDestino);
    if ($fechaDestino <= $fechaPartida) {
        throw new Exception('La llegada debe ser posterior a la partida');
    }

} catch (Exception $e) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'La fecha y hora de destino no son válidas.'
    ]);

    exit;
}


if (!in_array(
    $prioridad,
    ['baja', 'media', 'alta'],
    true
)) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'La prioridad no es válida.'
    ]);

    exit;
}


$estadosPermitidos = [

    'solicitado',
    'aprobado',
    'en_curso',
    'llegado',
    'regreso',
    'retornado',
    'finalizado',
    'cancelado',
    'abortado'

];


if (
    $estado !== '' &&
    !in_array(
        $estado,
        $estadosPermitidos,
        true
    )
) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Estado inválido.'
    ]);

    exit;
}


/*
 * ============================================================
 * ACTUALIZAR
 * ============================================================
 */

try {

    $traslado = new Traslado();

    if (!$traslado->existe((int) $idTraslado)) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'mensaje' => 'Traslado no encontrado.']);
        exit;
    }

    $traslado->actualizar((int) $idTraslado, [
        'origen' => $origen,
        'destino' => $destino,
        'fecha_hora_partida' => $fechaPartida->format('Y-m-d H:i:s'),
        'fecha_hora_destino' => $fechaDestino->format('Y-m-d H:i:s'),
        'prioridad' => $prioridad,
        'observaciones' => $observaciones !== '' ? $observaciones : null,
        'estado' => $estado
    ]);

    echo json_encode([
        'ok' => true,
        'mensaje' => 'Traslado actualizado correctamente.'
    ]);

} catch (DomainException $e) {
    http_response_code(409);
    echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al actualizar el traslado.'
    ]);
}

exit;