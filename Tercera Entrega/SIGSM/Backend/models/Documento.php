<?php

require_once __DIR__ . "/../config/database.php";

class Documento
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = Database::getInstancia()->getConexion();
    }

    // Obtener todos los documentos con su categoría y subcategoría.
    public function obtenerTodos(array $filtros = []): array
    {
        $sql = "SELECT
                    d.id_documento,
                    d.titulo,
                    d.ruta,
                    d.estado,
                    d.fecha,
                    d.id_subcategoria,
                    s.nombre AS subcategoria,
                    s.estado AS estado_subcategoria,
                    c.id_categoria,
                    c.nombre AS categoria,
                    c.estado AS estado_categoria
                FROM documentos d
                INNER JOIN subcategorias s
                    ON d.id_subcategoria = s.id_subcategoria
                INNER JOIN categorias c
                    ON s.id_categoria = c.id_categoria
                WHERE 1 = 1";

        $parametros = [];

        if (!empty($filtros['buscar'])) {
            $sql .= " AND d.titulo LIKE :buscar";
            $parametros[':buscar'] =
                '%' . $filtros['buscar'] . '%';
        }

        if (!empty($filtros['id_categoria'])) {
            $sql .= " AND c.id_categoria = :id_categoria";
            $parametros[':id_categoria'] =
                $filtros['id_categoria'];
        }

        if (!empty($filtros['id_subcategoria'])) {
            $sql .= " AND d.id_subcategoria = :id_subcategoria";
            $parametros[':id_subcategoria'] =
                $filtros['id_subcategoria'];
        }

        if (!empty($filtros['estado'])) {
            $sql .= " AND d.estado = :estado";
            $parametros[':estado'] = $filtros['estado'];
        }

        $sql .= " ORDER BY d.fecha DESC, d.id_documento DESC";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute($parametros);

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener un documento específico.
    public function obtenerPorId(int $idDocumento): array|false
    {
        $sql = "SELECT
                    d.id_documento,
                    d.titulo,
                    d.ruta,
                    d.estado,
                    d.fecha,
                    d.id_subcategoria,
                    s.nombre AS subcategoria,
                    s.estado AS estado_subcategoria,
                    c.id_categoria,
                    c.nombre AS categoria,
                    c.estado AS estado_categoria
                FROM documentos d
                INNER JOIN subcategorias s
                    ON d.id_subcategoria = s.id_subcategoria
                INNER JOIN categorias c
                    ON s.id_categoria = c.id_categoria
                WHERE d.id_documento = :id_documento";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([
            ':id_documento' => $idDocumento
        ]);

        return $consulta->fetch(PDO::FETCH_ASSOC);
    }

    // Crear un documento en la base de datos.
    public function crear(
        string $titulo,
        string $ruta,
        int $idSubcategoria
    ): int|false {
        if (!$this->subcategoriaDisponible($idSubcategoria)) {
            return false;
        }

        $sql = "INSERT INTO documentos
                    (titulo, ruta, estado, id_subcategoria)
                VALUES
                    (:titulo, :ruta, 'activo', :id_subcategoria)";

        $consulta = $this->conexion->prepare($sql);

        $resultado = $consulta->execute([
            ':titulo' => $titulo,
            ':ruta' => $ruta,
            ':id_subcategoria' => $idSubcategoria
        ]);

        if (!$resultado) {
            return false;
        }

        return (int) $this->conexion->lastInsertId();
    }

    // Actualizar el título, la ruta y la subcategoría.
    public function actualizar(
        int $idDocumento,
        string $titulo,
        string $ruta,
        int $idSubcategoria
    ): bool {
        if (!$this->subcategoriaDisponible($idSubcategoria)) {
            return false;
        }

        $sql = "UPDATE documentos
                SET titulo = :titulo,
                    ruta = :ruta,
                    id_subcategoria = :id_subcategoria
                WHERE id_documento = :id_documento";

        $consulta = $this->conexion->prepare($sql);

        return $consulta->execute([
            ':titulo' => $titulo,
            ':ruta' => $ruta,
            ':id_subcategoria' => $idSubcategoria,
            ':id_documento' => $idDocumento
        ]);
    }

    // Baja lógica irreversible: conservar registro y PDF para consulta interna.
    public function darDeBaja(int $idDocumento): bool
    {
        $consulta = $this->conexion->prepare(
            "UPDATE documentos SET estado = 'desvinculado'
             WHERE id_documento = :id_documento AND estado = 'activo'"
        );
        $consulta->execute([':id_documento' => $idDocumento]);
        return $consulta->rowCount() === 1;
    }

    // Comprobar que la subcategoría y su categoría estén activas.
    public function subcategoriaDisponible(
        int $idSubcategoria
    ): bool {
        $sql = "SELECT COUNT(*)
                FROM subcategorias s
                INNER JOIN categorias c
                    ON s.id_categoria = c.id_categoria
                WHERE s.id_subcategoria = :id_subcategoria
                  AND s.estado = 'activa'
                  AND c.estado = 'activa'";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([
            ':id_subcategoria' => $idSubcategoria
        ]);

        return (int) $consulta->fetchColumn() > 0;
    }
}
