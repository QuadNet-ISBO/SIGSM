<?php

/*
 * ============================================================
 * CONEXIÓN A LA BASE DE DATOS
 * ============================================================
 *
 * Este archivo tiene una sola responsabilidad: entregarle al
 * resto del sistema una conexión a MySQL ya lista para usar,
 * sin que cada archivo tenga que preocuparse por abrirla,
 * configurarla y manejar sus errores por su cuenta.
 *
 * La idea es simple: en vez de que cada modelo (Usuario,
 * Funcionario, etc.) abra su propia conexión con "new PDO(...)",
 * todos le piden la conexión a esta misma clase Database.
 * Asi evitamos tener varias conexiones abiertas al mismo tiempo
 */


// Datos de conexión para el entorno de desarrollo (XAMPP local).

define('SERVERNAME', 'localhost');
define('USERNAME', 'root');
define('PASSWORD', '');
define('DBNAME', 'sigsm');


/**
 * Database
 *
 * Clase que administra la conexión a la base de datos usando
 * el patrón Singleton: solo puede existir UNA instancia de esta
 * clase por cada ejecución del script. La primera vez que alguien
 * la pide, se crea la conexión, todas las veces siguientes, se
 * reutiliza la misma que ya estaba abierta.
 */

class Database
{
    // Aca se guarda la unica instancia que va a existir de esta clase.
    // Empieza en null porque todavía no se creó ninguna conexión.
    private static ?Database $instancia = null;

    // Guarda la conexión PDO con la base de datos.
    private PDO $conexion;

    /**
     * El constructor es privado a propósito: nadie desde afuera
     * puede hacer "new Database()". La unica forma de conseguir
     * una instancia de esta clase es pasando por getInstancia().
     * Eso es justamente lo que hace que el patrón Singleton
     * funcione: se corta de raíz la posibilidad de crear varias.
     */
    private function __construct()
    {

        // Variables necesarias para conectarse a MySQL

        $host = SERVERNAME;
        $db = DBNAME;
        $user = USERNAME;
        $pass = PASSWORD;
        $charset = 'utf8mb4';

        // utf8mb4 en vez de utf8 "a secas" para poder guardar
        // tildes, ñ, emojis, etc. sin problemas.

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

        // Crea la conexión PDO

        try {

            $this->conexion = new PDO(
                $dsn,
                $user,
                $pass
            );

            // Le decimos a PDO que si algo sale mal en una consulta
            // en vez de fallar en silencio o devolver "false" sin explicación, tire una excepcin.
            // Así los try/catch de los archivos que usan la conexión
            // se enteran del error y pueden reaccionar.

            $this->conexion->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION
            );

            // Configuramos el modo de "fetch" por defecto para que
            // los resultados de las consultas vuelvan como array
            // asociativo en vez de como objeto o como array numérico. 

            $this->conexion->setAttribute(
                PDO::ATTR_DEFAULT_FETCH_MODE,
                PDO::FETCH_ASSOC
            );

        } catch (PDOException $e) {

            // Si la conexión falla, relanza la excepción para que quien haya pedido
            // la conexión se entere y pueda mostrar un mensaje
            // adecuado en vez de que la página se rompa sin avisar.

            throw new PDOException(
                $e->getMessage(),
                (int) $e->getCode()
            );
        }
    }

    /**
     * Punto de entrada unico para conseguir la conexión.
     *
     * La primera vez que se llama, no hay ninguna instancia
     * creada todavía (self::$instancia es null), asi que se
     * crea una nueva llamando al constructor privado. De ahi
     * en adelante, cualquier otro archivo que llame a este mismo
     * método va a recibir esa misma instancia ya creada, sin
     * volver a conectarse a MySQL de nuevo.
     */
    
    public static function getInstancia(): Database
    {
        if (self::$instancia === null) {
            self::$instancia = new Database();
        }

        return self::$instancia;
    }

    /**
     * Devuelve el objeto PDO ya conectado, que es lo que
     * realmente se usa en los modelos para hacer prepare()
     * y execute() de las consultas.
     */

    public function getConexion(): PDO
    {
        return $this->conexion;
    }
}
