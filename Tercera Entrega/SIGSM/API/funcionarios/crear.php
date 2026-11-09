<?php

require_once __DIR__ . "/../../Backend/config/database.php";
require_once __DIR__ . "/../../Backend/config/auth.php";
require_once __DIR__ . "/../../Backend/models/Funcionario.php";

header('Content-Type: application/json; charset=utf-8');

iniciarSesion();


/*
 * Solo los administradores pueden crear funcionarios.
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
$ci = trim($datos['ci'] ?? '');
$nombre = trim($datos['nombre'] ?? '');
$apellido = trim($datos['apellido'] ?? '');
$telefono = trim($datos['telefono'] ?? '');
$horario = trim($datos['horario'] ?? '');

$puedeSerChofer =
    !empty($datos['puede_ser_chofer']);

$puedeSerEnfermero =
    !empty($datos['puede_ser_enfermero']);


/*
 * Validaciones obligatorias.
 */
if (
    $ci === '' ||
    $nombre === '' ||
    $apellido === '' ||
    $horario === ''
) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Complete todos los campos obligatorios.'
    ]);

    exit;
}


try {

    $funcionarioModel = new Funcionario();


    /*
     * Verifica que la CI no esté registrada.
     */
    if ($funcionarioModel->buscarPorCi($ci) !== null) {

        http_response_code(409);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'La CI ya está registrada.'
        ]);

        exit;
    }


    /*
     * Crea el funcionario.
     *
     * El modelo establece:
     * estado_laboral = activo
     * estado_operativo = libre
     */
    $idFuncionario = $funcionarioModel->crear([

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
            'activo',

        'estado_operativo' =>
            'libre',

        'puede_ser_chofer' =>
            $puedeSerChofer ? 1 : 0,

        'puede_ser_enfermero' =>
            $puedeSerEnfermero ? 1 : 0,

        'horario' =>
            $horario
    ]);


    echo json_encode([
        'ok' => true,
        'mensaje' => 'Funcionario creado correctamente.',
        'id_funcionario' => $idFuncionario
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al crear el funcionario.'
    ]);

}

exit;