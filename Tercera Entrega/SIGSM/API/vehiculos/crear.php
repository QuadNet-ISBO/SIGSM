<?php

require_once __DIR__ . "/../../Backend/config/database.php";
require_once __DIR__ . "/../../Backend/config/auth.php";
require_once __DIR__ . "/../../Backend/models/Vehiculo.php";

header('Content-Type: application/json; charset=utf-8');

iniciarSesion();


/*
 * Solo los administradores pueden crear vehículos.
 */
exigirAdministrador();


/*
 * Solo acepta solicitudes POST.
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
 * Recibe los datos enviados por el frontend.
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
 * Obtiene y limpia los datos.
 */
$matricula = trim(
    $datos['matricula'] ?? ''
);

$idTipoVehiculo = filter_var(
    $datos['id_tipo_vehiculo'] ?? null,
    FILTER_VALIDATE_INT
);


/*
 * Valida los campos obligatorios.
 */
if (
    $matricula === '' ||
    $idTipoVehiculo === false ||
    $idTipoVehiculo <= 0
) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Complete todos los campos obligatorios.'
    ]);

    exit;
}


try {

    /*
     * Crea el modelo.
     */
    $vehiculoModel = new Vehiculo();


    /*
     * Crea el vehículo.
     *
     * El modelo establece automáticamente:
     * estado_laboral = activo
     * estado_operativo = libre
     */
    $idVehiculo = $vehiculoModel->crear(
        $matricula,
        $idTipoVehiculo
    );


    echo json_encode([
        'ok' => true,
        'mensaje' => 'Vehículo creado correctamente.',
        'id_vehiculo' => $idVehiculo
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


    /*
     * Error general de base de datos.
     */
    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al crear el vehículo.'
    ]);

}

exit;