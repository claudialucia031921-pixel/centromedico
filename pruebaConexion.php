<?php
require_once __DIR__ . '/conexionBaseDatos.php';

$conexion = Conectarse();

echo "Conexión correcta con la base de datos centromedico.";

$conexion->close();