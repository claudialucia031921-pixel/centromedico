<?php

require_once __DIR__ . '/conexionBaseDatos.php';

class Usuario
{
    private $usuario;
    private $password;
    private $estado;
    private $rol;

    public function crearUsuario($usuario, $password, $estado, $rol)
    {
        $this->setUsuario($usuario);
        $this->setPassword($password);
        $this->setEstado($estado);
        $this->setRol($rol);
    }

    public function setUsuario($usuario)
    {
        $this->usuario = $usuario;
    }

    public function getUsuario()
    {
        return $this->usuario;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setEstado($estado)
    {
        $this->estado = $estado;
    }

    public function getEstado()
    {
        return $this->estado;
    }

    public function setRol($rol)
    {
        $this->rol = $rol;
    }

    public function getRol()
    {
        return $this->rol;
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

    public function agregarUsuario()
    {
        $claveProtegida = password_hash(
            $this->password,
            PASSWORD_BCRYPT
        );

        return $this->ejecutar(
            'INSERT INTO usuarios
                (usuLogin, usuPassword, usuEstado, usuRol)
             VALUES (?, ?, ?, ?)',
            'ssss',
            [
                $this->usuario,
                $claveProtegida,
                $this->estado,
                $this->rol
            ]
        );
    }

    public function consultarUsuario($usuario)
    {
        return $this->ejecutar(
            'SELECT idUsuario, usuLogin, usuEstado, usuRol
             FROM usuarios WHERE usuLogin = ?',
            's',
            [$usuario],
            true
        );
    }

    public function consultarUsuarioPorId($idUsuario)
    {
        return $this->ejecutar(
            'SELECT idUsuario, usuLogin, usuEstado, usuRol
             FROM usuarios WHERE idUsuario = ?',
            'i',
            [$idUsuario],
            true
        );
    }

    public function actualizarUsuario($idUsuario)
    {
        // Una contraseña vacía conserva la contraseña actual.
        if ($this->password === '') {
            return $this->ejecutar(
                'UPDATE usuarios
                 SET usuLogin = ?, usuEstado = ?, usuRol = ?
                 WHERE idUsuario = ?',
                'sssi',
                [
                    $this->usuario,
                    $this->estado,
                    $this->rol,
                    $idUsuario
                ]
            );
        }

        $claveProtegida = password_hash(
            $this->password,
            PASSWORD_BCRYPT
        );

        return $this->ejecutar(
            'UPDATE usuarios
             SET usuLogin = ?, usuPassword = ?,
                 usuEstado = ?, usuRol = ?
             WHERE idUsuario = ?',
            'ssssi',
            [
                $this->usuario,
                $claveProtegida,
                $this->estado,
                $this->rol,
                $idUsuario
            ]
        );
    }

    public function listarUsuario()
    {
        return $this->ejecutar(
            'SELECT idUsuario, usuLogin, usuEstado, usuRol
             FROM usuarios ORDER BY idUsuario',
            '',
            [],
            true
        );
    }

    public function eliminarUsuario($idUsuario)
    {
        return $this->ejecutar(
            'DELETE FROM usuarios WHERE idUsuario = ?',
            'i',
            [$idUsuario]
        );
    }
}