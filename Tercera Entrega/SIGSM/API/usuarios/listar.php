<?php

require_once __DIR__ . "/../../Backend/config/database.php";
require_once __DIR__ . "/../../Backend/config/auth.php";
require_once __DIR__ . "/../../Backend/models/Usuario.php";

header('Content-Type: application/json; charset=utf-8');

/*
 * Solo los administradores pueden consultar
 * el listado completo de usuarios.
 */
exigirAdministrador();

try {

    $usuarioModel = new Usuario();

    $usuarios = $usuarioModel->obtenerTodos();

    echo json_encode([
        'ok' => true,
        'usuarios' => $usuarios
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al obtener los usuarios.'
    ]);
}

exit;