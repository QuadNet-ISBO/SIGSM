<?php

require_once __DIR__ . "/../config/database.php";

/**
 * Usuario
 *
 * Este modelo representa a las personas que pueden ENTRAR AL
 * SISTEMA.
 *
 * La clase se conecta sola a la base de datos pidiéndole la
 * conexión a Database::getInstancia(), asi que no hace falta
 * pasarle nada por parámetro al crearla: alcanza con hacer
 * "new Usuario()" y ya queda lista para usarse.
 *
 * Aca adentro estan todas las consultas relacionadas a usuarios:
 * buscarlos, listarlos, crearlos, editarlos, cambiarles el estado
 * y validar su contraseña al momento de loguearse.
 */
class Usuario
{
    private PDO $conexion;

    /* Estos atributos representan las columnas de la tabla "usuarios". 
    La clase los usa como referencia de qué datos maneja un usuario.*/

    private ?int $idUsuario;
    private string $ci;
    private string $nombre;
    private string $apellido;
    private ?string $telefono;
    private string $email;
    private string $user;
    private string $password;
    private string $estadoLaboral;
    private int $idRol;

    /**
     * Al crear un Usuario nuevo, lo primero que se hace es
     * conseguir la conexión a la base a traves del Singleton
     * Database.
     * 
     * Luego se inicializan los atributos con datos vacios o predetermiandos.
     */
    public function __construct()
    {
        $database = Database::getInstancia();
        $this->conexion = $database->getConexion();

        $this->idUsuario = null;
        $this->ci = '';
        $this->nombre = '';
        $this->apellido = '';
        $this->telefono = null;
        $this->email = '';
        $this->user = '';
        $this->password = '';
        $this->estadoLaboral = 'activo';
        $this->idRol = 0;
    }


    /*
     * ============================================================
     * BUSCAR USUARIO POR NOMBRE DE USUARIO
     * ============================================================
     *
     * Este es el metodo que usa el login: recibe el "user" que
     * la persona escribio en el formulario y trae toda su fila
     * de la base. Si no existe ningún usuario con ese
     * nombre, devuelve null en vez de un array vacío o un error.
     */

    public function buscarPorUser(string $user): ?array
    {
        $sql = "
            SELECT
                u.id_usuario,
                u.ci,
                u.nombre,
                u.apellido,
                u.telefono,
                u.email,
                u.user,
                u.password,
                u.estado_laboral,
                u.id_rol,
                r.nombre AS rol
            FROM usuarios u
            INNER JOIN roles r
                ON u.id_rol = r.id_rol
            WHERE u.user = :user
            LIMIT 1
        ";

        $stmt = $this->conexion->prepare($sql); // Prepara la consulta

        $stmt->execute([                        // Se remplaza el parametro por el valor recibido
            ':user' => $user
        ]);

        $usuario = $stmt->fetch();              // Toma el registro encontrado y lo convierte en un array

        return $usuario ?: null;                // Si no encuentra nada devuelve null
    }


    /*
     * ============================================================
     * OBTENER TODOS LOS USUARIOS
     * ============================================================
     *
     * Trae el listado completo de usuarios ordenados por
     * apellido y nombre.
     */

    public function obtenerTodos(): array
    {
        $sql = "
            SELECT
                u.id_usuario,
                u.ci,
                u.nombre,
                u.apellido,
                u.telefono,
                u.email,
                u.user,
                u.estado_laboral,
                u.id_rol,
                r.nombre AS rol
            FROM usuarios u
            INNER JOIN roles r
                ON u.id_rol = r.id_rol
            ORDER BY u.apellido, u.nombre
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute();

        return $stmt->fetchAll();
    }


    /*
     * ============================================================
     * OBTENER UN USUARIO POR SU ID
     * ============================================================
     */

    public function obtenerPorId(int $idUsuario): ?array
    {
        $sql = "
            SELECT
                u.id_usuario,
                u.ci,
                u.nombre,
                u.apellido,
                u.telefono,
                u.email,
                u.user,
                u.estado_laboral,
                u.id_rol,
                r.nombre AS rol
            FROM usuarios u
            INNER JOIN roles r
                ON u.id_rol = r.id_rol
            WHERE u.id_usuario = :id_usuario
            LIMIT 1
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([
            ':id_usuario' => $idUsuario
        ]);

        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }


    /*
     * ============================================================
     * CREAR UN NUEVO USUARIO
     * ============================================================
     *
     * Importante: aca NUNCA se guarda la contraseña tal cual la
     * escribio la persona. Antes de insertarla, se pasa por
     * password_hash(), que la convierte en un hash.
     */

    public function crear(              // Recibe todos los datos necesarios
        string $ci,
        string $nombre,
        string $apellido,
        ?string $telefono,
        string $email,
        string $user,
        string $password,
        int $idRol
    ): bool {                           // Boolean, para confirmar si la creación fue exitosa o no.

        $passwordHash = password_hash(  // Toma la contraseña ingresada y genera un hash
            $password,
            PASSWORD_DEFAULT
        );

        $sql = "                        
            INSERT INTO usuarios (
                ci,
                nombre,
                apellido,
                telefono,
                email,
                user,
                password,
                estado_laboral,
                id_rol
            )
            VALUES (
                :ci,
                :nombre,
                :apellido,
                :telefono,
                :email,
                :user,
                :password,
                'activo',
                :id_rol
            )
        ";

        $stmt = $this->conexion->prepare($sql);     // Prepara la consulta

        return $stmt->execute([                     // Envia los valores y ejecuta el insert, luego recibe true/false
            ':ci' => $ci,
            ':nombre' => $nombre,
            ':apellido' => $apellido,
            ':telefono' => $telefono,
            ':email' => $email,
            ':user' => $user,
            ':password' => $passwordHash,
            ':id_rol' => $idRol
        ]);
    }


