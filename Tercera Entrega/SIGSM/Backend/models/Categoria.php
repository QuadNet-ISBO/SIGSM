<?php

require_once __DIR__ . "/../config/database.php";

class Categoria
{
    private PDO $conexion;

    private ?int $idCategoria;
    private string $nombre;
    private string $estado;

    public function __construct()
    {
        $database = Database::getInstancia();
        $this->conexion = $database->getConexion();

        $this->idCategoria = null;
        $this->nombre = '';
        $this->estado = 'activa';
    }

    // Obtener todas las categorías.
    public function obtenerTodas(): array
    {
        $stmt = $this->conexion->query("
            SELECT
                id_categoria,
                nombre,
                estado
            FROM categorias
            ORDER BY nombre ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener una categoría por su ID.
    public function obtenerPorId(int $idCategoria): ?array
    {
        $stmt = $this->conexion->prepare("
            SELECT
                id_categoria,
                nombre,
                estado
            FROM categorias
            WHERE id_categoria = :id
        ");

        $stmt->execute([
            ':id' => $idCategoria
        ]);

        $categoria = $stmt->fetch(PDO::FETCH_ASSOC);

        return $categoria ?: null;
    }

    // Crear una categoría.
    public function crear(string $nombre): int
    {
        $stmt = $this->conexion->prepare("
            INSERT INTO categorias (
                nombre,
                estado
            )
            VALUES (
                :nombre,
                'activa'
            )
        ");

        $stmt->execute([
            ':nombre' => $nombre
        ]);

        return (int) $this->conexion->lastInsertId();
    }

    // Actualizar el nombre y el estado de una categoría.
    public function actualizar(
        int $idCategoria,
        string $nombre,
        string $estado
    ): bool {
        $this->validarEstado($estado);

        try {
            $this->conexion->beginTransaction();

            if (!$this->existe($idCategoria)) {
                $this->conexion->rollBack();
                return false;
            }

            $stmt = $this->conexion->prepare("
                UPDATE categorias
                SET
                    nombre = :nombre,
                    estado = :estado
                WHERE id_categoria = :id
            ");

            $stmt->execute([
                ':nombre' => $nombre,
                ':estado' => $estado,
                ':id' => $idCategoria
            ]);

            // Si se desvincula la categoría,
            // también se desvinculan sus subcategorías.
            if ($estado === 'desvinculada') {
                $this->desvincularSubcategorias($idCategoria);
            }

            $this->conexion->commit();

            return true;

        } catch (Throwable $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            throw $e;
        }
    }

    // Cambiar el estado de una categoría.
    public function cambiarEstado(
        int $idCategoria,
        string $estado
    ): bool {
        $this->validarEstado($estado);

        try {
            $this->conexion->beginTransaction();

            if (!$this->existe($idCategoria)) {
                $this->conexion->rollBack();
                return false;
            }

            $stmt = $this->conexion->prepare("
                UPDATE categorias
                SET estado = :estado
                WHERE id_categoria = :id
            ");

            $stmt->execute([
                ':estado' => $estado,
                ':id' => $idCategoria
            ]);

            // Si se desvincula la categoría,
            // también se desvinculan sus subcategorías.
            if ($estado === 'desvinculada') {
                $this->desvincularSubcategorias($idCategoria);
            }

            $this->conexion->commit();

            return true;

        } catch (Throwable $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            throw $e;
        }
    }

    // Comprobar si una categoría existe.
    private function existe(int $idCategoria): bool
    {
        $stmt = $this->conexion->prepare("
            SELECT id_categoria
            FROM categorias
            WHERE id_categoria = :id
        ");

        $stmt->execute([
            ':id' => $idCategoria
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    // Desvincular las subcategorías de una categoría.
    private function desvincularSubcategorias(
        int $idCategoria
    ): void {
        $stmt = $this->conexion->prepare("
            UPDATE subcategorias
            SET estado = 'desvinculada'
            WHERE id_categoria = :id
        ");

        $stmt->execute([
            ':id' => $idCategoria
        ]);
    }

    // Validar los estados permitidos.
    private function validarEstado(string $estado): void
    {
        if (!in_array(
            $estado,
            ['activa', 'desvinculada'],
            true
        )) {
            throw new InvalidArgumentException(
                'Estado de categoría inválido.'
            );
        }
    }
}
