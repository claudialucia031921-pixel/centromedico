<?php

require_once __DIR__ . '/conexionBaseDatos.php';

class Consultorio
{
    // Definición de atributos
    private $nombre;
    private $Conexion;

    // Asignar los datos del consultorio
    public function crearConsultorio($nombre)
    {
        $this->nombre = $nombre;
    }

    // Modificar el nombre
    public function setNombre($nombre)
    {
        $this->nombre = $nombre;
    }

    // Consultar el nombre
    public function getNombre()
    {
        return $this->nombre;
    }

    // Agregar un consultorio
    public function agregarConsultorio()
    {
        $this->Conexion = Conectarse();

        try {
            $sql = "INSERT INTO consultorios (conNombre)
                    VALUES (?)";

            $consulta = $this->Conexion->prepare($sql);

            if ($consulta === false) {
                throw new RuntimeException($this->Conexion->error);
            }

            try {
                $consulta->bind_param("s", $this->nombre);
                return $consulta->execute();
            } finally {
                $consulta->close();
            }
        } finally {
            $this->Conexion->close();
        }
    }

    // Consultar un consultorio por su ID
    public function consultarConsultorio($idConsultorio)
    {
        $this->Conexion = Conectarse();

        try {
            $sql = "SELECT * FROM consultorios
                    WHERE idConsultorio = ?";

            $consulta = $this->Conexion->prepare($sql);

            if ($consulta === false) {
                throw new RuntimeException($this->Conexion->error);
            }

            try {
                $consulta->bind_param("i", $idConsultorio);

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

    // Actualizar un consultorio
    public function actualizarConsultorio($idConsultorio)
    {
        $this->Conexion = Conectarse();

        try {
            $sql = "UPDATE consultorios
                    SET conNombre = ?
                    WHERE idConsultorio = ?";

            $consulta = $this->Conexion->prepare($sql);

            if ($consulta === false) {
                throw new RuntimeException($this->Conexion->error);
            }

            try {
                $consulta->bind_param(
                    "si",
                    $this->nombre,
                    $idConsultorio
                );

                return $consulta->execute();
            } finally {
                $consulta->close();
            }
        } finally {
            $this->Conexion->close();
        }
    }

    // Listar todos los consultorios
    public function listarConsultorios()
    {
        $this->Conexion = Conectarse();

        try {
            $sql = "SELECT * FROM consultorios";
            return $this->Conexion->query($sql);
        } finally {
            $this->Conexion->close();
        }
    }

    // Eliminar un consultorio
    public function eliminarConsultorio($idConsultorio)
    {
        $this->Conexion = Conectarse();

        try {
            $sql = "DELETE FROM consultorios
                    WHERE idConsultorio = ?";

            $consulta = $this->Conexion->prepare($sql);

            if ($consulta === false) {
                throw new RuntimeException($this->Conexion->error);
            }

            try {
                $consulta->bind_param("i", $idConsultorio);
                return $consulta->execute();
            } finally {
                $consulta->close();
            }
        } finally {
            $this->Conexion->close();
        }
    }
}