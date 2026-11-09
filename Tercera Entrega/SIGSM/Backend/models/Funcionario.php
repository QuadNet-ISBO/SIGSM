<?php

require_once __DIR__ . "/../config/database.php";

class Funcionario
{
    private PDO $conexion;

    private ?int $idFuncionario;
    private string $ci;
    private string $nombre;
    private string $apellido;
    private ?string $telefono;
    private string $estadoLaboral;
    private string $estadoOperativo;
    private bool $puedeSerChofer;
    private bool $puedeSerEnfermero;
    private ?string $horario;


    /**
     * Constructor de la clase.
     *
     * Obtiene la conexión mediante la clase Database.
     */
    public function __construct()
    {
        $database = Database::getInstancia();
        $this->conexion = $database->getConexion();

        $this->idFuncionario = null;
        $this->ci = '';
        $this->nombre = '';
        $this->apellido = '';
        $this->telefono = null;
        $this->estadoLaboral = 'activo';
        $this->estadoOperativo = 'libre';
        $this->puedeSerChofer = false;
        $this->puedeSerEnfermero = false;
        $this->horario = null;
    }


    /*
     * ============================================================
     * OBTENER TODOS LOS FUNCIONARIOS
     * ============================================================
     */

    public function obtenerTodos(): array
    {
        $sql = "
            SELECT
                id_funcionario,
                ci,
                nombre,
                apellido,
                telefono,
                estado_laboral,
                estado_operativo,
                puede_ser_chofer,
                puede_ser_enfermero,
                horario
            FROM funcionarios
            ORDER BY apellido ASC, nombre ASC
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /*
     * ============================================================
     * BUSCAR FUNCIONARIO POR ID
     * ============================================================
     */

    public function buscarPorId(int $id): ?array
    {
        $sql = "
            SELECT
                id_funcionario,
                ci,
                nombre,
                apellido,
                telefono,
                estado_laboral,
                estado_operativo,
                puede_ser_chofer,
                puede_ser_enfermero,
                horario
            FROM funcionarios
            WHERE id_funcionario = :id
            LIMIT 1
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([
            ':id' => $id
        ]);

        $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);

        return $funcionario ?: null;
    }


    /*
     * ============================================================
     * BUSCAR FUNCIONARIO POR CI
     * ============================================================
     */

    public function buscarPorCi(string $ci): ?array
    {
        $sql = "
            SELECT
                id_funcionario,
                ci,
                nombre,
                apellido,
                telefono,
                estado_laboral,
                estado_operativo,
                puede_ser_chofer,
                puede_ser_enfermero,
                horario
            FROM funcionarios
            WHERE ci = :ci
            LIMIT 1
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([
            ':ci' => $ci
        ]);

        $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);

