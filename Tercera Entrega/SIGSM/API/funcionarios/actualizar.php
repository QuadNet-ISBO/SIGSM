<?php

require_once __DIR__ . "/../../Backend/config/database.php";
require_once __DIR__ . "/../../Backend/config/auth.php";
require_once __DIR__ . "/../../Backend/models/Funcionario.php";

header('Content-Type: application/json; charset=utf-8');

iniciarSesion();


/*
 * Solo los administradores pueden actualizar funcionarios.
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


/*
 * Obtiene los datos.
 */
$id = filter_var(
    $datos['id_funcionario'] ?? null,
    FILTER_VALIDATE_INT
);

$ci = trim($datos['ci'] ?? '');
$nombre = trim($datos['nombre'] ?? '');
$apellido = trim($datos['apellido'] ?? '');
$telefono = trim($datos['telefono'] ?? '');
$horario = trim($datos['horario'] ?? '');

$estadoLaboral =
    trim($datos['estado_laboral'] ?? '');

$puedeSerChofer =
    !empty($datos['puede_ser_chofer']);

$puedeSerEnfermero =
    !empty($datos['puede_ser_enfermero']);


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
        'mensaje' => 'ID de funcionario inválido.'
    ]);

    exit;
}


/*
 * Valida campos obligatorios.
 */
if (
    $ci === '' ||
    $nombre === '' ||
    $apellido === '' ||
    $horario === '' ||
    $estadoLaboral === ''
) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Datos obligatorios incompletos.'
    ]);

    exit;
}


/*
 * Estados laborales permitidos.
 */
$estadosValidos = [
    'activo',
    'inactivo',
    'desvinculado'
];

if (!in_array($estadoLaboral, $estadosValidos, true)) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Estado laboral inválido.'
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
     * Verifica que la CI no pertenezca
     * a otro funcionario.
     */
    $funcionarioPorCi =
        $funcionarioModel->buscarPorCi($ci);

    if (
        $funcionarioPorCi !== null &&
        (int) $funcionarioPorCi['id_funcionario'] !== (int) $id
    ) {

        http_response_code(409);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'La CI ya está registrada.'
        ]);

        exit;
    }


    /*
     * Actualiza el funcionario.
     */
    $actualizado =
        $funcionarioModel->actualizar(
            $id,
            [
                'ci' =>
                    $ci,

                'nombre' =>
                    $nombre,

                'apellido' =>
                    $apellido,

                'telefono' =>
                    $telefono === ''
                        ? null
                        : $telefono,

                'estado_laboral' =>
                    $estadoLaboral,

                'puede_ser_chofer' =>
                    $puedeSerChofer ? 1 : 0,

                'puede_ser_enfermero' =>
                    $puedeSerEnfermero ? 1 : 0,

                'horario' =>
                    $horario
            ]
        );


    if (!$actualizado) {

        http_response_code(500);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'No se pudo actualizar el funcionario.'
        ]);

        exit;
    }


    echo json_encode([
        'ok' => true,
        'mensaje' => 'Funcionario actualizado correctamente.'
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
        'mensaje' => 'Error al actualizar el funcionario.'
    ]);

}

exit;