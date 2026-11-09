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


/*
 * ============================================================
 * MÉTODO
 * ============================================================
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Método no permitido.'
    ]);

    exit;
}


/*
 * ============================================================
 * RECIBIR JSON
 * ============================================================
 */

$contenido = file_get_contents('php://input');

$datos = json_decode($contenido, true);


if (!is_array($datos)) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Los datos enviados no son válidos.'
    ]);

    exit;
}


/*
 * ============================================================
 * DATOS GENERALES
 * ============================================================
 */

$tipoTraslado = trim(
    $datos['tipo_traslado'] ?? ''
);

$idVehiculo = filter_var(
    $datos['id_vehiculo'] ?? null,
    FILTER_VALIDATE_INT
);

$idChofer = filter_var(
    $datos['id_chofer'] ?? null,
    FILTER_VALIDATE_INT
);

$idEnfermero = filter_var(
    $datos['id_enfermero'] ?? null,
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


/*
 * ============================================================
 * DATOS DEL PACIENTE
 * ============================================================
 */

$ciPaciente = trim(
    $datos['ci_paciente'] ?? ''
);

$nombrePaciente = trim(
    $datos['nombre_paciente'] ?? ''
);

$telefonoPaciente = trim(
    $datos['telefono_paciente'] ?? ''
);

$quienRecibe = trim(
    $datos['quien_recibe'] ?? ''
);

$puerta = trim(
    $datos['puerta'] ?? ''
);

$piso = trim(
    $datos['piso'] ?? ''
);

$sala = trim(
    $datos['sala'] ?? ''
);


/*
 * ============================================================
 * DATOS DE OTRO
 * ============================================================
 */

$descripcionOtro = trim(
    $datos['descripcion_otro'] ?? ''
);


/*
 * ============================================================
 * VALIDACIONES
 * ============================================================
 */

if (!in_array($tipoTraslado, ['paciente', 'otro'], true)) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'El tipo de traslado no es válido.'
    ]);

    exit;
}


if (!$idVehiculo) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Debe seleccionar un vehículo.'
    ]);

    exit;
}


if (!$idChofer) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Debe seleccionar un chofer.'
    ]);

    exit;
}


if (!$idEnfermero) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Debe seleccionar un enfermero.'
    ]);

    exit;
}


if ($idChofer === $idEnfermero) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'El chofer y el enfermero deben ser funcionarios diferentes.'
    ]);

    exit;
}


if ($origen === '') {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'El origen es obligatorio.'
    ]);

    exit;
}


if ($destino === '') {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'El destino es obligatorio.'
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
        throw new Exception('La llegada estimada debe ser posterior a la partida.');
    }

} catch (Exception $e) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'La fecha y hora de destino no son válidas.'
    ]);

    exit;
}


if (!in_array($prioridad, ['baja', 'media', 'alta'], true)) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'La prioridad no es válida.'
    ]);

    exit;
}


/*
 * ============================================================
 * VALIDACIONES ESPECÍFICAS
 * ============================================================
 */

if ($tipoTraslado === 'paciente') {

    if ($ciPaciente === '') {

        http_response_code(400);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'La cédula del paciente es obligatoria.'
        ]);

        exit;
    }


    if ($nombrePaciente === '') {

        http_response_code(400);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'El nombre del paciente es obligatorio.'
        ]);

        exit;
    }

} else {

    if ($descripcionOtro === '') {

        http_response_code(400);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'La descripción del traslado es obligatoria.'
        ]);

        exit;
    }
}


/* ============================================================
 * CREACIÓN MEDIANTE EL MODELO
 * ============================================================ */
try {
    $modelo = new Traslado();
    $idTraslado = $modelo->crear([
        'tipoTraslado' => $tipoTraslado,
        'idVehiculo' => $idVehiculo,
        'idChofer' => $idChofer,
        'idEnfermero' => $idEnfermero,
        'origen' => $origen,
        'destino' => $destino,
        'fechaPartida' => $fechaPartida,
        'fechaDestino' => $fechaDestino,
        'prioridad' => $prioridad,
        'observaciones' => $observaciones,
        'ciPaciente' => $ciPaciente,
        'nombrePaciente' => $nombrePaciente,
        'telefonoPaciente' => $telefonoPaciente,
        'quienRecibe' => $quienRecibe,
        'puerta' => $puerta,
        'piso' => $piso,
        'sala' => $sala,
        'descripcionOtro' => $descripcionOtro,
    ]);

    echo json_encode([
        'ok' => true,
        'mensaje' => 'Traslado creado correctamente.',
        'id_traslado' => $idTraslado
    ]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'mensaje' => $e->getMessage()
    ]);
}
exit;
