<?php
session_start();

require_once __DIR__ . '/medicamento.php';

function escapar($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function validarMedicamento($nombre, $importe)
{
    if ($nombre === '' || mb_strlen($nombre) > 100) {
        throw new RuntimeException(
            'El nombre debe tener entre 1 y 100 caracteres.'
        );
    }

    if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $importe)) {
        throw new RuntimeException(
            'Escribe un precio válido, por ejemplo: 12500.00, sin puntos de miles.'
        );
    }
}

if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

$medicamento = new Medicamento();
$idEditar = 0;
$nombre = '';
$importe = '';
$error = '';
$mensaje = $_SESSION['mensaje'] ?? '';
unset($_SESSION['mensaje']);

try {
    // Guardar, actualizar o eliminar mediante POST.
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals(
            $_SESSION['token'],
            (string) ($_POST['token'] ?? '')
        )) {
            throw new RuntimeException('Solicitud inválida. Recarga la página.');
        }

        $accion = $_POST['accion'] ?? '';
        $id = filter_var(
            $_POST['idMedicamento'] ?? '',
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($accion === 'guardar' || $accion === 'actualizar') {
            if ($accion === 'actualizar') {
                if (!$id) {
                    throw new RuntimeException('Identificador inválido.');
                }

                $idEditar = $id;
            }

            $nombre = trim($_POST['nombre'] ?? '');
            $importe = trim($_POST['importe'] ?? '');

            validarMedicamento($nombre, $importe);

            $medicamento->crearMedicamento($nombre, $importe);

            if ($accion === 'guardar') {
                $correcto = $medicamento->agregarMedicamento();
                $texto = 'Medicamento guardado.';
            } else {
                $correcto = $medicamento->actualizarMedicamento($id);
                $texto = 'Medicamento actualizado.';
            }

            if (!$correcto) {
                throw new RuntimeException('No se pudo guardar el medicamento.');
            }

            $_SESSION['mensaje'] = $texto;
        } elseif ($accion === 'eliminar') {
            if (!$id) {
                throw new RuntimeException('Identificador inválido.');
            }

            if (!$medicamento->eliminarMedicamento($id)) {
                throw new RuntimeException('No se pudo eliminar.');
            }

            $_SESSION['mensaje'] = 'Medicamento eliminado.';
        } else {
            throw new RuntimeException('Acción inválida.');
        }

        header('Location: gestionarMedicamentos.php');
        exit;
    }

    // Consultar mediante GET el registro que se editará.
    if (isset($_GET['editar'])) {
        $id = filter_var(
            $_GET['editar'],
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if (!$id) {
            throw new RuntimeException('Identificador inválido.');
        }

        $resultado = $medicamento->consultarMedicamento($id);
        $fila = $resultado ? $resultado->fetch_assoc() : null;

        if (!$fila) {
            throw new RuntimeException('Medicamento no encontrado.');
        }

        $idEditar = $fila['idMedicamento'];

        $medicamento->setNombre($fila['medNombre']);
        $medicamento->setImporte($fila['medImporte']);

        $nombre = $medicamento->getNombre();
        $importe = $medicamento->getImporte();
    }
} catch (mysqli_sql_exception $excepcion) {
    $error = 'No se pudo completar la operación en la base de datos.';
} catch (RuntimeException $excepcion) {
    $error = $excepcion->getMessage();
}

$lista = null;

try {
    $lista = $medicamento->listarMedicamentos();

    if (!$lista) {
        $error = 'No se pudo consultar la lista.';
    }
} catch (mysqli_sql_exception $excepcion) {
    $error = 'No se pudo consultar la lista de medicamentos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Medicamentos</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #eef3f8;
            margin: 0;
            padding: 30px 15px;
        }

        main {
            max-width: 900px;
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
            margin: 15px 0 8px;
        }

        input[type="text"], input[type="number"] {
            padding: 10px;
            width: 100%;
            max-width: 350px;
            box-sizing: border-box;
        }

        .botones {
            margin-top: 15px;
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

    <h1>Medicamentos</h1>

    <?php if ($mensaje !== ''): ?>
        <p class="mensaje"><?= escapar($mensaje) ?></p>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <p class="error"><?= escapar($error) ?></p>
    <?php endif; ?>

    <h2><?= $idEditar ? 'Editar medicamento' : 'Nuevo medicamento' ?></h2>

    <form method="post" action="gestionarMedicamentos.php">
        <input type="hidden" name="token"
               value="<?= escapar($_SESSION['token']) ?>">

        <input type="hidden" name="accion"
               value="<?= $idEditar ? 'actualizar' : 'guardar' ?>">

        <input type="hidden" name="idMedicamento"
               value="<?= escapar($idEditar) ?>">

        <label for="nombre">Nombre del medicamento</label>
        <input type="text" id="nombre" name="nombre"
               maxlength="100" required
               value="<?= escapar($nombre) ?>">

        <label for="importe">Precio en pesos</label>
        <input type="number" id="importe" name="importe"
               min="0" max="99999999.99" step="0.01" required
               value="<?= escapar($importe) ?>">

        <div class="botones">
            <button type="submit">
                <?= $idEditar ? 'Actualizar' : 'Guardar' ?>
            </button>

            <?php if ($idEditar): ?>
                <a href="gestionarMedicamentos.php">Cancelar</a>
            <?php endif; ?>
        </div>
    </form>

    <h2>Medicamentos registrados</h2>

    <div class="tabla">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Precio</th>
                <th>Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($lista && $lista->num_rows > 0): ?>
                <?php while ($fila = $lista->fetch_assoc()): ?>
                    <tr>
                        <td><?= (int) $fila['idMedicamento'] ?></td>
                        <td><?= escapar($fila['medNombre']) ?></td>
                        <td>
                            $<?= number_format(
                                (float) $fila['medImporte'], 2, ',', '.'
                            ) ?>
                        </td>
                        <td>
                            <div class="acciones">
                                <a href="gestionarMedicamentos.php?editar=<?= (int) $fila['idMedicamento'] ?>">
                                    Editar
                                </a>

                                <form method="post"
                                      action="gestionarMedicamentos.php"
                                      onsubmit="return confirm('¿Eliminar este medicamento?');">
                                    <input type="hidden" name="token"
                                           value="<?= escapar($_SESSION['token']) ?>">
                                    <input type="hidden" name="accion"
                                           value="eliminar">
                                    <input type="hidden" name="idMedicamento"
                                           value="<?= (int) $fila['idMedicamento'] ?>">
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
                    <td colspan="4">No hay medicamentos para mostrar.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>