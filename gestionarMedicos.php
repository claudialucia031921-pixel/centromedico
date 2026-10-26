<?php

session_start();

require_once __DIR__ . '/medico.php';
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
        throw new RuntimeException('El identificador no es válido.');
    }

    return $id;
}

function buscarMedico($medico, $id)
{
    $resultado = $medico->listarMedicos();

    while ($fila = $resultado->fetch_assoc()) {
        if ((int) $fila['idMedico'] === $id) {
            return $fila;
        }
    }

    throw new RuntimeException('No se encontró el médico.');
}

if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

$medico = new Medico();
$usuario = new Usuario();

$idEditar = null;
$identificacion = '';
$nombres = '';
$apellidos = '';
$especialidad = '';
$telefono = '';
$correo = '';
$idUsuario = '';

$filas = [];
$usuarios = [];
$error = '';
$mensaje = $_SESSION['mensaje'] ?? '';

unset($_SESSION['mensaje']);

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
            $id = obtenerId($_POST['idMedico'] ?? '');

            buscarMedico($medico, $id);
            $medico->eliminarMedico($id);

            $_SESSION['mensaje'] = 'Médico eliminado correctamente.';
        } elseif ($accion === 'guardar' || $accion === 'actualizar') {
            if ($accion === 'actualizar') {
                $id = obtenerId($_POST['idMedico'] ?? '');
                $existente = buscarMedico($medico, $id);

                $idEditar = $id;
                $idUsuario = $existente['usuarios_idUsuario'];
            } else {
                $idUsuario = $_POST['idUsuario'] ?? '';
            }

            $identificacion = trim($_POST['identificacion'] ?? '');
            $nombres = trim($_POST['nombres'] ?? '');
            $apellidos = trim($_POST['apellidos'] ?? '');
            $especialidad = trim($_POST['especialidad'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $correo = trim($_POST['correo'] ?? '');

            if ($identificacion === '' ||
                mb_strlen($identificacion) > 15) {
                throw new RuntimeException(
                    'La identificación debe tener entre 1 y 15 caracteres.'
                );
            }

            if ($nombres === '' || mb_strlen($nombres) > 50) {
                throw new RuntimeException(
                    'Los nombres deben tener entre 1 y 50 caracteres.'
                );
            }

            if ($apellidos === '' || mb_strlen($apellidos) > 50) {
                throw new RuntimeException(
                    'Los apellidos deben tener entre 1 y 50 caracteres.'
                );
            }

            if ($especialidad === '' ||
                mb_strlen($especialidad) > 50) {
                throw new RuntimeException(
                    'La especialidad debe tener entre 1 y 50 caracteres.'
                );
            }

            if ($telefono === '' || mb_strlen($telefono) > 15) {
                throw new RuntimeException(
                    'El teléfono debe tener entre 1 y 15 caracteres.'
                );
            }

            if (mb_strlen($correo) > 50 ||
                !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException(
                    'Escribe un correo válido de máximo 50 caracteres.'
                );
            }

            if ($accion === 'guardar') {
                $idUsuario = obtenerId($idUsuario);

                $cuenta = $usuario->consultarUsuarioPorId($idUsuario);

                if (!$cuenta->fetch_assoc()) {
                    throw new RuntimeException(
                        'El usuario seleccionado ya no existe.'
                    );
                }
            }

            $medico->crearMedico(
                $identificacion,
                $nombres,
                $apellidos,
                $especialidad,
                $telefono,
                $correo
            );

            if ($accion === 'guardar') {
                $medico->agregarMedico($idUsuario);

                $_SESSION['mensaje'] = 'Médico registrado correctamente.';
            } else {
                $medico->actualizarMedico($idEditar);

                $_SESSION['mensaje'] = 'Médico actualizado correctamente.';
            }
        } else {
            throw new RuntimeException('La acción no es válida.');
        }

        header('Location: gestionarMedicos.php');
        exit;
    }

    if (isset($_GET['editar'])) {
        $id = obtenerId($_GET['editar']);
        $fila = buscarMedico($medico, $id);

        $idEditar = $id;

        $medico->crearMedico(
            $fila['medIdentificacion'],
            $fila['medNombres'],
            $fila['medApellidos'],
            $fila['medEspecialidad'],
            $fila['medTelefono'],
            $fila['medCorreo']
        );

        $identificacion = $medico->getIdentificacion();
        $nombres = $medico->getNombres();
        $apellidos = $medico->getApellidos();
        $especialidad = $medico->getEspecialidad();
        $telefono = $medico->getTelefono();
        $correo = $medico->getCorreo();

        $idUsuario = $fila['usuarios_idUsuario'];
    }
} catch (mysqli_sql_exception $e) {
    if ((int) $e->getCode() === 1062) {
        $error = 'Ya existe un médico con esa identificación.';
    } elseif ((int) $e->getCode() === 1451) {
        $error = 'No se puede eliminar: el médico tiene registros asociados.';
    } elseif ((int) $e->getCode() === 1452) {
        $error = 'El usuario seleccionado ya no existe. Recarga la página.';
    } else {
        $error = 'No se pudo completar la operación en la base de datos.';
    }
} catch (RuntimeException $e) {
    $error = $e->getMessage();
}

