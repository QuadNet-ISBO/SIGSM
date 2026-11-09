<?php

require_once __DIR__ . "/../../Backend/config/database.php";
require_once __DIR__ . "/../../Backend/config/auth.php";
require_once __DIR__ . "/../../Backend/models/Usuario.php";

header('Content-Type: application/json; charset=utf-8');

iniciarSesion();


/*
 * ============================================================
 * MÉTODO
 * ============================================================
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
 */

$contenido = file_get_contents('php://input');

$datos = json_decode(
    $contenido,
    true
);


/*
 * Verificar JSON válido.
 */

if (!is_array($datos)) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Los datos enviados no son válidos.'
    ]);

    exit;
}


$user = trim(
    $datos['user'] ?? ''
);

$password =
    $datos['password'] ?? '';


/*
 * ============================================================
 * VALIDACIÓN
 * ============================================================
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
 * CONTROL DE INTENTOS: 5 errores por usuario / 15 minutos.
 * Se guarda en MySQL, por lo que no se evita borrando cookies.
 * Bloqueo transaccional para que peticiones simultáneas no lo eludan.
 */
try {
    $pdo = Database::getInstancia()->getConexion();
    $usuarioModel = new Usuario();
    $clave = hash('sha256', mb_strtolower($user, 'UTF-8'));
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT IGNORE INTO intentos_login (usuario_hash, intentos, ventana_inicio) VALUES (:hash, 0, NOW())");
    $stmt->execute([':hash' => $clave]);
    $stmt = $pdo->prepare('SELECT intentos, ventana_inicio, bloqueado_hasta FROM intentos_login WHERE usuario_hash = :hash FOR UPDATE');
    $stmt->execute([':hash' => $clave]);
    $control = $stmt->fetch(PDO::FETCH_ASSOC);
    $ahora = new DateTimeImmutable((string) $pdo->query('SELECT NOW()')->fetchColumn());

    if ($control['bloqueado_hasta'] !== null && new DateTimeImmutable($control['bloqueado_hasta']) > $ahora) {
        $pdo->commit();
        http_response_code(429);
        header('Retry-After: 900');
        echo json_encode(['ok' => false, 'mensaje' => 'Demasiados intentos. Espere 15 minutos antes de volver a intentar.']);
        exit;
    }

    $inicio = new DateTimeImmutable($control['ventana_inicio']);
    $reiniciar = $inicio <= $ahora->modify('-15 minutes') || ($control['bloqueado_hasta'] !== null);
    $intentos = $reiniciar ? 0 : (int) $control['intentos'];

    $usuario = $usuarioModel->buscarPorUser($user);
    $valido = $usuario !== null && $usuarioModel->verificarPassword($password, $usuario['password']);

    if (!$valido) {
        $intentos++;
        $bloquear = $intentos >= 5;
        $stmt = $pdo->prepare('UPDATE intentos_login SET intentos = :intentos, ventana_inicio = :inicio, bloqueado_hasta = :hasta WHERE usuario_hash = :hash');
        $stmt->execute([
            ':intentos' => $intentos,
            ':inicio' => $reiniciar ? $ahora->format('Y-m-d H:i:s') : $control['ventana_inicio'],
            ':hasta' => $bloquear ? $ahora->modify('+15 minutes')->format('Y-m-d H:i:s') : null,
            ':hash' => $clave
        ]);
        $pdo->commit();
        http_response_code($bloquear ? 429 : 401);
        if ($bloquear) header('Retry-After: 900');
        echo json_encode([
            'ok' => false,
            'mensaje' => $bloquear ? 'Demasiados intentos. Espere 15 minutos antes de volver a intentar.' : 'Usuario o contraseña incorrectos.'
        ]);
        exit;
    }

    if ($usuario['estado_laboral'] !== 'activo') {
        $pdo->commit();
        http_response_code(403);
        echo json_encode(['ok' => false, 'mensaje' => 'El usuario no se encuentra activo.']);
        exit;
    }

    $stmt = $pdo->prepare('DELETE FROM intentos_login WHERE usuario_hash = :hash');
    $stmt->execute([':hash' => $clave]);
    $pdo->commit();
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log('Error en control de intentos de login: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error al procesar el inicio de sesión.']);
    exit;
}

/*
 * ============================================================
 * REGENERAR SESIÓN
 * ============================================================
 */

session_regenerate_id(true);
unset($_SESSION['csrf_token']);


/*
 * ============================================================
 * GUARDAR DATOS EN SESIÓN
 * ============================================================
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

// Inicio del período de actividad de la nueva sesión autenticada.
$_SESSION['ultima_actividad'] = time();


/*
 * ============================================================
 * RESPUESTA
 * ============================================================
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