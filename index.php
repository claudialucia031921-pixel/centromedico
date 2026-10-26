<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Centro Médico</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #eef3f8;
            margin: 0;
            padding: 40px 20px;
        }

        main {
            max-width: 650px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
        }

        h1 {
            color: #174a75;
        }

        nav {
            display: grid;
            gap: 12px;
            margin-top: 25px;
        }

        a {
            display: block;
            padding: 16px;
            background: #176baf;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }

        a:hover {
            background: #104f83;
        }
    </style>
</head>
<body>
    <main>
        <h1>Centro Médico</h1>
        <p>Selecciona el módulo que deseas administrar.</p>

        <nav>
            <a href="gestionarPacientes.php">Pacientes</a>
            <a href="gestionarUsuarios.php">Usuarios</a>
            <a href="gestionarMedicos.php">Médicos</a>
            <a href="gestionarConsultorios.php">Consultorios</a>
            <a href="gestionarMedicamentos.php">Medicamentos</a>
        </nav>
    </main>
</body>
</html>