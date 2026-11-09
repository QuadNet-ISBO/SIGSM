<?php

require_once __DIR__ . "/../../Backend/config/database.php";
require_once __DIR__ . "/../../Backend/config/auth.php";
require_once __DIR__ . "/../../Backend/models/Vehiculo.php";

header('Content-Type: application/json; charset=utf-8');

iniciarSesion();


/*
 * Solo los administradores pueden consultar
 * información de vehículos.
 */
exigirAdministrador();


/*
 * Solo acepta solicitudes GET.
 */
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {

    http_response_code(405);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Método no permitido.'
    ]);

    exit;
}


/*
 * Obtiene el ID enviado mediante la URL.
 *
 * Ejemplo:
 * obtener.php?id=1
 */
$idVehiculo = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
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
        'mensaje' => 'ID de vehículo inválido.'
    ]);

    exit;
}


try {

    /*
     * Crea el modelo.
     */
    $vehiculoModel = new Vehiculo();


    /*
     * Busca el vehículo.
     */
    $vehiculo =
        $vehiculoModel->obtenerPorId($idVehiculo);


    /*
     * Verifica que exista.
     */
    if ($vehiculo === null) {

        http_response_code(404);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'Vehículo no encontrado.'
        ]);

        exit;
    }


    /*
     * Respuesta exitosa.
     */
    echo json_encode([
        'ok' => true,
        'vehiculo' => $vehiculo
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al obtener el vehículo.'
    ]);
}

exit;