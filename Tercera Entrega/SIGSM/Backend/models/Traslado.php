<?php

require_once __DIR__ . "/../config/database.php";

class Traslado
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = Database::getInstancia()->getConexion();
    }

    /* ============================================================
     * LISTAR TRASLADOS
     * ============================================================ */
    public function obtenerTodos(): array
    {
        $stmt = $this->conexion->query("
        SELECT

            /*
             * TRASLADO
             */

            t.id_traslado,

            t.origen,
            t.destino,
            t.prioridad,
            t.estado,

            t.fecha_creacion,
            t.fecha_hora_partida,
            t.fecha_hora_destino,

            t.hora_salida,
            t.hora_llegada,
            t.hora_regreso,
            t.hora_retorno,
            t.hora_finalizado,

            t.observaciones,


            /*
             * TIPO DE TRASLADO
             */

            CASE

                WHEN p.id_traslado IS NOT NULL
                    THEN 'paciente'

                WHEN o.id_traslado IS NOT NULL
                    THEN 'otro'

                ELSE 'desconocido'

            END AS tipo_traslado,


            /*
             * VEHÍCULO
             */

            v.id_vehiculo,

            v.matricula,

            v.estado_operativo
                AS estado_operativo_vehiculo,

            tv.id_tipo_vehiculo,

            tv.nombre
                AS tipo_vehiculo,


            /*
             * PACIENTE
             */

            p.ci
                AS ci_paciente,

            p.nombre
                AS nombre_paciente,

            p.telefono
                AS telefono_paciente,

            p.quien_recibe,

            p.puerta,

            p.piso,

            p.sala,


            /*
             * OTRO
             */

            o.descripcion
                AS descripcion_otro


        FROM traslados t


        /*
         * VEHÍCULO
         */

        INNER JOIN vehiculos v

            ON v.id_vehiculo =
               t.id_vehiculo


        /*
         * TIPO DE VEHÍCULO
         */

        INNER JOIN tipos_vehiculo tv

            ON tv.id_tipo_vehiculo =
               v.id_tipo_vehiculo


        /*
         * PACIENTE
         */

        LEFT JOIN paciente p

            ON p.id_traslado =
               t.id_traslado


        /*
         * OTRO
         */

        LEFT JOIN otro o

            ON o.id_traslado =
               t.id_traslado


        /*
         * ORDEN
         */

        ORDER BY

            t.fecha_creacion DESC,

            t.id_traslado DESC
    ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ============================================================
     * OBTENER TRASLADO Y FUNCIONARIOS
     * ============================================================ */
    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->conexion->prepare("
        SELECT

            t.*,

            v.matricula,

            tv.id_tipo_vehiculo,
            tv.nombre AS tipo_vehiculo,

            p.ci AS ci_paciente,
            p.nombre AS nombre_paciente,
            p.telefono AS telefono_paciente,
            p.quien_recibe,
            p.puerta,
            p.piso,
            p.sala,

            o.descripcion AS descripcion_otro

        FROM traslados t

        INNER JOIN vehiculos v
            ON v.id_vehiculo = t.id_vehiculo

        INNER JOIN tipos_vehiculo tv
            ON tv.id_tipo_vehiculo = v.id_tipo_vehiculo

        LEFT JOIN paciente p
            ON p.id_traslado = t.id_traslado

        LEFT JOIN otro o
            ON o.id_traslado = t.id_traslado

        WHERE t.id_traslado = :id
    ");
        $stmt->execute([':id' => $id]);
        $traslado = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$traslado) {
            return null;
        }

        $stmt = $this->conexion->prepare("
        SELECT

            tf.id_funcionario,
            tf.rol_en_traslado,

            f.ci,
            f.nombre,
            f.apellido,
            f.telefono

        FROM traslado_funcionario tf

        INNER JOIN funcionarios f
            ON f.id_funcionario = tf.id_funcionario

        WHERE tf.id_traslado = :id
    ");
        $stmt->execute([':id' => $id]);
        $traslado['funcionarios'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $traslado['tipo_traslado'] = !empty($traslado['ci_paciente']) ? 'paciente' : 'otro';

        if (!empty($traslado['hora_finalizado'])) {
            $traslado['estado_operativo'] = 'finalizado';
        } elseif (!empty($traslado['hora_retorno'])) {
            $traslado['estado_operativo'] = 'retornado';
        } elseif (!empty($traslado['hora_regreso'])) {
            $traslado['estado_operativo'] = 'en regreso';
        } elseif (!empty($traslado['hora_llegada'])) {
            $traslado['estado_operativo'] = 'llegado';
        } elseif (!empty($traslado['hora_salida'])) {
            $traslado['estado_operativo'] = 'en curso';
        } else {
            $traslado['estado_operativo'] = 'pendiente';
        }

        return $traslado;
    }

    /*
     * ============================================================
     * ACTUALIZAR DATOS GENERALES
     * ============================================================
     */

    public function existe(int $idTraslado): bool
    {
        $stmt = $this->conexion->prepare("
            SELECT id_traslado FROM traslados WHERE id_traslado = :id
        ");
        $stmt->execute([':id' => $idTraslado]);
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function actualizar(int $idTraslado, array $datos): void
    {
        $this->conexion->beginTransaction();
        try {
        $bloqueo = $this->conexion->prepare('SELECT estado FROM traslados WHERE id_traslado = :id FOR UPDATE');
        $bloqueo->execute([':id' => $idTraslado]);
        $actual = $bloqueo->fetch(PDO::FETCH_ASSOC);
        if (!$actual || !in_array($actual['estado'], ['solicitado', 'aprobado'], true)) {
            throw new DomainException('Solo se puede modificar un traslado antes de su salida.');
        }
        $partida = new DateTimeImmutable($datos['fecha_hora_partida']);
        $stmt = $this->conexion->prepare('SELECT f.horario, tf.rol_en_traslado FROM traslado_funcionario tf INNER JOIN funcionarios f ON f.id_funcionario = tf.id_funcionario WHERE tf.id_traslado = :id FOR UPDATE');
        $stmt->execute([':id' => $idTraslado]);
        $asignados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($asignados) !== 2) {
            throw new DomainException('El traslado no tiene los dos funcionarios asignados.');
        }
        foreach ($asignados as $asignado) {
            if (!self::funcionarioEnTurno($asignado['horario'], $partida)) {
                throw new DomainException('El horario nuevo queda fuera del turno de un funcionario asignado.');
            }
        }
        $campos = [
            'origen = :origen',
            'destino = :destino',
            'fecha_hora_partida = :fecha_hora_partida',
            'fecha_hora_destino = :fecha_hora_destino',
            'prioridad = :prioridad',
            'observaciones = :observaciones'
        ];

        $parametros = [
            ':id' => $idTraslado,
            ':origen' => $datos['origen'],
            ':destino' => $datos['destino'],
            ':fecha_hora_partida' => $datos['fecha_hora_partida'],
            ':fecha_hora_destino' => $datos['fecha_hora_destino'],
            ':prioridad' => $datos['prioridad'],
            ':observaciones' => $datos['observaciones']
        ];

        // Los estados se modifican exclusivamente desde la API de seguimiento.

        $sql = 'UPDATE traslados SET ' . implode(', ', $campos)
             . ' WHERE id_traslado = :id';

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($parametros);
        $this->conexion->commit();
        } catch (Throwable $e) {
            if ($this->conexion->inTransaction()) $this->conexion->rollBack();
            throw $e;
        }
    }

    /*
     * ============================================================
     * RECURSOS DISPONIBLES
     * ============================================================
     */

    public function obtenerVehiculosDisponibles(): array
    {
        $stmt = $this->conexion->query("
            SELECT
                v.id_vehiculo,
                v.matricula,
                v.estado_laboral,
                v.estado_operativo,
                v.id_tipo_vehiculo,
                tv.nombre AS tipo_vehiculo
            FROM vehiculos v
            INNER JOIN tipos_vehiculo tv
                ON tv.id_tipo_vehiculo = v.id_tipo_vehiculo
            WHERE v.estado_laboral = 'activo'
              AND v.estado_operativo = 'libre'
            ORDER BY tv.nombre ASC, v.matricula ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Comprueba el turno diario para la hora programada, incluso si cruza medianoche. */
    public static function funcionarioEnTurno(?string $horario, DateTimeInterface $partida): bool
    {
        // Los funcionarios guardan nombres de turno; también se admiten rangos HH:MM-HH:MM.
        $turnos = [
            'matutino' => '06:00-14:00',
            'diurno' => '14:00-22:00',
            'vespertino' => '22:00-06:00',
        ];
        $clave = is_string($horario) ? strtolower(trim($horario)) : '';
        $horario = $turnos[$clave] ?? $horario;
        if (!is_string($horario) || !preg_match('/^\s*([01]\d|2[0-3]):([0-5]\d)\s*-\s*([01]\d|2[0-3]):([0-5]\d)\s*$/', $horario, $m)) {
            return false; // Sin turno válido no se permite asignar.
        }
        $inicio = ((int)$m[1]) * 60 + (int)$m[2];
        $fin = ((int)$m[3]) * 60 + (int)$m[4];
        $hora = (int)$partida->format('H') * 60 + (int)$partida->format('i');
        if ($inicio === $fin) return false;
        return $inicio < $fin
            ? ($hora >= $inicio && $hora < $fin)
            : ($hora >= $inicio || $hora < $fin);
    }

    public function obtenerFuncionariosDisponibles(): array
    {
        $stmt = $this->conexion->query("
            SELECT
                id_funcionario, ci, nombre, apellido, telefono,
                estado_laboral, estado_operativo, puede_ser_chofer,
                puede_ser_enfermero, horario
            FROM funcionarios
            WHERE estado_laboral = 'activo'
              AND estado_operativo = 'libre'
            ORDER BY apellido ASC, nombre ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /* ============================================================
     * AVANZAR ESTADO (TRANSACCIÓN Y BLOQUEO)
     * ============================================================ */
    public function avanzarEstado(int $idTraslado, string $estadoEsperado): array
    {
        $pdo = $this->conexion;
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT id_traslado, id_vehiculo, estado FROM traslados WHERE id_traslado = :id FOR UPDATE");
            $stmt->execute([':id' => $idTraslado]);
            $traslado = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$traslado) {
                throw new Exception('El traslado no existe.');
            }

            $estadoActual = $traslado['estado'];
            // Comprobación dentro de la transacción, con la fila bloqueada.
            if ($estadoActual !== $estadoEsperado) {
                throw new DomainException(
                    'Otra persona ya modificó este traslado. Se va a actualizar la lista.',
                    409
                );
            }
            $transiciones = [
                'solicitado' => ['aprobado', null],
                'aprobado' => ['en_curso', 'hora_salida'],
                'en_curso' => ['llegado', 'hora_llegada'],
                'llegado' => ['regreso', 'hora_regreso'],
                'regreso' => ['retornado', 'hora_retorno'],
                'retornado' => ['finalizado', 'hora_finalizado']
            ];
            $errores = [
                'finalizado' => 'El traslado ya está finalizado.',
                'cancelado' => 'El traslado fue cancelado.',
                'abortado' => 'El traslado fue abortado.'
            ];
            if (!isset($transiciones[$estadoActual])) {
                throw new Exception($errores[$estadoActual] ?? 'El estado actual del traslado no es válido.');
            }
            [$siguienteEstado, $campoHora] = $transiciones[$estadoActual];
            // El nombre de columna proviene exclusivamente del mapa fijo anterior.
            $sql = 'UPDATE traslados SET estado = :estado';
            if ($campoHora !== null) {
                $sql .= ', ' . $campoHora . ' = NOW()';
            }
            $sql .= ' WHERE id_traslado = :id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':estado' => $siguienteEstado, ':id' => $idTraslado]);

            if ($siguienteEstado === 'finalizado') {
                $this->liberarRecursos($idTraslado, $traslado['id_vehiculo']);
            }
            $pdo->commit();
            return ['estado_anterior' => $estadoActual, 'estado_nuevo' => $siguienteEstado];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    /* ============================================================
     * CANCELAR O ABORTAR (TRANSACCIÓN Y BLOQUEO)
     * ============================================================ */
    public function cancelar(int $idTraslado): string
    {
        $pdo = $this->conexion;
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT id_traslado, id_vehiculo, estado, hora_salida FROM traslados WHERE id_traslado = :id FOR UPDATE");
            $stmt->execute([':id' => $idTraslado]);
            $traslado = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$traslado) {
                throw new Exception('El traslado no existe.');
            }
            if (in_array($traslado['estado'], ['finalizado', 'cancelado', 'abortado'], true)) {
                throw new Exception('El traslado ya terminó o fue cancelado anteriormente.');
            }
            $nuevoEstado = empty($traslado['hora_salida']) ? 'cancelado' : 'abortado';
            $stmt = $pdo->prepare('UPDATE traslados SET estado = :estado WHERE id_traslado = :id');
            $stmt->execute([':estado' => $nuevoEstado, ':id' => $idTraslado]);
            $this->liberarRecursos($idTraslado, $traslado['id_vehiculo']);
            $pdo->commit();
            return $nuevoEstado;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    /* ============================================================
     * LIBERAR RECURSOS EN LA TRANSACCIÓN ACTIVA
     * ============================================================ */
    private function liberarRecursos(int $idTraslado, $idVehiculo): void
    {
        $stmt = $this->conexion->prepare("UPDATE vehiculos SET estado_operativo = 'libre' WHERE id_vehiculo = :id");
        $stmt->execute([':id' => $idVehiculo]);

        $stmt = $this->conexion->prepare("UPDATE funcionarios f
            INNER JOIN traslado_funcionario tf ON tf.id_funcionario = f.id_funcionario
            SET f.estado_operativo = 'libre' WHERE tf.id_traslado = :id");
        $stmt->execute([':id' => $idTraslado]);
    }
    /* ============================================================
     * CREAR TRASLADO Y RESERVAR RECURSOS (TRANSACCIÓN ATÓMICA)
     * ============================================================ */
    public function crear(array $datos): int
    {
        $tipoTraslado = $datos['tipoTraslado'];
        $idVehiculo = $datos['idVehiculo'];
        $idChofer = $datos['idChofer'];
        $idEnfermero = $datos['idEnfermero'];
        $origen = $datos['origen'];
        $destino = $datos['destino'];
        $fechaPartida = $datos['fechaPartida'];
        $fechaDestino = $datos['fechaDestino'];
        $prioridad = $datos['prioridad'];
        $observaciones = $datos['observaciones'];
        $ciPaciente = $datos['ciPaciente'];
        $nombrePaciente = $datos['nombrePaciente'];
        $telefonoPaciente = $datos['telefonoPaciente'];
        $quienRecibe = $datos['quienRecibe'];
        $puerta = $datos['puerta'];
        $piso = $datos['piso'];
        $sala = $datos['sala'];
        $descripcionOtro = $datos['descripcionOtro'];

        try {
    $this->conexion->beginTransaction();


    /*
     * ========================================================
     * VEHÍCULO
     * ========================================================
     */

    $stmt = $this->conexion->prepare("
        SELECT
            v.id_vehiculo,
            v.matricula,
            v.estado_laboral,
            v.estado_operativo,
            v.id_tipo_vehiculo,
            tv.nombre AS tipo_vehiculo

        FROM vehiculos v

        INNER JOIN tipos_vehiculo tv
            ON tv.id_tipo_vehiculo = v.id_tipo_vehiculo

        WHERE v.id_vehiculo = :id_vehiculo

        FOR UPDATE
    ");

    $stmt->execute([
        ':id_vehiculo' => $idVehiculo
    ]);

    $vehiculo = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$vehiculo) {

        throw new Exception(
            'El vehículo seleccionado no existe.'
        );
    }


    if ($vehiculo['estado_laboral'] !== 'activo') {

        throw new Exception(
            'El vehículo seleccionado no está activo.'
        );
    }


    if ($vehiculo['estado_operativo'] !== 'libre') {

        throw new Exception(
            'El vehículo seleccionado ya está ocupado.'
        );
    }


    /*
     * Pacientes solamente utilizan ambulancias.
     */

    if (
        $tipoTraslado === 'paciente' &&
        strtolower(trim($vehiculo['tipo_vehiculo'])) !== 'ambulancia'
    ) {

        throw new Exception(
            'Los traslados de pacientes solamente pueden utilizar ambulancias.'
        );
    }


    /*
     * ========================================================
     * CHOFER
     * ========================================================
     */

    $stmt = $this->conexion->prepare("
        SELECT
            id_funcionario,
            nombre,
            apellido,
            estado_laboral,
            estado_operativo,
            puede_ser_chofer,
            horario

        FROM funcionarios

        WHERE id_funcionario = :id_funcionario

        FOR UPDATE
    ");

    $stmt->execute([
        ':id_funcionario' => $idChofer
    ]);

    $chofer = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$chofer) {

        throw new Exception(
            'El chofer seleccionado no existe.'
        );
    }


    if ($chofer['estado_laboral'] !== 'activo') {

        throw new Exception(
            'El chofer seleccionado no está activo.'
        );
    }


    if ($chofer['estado_operativo'] !== 'libre') {

        throw new Exception(
            'El chofer seleccionado ya está ocupado.'
        );
    }


    if ((int)$chofer['puede_ser_chofer'] !== 1) {

        throw new Exception(
            'El funcionario seleccionado no puede desempeñarse como chofer.'
        );
    }


    if (!self::funcionarioEnTurno($chofer['horario'], $fechaPartida)) {
        throw new DomainException('El chofer no está de turno a la hora de partida programada.');
    }

    /*
     * ========================================================
     * ENFERMERO
     * ========================================================
     */

    $stmt = $this->conexion->prepare("
        SELECT
            id_funcionario,
            nombre,
            apellido,
            estado_laboral,
            estado_operativo,
            puede_ser_enfermero,
            horario

        FROM funcionarios

        WHERE id_funcionario = :id_funcionario

        FOR UPDATE
    ");

    $stmt->execute([
        ':id_funcionario' => $idEnfermero
    ]);

    $enfermero = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$enfermero) {

        throw new Exception(
            'El enfermero seleccionado no existe.'
        );
    }


    if ($enfermero['estado_laboral'] !== 'activo') {

        throw new Exception(
            'El enfermero seleccionado no está activo.'
        );
    }


    if ($enfermero['estado_operativo'] !== 'libre') {

        throw new Exception(
            'El enfermero seleccionado ya está ocupado.'
        );
    }


    if ((int)$enfermero['puede_ser_enfermero'] !== 1) {

        throw new Exception(
            'El funcionario seleccionado no puede desempeñarse como enfermero.'
        );
    }


    if (!self::funcionarioEnTurno($enfermero['horario'], $fechaPartida)) {
        throw new DomainException('El enfermero no está de turno a la hora de partida programada.');
    }

    /*
     * ========================================================
     * CREAR TRASLADO
     * ========================================================
     */

    $stmt = $this->conexion->prepare("
        INSERT INTO traslados (
            id_vehiculo,
            origen,
            destino,
            prioridad,
            estado,
            fecha_hora_partida,
            fecha_hora_destino,
            observaciones
        )
        VALUES (
            :id_vehiculo,
            :origen,
            :destino,
            :prioridad,
            'solicitado',
            :fecha_hora_partida,
            :fecha_hora_destino,
            :observaciones
        )
    ");

    $stmt->execute([
        ':id_vehiculo' => $idVehiculo,
        ':origen' => $origen,
        ':destino' => $destino,
        ':prioridad' => $prioridad,

        ':fecha_hora_partida' => $fechaPartida->format('Y-m-d H:i:s'),
        ':fecha_hora_destino' =>
            $fechaDestino->format('Y-m-d H:i:s'),

        ':observaciones' =>
            $observaciones !== ''
                ? $observaciones
                : null
    ]);


    $idTraslado = (int)$this->conexion->lastInsertId();


    /*
     * ========================================================
     * GUARDAR PACIENTE
     * ========================================================
     */

    if ($tipoTraslado === 'paciente') {

        $stmt = $this->conexion->prepare("
            INSERT INTO paciente (
                id_traslado,
                ci,
                nombre,
                telefono,
                quien_recibe,
                puerta,
                piso,
                sala
            )
            VALUES (
                :id_traslado,
                :ci,
                :nombre,
                :telefono,
                :quien_recibe,
                :puerta,
                :piso,
                :sala
            )
        ");

        $stmt->execute([
            ':id_traslado' => $idTraslado,
            ':ci' => $ciPaciente,
            ':nombre' => $nombrePaciente,

            ':telefono' =>
                $telefonoPaciente !== ''
                    ? $telefonoPaciente
                    : null,

            ':quien_recibe' =>
                $quienRecibe !== ''
                    ? $quienRecibe
                    : null,

            ':puerta' =>
                $puerta !== ''
                    ? $puerta
                    : null,

            ':piso' =>
                $piso !== ''
                    ? $piso
                    : null,

            ':sala' =>
                $sala !== ''
                    ? $sala
                    : null
        ]);
    }


    /*
     * ========================================================
     * GUARDAR OTRO
     * ========================================================
     */

    if ($tipoTraslado === 'otro') {

        $stmt = $this->conexion->prepare("
            INSERT INTO otro (
                id_traslado,
                descripcion
            )
            VALUES (
                :id_traslado,
                :descripcion
            )
        ");

        $stmt->execute([
            ':id_traslado' => $idTraslado,
            ':descripcion' => $descripcionOtro
        ]);
    }


    /*
     * ========================================================
     * ASIGNAR CHOFER
     * ========================================================
     */

    $stmt = $this->conexion->prepare("
        INSERT INTO traslado_funcionario (
            id_traslado,
            id_funcionario,
            rol_en_traslado
        )
        VALUES (
            :id_traslado,
            :id_funcionario,
            'chofer'
        )
    ");

    $stmt->execute([
        ':id_traslado' => $idTraslado,
        ':id_funcionario' => $idChofer
    ]);


    /*
     * ========================================================
     * ASIGNAR ENFERMERO
     * ========================================================
     */

    $stmt = $this->conexion->prepare("
        INSERT INTO traslado_funcionario (
            id_traslado,
            id_funcionario,
            rol_en_traslado
        )
        VALUES (
            :id_traslado,
            :id_funcionario,
            'enfermero'
        )
    ");

    $stmt->execute([
        ':id_traslado' => $idTraslado,
        ':id_funcionario' => $idEnfermero
    ]);


    /*
     * ========================================================
     * OCUPAR VEHÍCULO
     * ========================================================
     */

    $stmt = $this->conexion->prepare("
        UPDATE vehiculos

        SET estado_operativo = 'ocupado'

        WHERE id_vehiculo = :id_vehiculo
          AND estado_operativo = 'libre'
    ");

    $stmt->execute([
        ':id_vehiculo' => $idVehiculo
    ]);


    if ($stmt->rowCount() !== 1) {

        throw new Exception(
            'No se pudo reservar el vehículo seleccionado.'
        );
    }


    /*
     * ========================================================
     * OCUPAR CHOFER
     * ========================================================
     */

    $stmt = $this->conexion->prepare("
        UPDATE funcionarios

        SET estado_operativo = 'ocupado'

        WHERE id_funcionario = :id_funcionario
          AND estado_operativo = 'libre'
    ");

    $stmt->execute([
        ':id_funcionario' => $idChofer
    ]);


    if ($stmt->rowCount() !== 1) {

        throw new Exception(
            'No se pudo reservar el chofer seleccionado.'
        );
    }


    /*
     * ========================================================
     * OCUPAR ENFERMERO
     * ========================================================
     */

    $stmt = $this->conexion->prepare("
        UPDATE funcionarios

        SET estado_operativo = 'ocupado'

        WHERE id_funcionario = :id_funcionario
          AND estado_operativo = 'libre'
    ");

    $stmt->execute([
        ':id_funcionario' => $idEnfermero
    ]);


    if ($stmt->rowCount() !== 1) {

        throw new Exception(
            'No se pudo reservar el enfermero seleccionado.'
        );
    }


    /*
     * ========================================================
     * CONFIRMAR
     * ========================================================
     */

    $this->conexion->commit();

            return $idTraslado;
        } catch (Throwable $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $e;
        }
    }

}
