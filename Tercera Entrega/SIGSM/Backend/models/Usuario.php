<?php

require_once __DIR__ . "/../config/database.php";

class Usuario
{
    public const ULTIMO_ADMINISTRADOR = 1;

    private PDO $conexion;

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


    /**
     * Busca un usuario por su nombre de usuario.
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

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([
            ':user' => $user
        ]);

        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }


    /**
     * Obtiene todos los usuarios del sistema.
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


    /**
     * Obtiene un usuario por su ID.
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


    /**
     * Crea un nuevo usuario.
     */
    public function crear(
        string $ci,
        string $nombre,
        string $apellido,
        ?string $telefono,
        string $email,
        string $user,
        string $password,
        int $idRol
    ): bool {

        $passwordHash = password_hash(
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

        $stmt = $this->conexion->prepare($sql);

        return $stmt->execute([
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


    /**
     * Actualiza los datos de un usuario.
     */
    public function actualizar(
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

        $estadosPermitidos = [
            'activo',
            'inactivo',
            'desvinculado'
        ];

        if (!in_array(
            $estadoLaboral,
            $estadosPermitidos,
            true
        )) {
            return false;
        }


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


        // Consultar el usuario actual evita transacciones innecesarias
        // cuando se edita a un operador o se mantiene el acceso administrativo.
        $actual = $this->obtenerPorId($idUsuario);
        if ($actual === null) {
            return false;
        }

        $esAdminActivo = $actual['rol'] === 'Administrador'
            && $actual['estado_laboral'] === 'activo';

        $pierdeAcceso = $estadoLaboral !== 'activo'
            || $idRol !== $this->idRolAdministrador();

        if (!$esAdminActivo || !$pierdeAcceso) {
            $stmt = $this->conexion->prepare($sql);
            return $stmt->execute($parametros);
        }

        return $this->ejecutarProtegido($idUsuario, $sql, $parametros);
    }


    /**
     * Cambia el estado laboral de un usuario.
     */
    public function cambiarEstado(
        int $idUsuario,
        string $estadoLaboral
    ): bool {

        $estadosPermitidos = [
            'activo',
            'inactivo',
            'desvinculado'
        ];

        if (!in_array(
            $estadoLaboral,
            $estadosPermitidos,
            true
        )) {
            return false;
        }

        $sql = "
            UPDATE usuarios
            SET estado_laboral = :estado_laboral
            WHERE id_usuario = :id_usuario
        ";

        if ($estadoLaboral === 'activo') {
            $stmt = $this->conexion->prepare($sql);
            return $stmt->execute([
                ':estado_laboral' => $estadoLaboral,
                ':id_usuario' => $idUsuario
            ]);
        }

        $actual = $this->obtenerPorId($idUsuario);
        if ($actual === null) {
            return false;
        }

        $parametros = [
            ':estado_laboral' => $estadoLaboral,
            ':id_usuario' => $idUsuario
        ];

        if ($actual['rol'] !== 'Administrador'
            || $actual['estado_laboral'] !== 'activo') {
            $stmt = $this->conexion->prepare($sql);
            return $stmt->execute($parametros);
        }

        return $this->ejecutarProtegido($idUsuario, $sql, $parametros);
    }


    /**
     * Ejecuta la modificación con bloqueo para proteger al último administrador.
     */
    private function ejecutarProtegido(
        int $idUsuario,
        string $sql,
        array $parametros
    ): bool {
        $this->conexion->beginTransaction();

        try {
            $stmt = $this->conexion->query("
                SELECT u.id_usuario
                FROM usuarios u
                INNER JOIN roles r ON r.id_rol = u.id_rol
                WHERE r.nombre = 'Administrador'
                  AND u.estado_laboral = 'activo'
                FOR UPDATE
            ");

            $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

            if (in_array($idUsuario, $ids, true) && count($ids) <= 1) {
                throw new DomainException(
                    'Debe quedar al menos un administrador activo en el sistema.',
                    self::ULTIMO_ADMINISTRADOR
                );
            }

            $stmt = $this->conexion->prepare($sql);
            $resultado = $stmt->execute($parametros);
            $this->conexion->commit();
            return $resultado;
        } catch (Throwable $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $e;
        }
    }

    private function idRolAdministrador(): ?int
    {
        $stmt = $this->conexion->query("
            SELECT id_rol FROM roles
            WHERE nombre = 'Administrador' LIMIT 1
        ");
        $id = $stmt->fetchColumn();
        return $id === false ? null : (int) $id;
    }


    /**
     * Verifica una contraseña contra el hash almacenado.
     */
    public function verificarPassword(
        string $password,
        string $passwordHash
    ): bool {

        return password_verify(
            $password,
            $passwordHash
        );
    }
}