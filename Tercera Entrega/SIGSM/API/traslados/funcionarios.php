<?php

require_once __DIR__ . "/../../Backend/models/Traslado.php";
require_once __DIR__ . "/../../Backend/config/auth.php";



header('Content-Type: application/json; charset=utf-8');

iniciarSesion();

if (!usuarioAutenticado()) {

    http_response_code(403);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'No autorizado.'
    ]);

    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {

    http_response_code(405);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Método no permitido.'
    ]);

    exit;
}

try {

    $traslado = new Traslado();
    $funcionarios = $traslado->obtenerFuncionariosDisponibles();

    $choferes = [];
    $enfermeros = [];


    foreach ($funcionarios as $funcionario) {

        if ((bool)$funcionario['puede_ser_chofer']) {
            $choferes[] = $funcionario;
        }

        if ((bool)$funcionario['puede_ser_enfermero']) {
            $enfermeros[] = $funcionario;
        }
    }


    echo json_encode([
        'ok' => true,
        'funcionarios' => $funcionarios,
        'choferes' => $choferes,
        'enfermeros' => $enfermeros
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al obtener los funcionarios disponibles.'
    ]);
}

exit;