<?php

/*
 * ============================================================
 * ENDPOINT: LOGIN
 * ============================================================
 *
 * Este archivo es al que llama login.html cuando alguien
 * envía el formulario de inicio de sesión. Recibe usuario y
 * contraseña, los valida con la base de datos y si está
 * todo bien arranca la sesión guardando los datos básicos
 * del usuario logueado.
 */

require_once __DIR__ . "/../../Backend/config/database.php";
require_once __DIR__ . "/../../Backend/config/auth.php";
require_once __DIR__ . "/../../Backend/models/Usuario.php";

header('Content-Type: application/json; charset=utf-8');

// Retoma la sesión antes que nada, porque
// mas abajo, si el login sale bien, va a necesitar guardar
// datos en $_SESSION.
iniciarSesion();


/*
 * ============================================================
 * MÉTODO
 * ============================================================
 *
 * Este endpoint solo tiene sentido como POST (se están mandando
 * credenciales, no pidiendo información). Si alguien intenta
 * entrar por GET o otro método, lo corta.
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
 * ============================================================
 * DATOS RECIBIDOS
 * ============================================================
 *
 * El frontend manda el body como JSON, así que leemos el
 * el body del request con file_get_contents('php://input')
 * y lo decodificamos con json_decode.
 */

$contenido = file_get_contents('php://input');

$datos = json_decode(
    $contenido,
    true
);


/*
 * Si lo que llego no es un JSON valido, json_decode no devuelve un
 * array y corta para no seguir trabajando con datos que
 * no tienen la forma esperada.
 */

if (!is_array($datos)) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Los datos enviados no son válidos.'
    ]);

    exit;
}

/*Busca dentro del array el campo user/password, los obtiene y los guarda en sus respectivas variables*/
$user = trim(
    $datos['user'] ?? ''
);

$password =
    $datos['password'] ?? '';


/*
 * ============================================================
 * VALIDACIÓN
 * ============================================================
 * 
 * Verifica que ambos campos no esten vacios.
 */

if (
    $user === '' ||
    $password === ''
) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Usuario y contraseña son obligatorios.'
    ]);

    exit;
}


/*
 * ============================================================
 * BUSCAR USUARIO
 * ============================================================
 *
 * Le delegamos al modelo Usuario la tarea de buscar en la base
 * si existe alguien con ese nombre de usuario.
 * 
 * Si ocurre un error detiene la ejecucion.
 */

try {

    $usuarioModel =
        new Usuario();

    $usuario =
        $usuarioModel->buscarPorUser($user);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al consultar el usuario.'
    ]);

    exit;
}


/*
 * ============================================================
 * VERIFICAR CREDENCIALES
 * ============================================================
 * 
 * Verifica si existe el usuario y si la contraseña ingresada coincide con la del usuario.
 */

if (
    $usuario === null ||
    !$usuarioModel->verificarPassword(
        $password,
        $usuario['password']
    )
) {

    http_response_code(401);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Usuario o contraseña incorrectos.'
    ]);

    exit;
}


/*
 * ============================================================
 * VERIFICAR ESTADO
 * ============================================================
 *
 * Aunque el usuario y la contraseña sean correctos, si a esa
 * persona ya no le corresponde entrar, no la deja pasar.
 */

if (
    $usuario['estado_laboral'] !== 'activo'
) {

    http_response_code(403);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'El usuario no se encuentra activo.'
    ]);

    exit;
}


/*
 * ============================================================
 * REGENERAR SESIÓN
 * ============================================================
 *
 * Justo antes de loguear a la persona, PHP genera un nuevo identificador de sesión por seguridad y elimina el identificador anterior, 
 * evitando posibles ataques de fijación de sesión.
 */

session_regenerate_id(true);


/*
 * ============================================================
 * GUARDAR DATOS EN SESIÓN
 * ============================================================
 *
 * Mientras dure la sesión, el servidor va a recordar quién es este usuario sin que haga falta volver
 * a mandar la contraseña en cada request. Guarda solo lo necesario para identificarlo.
 */

$_SESSION['id_usuario'] =
    $usuario['id_usuario'];

$_SESSION['user'] =
    $usuario['user'];

$_SESSION['nombre'] =
    $usuario['nombre'];

$_SESSION['apellido'] =
    $usuario['apellido'];

$_SESSION['rol'] =
    $usuario['rol'];


/*
 * ============================================================
 * RESPUESTA
 * ============================================================
 *
 * Le devuelve al frontend los datos basicos del usuario para
 * que login.html sepa que rol tiene y decida a donde
 * redirigirlo (panel de administración o dashboard).
 */

echo json_encode([
    'ok' => true,
    'mensaje' => 'Inicio de sesión exitoso.',

    'usuario' => [
        'id_usuario' =>
            $usuario['id_usuario'],

        'nombre' =>
            $usuario['nombre'],

        'apellido' =>
            $usuario['apellido'],

        'rol' =>
            $usuario['rol']
    ]
]);

exit;