    /*
     * ============================================================
     * ACTUALIZAR UN USUARIO
     * ============================================================
     *
     * La contraseña aca es opcional: si al editar un usuario
     * no se quiere cambiar la contraseña (lo mas comun), el
     * parametro $password llega como null o vacio, y en ese
     * caso NO se toca esa columna en el UPDATE.
     * 
     * Si en cambio llega una contraseña nueva, se hashea igual
     * que en crear() y ahi si se actualiza.
     */

    public function actualizar(                 // Recibe todos los datos necesarios
        int $idUsuario,
        string $ci,
        string $nombre,
        string $apellido,
        ?string $telefono,
        string $email,
        string $user,
        int $idRol,
        string $estadoLaboral,
        ?string $password = null
    ): bool {

        $estadosPermitidos = [                  // Define los estados permitidos
            'activo',
            'inactivo',
            'desvinculado'
        ];

        // Valida que el estado recibido sea permitido

        if (!in_array(
            $estadoLaboral,
            $estadosPermitidos,
            true
        )) {
            return false;
        }

        // Si la contraseña existe y no esta vacia, la hashea nuevamente y actualiza incluyendo la contraseña

        if ($password !== null && $password !== '') {

            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $sql = "
                UPDATE usuarios
                SET
                    ci = :ci,
                    nombre = :nombre,
                    apellido = :apellido,
                    telefono = :telefono,
                    email = :email,
                    user = :user,
                    password = :password,
                    id_rol = :id_rol,
                    estado_laboral = :estado_laboral
                WHERE id_usuario = :id_usuario
            ";

            $parametros = [
                ':ci' => $ci,
                ':nombre' => $nombre,
                ':apellido' => $apellido,
                ':telefono' => $telefono,
                ':email' => $email,
                ':user' => $user,
                ':password' => $passwordHash,
                ':id_rol' => $idRol,
                ':estado_laboral' => $estadoLaboral,
                ':id_usuario' => $idUsuario
            ];

            // Si no se proporciona una contraseña, se actualiza pero sin la contraseña

        } else {

            $sql = "
                UPDATE usuarios
                SET
                    ci = :ci,
                    nombre = :nombre,
                    apellido = :apellido,
                    telefono = :telefono,
                    email = :email,
                    user = :user,
                    id_rol = :id_rol,
                    estado_laboral = :estado_laboral
                WHERE id_usuario = :id_usuario
            ";

            $parametros = [
                ':ci' => $ci,
                ':nombre' => $nombre,
                ':apellido' => $apellido,
                ':telefono' => $telefono,
                ':email' => $email,
                ':user' => $user,
                ':id_rol' => $idRol,
                ':estado_laboral' => $estadoLaboral,
                ':id_usuario' => $idUsuario
            ];
        }

        // Prepara la consulta, la ejecuta y recibe true/false

        $stmt = $this->conexion->prepare($sql);

        return $stmt->execute($parametros);
    }


    /*
     * ============================================================
     * CAMBIAR EL ESTADO LABORAL DE UN USUARIO
     * ============================================================
     *
     * Metodo para cuando lo unico que se quiere hacer es, 
     * por ejemplo, desvincular a alguien, sin tener que pasar por el update 
     * completo de arriba con todos los demas campos.
     */

    public function cambiarEstado(          // Recibe id del usuario y indica a que estado laboral hay que cambiarlo
        int $idUsuario,
        string $estadoLaboral
    ): bool {

        $estadosPermitidos = [              // Define los estados permitidos
            'activo',
            'inactivo',
            'desvinculado'
        ];

        if (!in_array(                      // Comprueba que el estado recibido sea permitido 
            $estadoLaboral,
            $estadosPermitidos,
            true
        )) {
            return false;
        }

        // Prepara el UPDATE

        $sql = "
            UPDATE usuarios
            SET estado_laboral = :estado_laboral
            WHERE id_usuario = :id_usuario
        ";

        $stmt = $this->conexion->prepare($sql);     // Prepara la consulta

        return $stmt->execute([                     // Envia los valores, y recibe true/false
            ':estado_laboral' => $estadoLaboral,
            ':id_usuario' => $idUsuario
        ]);
    }


    /*
     * ============================================================
     * VERIFICAR CONTRASEÑA
     * ============================================================
     *
     * Compara la contraseña que la persona escribio en el login
     * (texto plano) contra el hash que está guardado en la base.
     * 
     * password_verify() es la contraparte de password_hash(): se
     * encarga de hacer esa comparación de forma segura.
     */

    public function verificarPassword(      // Recibe los datos necesarios
        string $password,
        string $passwordHash
    ): bool {

        return password_verify(             // Compara ambos valores y si la contraseña es correcta devuelve true sino false
            $password,
            $passwordHash
        );
    }
}
