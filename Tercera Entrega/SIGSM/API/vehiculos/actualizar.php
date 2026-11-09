<?php

require_once __DIR__ . "/../../Backend/config/database.php";
require_once __DIR__ . "/../../Backend/config/auth.php";
require_once __DIR__ . "/../../Backend/models/Vehiculo.php";

header('Content-Type: application/json; charset=utf-8');

iniciarSesion();


/*
 * Solo los administradores pueden actualizar vehículos.
 */
exigirAdministrador();


/*
 * Solo acepta solicitudes PUT.
 */
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {

    http_response_code(405);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Método no permitido.'
    ]);

    exit;
}


/*
 * Recibe los datos enviados en formato JSON.
 */
$datos = json_decode(
    file_get_contents('php://input'),
    true
);


if (!is_array($datos)) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Los datos enviados no son válidos.'
    ]);

    exit;
}


/*
 * Obtiene los datos.
 */
$idVehiculo = filter_var(
    $datos['id_vehiculo'] ?? null,
    FILTER_VALIDATE_INT
);

$matricula = trim(
    $datos['matricula'] ?? ''
);

$idTipoVehiculo = filter_var(
    $datos['id_tipo_vehiculo'] ?? null,
    FILTER_VALIDATE_INT
);

$estadoLaboral = trim(
    $datos['estado_laboral'] ?? ''
);


/*
 * Valida el ID.
 */
if (
    $idVehiculo === false ||
    $idVehiculo === null ||
    $idVehiculo <= 0
) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'El ID del vehículo no es válido.'
    ]);

    exit;
}


/*
 * Valida los campos obligatorios.
 */
if (
    $matricula === '' ||
    $idTipoVehiculo === false ||
    $idTipoVehiculo <= 0 ||
    $estadoLaboral === ''
) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Todos los campos obligatorios deben estar completos.'
    ]);

    exit;
}


/*
 * Estados laborales permitidos.
 */
$estadosPermitidos = [
    'activo',
    'inactivo',
    'desvinculado'
];


if (!in_array(
    $estadoLaboral,
    $estadosPermitidos,
    true
)) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'El estado laboral seleccionado no es válido.'
    ]);

    exit;
}


try {

    /*
     * Crea el modelo.
     */
    $vehiculoModel = new Vehiculo();


    /*
     * Verifica que el vehículo exista.
     */
    $vehiculoExistente =
        $vehiculoModel->obtenerPorId($idVehiculo);


    if ($vehiculoExistente === null) {

        http_response_code(404);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'Vehículo no encontrado.'
        ]);

        exit;
    }


    /*
     * Actualiza el vehículo.
     */
    $actualizado = $vehiculoModel->actualizar(
        $idVehiculo,
        $matricula,
        $idTipoVehiculo,
        $estadoLaboral
    );


    if (!$actualizado) {

        http_response_code(500);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'No se pudo actualizar el vehículo.'
        ]);

        exit;
    }


    echo json_encode([
        'ok' => true,
        'mensaje' => 'Vehículo actualizado correctamente.'
    ]);

} catch (DomainException $e) {

    http_response_code(409);

    echo json_encode([
        'ok' => false,
        'mensaje' => $e->getMessage()
    ]);

} catch (PDOException $e) {

    /*
     * Matrícula duplicada.
     */
    if (($e->errorInfo[1] ?? null) == 1062) {

        http_response_code(409);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'La matrícula ya está registrada.'
        ]);

        exit;
    }


    /*
     * El tipo de vehículo no existe.
     */
    if (($e->errorInfo[1] ?? null) == 1452) {

        http_response_code(400);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'El tipo de vehículo no existe.'
        ]);

        exit;
    }


    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al actualizar el vehículo.'
    ]);

}

exit;