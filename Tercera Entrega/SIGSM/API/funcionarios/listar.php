<?php

require_once __DIR__ . "/../../Backend/config/database.php";
require_once __DIR__ . "/../../Backend/config/auth.php";
require_once __DIR__ . "/../../Backend/models/Funcionario.php";

header('Content-Type: application/json; charset=utf-8');

iniciarSesion();


/*
 * Solo los administradores pueden consultar
 * el listado de funcionarios.
 */
if (!usuarioAutenticado() || !esAdministrador()) {

    http_response_code(403);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'No autorizado.'
    ]);

    exit;
}


try {

    $funcionarioModel = new Funcionario();

    $funcionarios =
        $funcionarioModel->obtenerTodos();


    echo json_encode([
        'ok' => true,
        'funcionarios' => $funcionarios
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al obtener los funcionarios.'
    ]);

}

exit;