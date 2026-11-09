<?php

require_once __DIR__ . "/../config/database.php";

class Subcategoria
{
    private PDO $conexion;

    private ?int $idSubcategoria;
    private string $nombre;
    private string $estado;
    private ?int $idCategoria;

    public function __construct()
    {
        $database = Database::getInstancia();
        $this->conexion = $database->getConexion();

        $this->idSubcategoria = null;
        $this->nombre = '';
        $this->estado = 'activa';
        $this->idCategoria = null;
    }

    // Obtener todas las subcategorías.
    public function obtenerTodas(): array
    {
        $stmt = $this->conexion->query("
            SELECT
                s.id_subcategoria,
                s.nombre,
                s.estado,
                s.id_categoria,
                c.nombre AS categoria,
                c.estado AS estado_categoria
            FROM subcategorias s
            INNER JOIN categorias c
                ON c.id_categoria = s.id_categoria
            ORDER BY c.nombre ASC, s.nombre ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener una subcategoría por su ID.
    public function obtenerPorId(int $idSubcategoria): ?array
    {
        $stmt = $this->conexion->prepare("
            SELECT
                s.id_subcategoria,
                s.nombre,
                s.estado,
                s.id_categoria,
                c.nombre AS categoria,
                c.estado AS estado_categoria
            FROM subcategorias s
            INNER JOIN categorias c
                ON c.id_categoria = s.id_categoria
            WHERE s.id_subcategoria = :id
        ");

        $stmt->execute([
            ':id' => $idSubcategoria
        ]);

        $subcategoria = $stmt->fetch(PDO::FETCH_ASSOC);

        return $subcategoria ?: null;
    }

    // Crear una subcategoría.
    public function crear(
        int $idCategoria,
        string $nombre
    ): int {
        try {
            $this->conexion->beginTransaction();

            // Bloquear la categoría mientras se valida y crea.
            $stmt = $this->conexion->prepare("
                SELECT estado
                FROM categorias
                WHERE id_categoria = :id
                FOR UPDATE
            ");

            $stmt->execute([
                ':id' => $idCategoria
            ]);

            $categoria = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$categoria) {
                throw new DomainException(
                    'La categoría seleccionada no existe.'
                );
            }

            if ($categoria['estado'] !== 'activa') {
                throw new DomainException(
                    'No se pueden agregar subcategorías a una categoría desvinculada.'
                );
            }

            $stmt = $this->conexion->prepare("
                INSERT INTO subcategorias (
                    nombre,
                    estado,
                    id_categoria
                )
                VALUES (
                    :nombre,
                    'activa',
                    :id_categoria
                )
            ");

            $stmt->execute([
                ':nombre' => $nombre,
                ':id_categoria' => $idCategoria
            ]);

            $id = (int) $this->conexion->lastInsertId();

            $this->conexion->commit();

            return $id;

        } catch (Throwable $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            throw $e;
        }
    }

    // Actualizar el nombre y el estado.
    public function actualizar(
        int $idSubcategoria,
        string $nombre,
        string $estado
    ): bool {
        $this->validarEstado($estado);

        try {
            $this->conexion->beginTransaction();

            $stmt = $this->conexion->prepare("
                SELECT
                    s.id_categoria,
                    c.estado AS estado_categoria
                FROM subcategorias s
                INNER JOIN categorias c
                    ON c.id_categoria = s.id_categoria
                WHERE s.id_subcategoria = :id
                FOR UPDATE
            ");

            $stmt->execute([
                ':id' => $idSubcategoria
            ]);

            $subcategoria = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$subcategoria) {
                $this->conexion->rollBack();
                return false;
            }

            if ($subcategoria['estado_categoria'] !== 'activa') {
                throw new DomainException(
                    'No se pueden editar subcategorías de una categoría desvinculada.'
                );
            }

            $stmt = $this->conexion->prepare("
                UPDATE subcategorias
                SET
                    nombre = :nombre,
                    estado = :estado
                WHERE id_subcategoria = :id
            ");

            $stmt->execute([
                ':nombre' => $nombre,
                ':estado' => $estado,
                ':id' => $idSubcategoria
            ]);

            $this->conexion->commit();

            return true;

        } catch (Throwable $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            throw $e;
        }
    }

    // Cambiar el estado de una subcategoría.
    public function cambiarEstado(
        int $idSubcategoria,
        string $estado
    ): bool {
        $this->validarEstado($estado);

        try {
            $this->conexion->beginTransaction();

            $stmt = $this->conexion->prepare("
                SELECT
                    s.id_categoria,
                    c.estado AS estado_categoria
                FROM subcategorias s
                INNER JOIN categorias c
                    ON c.id_categoria = s.id_categoria
                WHERE s.id_subcategoria = :id
                FOR UPDATE
            ");

            $stmt->execute([
                ':id' => $idSubcategoria
            ]);

            $subcategoria = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$subcategoria) {
                $this->conexion->rollBack();
                return false;
            }

            if ($subcategoria['estado_categoria'] !== 'activa') {
                throw new DomainException(
                    'No se puede cambiar el estado de una subcategoría cuya categoría está desvinculada.'
                );
            }

            $stmt = $this->conexion->prepare("
                UPDATE subcategorias
                SET estado = :estado
                WHERE id_subcategoria = :id
            ");

            $stmt->execute([
                ':estado' => $estado,
                ':id' => $idSubcategoria
            ]);

            $this->conexion->commit();

            return true;

        } catch (Throwable $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            throw $e;
        }
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
                'Estado de subcategoría inválido.'
            );
        }
    }
}
