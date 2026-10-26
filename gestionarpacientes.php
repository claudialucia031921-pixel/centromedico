<?php

session_start();
date_default_timezone_set('America/Bogota');

require_once __DIR__ . '/paciente.php';
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

if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

$paciente = new Paciente();
$usuario = new Usuario();

$idEditar = null;
$identificacion = '';
$nombres = '';
$apellidos = '';
$fechaNacimiento = '';
$sexo = '';
$idUsuario = null;

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
            $id = obtenerId($_POST['idPaciente'] ?? '');
            $paciente->eliminarPaciente($id);

            $_SESSION['mensaje'] = 'Paciente eliminado correctamente.';
        } elseif ($accion === 'guardar' || $accion === 'actualizar') {
            if ($accion === 'actualizar') {
                $idEditar = obtenerId($_POST['idPaciente'] ?? '');
                $existente = $paciente->consultarPacientePorId($idEditar);

                if (!$existente->fetch_assoc()) {
                    throw new RuntimeException('El paciente ya no existe.');
                }
            }

            $identificacion = trim($_POST['identificacion'] ?? '');
            $nombres = trim($_POST['nombres'] ?? '');
            $apellidos = trim($_POST['apellidos'] ?? '');
            $fechaNacimiento = trim($_POST['fechaNacimiento'] ?? '');
            $sexo = trim($_POST['sexo'] ?? '');
            $valorUsuario = $_POST['idUsuario'] ?? '';

            $idUsuario = $valorUsuario === ''
                ? null
                : obtenerId($valorUsuario);

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

            $fecha = DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $fechaNacimiento
            );

            if (!$fecha ||
                $fecha->format('Y-m-d') !== $fechaNacimiento ||
                $fechaNacimiento < '1000-01-01' ||
                $fecha > new DateTimeImmutable('today')) {
                throw new RuntimeException(
                    'Escribe una fecha de nacimiento válida que no sea futura.'
                );
            }

            if (!in_array(
                $sexo,
                ['Femenino', 'Masculino', 'Otro'],
                true
            )) {
                throw new RuntimeException('Selecciona una opción de sexo.');
            }

            if ($idUsuario !== null) {
                $resultadoUsuario = $usuario->consultarUsuarioPorId($idUsuario);

                if (!$resultadoUsuario->fetch_assoc()) {
                    throw new RuntimeException(
                        'El usuario seleccionado ya no existe.'
                    );
                }
            }

            $paciente->crearPaciente(
                $identificacion,
                $nombres,
                $apellidos,
                $fechaNacimiento,
                $sexo,
                $idUsuario
            );

            if ($accion === 'guardar') {
                $paciente->agregarPaciente();
                $_SESSION['mensaje'] = 'Paciente registrado correctamente.';
            } else {
                $paciente->actualizarPaciente($idEditar);
                $_SESSION['mensaje'] = 'Paciente actualizado correctamente.';
            }
        } else {
            throw new RuntimeException('La acción no es válida.');
        }

        header('Location: gestionarPacientes.php');
        exit;
    }

    if (isset($_GET['editar'])) {
        $idEditar = obtenerId($_GET['editar']);
        $resultado = $paciente->consultarPacientePorId($idEditar);
        $fila = $resultado->fetch_assoc();

        if (!$fila) {
            $idEditar = null;
            throw new RuntimeException('No se encontró el paciente.');
        }

        $paciente->crearPaciente(
            $fila['pacIdentificacion'],
            $fila['pacNombres'],
            $fila['pacApellidos'],
            $fila['pacFechaNacimiento'],
            $fila['pacSexo'],
            $fila['usuarios_idUsuario']
        );

        $identificacion = $paciente->getIdentificacion();
        $nombres = $paciente->getNombres();
        $apellidos = $paciente->getApellidos();
        $fechaNacimiento = $paciente->getFechaNacimiento();
        $sexo = $paciente->getSexo();
        $idUsuario = $paciente->getIdUsuario();
    }
} catch (mysqli_sql_exception $e) {
    if ((int) $e->getCode() === 1062) {
        $error = 'Ya existe un paciente con esa identificación.';
    } elseif ((int) $e->getCode() === 1451) {
        $error = 'No se puede eliminar: el paciente tiene registros asociados.';
    } elseif ((int) $e->getCode() === 1452) {
        $error = 'El usuario seleccionado ya no existe. Recarga la página.';
    } else {
        $error = 'No se pudo completar la operación en la base de datos.';
    }
} catch (RuntimeException $e) {
    $error = $e->getMessage();
}