        return $funcionario ?: null;
    }


    /*
     * ============================================================
     * CREAR FUNCIONARIO
     * ============================================================
     */

    public function crear(array $datos): int
    {
        $sql = "
            INSERT INTO funcionarios (
                ci,
                nombre,
                apellido,
                telefono,
                estado_laboral,
                estado_operativo,
                puede_ser_chofer,
                puede_ser_enfermero,
                horario
            )
            VALUES (
                :ci,
                :nombre,
                :apellido,
                :telefono,
                :estado_laboral,
                :estado_operativo,
                :puede_ser_chofer,
                :puede_ser_enfermero,
                :horario
            )
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([
            ':ci' => $datos['ci'],
            ':nombre' => $datos['nombre'],
            ':apellido' => $datos['apellido'],
            ':telefono' => $datos['telefono'],
            ':estado_laboral' => $datos['estado_laboral'],
            ':estado_operativo' => $datos['estado_operativo'],
            ':puede_ser_chofer' => $datos['puede_ser_chofer'],
            ':puede_ser_enfermero' => $datos['puede_ser_enfermero'],
            ':horario' => $datos['horario']
        ]);

        return (int) $this->conexion->lastInsertId();
    }


    /*
     * ============================================================
     * ACTUALIZAR FUNCIONARIO
     * ============================================================
     */

    /**
     * Impide desactivar a un funcionario asignado a un traslado abierto.
     * El bloqueo del funcionario coordina esta operación con la creación de traslados.
     */
    private function validarCambioLaboral(int $id, string $nuevoEstado): void
    {
        if ($nuevoEstado === 'activo') {
            return;
        }

        $stmt = $this->conexion->prepare("
            SELECT 1
            FROM traslado_funcionario tf
            INNER JOIN traslados t ON t.id_traslado = tf.id_traslado
            WHERE tf.id_funcionario = :id
              AND t.estado NOT IN ('finalizado', 'cancelado', 'abortado')
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);

        if ($stmt->fetchColumn() !== false) {
            throw new DomainException(
                'No se puede desactivar o desvincular al funcionario porque está asignado a un traslado activo.'
            );
        }
    }

    private function bloquearFuncionario(int $id): array
    {
        $stmt = $this->conexion->prepare("
            SELECT id_funcionario, estado_laboral, puede_ser_chofer, puede_ser_enfermero, horario
            FROM funcionarios
            WHERE id_funcionario = :id
            FOR UPDATE
        ");
        $stmt->execute([':id' => $id]);
        $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$funcionario) {
            throw new DomainException('El funcionario no existe.');
        }
        return $funcionario;
    }

    public function actualizar(int $id, array $datos): bool
    {
        $this->conexion->beginTransaction();
        try {
            $actual = $this->bloquearFuncionario($id);
            if ($actual['estado_laboral'] === 'activo' && $datos['estado_laboral'] !== 'activo') {
                $this->validarCambioLaboral($id, $datos['estado_laboral']);
            }

            // No permitir cambiar el turno mientras exista una asignación abierta.
            // Se compara contra el valor almacenado bajo bloqueo FOR UPDATE;
            // así una edición de otros datos no se rechaza innecesariamente.
            if ((string) ($actual['horario'] ?? '') !== (string) $datos['horario']) {
                $stmtHorario = $this->conexion->prepare("
                    SELECT 1
                    FROM traslado_funcionario tf
                    INNER JOIN traslados t ON t.id_traslado = tf.id_traslado
                    WHERE tf.id_funcionario = :id
                      AND t.estado NOT IN ('finalizado', 'cancelado', 'abortado')
                    LIMIT 1
                ");
                $stmtHorario->execute([':id' => $id]);
                if ($stmtHorario->fetchColumn() !== false) {
                    throw new DomainException(
                        'No se puede modificar el horario mientras el funcionario esté asignado a un traslado activo.'
                    );
                }
            }

            // No retirar una capacidad que el funcionario usa en un traslado abierto.
            $quitaChofer = (int) $actual['puede_ser_chofer'] === 1 && (int) $datos['puede_ser_chofer'] === 0;
            $quitaEnfermero = (int) $actual['puede_ser_enfermero'] === 1 && (int) $datos['puede_ser_enfermero'] === 0;
            if ($quitaChofer || $quitaEnfermero) {
                $roles = [];
                if ($quitaChofer) $roles[] = 'chofer';
                if ($quitaEnfermero) $roles[] = 'enfermero';
                $placeholders = implode(', ', array_fill(0, count($roles), '?'));
                $stmt = $this->conexion->prepare("
                    SELECT 1 FROM traslado_funcionario tf
                    INNER JOIN traslados t ON t.id_traslado = tf.id_traslado
                    WHERE tf.id_funcionario = ?
                      AND tf.rol_en_traslado IN ($placeholders)
                      AND t.estado NOT IN ('finalizado', 'cancelado', 'abortado')
                    LIMIT 1
                ");
                $stmt->execute(array_merge([$id], $roles));
                if ($stmt->fetchColumn() !== false) {
                    throw new DomainException('No se puede quitar la habilitación de chofer o enfermero mientras el funcionario cumpla ese rol en un traslado activo.');
                }
            }

            $sql = "
                UPDATE funcionarios SET
                    ci = :ci, nombre = :nombre, apellido = :apellido,
                    telefono = :telefono, estado_laboral = :estado_laboral,
                    puede_ser_chofer = :puede_ser_chofer,
                    puede_ser_enfermero = :puede_ser_enfermero, horario = :horario
                WHERE id_funcionario = :id
            ";
            $stmt = $this->conexion->prepare($sql);
            $ok = $stmt->execute([
                ':id' => $id,
                ':ci' => $datos['ci'],
                ':nombre' => $datos['nombre'],
                ':apellido' => $datos['apellido'],
                ':telefono' => $datos['telefono'],
                ':estado_laboral' => $datos['estado_laboral'],
                ':puede_ser_chofer' => $datos['puede_ser_chofer'],
                ':puede_ser_enfermero' => $datos['puede_ser_enfermero'],
                ':horario' => $datos['horario']
            ]);
            $this->conexion->commit();
            return $ok;
        } catch (Throwable $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $e;
        }
    }

    public function cambiarEstado(int $id, string $estado): bool
    {
        $this->conexion->beginTransaction();
        try {
            $actual = $this->bloquearFuncionario($id);
            if ($actual['estado_laboral'] === 'activo' && $estado !== 'activo') {
                $this->validarCambioLaboral($id, $estado);
            }
            $stmt = $this->conexion->prepare("
                UPDATE funcionarios SET estado_laboral = :estado
                WHERE id_funcionario = :id
            ");
            $ok = $stmt->execute([':id' => $id, ':estado' => $estado]);
            $this->conexion->commit();
            return $ok;
        } catch (Throwable $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $e;
        }
    }
}
