<?php

/**
 * Protección de la carpeta de archivos subidos.
 *
 * Los documentos se almacenan en uploads/documentos/.
 * El archivo .htaccess bloquea el acceso directo por URL.
 *
 * Los documentos se entregan mediante las API PHP:
 * - API/documentos/visualizar.php
 * - API/portal/visualizar.php
 */

const CONTENIDO_HTACCESS_UPLOADS = <<<'HTACCESS'
# Bloquear acceso directo a uploads y sus subcarpetas.

<IfModule mod_authz_core.c>
    Require all denied
</IfModule>

<IfModule !mod_authz_core.c>
    Order allow,deny
    Deny from all
</IfModule>

HTACCESS;

function protegerCarpetaUploads(string $directorioDestino): void
{
    $carpetaUploads = dirname($directorioDestino);
    if (!is_dir($carpetaUploads)) {
        throw new RuntimeException('La carpeta uploads no existe.');
    }

    $archivo = $carpetaUploads . DIRECTORY_SEPARATOR . '.htaccess';
    $contenidoEsperado = trim(CONTENIDO_HTACCESS_UPLOADS);

    // No reemplazar configuraciones existentes de forma silenciosa.
    if (!is_file($archivo)) {
        if (file_exists($archivo)) {
            throw new RuntimeException('La ruta de protección no es un archivo regular.');
        }
        if (@file_put_contents($archivo, CONTENIDO_HTACCESS_UPLOADS, LOCK_EX) === false) {
            throw new RuntimeException('No se pudo crear la protección de documentos.');
        }
    }

    $contenidoActual = @file_get_contents($archivo);
    if ($contenidoActual === false) {
        throw new RuntimeException('No se pudo leer la protección de documentos.');
    }

    // Comparar directivas, no comentarios ni espacios en blanco.
    // Cualquier directiva adicional o distinta provoca un rechazo seguro.
    $normalizar = static function (string $contenido): array {
        $lineas = preg_split('/\r\n|\r|\n/', $contenido);
        $directivas = [];
        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if ($linea === '' || str_starts_with($linea, '#')) {
                continue;
            }
            $directivas[] = preg_replace('/\s+/', ' ', $linea);
        }
        return $directivas;
    };

    if ($normalizar($contenidoActual) !== $normalizar($contenidoEsperado)) {
        throw new RuntimeException('No se pudo verificar la protección de documentos.');
    }
}
