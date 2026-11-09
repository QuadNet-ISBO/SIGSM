<?php

// XAMPP: valores predeterminados. Docker: variables de entorno de Compose.
define('SERVERNAME', getenv('DB_HOST') ?: 'localhost');
define('USERNAME', getenv('DB_USER') ?: 'root');
define('PASSWORD', getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '');
define('DBNAME', getenv('DB_NAME') ?: 'sigsm');
define('DB_PORT', (int) (getenv('DB_PORT') ?: 3306));

class Database
{
    private static ?Database $instancia = null;
    private PDO $conexion;

    // Constructor privado para evitar múltiples instancias
    private function __construct()
    {
        $host = SERVERNAME;
        $db = DBNAME;
        $user = USERNAME;
        $pass = PASSWORD;
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;port=" . DB_PORT . ";dbname=$db;charset=$charset";

        try {

            $this->conexion = new PDO(
                $dsn,
                $user,
                $pass
            );

            $this->conexion->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION
            );

            $this->conexion->setAttribute(
                PDO::ATTR_DEFAULT_FETCH_MODE,
                PDO::FETCH_ASSOC
            );

        } catch (PDOException $e) {

            throw new PDOException(
                $e->getMessage(),
                (int) $e->getCode()
            );
        }
    }

    public static function getInstancia(): Database
    {
        if (self::$instancia === null) {
            self::$instancia = new Database();
        }

        return self::$instancia;
    }

    public function getConexion(): PDO
    {
        return $this->conexion;
    }
}


/**
 * Deshace la transacción si quedó abierta.
 *
 * Se usa en los catch de los endpoints que trabajan con
 * transacciones (traslados, por ejemplo). Si la conexión ya se
 * cayó y ni siquiera se puede deshacer, no vuelve a fallar: lo que
 * importa en ese momento es poder responderle algo a la persona.
 */
function revertirTransaccion(?PDO $pdo): void
{
    try {

        if ($pdo instanceof PDO && $pdo->inTransaction()) {

            $pdo->rollBack();
        }

    } catch (Throwable $ignorado) {
    }
}
