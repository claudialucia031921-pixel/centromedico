<?php
session_start();

require_once __DIR__ . '/consultorio.php';

function escapar($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

$consultorio = new Consultorio();
$error = '';
$idEditar = 0;
$nombre = '';

$mensaje = $_SESSION['mensaje'] ?? '';
unset($_SESSION['mensaje']);

try {
    // Recibir las acciones enviadas por los formularios.
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals(
            $_SESSION['token'],
            (string) ($_POST['token'] ?? '')
        )) {
            throw new RuntimeException('Solicitud inválida. Recarga la página.');
        }

        $accion = $_POST['accion'] ?? '';
        $id = filter_var(
            $_POST['idConsultorio'] ?? '',
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($accion === 'guardar') {
            $nombre = trim($_POST['nombre'] ?? '');

            if ($nombre === '' || mb_strlen($nombre) > 15) {
                throw new RuntimeException(
                    'Escribe un nombre de entre 1 y 15 caracteres.'
                );
            }

            $consultorio->crearConsultorio($nombre);

            if (!$consultorio->agregarConsultorio()) {
                throw new RuntimeException('No se pudo guardar.');
            }

            $_SESSION['mensaje'] = 'Consultorio guardado.';
        } elseif ($accion === 'actualizar') {
            if (!$id) {
                throw new RuntimeException('Identificador inválido.');
            }

            $idEditar = $id;
            $nombre = trim($_POST['nombre'] ?? '');

            if ($nombre === '' || mb_strlen($nombre) > 15) {
                throw new RuntimeException(
                    'Escribe un nombre de entre 1 y 15 caracteres.'
                );
            }

            $consultorio->setNombre($nombre);

            if (!$consultorio->actualizarConsultorio($id)) {
                throw new RuntimeException('No se pudo actualizar.');
            }

            $_SESSION['mensaje'] = 'Consultorio actualizado.';
        } elseif ($accion === 'eliminar') {
            if (!$id) {
                throw new RuntimeException('Identificador inválido.');
            }

            if (!$consultorio->eliminarConsultorio($id)) {
                throw new RuntimeException('No se pudo eliminar.');
            }

            $_SESSION['mensaje'] = 'Consultorio eliminado.';
        } else {
            throw new RuntimeException('Acción inválida.');
        }

        // Evitar que se repita la acción al actualizar la página.
        header('Location: gestionarConsultorios.php');
        exit;
    }

    // Consultar el registro seleccionado para editarlo.
    if (isset($_GET['editar'])) {
        $id = filter_var(
            $_GET['editar'],
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if (!$id) {
            throw new RuntimeException('Identificador inválido.');
        }

        $resultado = $consultorio->consultarConsultorio($id);
        $fila = $resultado ? $resultado->fetch_assoc() : null;

        if (!$fila) {
            throw new RuntimeException('Consultorio no encontrado.');
        }

        $idEditar = $fila['idConsultorio'];
        $consultorio->setNombre($fila['conNombre']);
        $nombre = $consultorio->getNombre();
    }
} catch (mysqli_sql_exception $excepcion) {
    $error = 'No se pudo completar la operación en la base de datos.';
} catch (RuntimeException $excepcion) {
    $error = $excepcion->getMessage();
}

$lista = null;

try {
    $lista = $consultorio->listarConsultorios();

    if (!$lista) {
        $error = 'No se pudo consultar la lista.';
    }
} catch (mysqli_sql_exception $excepcion) {
    $error = 'No se pudo consultar la lista de consultorios.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Consultorios</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #eef3f8;
            margin: 0;
            padding: 30px 15px;
        }

        main {
            max-width: 850px;
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
            margin-bottom: 8px;
        }

        input[type="text"] {
            padding: 10px;
            width: 100%;
            max-width: 300px;
            box-sizing: border-box;
        }

        button {
            padding: 10px 15px;
            background: #176baf;
            color: white;
            border: 0;
            border-radius: 5px;
            cursor: pointer;
        }

        .eliminar {
            background: #b52b36;
        }

        .mensaje {
            padding: 12px;
            background: #e2f4e7;
            color: #205c31;
        }

        .error {
            padding: 12px;
            background: #fde6e6;
            color: #8b2020;
        }

        .tabla {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            border-bottom: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        th {
            background: #eef3f8;
        }

        .acciones {
            display: flex;
            align-items: center;
            gap: 15px;
        }
    </style>
</head>
<body>
<main>
    <a href="index.php">Volver al inicio</a>

    <h1>Consultorios</h1>

    <?php if ($mensaje !== ''): ?>
        <p class="mensaje"><?= escapar($mensaje) ?></p>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <p class="error"><?= escapar($error) ?></p>
    <?php endif; ?>

    <h2><?= $idEditar ? 'Editar consultorio' : 'Nuevo consultorio' ?></h2>

    <form method="post" action="gestionarConsultorios.php">
        <input type="hidden" name="token"
               value="<?= escapar($_SESSION['token']) ?>">

        <input type="hidden" name="accion"
               value="<?= $idEditar ? 'actualizar' : 'guardar' ?>">

        <input type="hidden" name="idConsultorio"
               value="<?= escapar($idEditar) ?>">

        <label for="nombre">Nombre del consultorio</label>

        <input type="text" id="nombre" name="nombre"
               maxlength="15" required
               value="<?= escapar($nombre) ?>">

        <button type="submit">
            <?= $idEditar ? 'Actualizar' : 'Guardar' ?>
        </button>

        <?php if ($idEditar): ?>
            <a href="gestionarConsultorios.php">Cancelar</a>
        <?php endif; ?>
    </form>

    <h2>Consultorios registrados</h2>

    <div class="tabla">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($lista && $lista->num_rows > 0): ?>
                <?php while ($fila = $lista->fetch_assoc()): ?>
                    <tr>
                        <td><?= escapar($fila['idConsultorio']) ?></td>
                        <td><?= escapar($fila['conNombre']) ?></td>
                        <td>
                            <div class="acciones">
                                <a href="gestionarConsultorios.php?editar=<?= (int) $fila['idConsultorio'] ?>">
                                    Editar
                                </a>

                                <form method="post"
                                      action="gestionarConsultorios.php"
                                      onsubmit="return confirm('¿Eliminar este consultorio?');">
                                    <input type="hidden" name="token"
                                           value="<?= escapar($_SESSION['token']) ?>">
                                    <input type="hidden" name="accion"
                                           value="eliminar">
                                    <input type="hidden" name="idConsultorio"
                                           value="<?= (int) $fila['idConsultorio'] ?>">
                                    <button type="submit" class="eliminar">
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3">No hay consultorios para mostrar.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>