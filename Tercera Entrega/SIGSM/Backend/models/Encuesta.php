<?php

require_once __DIR__ . "/../config/database.php";

class Encuesta
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = Database::getInstancia()->getConexion();
    }

    public function obtenerTodas(): array
    {
        $sql = "SELECT
                    e.id_encuesta,
                    e.titulo,
                    e.estado,
                    e.id_subcategoria,
                    s.nombre AS subcategoria,
                    c.nombre AS categoria
                FROM encuestas e
                INNER JOIN subcategorias s
                    ON e.id_subcategoria = s.id_subcategoria
                INNER JOIN categorias c
                    ON s.id_categoria = c.id_categoria
                ORDER BY e.id_encuesta DESC";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId(int $idEncuesta): array|false
    {
        $sql = "SELECT
                    e.id_encuesta,
                    e.titulo,
                    e.estado,
                    e.id_subcategoria,
                    s.nombre AS subcategoria,
                    c.nombre AS categoria
                FROM encuestas e
                INNER JOIN subcategorias s
                    ON e.id_subcategoria = s.id_subcategoria
                INNER JOIN categorias c
                    ON s.id_categoria = c.id_categoria
                WHERE e.id_encuesta = :id_encuesta";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':id_encuesta' => $idEncuesta]);

        return $consulta->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerPreguntas(int $idEncuesta): array
    {
        $sql = "SELECT id_pregunta, tipo, texto, orden
                FROM preguntas
                WHERE id_encuesta = :id_encuesta
                ORDER BY orden ASC, id_pregunta ASC";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':id_encuesta' => $idEncuesta]);

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerOpciones(int $idPregunta): array
    {
        $sql = "SELECT id_opcion, texto_opcion, orden
                FROM opciones_pregunta
                WHERE id_pregunta = :id_pregunta
                ORDER BY orden ASC";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':id_pregunta' => $idPregunta]);

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crear(string $titulo, int $idSubcategoria): int|false
    {
        if (!$this->subcategoriaDisponible($idSubcategoria)) {
            return false;
        }

        $sql = "SELECT id_encuesta
                FROM encuestas
                WHERE id_subcategoria = :id_subcategoria
                  AND estado = 'activa'
                LIMIT 1";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':id_subcategoria' => $idSubcategoria]);

        if ($consulta->fetch()) {
            return false;
        }

        $sql = "INSERT INTO encuestas (titulo, estado, id_subcategoria)
                VALUES (:titulo, 'activa', :id_subcategoria)";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([
            ':titulo' => $titulo,
            ':id_subcategoria' => $idSubcategoria
        ]);

        return (int) $this->conexion->lastInsertId();
    }

    public function crearCompleta(
        string $titulo,
        int $idSubcategoria,
        array $preguntas
    ): int|false {
        $this->conexion->beginTransaction();

        try {
            $idEncuesta = $this->crear($titulo, $idSubcategoria);

            if ($idEncuesta === false) {
                $this->conexion->rollBack();
                return false;
            }

            $this->guardarPreguntas($idEncuesta, $preguntas);

            $this->conexion->commit();

            return $idEncuesta;
        } catch (Throwable $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Reemplaza una encuesta dentro de una sola transacción.
     * Conserva la encuesta anterior y sus respuestas históricas.
     */
    public function reemplazarCompleta(
        int $idEncuestaAnterior,
        string $titulo,
        int $idSubcategoria,
        array $preguntas
    ): int|false {
        if ($this->conexion->inTransaction()) {
            throw new RuntimeException(
                'Ya existe una transacción activa.'
            );
        }

        $this->conexion->beginTransaction();

        try {
            // Bloquea la encuesta anterior para evitar modificaciones
            // simultáneas durante el reemplazo.
            $sql = "SELECT id_encuesta, id_subcategoria
                    FROM encuestas
                    WHERE id_encuesta = :id_encuesta
                      AND estado = 'activa'
                    FOR UPDATE";

            $consulta = $this->conexion->prepare($sql);
            $consulta->execute([
                ':id_encuesta' => $idEncuestaAnterior
            ]);

            $encuestaAnterior = $consulta->fetch(PDO::FETCH_ASSOC);

            if (!$encuestaAnterior) {
                $this->conexion->rollBack();
                return false;
            }

            // La nueva encuesta debe pertenecer a la misma subcategoría.
            if ((int) $encuestaAnterior['id_subcategoria'] !== $idSubcategoria) {
                $this->conexion->rollBack();
                return false;
            }

            if (!$this->subcategoriaDisponible($idSubcategoria)) {
                $this->conexion->rollBack();
                return false;
            }

            // Retira la anterior sin borrar preguntas ni respuestas.
            $sql = "UPDATE encuestas
                    SET estado = 'retirada'
                    WHERE id_encuesta = :id_encuesta
                      AND estado = 'activa'";

            $consulta = $this->conexion->prepare($sql);
            $consulta->execute([
                ':id_encuesta' => $idEncuestaAnterior
            ]);

            if ($consulta->rowCount() !== 1) {
                $this->conexion->rollBack();
                return false;
            }

            // Crea la nueva encuesta y sus preguntas/opciones
            // dentro de la misma transacción.
            $idNuevaEncuesta = $this->crear(
                $titulo,
                $idSubcategoria
            );

            if ($idNuevaEncuesta === false) {
                throw new RuntimeException(
                    'No se pudo crear la nueva encuesta.'
                );
            }

            $this->guardarPreguntas(
                $idNuevaEncuesta,
                $preguntas
            );

            $this->conexion->commit();

            return $idNuevaEncuesta;
        } catch (Throwable $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Guarda preguntas y opciones de una encuesta existente.
     * Debe ejecutarse dentro de la transacción correspondiente.
     */
    private function guardarPreguntas(
        int $idEncuesta,
        array $preguntas
    ): void {
        $sqlPregunta = "INSERT INTO preguntas
                            (tipo, texto, id_encuesta, orden)
                        VALUES
                            (:tipo, :texto, :id_encuesta, :orden)";

        $consultaPregunta = $this->conexion->prepare($sqlPregunta);

        $sqlOpcion = "INSERT INTO opciones_pregunta
                          (texto_opcion, orden, id_pregunta)
                      VALUES
                          (:texto_opcion, :orden, :id_pregunta)";

        $consultaOpcion = $this->conexion->prepare($sqlOpcion);

        foreach ($preguntas as $indicePregunta => $pregunta) {
            $consultaPregunta->execute([
                ':tipo' => $pregunta['tipo'],
                ':texto' => $pregunta['texto'],
                ':id_encuesta' => $idEncuesta,
                ':orden' => $indicePregunta + 1
            ]);

            $idPregunta = (int) $this->conexion->lastInsertId();

            foreach ($pregunta['opciones'] as $indiceOpcion => $textoOpcion) {
                $consultaOpcion->execute([
                    ':texto_opcion' => $textoOpcion,
                    ':orden' => $indiceOpcion + 1,
                    ':id_pregunta' => $idPregunta
                ]);
            }
        }
    }

    public function actualizar(int $idEncuesta, string $titulo): bool
    {
        $sql = "UPDATE encuestas
                SET titulo = :titulo
                WHERE id_encuesta = :id_encuesta
                  AND estado = 'activa'";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            ':titulo' => $titulo,
            ':id_encuesta' => $idEncuesta
        ]);

        return $consulta->rowCount() > 0;
    }

    public function retirar(int $idEncuesta): bool
    {
        $sql = "UPDATE encuestas
                SET estado = 'retirada'
                WHERE id_encuesta = :id_encuesta
                  AND estado = 'activa'";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':id_encuesta' => $idEncuesta]);

        return $consulta->rowCount() > 0;
    }

    public function subcategoriaDisponible(int $idSubcategoria): bool
    {
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

    public function obtenerActivaPorSubcategoria(int $idSubcategoria): array|false
    {
        $sql = "SELECT
                    id_encuesta,
                    titulo,
                    id_subcategoria
                FROM encuestas
                WHERE id_subcategoria = :id_subcategoria
                AND estado = 'activa'
                LIMIT 1";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            ':id_subcategoria' => $idSubcategoria
        ]);

        return $consulta->fetch(PDO::FETCH_ASSOC);
    }
}