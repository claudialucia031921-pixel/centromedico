<?php

require_once __DIR__ . '/conexionBaseDatos.php';

class Paciente
{
    private $identificacion;
    private $nombres;
    private $apellidos;
    private $fechaNacimiento;
    private $sexo;
    private $idUsuario;

    public function crearPaciente(
        $identificacion,
        $nombres,
        $apellidos,
        $fechaNacimiento,
        $sexo,
        $idUsuario = null
    ) {
        $this->setIdentificacion($identificacion);
        $this->setNombres($nombres);
        $this->setApellidos($apellidos);
        $this->setFechaNacimiento($fechaNacimiento);
        $this->setSexo($sexo);
        $this->setIdUsuario($idUsuario);
    }

    public function setIdentificacion($identificacion)
    {
        $this->identificacion = $identificacion;
    }

    public function getIdentificacion()
    {
        return $this->identificacion;
    }

    public function setNombres($nombres)
    {
        $this->nombres = $nombres;
    }

    public function getNombres()
    {
        return $this->nombres;
    }

    public function setApellidos($apellidos)
    {
        $this->apellidos = $apellidos;
    }

    public function getApellidos()
    {
        return $this->apellidos;
    }

    public function setFechaNacimiento($fechaNacimiento)
    {
        $this->fechaNacimiento = $fechaNacimiento;
    }

    public function getFechaNacimiento()
    {
        return $this->fechaNacimiento;
    }

    public function setSexo($sexo)
    {
        $this->sexo = $sexo;
    }

    public function getSexo()
    {
        return $this->sexo;
    }

    public function setIdUsuario($idUsuario)
    {
        $this->idUsuario = $idUsuario;
    }

    public function getIdUsuario()
    {
        return $this->idUsuario;
    }

    private function ejecutar($sql, $tipos = '', $parametros = [], $consulta = false)
    {
        $conexion = Conectarse();
        $sentencia = null;

        try {
            $conexion->set_charset('utf8mb4');
            $sentencia = $conexion->prepare($sql);

            if (!$sentencia) {
                throw new RuntimeException('No se pudo preparar la operación.');
            }

            if ($tipos !== '') {
                $sentencia->bind_param($tipos, ...$parametros);
            }

            if (!$sentencia->execute()) {
                throw new RuntimeException('No se pudo completar la operación.');
            }

            if ($consulta) {
                return $sentencia->get_result();
            }

            return true;
        } finally {
            if ($sentencia) {
                $sentencia->close();
            }

            $conexion->close();
        }
    }

    public function agregarPaciente()
    {
        return $this->ejecutar(
            'INSERT INTO pacientes
                (pacIdentificacion, pacNombres, pacApellidos,
                 pacFechaNacimiento, pacSexo, usuarios_idUsuario)
             VALUES (?, ?, ?, ?, ?, ?)',
            'sssssi',
            [
                $this->identificacion,
                $this->nombres,
                $this->apellidos,
                $this->fechaNacimiento,
                $this->sexo,
                $this->idUsuario
            ]
        );
    }

    public function consultarPaciente($identificacion)
    {
        return $this->ejecutar(
            'SELECT * FROM pacientes WHERE pacIdentificacion = ?',
            's',
            [$identificacion],
            true
        );
    }

    public function consultarPacientePorId($idPaciente)
    {
        return $this->ejecutar(
            'SELECT * FROM pacientes WHERE idPaciente = ?',
            'i',
            [$idPaciente],
            true
        );
    }

    public function actualizarPaciente($idPaciente)
    {
        return $this->ejecutar(
            'UPDATE pacientes
             SET pacIdentificacion = ?, pacNombres = ?,
                 pacApellidos = ?, pacFechaNacimiento = ?,
                 pacSexo = ?, usuarios_idUsuario = ?
             WHERE idPaciente = ?',
            'sssssii',
            [
                $this->identificacion,
                $this->nombres,
                $this->apellidos,
                $this->fechaNacimiento,
                $this->sexo,
                $this->idUsuario,
                $idPaciente
            ]
        );
    }

    public function listarPacientes()
    {
        return $this->ejecutar(
            'SELECT p.*, u.usuLogin
             FROM pacientes AS p
             LEFT JOIN usuarios AS u
                ON p.usuarios_idUsuario = u.idUsuario
             ORDER BY p.idPaciente',
            '',
            [],
            true
        );
    }

    public function eliminarPaciente($idPaciente)
    {
        return $this->ejecutar(
            'DELETE FROM pacientes WHERE idPaciente = ?',
            'i',
            [$idPaciente]
        );
    }
}