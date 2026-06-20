<?php
// Datos de conexión a MySQL
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'audiobook_converter');

// Crear conexión usando la constante definida (no hardcoded)
$conexion = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, 3306);

// Verificar conexión — enviar header JSON antes del die() para que el frontend pueda parsear el error
if (!$conexion) {
    header('Content-Type: application/json');
    http_response_code(500);
    die(json_encode([
        'exito'   => false,
        'mensaje' => 'Error de conexión a la base de datos: ' . mysqli_connect_error()
    ]));
}

// Configurar charset a utf8mb4 para soporte completo de caracteres
mysqli_set_charset($conexion, 'utf8mb4');
?>