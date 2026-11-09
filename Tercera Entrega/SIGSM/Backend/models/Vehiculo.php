<?php

require_once __DIR__ . "/../config/database.php";

class Vehiculo
{
    private PDO $conexion;

    private ?int $idVehiculo;
    private string $matricula;
    private string $estadoLaboral;
    private string $estadoOperativo;
    private int $idTipoVehiculo;


    public function __construct()
    {
        $database = Database::getInstancia();
        $this->conexion = $database->getConexion();

        $this->idVehiculo = null;
        $this->matricula = '';
        $this->estadoLaboral = 'activo';
        $this->estadoOperativo = 'libre';
        $this->idTipoVehiculo = 0;
    }


    /*
     * ============================================================
     * OBTENER TODOS LOS VEHÍCULOS
     * ============================================================
     */

    public function obtenerTodos(): array
    {
        $sql = "
            SELECT
                v.id_vehiculo,
                v.matricula,
                v.estado_laboral,
                v.estado_operativo,
                v.id_tipo_vehiculo,
                t.nombre AS tipo
            FROM vehiculos v
            INNER JOIN tipos_vehiculo t
                ON v.id_tipo_vehiculo = t.id_tipo_vehiculo
            ORDER BY v.matricula ASC
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /*
     * ============================================================
     * OBTENER VEHÍCULO POR ID
     * ============================================================
     */

    public function obtenerPorId(int $idVehiculo): ?array
    {
        $sql = "
            SELECT
                v.id_vehiculo,
                v.matricula,
                v.estado_laboral,
                v.estado_operativo,
                v.id_tipo_vehiculo,
                t.nombre AS tipo
            FROM vehiculos v
            INNER JOIN tipos_vehiculo t
                ON v.id_tipo_vehiculo = t.id_tipo_vehiculo
            WHERE v.id_vehiculo = :id_vehiculo
            LIMIT 1
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([
            ':id_vehiculo' => $idVehiculo
        ]);

        $vehiculo = $stmt->fetch(PDO::FETCH_ASSOC);

        return $vehiculo ?: null;
    }


    /*
     * ============================================================
     * CREAR VEHÍCULO
     * ============================================================
     */

    public function crear(
        string $matricula,
        int $idTipoVehiculo
    ): int {

        $sql = "
            INSERT INTO vehiculos (
                matricula,
                estado_laboral,
                estado_operativo,
                id_tipo_vehiculo
            )
            VALUES (
                :matricula,
                'activo',
                'libre',
                :id_tipo_vehiculo
            )
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([
            ':matricula' => $matricula,
            ':id_tipo_vehiculo' => $idTipoVehiculo
        ]);

        return (int) $this->conexion->lastInsertId();
    }


    /**
     * Modifica el vehículo con bloqueo transaccional para evitar que
     * se inactive mientras otro proceso lo asigna a un traslado.
     */
    public function actualizar(
        int $idVehiculo,
        string $matricula,
        int $idTipoVehiculo,
        string $estadoLaboral
    ): bool {
        if (!in_array($estadoLaboral, ['activo', 'inactivo', 'desvinculado'], true)) {
            return false;
        }

        return $this->modificarConControlDeTraslados($idVehiculo, $estadoLaboral, $matricula, $idTipoVehiculo);
    }

    public function cambiarEstado(int $idVehiculo, string $estadoLaboral): bool
    {
        if (!in_array($estadoLaboral, ['activo', 'inactivo', 'desvinculado'], true)) {
            return false;
        }

        return $this->modificarConControlDeTraslados($idVehiculo, $estadoLaboral);
    }

    private function modificarConControlDeTraslados(
        int $idVehiculo,
        string $estadoLaboral,
        ?string $matricula = null,
        ?int $idTipoVehiculo = null
    ): bool {
        $pdo = $this->conexion;
        $pdo->beginTransaction();

        try {
            // Mismo orden de bloqueo que en Traslado::crear(): primero vehículo.
            $stmt = $pdo->prepare('SELECT estado_laboral, id_tipo_vehiculo FROM vehiculos WHERE id_vehiculo = :id FOR UPDATE');
            $stmt->execute([':id' => $idVehiculo]);
            $actual = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($actual === false) {
                $pdo->rollBack();
                return false;
            }

            if ($estadoLaboral !== 'activo' && $actual['estado_laboral'] === 'activo') {
                $stmt = $pdo->prepare("
                    SELECT id_traslado
                    FROM traslados
                    WHERE id_vehiculo = :id
                      AND estado IN ('solicitado', 'aprobado', 'en_curso', 'llegado', 'regreso', 'retornado')
                    LIMIT 1
                ");
                $stmt->execute([':id' => $idVehiculo]);

                if ($stmt->fetchColumn() !== false) {
                    throw new DomainException(
                        'No se puede inactivar o desvincular el vehículo porque está asignado a un traslado activo.'
                    );
                }
            }

            // El tipo de vehículo no puede cambiar mientras participa en un traslado abierto.
            if ($idTipoVehiculo !== null && $idTipoVehiculo !== (int) $actual['id_tipo_vehiculo']) {
                $stmt = $pdo->prepare("SELECT 1 FROM traslados WHERE id_vehiculo = :id AND estado NOT IN ('finalizado', 'cancelado', 'abortado') LIMIT 1");
                $stmt->execute([':id' => $idVehiculo]);
                if ($stmt->fetchColumn() !== false) {
                    throw new DomainException('No se puede cambiar el tipo del vehículo mientras esté asignado a un traslado activo.');
                }
            }

            if ($matricula !== null && $idTipoVehiculo !== null) {
                $stmt = $pdo->prepare('UPDATE vehiculos SET matricula = :matricula, id_tipo_vehiculo = :tipo, estado_laboral = :estado WHERE id_vehiculo = :id');
                $ok = $stmt->execute([
                    ':matricula' => $matricula,
                    ':tipo' => $idTipoVehiculo,
                    ':estado' => $estadoLaboral,
                    ':id' => $idVehiculo
                ]);
            } else {
                $stmt = $pdo->prepare('UPDATE vehiculos SET estado_laboral = :estado WHERE id_vehiculo = :id');
                $ok = $stmt->execute([':estado' => $estadoLaboral, ':id' => $idVehiculo]);
            }

            $pdo->commit();
            return $ok;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }


    /*
     * ============================================================
     * OBTENER TIPOS DE VEHÍCULO
     * ============================================================
     */

    public function obtenerTipos(): array
    {
        $sql = "
            SELECT
                id_tipo_vehiculo,
                nombre
            FROM tipos_vehiculo
            ORDER BY nombre ASC
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}