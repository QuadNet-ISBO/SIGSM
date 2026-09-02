<?php

/*
 * ============================================================
 * ENDPOINT: LOGOUT
 * ============================================================
 *
 * Cierra la sesión del usuario actual. Lo llama el boton
 * "Salir" que aparece en el header de todas las paginas
 * protegidas (dashboard, admin, traslados, documentos), a
 * traves de las funciones cerrarSesion() de auth.js y de
 * admin-auth.js.
 */

require_once __DIR__ . "/../../Backend/config/auth.php";

header('Content-Type: application/json; charset=utf-8');

iniciarSesion();


/*
 * ============================================================
 * MÉTODO
 * ============================================================
 *
 * Igual que en login.php, exige POST: cerrar sesión es una
 * acción que modifica estado en el servidor.
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
 * OBTENER PARÁMETROS DE LA COOKIE
 * ============================================================
 *
 * Necesitamos los mismos parámetros (path, domain, secure, etc.)
 * con los que se creo la cookie de sesión para poder borrarla
 * correctamente.
 */

$params =
    session_get_cookie_params();


/*
 * ============================================================
 * ELIMINAR VARIABLES DE SESIÓN
 * ============================================================
 *
 * Vacia todo lo que estuviera guardado (id_usuario, user,
 * nombre, apellido, rol) para que ya no quede ningun dato.
 */

$_SESSION = [];


/*
 * ============================================================
 * ELIMINAR COOKIE
 * ============================================================
 *
 * Le decimos al navegador que borre la cookie de sesión
 * mandándole la misma cookie pero con una fecha de expiración
 * en el pasado. Es la forma estandar de "borrar" una cookie,
 * ya que no existe un comando de PHP que directamente le diga
 * al navegador "eliminala".
 */

if (
    ini_get('session.use_cookies')          // Comprueba si se usan cookies
) {

    setcookie(                      // Modifica la cookie
        session_name(),             // Obtiene el nombre de la sesion
        '',                         // Deja la cookie sin valor
        [
            'expires' =>            // Expira la cookie restandole 42000s
                time() - 42000,

            'path' =>
                $params['path'],

            'domain' =>
                $params['domain'],

            'secure' =>
                $params['secure'],

            'httponly' =>
                $params['httponly'],

            'samesite' =>
                $params['samesite'] ?? 'Lax'
        ]
    );
}


/*
 * ============================================================
 * DESTRUIR SESIÓN
 * ============================================================
 *
 * Esto borra del lado del servidor el archivo (o registro)
 * donde PHP guarda los datos de esta sesión en particular.
 * Con esto mas el paso anterior (borrar la cookie), la sesión
 * queda completamente cerrada.
 */

session_destroy();


/*
 * ============================================================
 * RESPUESTA
 * ============================================================
 */

echo json_encode([
    'ok' => true,
    'autenticado' => false,
    'mensaje' => 'Sesión cerrada correctamente.'
]);

exit;