try {
    $resultado = $medico->listarMedicos();

    while ($fila = $resultado->fetch_assoc()) {
        $filas[] = $fila;
    }
} catch (Exception $e) {
    $error = 'No se pudo cargar la lista de médicos.';
}

try {
    $resultado = $usuario->listarUsuario();

    while ($fila = $resultado->fetch_assoc()) {
        $usuarios[(int) $fila['idUsuario']] = $fila['usuLogin'];
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

    <title>Gestión de médicos</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #eef3f8;
            margin: 0;
            padding: 30px 15px;
        }

        main {
            max-width: 1200px;
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

        .ayuda {
            color: #555;
            font-size: 14px;
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
    </style>
</head>

<body>
<main>
    <a href="index.php">← Volver al inicio</a>

    <h1>Gestión de médicos</h1>

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

    <h2>
        <?= $idEditar !== null ? 'Editar médico' : 'Registrar médico' ?>
    </h2>

    <form method="post" action="gestionarMedicos.php">
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
                name="idMedico"
                value="<?= (int) $idEditar ?>"
            >
        <?php endif; ?>

        <label for="identificacion">Identificación</label>
        <input
            type="text"
            id="identificacion"
            name="identificacion"
            maxlength="15"
            value="<?= escapar($identificacion) ?>"
            required
        >

        <label for="nombres">Nombres</label>
        <input
            type="text"
            id="nombres"
            name="nombres"
            maxlength="50"
            value="<?= escapar($nombres) ?>"
            required
        >

        <label for="apellidos">Apellidos</label>
        <input
            type="text"
            id="apellidos"
            name="apellidos"
            maxlength="50"
            value="<?= escapar($apellidos) ?>"
            required
        >

        <label for="especialidad">Especialidad</label>
        <input
            type="text"
            id="especialidad"
            name="especialidad"
            maxlength="50"
            placeholder="Ejemplo: Medicina general"
            value="<?= escapar($especialidad) ?>"
            required
        >

        <label for="telefono">Teléfono</label>
        <input
            type="tel"
            id="telefono"
            name="telefono"
            maxlength="15"
            value="<?= escapar($telefono) ?>"
            required
        >

        <label for="correo">Correo electrónico</label>
        <input
            type="email"
            id="correo"
            name="correo"
            maxlength="50"
            value="<?= escapar($correo) ?>"
            required
        >

        <?php if ($idEditar === null): ?>
            <label for="idUsuario">Usuario asociado</label>

            <select id="idUsuario" name="idUsuario" required>
                <option value="">Selecciona un usuario</option>

                <?php foreach ($usuarios as $idCuenta => $login): ?>
                    <option
                        value="<?= (int) $idCuenta ?>"
                        <?= (string) $idUsuario === (string) $idCuenta
                            ? 'selected' : '' ?>
                    >
                        <?= escapar($login) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?php if (count($usuarios) === 0): ?>
                <p class="ayuda">
                    Primero registra una cuenta en
                    <a href="gestionarUsuarios.php">Usuarios</a>.
                </p>
            <?php endif; ?>
        <?php else: ?>
            <p>
                <strong>Usuario asociado:</strong>
                <?= escapar($usuarios[(int) $idUsuario] ?? $idUsuario) ?>
            </p>

            <p class="ayuda">
                Al editar se conserva el usuario asociado.
            </p>
        <?php endif; ?>

        <button class="guardar" type="submit">
            <?= $idEditar !== null ? 'Actualizar médico' : 'Guardar médico' ?>
        </button>

        <?php if ($idEditar !== null): ?>
            <a href="gestionarMedicos.php">Cancelar edición</a>
        <?php endif; ?>
    </form>

    <h2>Médicos registrados</h2>

    <div class="tabla">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Identificación</th>
                    <th>Nombres</th>
                    <th>Apellidos</th>
                    <th>Especialidad</th>
                    <th>Teléfono</th>
                    <th>Correo</th>
                    <th>Usuario</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
            <?php if (count($filas) === 0): ?>
                <tr>
                    <td colspan="9">
                        Todavía no hay médicos registrados.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($filas as $fila): ?>
                    <tr>
                        <td><?= (int) $fila['idMedico'] ?></td>
                        <td><?= escapar($fila['medIdentificacion']) ?></td>
                        <td><?= escapar($fila['medNombres']) ?></td>
                        <td><?= escapar($fila['medApellidos']) ?></td>
                        <td><?= escapar($fila['medEspecialidad']) ?></td>
                        <td><?= escapar($fila['medTelefono']) ?></td>
                        <td><?= escapar($fila['medCorreo']) ?></td>
                        <td>
                            <?= escapar(
                                $usuarios[(int) $fila['usuarios_idUsuario']]
                                ?? $fila['usuarios_idUsuario']
                            ) ?>
                        </td>

                        <td>
                            <div class="acciones">
                                <a href="gestionarMedicos.php?editar=<?= (int) $fila['idMedico'] ?>">
                                    Editar
                                </a>

                                <form
                                    method="post"
                                    action="gestionarMedicos.php"
                                    onsubmit="return confirm('¿Eliminar este médico?');"
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
                                        name="idMedico"
                                        value="<?= (int) $fila['idMedico'] ?>"
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