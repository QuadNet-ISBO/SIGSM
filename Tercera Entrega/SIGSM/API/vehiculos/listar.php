<?php

require_once __DIR__ . "/../../Backend/config/database.php";
require_once __DIR__ . "/../../Backend/config/auth.php";
require_once __DIR__ . "/../../Backend/models/Vehiculo.php";

header('Content-Type: application/json; charset=utf-8');

iniciarSesion();


/*
 * Solo los administradores pueden consultar
 * el listado de vehículos.
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


try {

    /*
     * Crea el modelo.
     */
    $vehiculoModel = new Vehiculo();


    /*
     * Obtiene todos los vehículos.
     */
    $vehiculos =
        $vehiculoModel->obtenerTodos();


    /*
     * Respuesta exitosa.
     */
    echo json_encode([
        'ok' => true,
        'vehiculos' => $vehiculos
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al obtener los vehículos.'
    ]);
}

exit;