<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$carpeta_audios = __DIR__ . '/../uploads/audios/';
$carpeta_audios_url = 'uploads/audios/';

// Crear carpeta si no existe
if (!is_dir($carpeta_audios)) {
    mkdir($carpeta_audios, 0755, true);
}

$action = $_GET['action'] ?? ($_POST['action'] ?? 'list');

// ============================================
// LISTAR AUDIOS SUBIDOS POR EL USUARIO
// ============================================
if ($action === 'list') {
    $archivos = [];
    $extensiones = ['mp3', 'wav', 'ogg', 'm4a', 'flac', 'aac'];
    $items = scandir($carpeta_audios);

    foreach ($items as $archivo) {
        if ($archivo === '.' || $archivo === '..') continue;
        $ruta = $carpeta_audios . $archivo;
        if (!is_file($ruta)) continue;

        $ext = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
        if (!in_array($ext, $extensiones)) continue;

        $tamano = filesize($ruta);
        if ($tamano < 1024 * 1024) {
            $tamano_str = round($tamano / 1024, 1) . ' KB';
        } else {
            $tamano_str = round($tamano / (1024 * 1024), 2) . ' MB';
        }

        $archivos[] = [
            'nombre'     => $archivo,
            'extension'  => strtoupper($ext),
            'tamano'     => $tamano_str,
            'fecha'      => date('d/m/Y H:i', filemtime($ruta)),
            'url'        => 'http://localhost/audiobook-converter/' . $carpeta_audios_url . rawurlencode($archivo),
        ];
    }

    // Más reciente primero
    usort($archivos, fn($a, $b) => strcmp($b['fecha'], $a['fecha']));

    echo json_encode(['exito' => true, 'archivos' => $archivos]);
    exit;
}

// ============================================
// SUBIR AUDIO DEL USUARIO
// ============================================
if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['audio']) || $_FILES['audio']['error'] !== 0) {
        http_response_code(400);
        echo json_encode(['exito' => false, 'mensaje' => 'No se recibió ningún archivo de audio.']);
        exit;
    }

    $archivo  = $_FILES['audio'];
    $nombre   = basename($archivo['name']);
    $ext      = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
    $permitidas = ['mp3', 'wav', 'ogg', 'm4a', 'flac', 'aac'];

    if (!in_array($ext, $permitidas)) {
        echo json_encode(['exito' => false, 'mensaje' => 'Formato no permitido. Usa MP3, WAV, OGG, M4A, FLAC o AAC.']);
        exit;
    }

    // Máximo 50 MB
    if ($archivo['size'] > 50 * 1024 * 1024) {
        echo json_encode(['exito' => false, 'mensaje' => 'El archivo no puede superar los 50 MB.']);
        exit;
    }

    // Nombre seguro: prefijo timestamp + nombre original saneado
    $nombre_seguro = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $nombre);
    $destino = $carpeta_audios . $nombre_seguro;

    if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
        http_response_code(500);
        echo json_encode(['exito' => false, 'mensaje' => 'Error al guardar el archivo en el servidor.']);
        exit;
    }

    $tamano = filesize($destino);
    $tamano_str = $tamano < 1024 * 1024
        ? round($tamano / 1024, 1) . ' KB'
        : round($tamano / (1024 * 1024), 2) . ' MB';

    echo json_encode([
        'exito'     => true,
        'mensaje'   => '¡Audio subido correctamente!',
        'archivo'   => [
            'nombre'    => $nombre_seguro,
            'extension' => strtoupper($ext),
            'tamano'    => $tamano_str,
            'fecha'     => date('d/m/Y H:i'),
            'url'       => 'http://localhost/audiobook-converter/' . $carpeta_audios_url . rawurlencode($nombre_seguro),
        ]
    ]);
    exit;
}

// ============================================
// ELIMINAR AUDIO
// ============================================
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = basename($_POST['nombre'] ?? '');
    if (empty($nombre)) {
        echo json_encode(['exito' => false, 'mensaje' => 'Nombre de archivo inválido.']);
        exit;
    }

    $ruta = $carpeta_audios . $nombre;
    if (!file_exists($ruta)) {
        echo json_encode(['exito' => false, 'mensaje' => 'El archivo no existe.']);
        exit;
    }

    unlink($ruta);
    echo json_encode(['exito' => true, 'mensaje' => 'Archivo eliminado.']);
    exit;
}

http_response_code(400);
echo json_encode(['exito' => false, 'mensaje' => 'Acción no reconocida.']);
