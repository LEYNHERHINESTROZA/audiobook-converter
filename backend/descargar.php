<?php
// Validar que se envió el nombre del archivo
if (!isset($_GET['archivo']) || empty($_GET['archivo'])) {
    http_response_code(400);
    echo 'Archivo no especificado.';
    exit;
}

// Sanitizar nombre del archivo (solo permitir letras, números, guiones y puntos)
$nombre_archivo = preg_replace('/[^a-zA-Z0-9._-]/', '', $_GET['archivo']);
$ruta_archivo = '../outputs/' . $nombre_archivo;

// Verificar que el archivo existe
if (!file_exists($ruta_archivo)) {
    http_response_code(404);
    echo 'Archivo no encontrado.';
    exit;
}

// Verificar que sea un MP3
if (pathinfo($ruta_archivo, PATHINFO_EXTENSION) !== 'mp3') {
    http_response_code(400);
    echo 'Tipo de archivo no permitido.';
    exit;
}

// Enviar el archivo al navegador para descarga
header('Content-Type: audio/mpeg');
header('Content-Disposition: attachment; filename="audiolibro_' . $nombre_archivo . '"');
header('Content-Length: ' . filesize($ruta_archivo));
header('Cache-Control: no-cache');

readfile($ruta_archivo);
exit;
?>