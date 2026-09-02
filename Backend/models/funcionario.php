<?php

require_once __DIR__ . "/../config/database.php";

/**
 * Funcionario
 *
 * Este modelo representa al personal operativo: choferes y
 * enfermeros que salen efectivamente en los traslados.
 *
 * Al igual que Usuario, se conecta solo a la base pidiéndole
 * la conexión a Database::getInstancia(), así que alcanza con
 * "new Funcionario()" para tener todo listo.
 */
class Funcionario
{
    private PDO $conexion;

    /* Estos atributos representan las columnas de la tabla "usuarios". 
    La clase los usa como referencia de qué datos maneja un usuario.*/

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
     * Obtiene la conexión mediante la clase Database, del mismo
     * modo que lo hace el modelo Usuario, para no depender de
     * que se la pasen desde afuera.
     * 
     * Luego se inicializan los atributos con datos vacios o predetermiandos.
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
     *
     * Trae el listado completo, ordenado alfabéticamente por
     * apellido y nombre.
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

        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Devuelve el resultado de la consulta SQL en un array asociativo.
    }


    /*
     * ============================================================
     * BUSCAR FUNCIONARIO POR ID
     * ============================================================
     */

    public function buscarPorId(int $id): ?array  // Recibe un id y devuelve un array o null si no encuentra nada
    {

    // Consulta para buscar a un funcionario por su id

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

        $stmt = $this->conexion->prepare($sql);     // Prepara la cnsulta

        $stmt->execute([            // Ejecuta la consulta y remplaza :id por el id recibido
            ':id' => $id
        ]);

        $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);        // Obtiene el primer registro encontrado y lo convierte en un array asociativo

        return $funcionario ?: null;            // Devuelve los datos, si no encontro al funcionario no devuelve nada.
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
     *
     * Recibe un array asociativo con todos los datos del funcionario.
     * Devuelve un entero que es el ID que MySQL le asigno. 
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

        // Le pasa los datos

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

        // Devuelve el id creado

        return (int) $this->conexion->lastInsertId();
    }


    /*
     * ============================================================
     * ACTUALIZAR FUNCIONARIO
     * ============================================================
     *
     * Recibe un entero que es el id del funcionario y un array con los nuevos datos.
     */

    public function actualizar(
        int $id,
        array $datos
    ): bool {               // Devuelve true/false, indicando si la ejecucion fue exitosa

        $sql = "
            UPDATE funcionarios
            SET
                ci = :ci,
                nombre = :nombre,
                apellido = :apellido,
                telefono = :telefono,
                estado_laboral = :estado_laboral,
                puede_ser_chofer = :puede_ser_chofer,
                puede_ser_enfermero = :puede_ser_enfermero,
                horario = :horario
            WHERE id_funcionario = :id
        ";

        $stmt = $this->conexion->prepare($sql);

        return $stmt->execute([
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
    }


    /*
     * ============================================================
     * CAMBIAR ESTADO LABORAL
     * ============================================================
     * 
     * Modifica el estado laboral de un funcionario a diferencia de la funcion actualizar
     * 
     * Recibe el id y el nuevo estado y devuelve true si fue exitosa
     */

    public function cambiarEstado(
        int $id,
        string $estado
    ): bool {

        $sql = "
            UPDATE funcionarios
            SET estado_laboral = :estado
            WHERE id_funcionario = :id
        ";

        $stmt = $this->conexion->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':estado' => $estado
        ]);
    }
}
