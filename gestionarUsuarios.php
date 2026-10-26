<?php

session_start();

require_once __DIR__ . '/usuario.php';

function escapar($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function obtenerId($valor)
{
    $id = filter_var(
        $valor,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($id === false) {
        throw new RuntimeException('El usuario seleccionado no es válido.');
    }

    return $id;
}

if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

$usuario = new Usuario();
$error = '';
$mensaje = $_SESSION['mensaje'] ?? '';
unset($_SESSION['mensaje']);

$idEditar = null;
$login = '';
$estado = 'Activo';
$rol = '';
$filas = [];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['token'] ?? '';

        if (!is_string($token) ||
            !hash_equals($_SESSION['token'], $token)) {
            throw new RuntimeException(
                'La solicitud no es válida. Recarga la página.'
            );
        }

        $accion = $_POST['accion'] ?? '';

        if ($accion === 'eliminar') {
            $id = obtenerId($_POST['idUsuario'] ?? '');

            $usuario->eliminarUsuario($id);

            $_SESSION['mensaje'] = 'Usuario eliminado correctamente.';
        } elseif ($accion === 'guardar' || $accion === 'actualizar') {
            if ($accion === 'actualizar') {
                $idEditar = obtenerId($_POST['idUsuario'] ?? '');

                $existente = $usuario->consultarUsuarioPorId($idEditar);

                if (!$existente->fetch_assoc()) {
                    throw new RuntimeException('El usuario ya no existe.');
                }
            }

            $login = trim($_POST['login'] ?? '');
            $password = $_POST['password'] ?? '';
            $estado = trim($_POST['estado'] ?? '');
            $rol = trim($_POST['rol'] ?? '');

            if ($login === '' || mb_strlen($login) > 15) {
                throw new RuntimeException(
                    'El nombre de usuario debe tener entre 1 y 15 caracteres.'
                );
            }

            if (!in_array($estado, ['Activo', 'Inactivo'], true)) {
                throw new RuntimeException('Selecciona un estado válido.');
            }

            if ($rol === '' || mb_strlen($rol) > 100) {
                throw new RuntimeException(
                    'El rol debe tener entre 1 y 100 caracteres.'
                );
            }

            if ($accion === 'guardar' && $password === '') {
                throw new RuntimeException('Escribe una contraseña.');
            }

            if (strlen($password) > 72) {
                throw new RuntimeException(
                    'La contraseña es demasiado larga. Usa hasta 72 bytes.'
                );
            }

            $usuario->crearUsuario($login, $password, $estado, $rol);

            if ($accion === 'guardar') {
                $usuario->agregarUsuario();
                $_SESSION['mensaje'] = 'Usuario registrado correctamente.';
            } else {
                $usuario->actualizarUsuario($idEditar);
                $_SESSION['mensaje'] = 'Usuario actualizado correctamente.';
            }
        } else {
            throw new RuntimeException('La acción no es válida.');
        }

        header('Location: gestionarUsuarios.php');
        exit;
    }

    if (isset($_GET['editar'])) {
        $idEditar = obtenerId($_GET['editar']);
        $resultado = $usuario->consultarUsuarioPorId($idEditar);
        $fila = $resultado->fetch_assoc();

        if (!$fila) {
            $idEditar = null;
            throw new RuntimeException('No se encontró el usuario.');
        }

        $usuario->setUsuario($fila['usuLogin']);
        $usuario->setEstado($fila['usuEstado']);
        $usuario->setRol($fila['usuRol']);

        $login = $usuario->getUsuario();
        $estado = $usuario->getEstado();
        $rol = $usuario->getRol();
    }
} catch (mysqli_sql_exception $e) {
    if ((int) $e->getCode() === 1062) {
        $error = 'Ese nombre de usuario ya existe. Escribe otro.';
    } elseif ((int) $e->getCode() === 1451) {
        $error = 'No se puede eliminar: este usuario tiene médicos o pacientes asociados.';
    } else {
        $error = 'No se pudo completar la operación en la base de datos.';
    }
} catch (RuntimeException $e) {
    $error = $e->getMessage();
}