try {
    $resultado = $paciente->listarPacientes();

    while ($fila = $resultado->fetch_assoc()) {
        $filas[] = $fila;
    }
} catch (Exception $e) {
    $error = 'No se pudo cargar la lista de pacientes.';
}

try {
    $resultado = $usuario->listarUsuario();

    while ($fila = $resultado->fetch_assoc()) {
        $usuarios[] = $fila;
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
    <title>Gestión de pacientes</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #eef3f8;
            margin: 0;
            padding: 30px 15px;
        }

        main {
            max-width: 1100px;
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
    </style>
</head>

<body>
<main>
    <a href="index.php">← Volver al inicio</a>

    <h1>Gestión de pacientes</h1>

    <?php if ($mensaje !== ''): ?>
        <p class="mensaje" role="status"><?= escapar($mensaje) ?></p>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <p class="error" role="alert"><?= escapar($error) ?></p>
    <?php endif; ?>

    <h2>
        <?= $idEditar !== null ? 'Editar paciente' : 'Registrar paciente' ?>
    </h2>

    <form method="post" action="gestionarPacientes.php">
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
                name="idPaciente"
                value="<?= escapar($idEditar) ?>"
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

        <label for="fechaNacimiento">Fecha de nacimiento</label>
        <input
            type="date"
            id="fechaNacimiento"
            name="fechaNacimiento"
            min="1000-01-01"
            max="<?= date('Y-m-d') ?>"
            value="<?= escapar($fechaNacimiento) ?>"
            required
        >

        <label for="sexo">Sexo</label>
        <select id="sexo" name="sexo" required>
            <option value="">Selecciona una opción</option>

            <?php foreach (['Femenino', 'Masculino', 'Otro'] as $opcion): ?>
                <option
                    value="<?= escapar($opcion) ?>"
                    <?= $sexo === $opcion ? 'selected' : '' ?>
                >
                    <?= escapar($opcion) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="idUsuario">Usuario asociado (opcional)</label>
        <select id="idUsuario" name="idUsuario">
            <option value="">Sin usuario asociado</option>

            <?php foreach ($usuarios as $cuenta): ?>
                <option
                    value="<?= (int) $cuenta['idUsuario'] ?>"
                    <?= (string) $idUsuario === (string) $cuenta['idUsuario']
                        ? 'selected' : '' ?>
                >
                    <?= escapar($cuenta['usuLogin']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button class="guardar" type="submit">
            <?= $idEditar !== null ? 'Actualizar paciente' : 'Guardar paciente' ?>
        </button>

        <?php if ($idEditar !== null): ?>
            <a href="gestionarPacientes.php">Cancelar edición</a>
        <?php endif; ?>
    </form>

    <h2>Pacientes registrados</h2>

    <div class="tabla">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Identificación</th>
                    <th>Nombres</th>
                    <th>Apellidos</th>
                    <th>Nacimiento</th>
                    <th>Sexo</th>
                    <th>Usuario</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
            <?php if (count($filas) === 0): ?>
                <tr>
                    <td colspan="8">Todavía no hay pacientes registrados.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($filas as $fila): ?>
                    <tr>
                        <td><?= (int) $fila['idPaciente'] ?></td>
                        <td><?= escapar($fila['pacIdentificacion']) ?></td>
                        <td><?= escapar($fila['pacNombres']) ?></td>
                        <td><?= escapar($fila['pacApellidos']) ?></td>
                        <td><?= escapar($fila['pacFechaNacimiento']) ?></td>
                        <td><?= escapar($fila['pacSexo']) ?></td>
                        <td>
                            <?= escapar($fila['usuLogin'] ?? 'Sin usuario') ?>
                        </td>

                        <td>
                            <div class="acciones">
                                <a href="gestionarPacientes.php?editar=<?= (int) $fila['idPaciente'] ?>">
                                    Editar
                                </a>

                                <form
                                    method="post"
                                    action="gestionarPacientes.php"
                                    onsubmit="return confirm('¿Eliminar este paciente?');"
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
                                        name="idPaciente"
                                        value="<?= (int) $fila['idPaciente'] ?>"
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