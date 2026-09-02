<?php

/*
 * ============================================================
 * FUNCIONES DE SESIÓN Y PERMISOS
 * ============================================================
 *
 * Este archivo trabaja con la sesión de PHP ($_SESSION), 
 * que es donde queda guardado quién es el usuario logueado mientras navega por el sistema.
 */


/**
 * Inicia la sesión si todavía no arrancó ninguna.
 *
 * Esto hay que llamarlo al principio de CUALQUIER archivo que
 * necesite leer o escribir en $_SESSION (login, logout, sesión)
 */
function iniciarSesion(): void
{
    if (session_status() === PHP_SESSION_NONE) {

        $secure = (
            isset($_SERVER['HTTPS']) &&
            $_SERVER['HTTPS'] !== 'off'
        );

        session_set_cookie_params([
            'lifetime' => 0, /*lifetime: la cookie durahasta que se cierre el navegador*/
            'path' => '/', /*path: la cookie esta disponible para todo el sitio*/
            'secure' => $secure, /*secure: la cookie solo viaja por HTTPS*/
            'httponly' => true, /*httponly: la cookie no se puede leer desde JavaScript.*/
            'samesite' => 'Lax' /*samesite 'Lax': el navegador no manda la cookie si el request viene originado desde otro sitio.*/
        ]);

        session_start();
    }
}


/**
 * Verifica si existe una sesión activa.
 *
 * Se fija si en $_SESSION quedo guardado el
 * "id_usuario" que se setea en el logins si está se asume
 * que hay alguien logueado, si no está, no.
 */
function usuarioAutenticado(): bool
{
    iniciarSesion();

    return isset($_SESSION['id_usuario']);
}


/**
 * Obtiene el rol del usuario actualmente autenticado
 *
 * Devuelve null si no hay nadie logueado.
 */
function obtenerRolUsuario(): ?string
{
    iniciarSesion();

    return $_SESSION['rol'] ?? null;
}


/**
 * Compara el rol del usuario logueado con el que se le pasa
 * por parametro.
 */
function tieneRol(string $rol): bool
{
    return obtenerRolUsuario() === $rol;
}


/**
 * Funcion para no tener que escribir
 * tieneRol('Administrador') en todos lados.
 */
function esAdministrador(): bool
{
    return tieneRol('Administrador');
}


/**
 * Corta la ejecución del script si no hay una sesión activa,
 * respondiendo con un 401 (No autorizado).
 *
 * Está pensada para usarse al principio de un endpoint que
 * requiera estar logueado.
 */
function exigirAutenticacion(): void
{
    if (!usuarioAutenticado()) {

        http_response_code(401);

        exit('No autorizado.');
    }
}


/**
 * Lo mismo que exigirAutenticacion(), pero además exige que
 * el usuario logueado sea Administrador. Si está logueado pero
 * con otro rol, corta con un 403 (Prohibido).
 */
function exigirAdministrador(): void
{
    exigirAutenticacion();

    if (!esAdministrador()) {

        http_response_code(403);

        exit('Acceso denegado.');
    }
}
