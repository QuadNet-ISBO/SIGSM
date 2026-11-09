<?php

require_once __DIR__ . "/../../Backend/config/database.php";
require_once __DIR__ . "/../../Backend/config/auth.php";
require_once __DIR__ . "/../../Backend/models/Vehiculo.php";

header('Content-Type: application/json; charset=utf-8');

iniciarSesion();


/*
 * Solo los administradores pueden consultar
 * los tipos de vehículo.
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
     * Obtiene los tipos.
     */
    $tipos =
        $vehiculoModel->obtenerTipos();


    /*
     * Respuesta exitosa.
     */
    echo json_encode([
        'ok' => true,
        'tipos' => $tipos
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al obtener los tipos de vehículo.'
    ]);
}

exit;