<?php

/**
 * Inicia la sesión si todavía no existe.
 */
function iniciarSesion(): void
{
    if (session_status() === PHP_SESSION_NONE) {

        $secure = (
            isset($_SERVER['HTTPS']) &&
            $_SERVER['HTTPS'] !== 'off'
        );

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        // No aceptar identificadores de sesión que no haya creado PHP.
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_start();

        // Expirar únicamente sesiones autenticadas tras 30 minutos sin peticiones.
        // Las sesiones anónimas siguen disponibles para obtener el token CSRF del login.
        if (isset($_SESSION['id_usuario'])) {
            $ahora = time();
            $ultimaActividad = (int) ($_SESSION['ultima_actividad'] ?? 0);

            if ($ultimaActividad > 0 && ($ahora - $ultimaActividad) >= 1800) {
                $_SESSION = [];
                session_regenerate_id(true);
                // Se conserva una sesión anónima nueva para permitir el login y su CSRF.
            } else {
                $_SESSION['ultima_actividad'] = $ahora;
            }
        }
    }
}


/**
 * Verifica si existe una sesión activa.
 */
function usuarioAutenticado(): bool
{
    iniciarSesion();

    return isset($_SESSION['id_usuario']);
}


/**
 * Obtiene el rol del usuario actualmente autenticado.
 */
function obtenerRolUsuario(): ?string
{
    iniciarSesion();

    return $_SESSION['rol'] ?? null;
}


/**
 * Verifica si el usuario tiene un rol específico.
 */
function tieneRol(string $rol): bool
{
    return obtenerRolUsuario() === $rol;
}


/**
 * Verifica si el usuario es administrador.
 */
function esAdministrador(): bool
{
    return tieneRol('Administrador');
}


/**
 * Exige que exista una sesión.
 */
function exigirAutenticacion(): void
{
    if (!usuarioAutenticado()) {

        http_response_code(401);

        exit('No autorizado.');
    }
}


/**
 * Exige que el usuario sea administrador.
 */
function exigirAdministrador(): void
{
    exigirAutenticacion();

    if (!esAdministrador()) {

        http_response_code(403);

        exit('Acceso denegado.');
    }
}

/**
 * Token CSRF de la sesión. Se crea una sola vez por sesión.
 */
function obtenerTokenCsrf(): string
{
    iniciarSesion();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Rechaza peticiones que modifican datos sin un token válido.
 * Todas las API privadas cargan auth.php, por lo que se aplica
 * también a login y logout. Las API públicas del portal no lo cargan.
 */
function verificarCsrf(): void
{
    $metodo = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if (!in_array($metodo, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        return;
    }

    $recibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($recibido) || !hash_equals(obtenerTokenCsrf(), $recibido)) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode([
            'ok' => false,
            'mensaje' => 'Solicitud rechazada: token de seguridad inválido. Actualice la página e intente nuevamente.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Protección centralizada para las API que importan auth.php.
verificarCsrf();
