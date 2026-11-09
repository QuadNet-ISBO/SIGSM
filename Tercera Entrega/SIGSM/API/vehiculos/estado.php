<?php

require_once __DIR__ . "/../../Backend/config/database.php";
require_once __DIR__ . "/../../Backend/config/auth.php";
require_once __DIR__ . "/../../Backend/models/Vehiculo.php";

header('Content-Type: application/json; charset=utf-8');

iniciarSesion();


/*
 * Solo los administradores pueden cambiar
 * el estado laboral de los vehículos.
 */
exigirAdministrador();


/*
 * Solo acepta solicitudes PATCH.
 */
if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {

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
 * Estados laborales permitidos.
 */
$estadosPermitidos = [
    'activo',
    'inactivo',
    'desvinculado'
];


/*
 * Valida el estado.
 */
if (!in_array($estadoLaboral, $estadosPermitidos, true)) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'El estado laboral no es válido.'
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
    $vehiculo = $vehiculoModel->obtenerPorId(
        $idVehiculo
    );


    if ($vehiculo === null) {

        http_response_code(404);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'Vehículo no encontrado.'
        ]);

        exit;
    }


    /*
     * Cambia el estado laboral.
     */
    $actualizado = $vehiculoModel->cambiarEstado(
        $idVehiculo,
        $estadoLaboral
    );


    if (!$actualizado) {

        http_response_code(500);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'No se pudo actualizar el estado.'
        ]);

        exit;
    }


    /*
     * Respuesta exitosa.
     */
    echo json_encode([
        'ok' => true,
        'mensaje' => 'Estado del vehículo actualizado correctamente.'
    ]);

} catch (DomainException $e) {

    http_response_code(409);

    echo json_encode([
        'ok' => false,
        'mensaje' => $e->getMessage()
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al actualizar el estado del vehículo.'
    ]);
}

exit;