try {
    $resultado = $usuario->listarUsuario();

    while ($fila = $resultado->fetch_assoc()) {
        $filas[] = $fila;
    }
} catch (Exception $e) {
    $error = 'No se pudo cargar la lista de usuarios.';
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de usuarios</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #eef3f8;
            margin: 0;
            padding: 30px 15px;
        }

        main {
            max-width: 950px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 12px;
        }

        h1, h2 {
            color: #174a75;
        }

        a {
            color: #176baf;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }

        input, select {
            width: 100%;
            box-sizing: border-box;
            padding: 10px;
            margin-top: 6px;
            border: 1px solid #bbb;
            border-radius: 5px;
            font-size: 16px;
        }

        button {
            background: #176baf;
            color: white;
            border: none;
            padding: 11px 16px;
            border-radius: 5px;
            cursor: pointer;
        }

        .guardar {
            margin-top: 20px;
        }

        .eliminar {
            background: #b52b36;
        }

        .mensaje, .error {
            padding: 12px;
            border-radius: 5px;
        }

        .mensaje {
            background: #e2f4e7;
        }

        .error {
            background: #fde6e6;
        }

        .tabla {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .acciones {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .acciones form {
            margin: 0;
        }

        .ayuda {
            color: #555;
            font-size: 14px;
        }
    </style>
</head>

<body>
<main>
    <a href="index.php">← Volver al inicio</a>

    <h1>Gestión de usuarios</h1>

    <?php if ($mensaje !== ''): ?>
        <p class="mensaje" role="status">
            <?= escapar($mensaje) ?>
        </p>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <p class="error" role="alert">
            <?= escapar($error) ?>
        </p>
    <?php endif; ?>

    <h2><?= $idEditar !== null ? 'Editar usuario' : 'Registrar usuario' ?></h2>

    <form method="post" action="gestionarUsuarios.php">
        <input
            type="hidden"
            name="token"
            value="<?= escapar($_SESSION['token']) ?>"
        >

        <input
            type="hidden"
            name="accion"
            value="<?= $idEditar !== null ? 'actualizar' : 'guardar' ?>"
        >

        <?php if ($idEditar !== null): ?>
            <input
                type="hidden"
                name="idUsuario"
                value="<?= escapar($idEditar) ?>"
            >
        <?php endif; ?>

        <label for="login">Nombre de usuario</label>
        <input
            type="text"
            id="login"
            name="login"
            maxlength="15"
            value="<?= escapar($login) ?>"
            required
        >

        <label for="password">
            <?= $idEditar !== null ? 'Nueva contraseña' : 'Contraseña' ?>
        </label>
        <input
            type="password"
            id="password"
            name="password"
            maxlength="72"
            autocomplete="new-password"
            <?= $idEditar === null ? 'required' : '' ?>
        >

        <?php if ($idEditar !== null): ?>
            <p class="ayuda">
                Deja la contraseña vacía para conservar la actual.
            </p>
        <?php endif; ?>

        <label for="estado">Estado</label>
        <select id="estado" name="estado" required>
            <option value="Activo" <?= $estado === 'Activo' ? 'selected' : '' ?>>
                Activo
            </option>
            <option value="Inactivo" <?= $estado === 'Inactivo' ? 'selected' : '' ?>>
                Inactivo
            </option>
        </select>

        <label for="rol">Rol</label>
        <input
            type="text"
            id="rol"
            name="rol"
            maxlength="100"
            placeholder="Ejemplo: Médico"
            value="<?= escapar($rol) ?>"
            required
        >

        <button class="guardar" type="submit">
            <?= $idEditar !== null ? 'Actualizar usuario' : 'Guardar usuario' ?>
        </button>

        <?php if ($idEditar !== null): ?>
            <a href="gestionarUsuarios.php">Cancelar edición</a>
        <?php endif; ?>
    </form>

    <h2>Usuarios registrados</h2>

    <div class="tabla">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>Estado</th>
                    <th>Rol</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
            <?php if (count($filas) === 0): ?>
                <tr>
                    <td colspan="5">Todavía no hay usuarios registrados.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($filas as $fila): ?>
                    <tr>
                        <td><?= escapar($fila['idUsuario']) ?></td>
                        <td><?= escapar($fila['usuLogin']) ?></td>
                        <td><?= escapar($fila['usuEstado']) ?></td>
                        <td><?= escapar($fila['usuRol']) ?></td>

                        <td>
                            <div class="acciones">
                                <a href="gestionarUsuarios.php?editar=<?= (int) $fila['idUsuario'] ?>">
                                    Editar
                                </a>

                                <form
                                    method="post"
                                    action="gestionarUsuarios.php"
                                    onsubmit="return confirm('¿Eliminar este usuario?');"
                                >
                                    <input
                                        type="hidden"
                                        name="token"
                                        value="<?= escapar($_SESSION['token']) ?>"
                                    >
                                    <input
                                        type="hidden"
                                        name="accion"
                                        value="eliminar"
                                    >
                                    <input
                                        type="hidden"
                                        name="idUsuario"
                                        value="<?= (int) $fila['idUsuario'] ?>"
                                    >

                                    <button class="eliminar" type="submit">
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>