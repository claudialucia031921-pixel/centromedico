<?php

require_once __DIR__ . '/conexionBaseDatos.php';

class Medico
{
    // Definición de atributos
    private $identificacion;
    private $nombres;
    private $apellidos;
    private $especialidad;
    private $telefono;
    private $correo;
    private $Conexion;

    // Método para asignar los datos del médico
    public function crearMedico(
        $identificacion,
        $nombres,
        $apellidos,
        $especialidad,
        $telefono,
        $correo
    ) {
        $this->identificacion = $identificacion;
        $this->nombres = $nombres;
        $this->apellidos = $apellidos;
        $this->especialidad = $especialidad;
        $this->telefono = $telefono;
        $this->correo = $correo;
    }

    // Métodos modificadores y consultores

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

    public function setEspecialidad($especialidad)
    {
        $this->especialidad = $especialidad;
    }

    public function getEspecialidad()
    {
        return $this->especialidad;
    }

    public function setTelefono($telefono)
    {
        $this->telefono = $telefono;
    }

    public function getTelefono()
    {
        return $this->telefono;
    }

    public function setCorreo($correo)
    {
        $this->correo = $correo;
    }

    public function getCorreo()
    {
        return $this->correo;
    }

    // Agregar un médico
    // Recibe el ID de un usuario que ya exista en la tabla usuarios.
    public function agregarMedico($idUsuario)
    {
        $this->Conexion = Conectarse();

        try {
            $sql = "INSERT INTO medicos (
                        medIdentificacion,
                        medNombres,
                        medApellidos,
                        medEspecialidad,
                        medTelefono,
                        medCorreo,
                        usuarios_idUsuario
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)";

            $consulta = $this->Conexion->prepare($sql);

            if ($consulta === false) {
                throw new RuntimeException($this->Conexion->error);
            }

            try {
                $consulta->bind_param(
                    "ssssssi",
                    $this->identificacion,
                    $this->nombres,
                    $this->apellidos,
                    $this->especialidad,
                    $this->telefono,
                    $this->correo,
                    $idUsuario
                );

                return $consulta->execute();
            } finally {
                $consulta->close();
            }
        } finally {
            $this->Conexion->close();
        }
    }

    // Consultar un médico por su identificación
    public function consultarMedico($identificacion)
    {
        $this->Conexion = Conectarse();

        try {
            $sql = "SELECT * FROM medicos
                    WHERE medIdentificacion = ?";

            $consulta = $this->Conexion->prepare($sql);

            if ($consulta === false) {
                throw new RuntimeException($this->Conexion->error);
            }

            try {
                $consulta->bind_param("s", $identificacion);

                if (!$consulta->execute()) {
                    return false;
                }

                return $consulta->get_result();
            } finally {
                $consulta->close();
            }
        } finally {
            $this->Conexion->close();
        }
    }

    // Actualizar los datos de un médico por su ID
    public function actualizarMedico($idMedico)
    {
        $this->Conexion = Conectarse();

        try {
            $sql = "UPDATE medicos SET
                        medIdentificacion = ?,
                        medNombres = ?,
                        medApellidos = ?,
                        medEspecialidad = ?,
                        medTelefono = ?,
                        medCorreo = ?
                    WHERE idMedico = ?";

            $consulta = $this->Conexion->prepare($sql);

            if ($consulta === false) {
                throw new RuntimeException($this->Conexion->error);
            }

            try {
                $consulta->bind_param(
                    "ssssssi",
                    $this->identificacion,
                    $this->nombres,
                    $this->apellidos,
                    $this->especialidad,
                    $this->telefono,
                    $this->correo,
                    $idMedico
                );

                return $consulta->execute();
            } finally {
                $consulta->close();
            }
        } finally {
            $this->Conexion->close();
        }
    }

    // Listar todos los médicos
    public function listarMedicos()
    {
        $this->Conexion = Conectarse();

        try {
            $sql = "SELECT * FROM medicos";

            return $this->Conexion->query($sql);
        } finally {
            $this->Conexion->close();
        }
    }

    // Eliminar un médico por su ID
    public function eliminarMedico($idMedico)
    {
        $this->Conexion = Conectarse();

        try {
            $sql = "DELETE FROM medicos WHERE idMedico = ?";

            $consulta = $this->Conexion->prepare($sql);

            if ($consulta === false) {
                throw new RuntimeException($this->Conexion->error);
            }

            try {
                $consulta->bind_param("i", $idMedico);

                return $consulta->execute();
            } finally {
                $consulta->close();
            }
        } finally {
            $this->Conexion->close();
        }
    }
}