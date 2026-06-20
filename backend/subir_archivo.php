<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Responder preflight OPTIONS inmediatamente
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once 'conectar.php';

// Carpetas del proyecto
$carpeta_uploads = '../uploads/';
$carpeta_outputs = '../outputs/';

// Generar nombre único para el archivo
$nombre_unico = uniqid('audio_', true);

// Capturar parámetros de voz
$idioma = isset($_POST['idioma']) ? $_POST['idioma'] : 'es-MX';
$voz = isset($_POST['voz']) ? $_POST['voz'] : 'male-premium-1';
$velocidad = isset($_POST['velocidad']) ? floatval($_POST['velocidad']) : 1.0;
$tono = isset($_POST['tono']) ? intval($_POST['tono']) : 0;

$ruta_archivo = '';
$nombre_archivo = '';
$tipo_archivo = '';

// ============================================
// OPCIÓN 1: El usuario pegó texto directamente
// ============================================
if (isset($_POST['texto']) && !empty(trim($_POST['texto']))) {

    $texto = trim($_POST['texto']);
    $nombre_archivo = 'texto_directo';
    $tipo_archivo = 'txt';

    // Guardar texto en archivo temporal
    $ruta_archivo = $carpeta_uploads . $nombre_unico . '.txt';
    file_put_contents($ruta_archivo, $texto);

// ============================================
// OPCIÓN 2: El usuario subió un archivo
// ============================================
} elseif (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === 0) {

    $archivo = $_FILES['archivo'];
    $nombre_original = basename($archivo['name']);
    $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));

    // Validar que sea PDF o DOCX
    $extensiones_permitidas = ['pdf', 'docx'];
    if (!in_array($extension, $extensiones_permitidas)) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'Solo se permiten archivos PDF o Word (.docx)'
        ]);
        exit;
    }

    // Validar tamaño (máximo 10MB)
    if ($archivo['size'] > 10 * 1024 * 1024) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'El archivo no puede superar los 10MB'
        ]);
        exit;
    }

    // Mover archivo a carpeta uploads
    $ruta_archivo = $carpeta_uploads . $nombre_unico . '.' . $extension;
    move_uploaded_file($archivo['tmp_name'], $ruta_archivo);

    $nombre_archivo = $nombre_original;
    $tipo_archivo = $extension;

} else {
    echo json_encode([
        'exito' => false,
        'mensaje' => 'No se recibió ningún archivo ni texto.'
    ]);
    exit;
}

// ============================================
// VALIDACIÓN ADICIONAL CON VALIDADOR JAVA
// ============================================
$comando_validador = 'java -cp ' . escapeshellarg(__DIR__ . '/../java') . ' Validador ' . escapeshellarg($ruta_archivo);
$salida_validador = [];
$codigo_retorno = 0;
exec($comando_validador, $salida_validador, $codigo_retorno);

if ($codigo_retorno !== 0) {
    $mensaje_error = !empty($salida_validador) ? implode(' ', $salida_validador) : 'Error de validación con Java.';
    if (file_exists($ruta_archivo)) {
        unlink($ruta_archivo);
    }
    echo json_encode([
        'exito' => false,
        'mensaje' => $mensaje_error
    ]);
    exit;
}

// Llamar a Python para convertir
$resultado = convertirConPython($ruta_archivo, $nombre_unico, $carpeta_outputs, $idioma, $voz, $velocidad, $tono);

// ============================================
// GUARDAR EN BASE DE DATOS Y RESPONDER
// ============================================
if ($resultado['exito']) {

    $nombre_audio = $nombre_unico . '.mp3';

    // Guardar en historial MySQL
    $sql = "INSERT INTO conversiones (nombre_archivo, tipo_archivo, nombre_audio, estado)
            VALUES (?, ?, ?, 'completado')";
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, 'sss', $nombre_archivo, $tipo_archivo, $nombre_audio);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    echo json_encode([
        'exito' => true,
        'mensaje' => '¡Audio generado correctamente!',
        'archivo_audio' => $nombre_audio
    ]);

} else {
    echo json_encode([
        'exito' => false,
        'mensaje' => $resultado['mensaje']
    ]);
}

mysqli_close($conexion);

// ============================================
// FUNCIÓN: Llamar al script Python
// ============================================
function convertirConPython($ruta_archivo, $nombre_unico, $carpeta_outputs, $idioma, $voz, $velocidad, $tono) {
    $script_python = __DIR__ . '/../python/convertir.py';
    $ruta_salida = $carpeta_outputs . $nombre_unico . '.mp3';

    // Ejecutar Python pasándole los argumentos
    $comando = 'python ' . escapeshellarg($script_python) .
        ' ' . escapeshellarg($ruta_archivo) .
        ' ' . escapeshellarg($ruta_salida) .
        ' --lang ' . escapeshellarg($idioma) .
        ' --voice ' . escapeshellarg($voz) .
        ' --speed ' . escapeshellarg($velocidad) .
        ' --pitch ' . escapeshellarg($tono);

    $salida = shell_exec($comando . ' 2>&1');

    if (file_exists($ruta_salida)) {
        return ['exito' => true];
    } else {
        return [
            'exito' => false,
            'mensaje' => 'Error al generar el audio: ' . $salida
        ];
    }
}
?>