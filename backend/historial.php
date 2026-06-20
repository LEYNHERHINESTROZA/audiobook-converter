<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'conectar.php';

// Consultar las últimas 20 conversiones
$sql = "SELECT nombre_archivo, tipo_archivo, nombre_audio,
               DATE_FORMAT(fecha, '%d/%m/%Y %H:%i') AS fecha, estado
        FROM conversiones
        ORDER BY fecha DESC
        LIMIT 20";

$resultado = mysqli_query($conexion, $sql);

// Verificar que la consulta se ejecutó correctamente
if ($resultado === false) {
    http_response_code(500);
    echo json_encode([
        'exito'   => false,
        'mensaje' => 'Error en la consulta: ' . mysqli_error($conexion)
    ]);
    mysqli_close($conexion);
    exit;
}

$conversiones = [];
while ($fila = mysqli_fetch_assoc($resultado)) {
    $conversiones[] = $fila;
}

echo json_encode($conversiones);

mysqli_close($conexion);
?>