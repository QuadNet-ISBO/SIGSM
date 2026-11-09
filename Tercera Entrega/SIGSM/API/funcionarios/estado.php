<?php

require_once __DIR__ . "/../../Backend/config/database.php";
require_once __DIR__ . "/../../Backend/config/auth.php";
require_once __DIR__ . "/../../Backend/models/Funcionario.php";

header('Content-Type: application/json; charset=utf-8');

iniciarSesion();


/*
 * Solo los administradores pueden cambiar
 * el estado laboral.
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
 * Recibe los datos enviados en JSON.
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


$id = filter_var(
    $datos['id_funcionario'] ?? null,
    FILTER_VALIDATE_INT
);

$estado =
    trim($datos['estado_laboral'] ?? '');


/*
 * Valida el ID.
 */
if (
    $id === false ||
    $id === null ||
    $id <= 0
) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'ID inválido.'
    ]);

    exit;
}


/*
 * Estados permitidos.
 */
$estadosValidos = [
    'activo',
    'inactivo',
    'desvinculado'
];

if (!in_array($estado, $estadosValidos, true)) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Estado inválido.'
    ]);

    exit;
}


try {

    $funcionarioModel = new Funcionario();


    /*
     * Verifica que el funcionario exista.
     */
    $funcionario =
        $funcionarioModel->buscarPorId($id);

    if ($funcionario === null) {

        http_response_code(404);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'Funcionario no encontrado.'
        ]);

        exit;
    }


    /*
     * Cambia el estado laboral.
     */
    $actualizado =
        $funcionarioModel->cambiarEstado(
            $id,
            $estado
        );


    if (!$actualizado) {

        http_response_code(500);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'No se pudo actualizar el estado.'
        ]);

        exit;
    }


    echo json_encode([
        'ok' => true,
        'mensaje' => 'Estado actualizado correctamente.'
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
        'mensaje' => 'Error al cambiar el estado.'
    ]);

}

exit;