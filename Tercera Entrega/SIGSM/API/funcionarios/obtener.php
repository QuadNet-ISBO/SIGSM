<?php

require_once __DIR__ . "/../../Backend/config/database.php";
require_once __DIR__ . "/../../Backend/config/auth.php";
require_once __DIR__ . "/../../Backend/models/Funcionario.php";

header('Content-Type: application/json; charset=utf-8');

iniciarSesion();


/*
 * Solo los administradores pueden consultar
 * información de funcionarios.
 */
if (!usuarioAutenticado() || !esAdministrador()) {

    http_response_code(403);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'No autorizado.'
    ]);

    exit;
}


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
 * Obtiene el ID desde la URL.
 *
 * Ejemplo:
 * obtener.php?id=1
 */
$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);


/*
 * Verifica que el ID sea válido.
 */
if (
    $id === false ||
    $id === null ||
    $id <= 0
) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'ID de funcionario inválido.'
    ]);

    exit;
}


try {

    $funcionarioModel = new Funcionario();


    $funcionario =
        $funcionarioModel->buscarPorId($id);


    /*
     * Funcionario inexistente.
     */
    if ($funcionario === null) {

        http_response_code(404);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'Funcionario no encontrado.'
        ]);

        exit;
    }


    echo json_encode([
        'ok' => true,
        'funcionario' => $funcionario
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al obtener el funcionario.'
    ]);

}

exit;