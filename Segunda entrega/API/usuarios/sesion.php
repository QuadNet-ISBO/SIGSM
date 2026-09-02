<?php

/*
 * ============================================================
 * ENDPOINT: SESIÓN
 * ============================================================
 *
 * Este es se encarga de comprobar quien es el usuario que esta conectado 
 * y si tiene una sesion valida para acceder al sistema. 
 * Todas las páginas protegidas (dashboard, traslados, documentos,
 * admin) lo consultan apenas cargan, a través de verificarSesion()
 * en auth.js o verificarAdministrador() en admin-auth.js.
 */

require_once __DIR__ . "/../../Backend/config/auth.php";

header('Content-Type: application/json; charset=utf-8');

iniciarSesion();


/*
 * ============================================================
 * VERIFICAR SESIÓN
 * ============================================================
 *
 * Si no hay ningún id_usuario guardado en $_SESSION, quiere
 * decir que nadie inició sesión (o que la sesión ya venció),
 * asi que devuelve 401 y el frontend va a redirigir al login.
 */

if (!usuarioAutenticado()) {

    http_response_code(401);

    echo json_encode([
        'ok' => false,
        'autenticado' => false,
        'autorizado' => false,
        'mensaje' => 'No hay una sesión activa.'
    ]);

    exit;
}


/*
 * ============================================================
 * VERIFICAR DATOS DE SESIÓN
 * ============================================================
 *
 * Chequeo extra de que la sesión no este "incompleta". En
 * teoria, si usuarioAutenticado() dio true, estos datos ya
 * deberian estar todos guardados, pero esta validación es una red de seguridad extra
 * por si algo raro paso con la sesión.
 */

if (                                // Comprueba que todas las variables existan y no sean null
    !isset(
        $_SESSION['id_usuario'],
        $_SESSION['user'],
        $_SESSION['nombre'],
        $_SESSION['apellido'],
        $_SESSION['rol']
    )
) {

    http_response_code(401);

    echo json_encode([
        'ok' => false,
        'autenticado' => false,
        'autorizado' => false,
        'mensaje' => 'La sesión no contiene los datos necesarios.'
    ]);

    exit;
}


/*
 * ============================================================
 * ROL SOLICITADO
 * ============================================================
 */

$rolSolicitado =                // Busca en la URL un parametro llamado rol
    $_GET['rol'] ?? null;


if (                                            // Tiene como condicion que se pida un rol y que sea igual al solicitado
    $rolSolicitado !== null &&
    obtenerRolUsuario() !== $rolSolicitado
) {

    http_response_code(403);

    echo json_encode([
        'ok' => false,
        'autenticado' => true,
        'autorizado' => false,
        'mensaje' => 'No tiene permisos para acceder a esta sección.'
    ]);

    exit;
}


/*
 * ============================================================
 * SESIÓN VÁLIDA
 * ============================================================
 *
 * Si llega hasta aca, la sesión existe, esta completa y (si
 * se pidio un rol en particular) el usuario tiene ese rol.
 * 
 * Se devuelve estso datos al frontend
 */

echo json_encode([

    'ok' => true,

    'autenticado' => true,

    'autorizado' => true,

    'usuario' => [

        'id_usuario' =>
            $_SESSION['id_usuario'],

        'user' =>
            $_SESSION['user'],

        'nombre' =>
            $_SESSION['nombre'],

        'apellido' =>
            $_SESSION['apellido'],

        'rol' =>
            $_SESSION['rol']
    ]

]);

exit;
