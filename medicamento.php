<?php

require_once __DIR__ . '/conexionBaseDatos.php';

class Medicamento
{
    // Definición de atributos
    private $nombre;
    private $importe;
    private $Conexion;

    // Asignar los datos del medicamento
    public function crearMedicamento($nombre, $importe)
    {
        $this->nombre = $nombre;
        $this->importe = $importe;
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

    // Modificar el importe
    public function setImporte($importe)
    {
        $this->importe = $importe;
    }

    // Consultar el importe
    public function getImporte()
    {
        return $this->importe;
    }

    // Agregar un medicamento
    public function agregarMedicamento()
    {
        $this->Conexion = Conectarse();

        try {
            $sql = "INSERT INTO medicamentos (
                        medNombre,
                        medImporte
                    ) VALUES (?, ?)";

            $consulta = $this->Conexion->prepare($sql);

            if ($consulta === false) {
                throw new RuntimeException($this->Conexion->error);
            }

            try {
                $consulta->bind_param(
                    "ss",
                    $this->nombre,
                    $this->importe
                );

                return $consulta->execute();
            } finally {
                $consulta->close();
            }
        } finally {
            $this->Conexion->close();
        }
    }

    // Consultar un medicamento por su ID
    public function consultarMedicamento($idMedicamento)
    {
        $this->Conexion = Conectarse();

        try {
            $sql = "SELECT * FROM medicamentos
                    WHERE idMedicamento = ?";

            $consulta = $this->Conexion->prepare($sql);

            if ($consulta === false) {
                throw new RuntimeException($this->Conexion->error);
            }

            try {
                $consulta->bind_param("i", $idMedicamento);

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

    // Actualizar un medicamento
    public function actualizarMedicamento($idMedicamento)
    {
        $this->Conexion = Conectarse();

        try {
            $sql = "UPDATE medicamentos SET
                        medNombre = ?,
                        medImporte = ?
                    WHERE idMedicamento = ?";

            $consulta = $this->Conexion->prepare($sql);

            if ($consulta === false) {
                throw new RuntimeException($this->Conexion->error);
            }

            try {
                $consulta->bind_param(
                    "ssi",
                    $this->nombre,
                    $this->importe,
                    $idMedicamento
                );

                return $consulta->execute();
            } finally {
                $consulta->close();
            }
        } finally {
            $this->Conexion->close();
        }
    }

    // Listar todos los medicamentos
    public function listarMedicamentos()
    {
        $this->Conexion = Conectarse();

        try {
            $sql = "SELECT * FROM medicamentos";

            return $this->Conexion->query($sql);
        } finally {
            $this->Conexion->close();
        }
    }

    // Eliminar un medicamento
    public function eliminarMedicamento($idMedicamento)
    {
        $this->Conexion = Conectarse();

        try {
            $sql = "DELETE FROM medicamentos
                    WHERE idMedicamento = ?";

            $consulta = $this->Conexion->prepare($sql);

            if ($consulta === false) {
                throw new RuntimeException($this->Conexion->error);
            }

            try {
                $consulta->bind_param("i", $idMedicamento);

                return $consulta->execute();
            } finally {
                $consulta->close();
            }
        } finally {
            $this->Conexion->close();
        }
    }
}