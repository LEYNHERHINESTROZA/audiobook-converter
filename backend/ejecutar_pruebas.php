<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'conectar.php';

// Aumentar el tiempo de ejecución ya que la síntesis de varios archivos puede tardar unos segundos
set_time_limit(120);

$dataset = isset($_POST['dataset']) ? $_POST['dataset'] : 'seguros';
$cantidad = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 3;
if ($cantidad < 1 || $cantidad > 10) $cantidad = 3;

$carpeta_uploads = '../uploads/';
$carpeta_outputs = '../outputs/';

// Crear carpetas si no existen
if (!file_exists($carpeta_uploads)) mkdir($carpeta_uploads, 0777, true);
if (!file_exists($carpeta_outputs)) mkdir($carpeta_outputs, 0777, true);

$resultados = [];
$logs = [];

$logs[] = "[INFO] Iniciando Suite de Pruebas QA - " . date('Y-m-d H:i:s');
$logs[] = "[INFO] Parámetros: Dataset = $dataset, Cantidad de Casos = $cantidad";

// 1. Cargar registros del CSV seleccionado
$filas = [];
if ($dataset === 'seguros') {
    $ruta_csv = __DIR__ . '/../DatosSeguros.csv';
    if (!file_exists($ruta_csv)) {
        echo json_encode(['exito' => false, 'mensaje' => 'No se encontró DatosSeguros.csv']);
        exit;
    }
    
    if (($gestor = fopen($ruta_csv, "r")) !== FALSE) {
        $cabecera = fgetcsv($gestor, 1000, ',');
        foreach ($cabecera as &$col) $col = trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $col));
        
        while (($datos = fgetcsv($gestor, 1000, ',')) !== FALSE) {
            if (count($datos) < count($cabecera)) continue;
            $filas[] = array_combine($cabecera, $datos);
        }
        fclose($gestor);
    }
} else {
    $ruta_csv = __DIR__ . '/../Data_Caso_Propuesto.csv';
    if (!file_exists($ruta_csv)) {
        echo json_encode(['exito' => false, 'mensaje' => 'No se encontró Data_Caso_Propuesto.csv']);
        exit;
    }
    
    if (($gestor = fopen($ruta_csv, "r")) !== FALSE) {
        $cabecera = fgetcsv($gestor, 1000, ',');
        foreach ($cabecera as &$col) $col = trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $col));
        
        while (($datos = fgetcsv($gestor, 2000, ',')) !== FALSE) {
            if (count($datos) < count($cabecera)) continue;
            $filas[] = array_combine($cabecera, $datos);
        }
        fclose($gestor);
    }
}

$total_disponibles = count($filas);
$logs[] = "[INFO] Registros cargados desde el CSV: $total_disponibles disponibles.";

if ($total_disponibles === 0) {
    echo json_encode([
        'exito' => false,
        'mensaje' => 'El dataset seleccionado está vacío o no se pudo leer.'
    ]);
    exit;
}

// Seleccionar una muestra aleatoria
$claves_aleatorias = array_rand($filas, min($cantidad, $total_disponibles));
if (!is_array($claves_aleatorias)) {
    $claves_aleatorias = [$claves_aleatorias];
}

$logs[] = "[INFO] Se seleccionaron " . count($claves_aleatorias) . " registros aleatorios para la prueba.";

$pruebas_exitosas = 0;
$pruebas_fallidas = 0;
$tiempo_sintesis_total = 0;

$casos_ejecutados = [];

foreach ($claves_aleatorias as $index => $key) {
    $registro = $filas[$key];
    $caso_id = $index + 1;
    
    $logs[] = "--------------------------------------------------";
    $logs[] = "[CASE $caso_id] Iniciando verificación del caso de prueba...";

    // Generar el texto según el dataset
    $texto_prueba = "";
    $id_prueba = "";
    
    if ($dataset === 'seguros') {
        $edad = $registro['edad'];
        $sexo = ($registro['sexo'] === 'F' || $registro['sexo'] === 'female') ? 'Femenino' : 'Masculino';
        $imc = $registro['imc'];
        $hijos = $registro['hijos'];
        $fumador = ($registro['fumador'] === 'yes') ? 'Sí fuma' : 'No fuma';
        $region = $registro['region'];
        $valor = $registro['valor_seguro'];
        
        $texto_prueba = "Muestra de prueba médica. Paciente de edad $edad años, sexo $sexo, con índice de masa corporal de $imc. Hábitos reportados: $fumador. Cantidad de hijos a cargo: $hijos. Valor de la póliza de seguro médico en la región de $region es de $valor dólares.";
        $id_prueba = "SEGURO_" . $key . "_" . $edad;
    } else {
        $codigo = $registro['Codigo'];
        $ciudad = $registro['Ciudad'];
        $depto = $registro['Departamento'];
        $direccion = $registro['Direccion'];
        $area_t = $registro['Area Terreno'];
        $area_c = $registro['Area Construida'];
        $estrato = $registro['Estrato'];
        $precio = $registro['Precio'];
        $tipo = $registro['Tipo de Inmueble'];
        $detalles = isset($registro['Datos Adicionales']) ? $registro['Datos Adicionales'] : '';
        
        $texto_prueba = "Muestra de prueba inmobiliaria de predio código $codigo. Inmueble tipo $tipo ubicado en la ciudad de $ciudad, departamento de $depto, dirección $direccion. Superficie: terreno de $area_t metros cuadrados y construida de $area_c metros cuadrados. Estrato de la zona: $estrato. Precio comercial de $precio pesos. $detalles";
        $id_prueba = "INMUEBLE_" . $codigo;
    }

    $logs[] = "[CASE $caso_id] Texto de prueba generado: \"" . substr($texto_prueba, 0, 75) . "...\" (" . strlen($texto_prueba) . " caracteres)";

    // Guardar texto en archivo temporal para que lo valide Java y Python
    $nombre_unico = "test_" . uniqid() . "_" . $caso_id;
    $ruta_txt = $carpeta_uploads . $nombre_unico . ".txt";
    file_put_contents($ruta_txt, $texto_prueba);
    
    $logs[] = "[CASE $caso_id] Guardado archivo temporal en uploads: " . basename($ruta_txt);

    // 2. LLAMAR A JAVA VALIDADOR
    $logs[] = "[CASE $caso_id] [JAVA] Invocando Validador.class...";
    $comando_validador = 'java -cp ' . escapeshellarg(__DIR__ . '/../java') . ' Validador ' . escapeshellarg($ruta_txt);
    $salida_validador = [];
    $codigo_retorno_java = 0;
    exec($comando_validador, $salida_validador, $codigo_retorno_java);

    if ($codigo_retorno_java !== 0) {
        $mensaje_error = !empty($salida_validador) ? implode(' ', $salida_validador) : 'Error de validación con Java.';
        $logs[] = "[CASE $caso_id] [JAVA] ❌ Error de validación: $mensaje_error";
        $pruebas_fallidas++;
        if (file_exists($ruta_txt)) unlink($ruta_txt);
        
        $casos_ejecutados[] = [
            'id' => $caso_id,
            'nombre' => $id_prueba,
            'texto' => $texto_prueba,
            'estado' => 'fallido',
            'error' => 'Validación Java: ' . $mensaje_error,
            'tiempo' => 0,
            'audio' => null
        ];
        continue;
    }
    
    $logs[] = "[CASE $caso_id] [JAVA] ✅ Validación exitosa.";

    // 3. LLAMAR A PYTHON CONVERTIR
    $logs[] = "[CASE $caso_id] [PYTHON] Invocando convertir.py...";
    $ruta_salida_mp3 = $carpeta_outputs . $nombre_unico . ".mp3";
    $script_python = __DIR__ . '/../python/convertir.py';
    
    $comando_python = 'python ' . escapeshellarg($script_python) .
        ' ' . escapeshellarg($ruta_txt) .
        ' ' . escapeshellarg($ruta_salida_mp3) .
        ' --lang es-MX --voice male-premium-1 --speed 1.0';

    $tiempo_inicio = microtime(true);
    $salida_python = shell_exec($comando_python . ' 2>&1');
    $tiempo_fin = microtime(true);
    
    $duracion_sintesis = round($tiempo_fin - $tiempo_inicio, 2);
    $tiempo_sintesis_total += $duracion_sintesis;

    // Verificar si el archivo MP3 se creó correctamente
    if (file_exists($ruta_salida_mp3) && filesize($ruta_salida_mp3) > 0) {
        $logs[] = "[CASE $caso_id] [PYTHON] ✅ Conversión exitosa en $duracion_sintesis segundos.";
        $logs[] = "[CASE $caso_id] [PYTHON] Audio generado: " . basename($ruta_salida_mp3) . " (" . round(filesize($ruta_salida_mp3) / 1024, 1) . " KB)";
        
        // 4. GUARDAR EN MYSQL HISTORIAL CON ESTADO 'test'
        $nombre_audio = $nombre_unico . '.mp3';
        $nombre_archivo_test = 'TEST_CASE_' . $id_prueba;
        
        $sql = "INSERT INTO conversiones (nombre_archivo, tipo_archivo, nombre_audio, estado)
                VALUES (?, 'txt', ?, 'test')";
        $stmt = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param($stmt, 'ss', $nombre_archivo_test, $nombre_audio);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        $logs[] = "[CASE $caso_id] [DB] ✅ Test registrado en base de datos.";
        $pruebas_exitosas++;

        $casos_ejecutados[] = [
            'id' => $caso_id,
            'nombre' => $id_prueba,
            'texto' => $texto_prueba,
            'estado' => 'pasado',
            'error' => null,
            'tiempo' => $duracion_sintesis,
            'audio' => $nombre_audio
        ];
    } else {
        $logs[] = "[CASE $caso_id] [PYTHON] ❌ Error en conversión de audio. Salida: $salida_python";
        $pruebas_fallidas++;
        
        $casos_ejecutados[] = [
            'id' => $caso_id,
            'nombre' => $id_prueba,
            'texto' => $texto_prueba,
            'estado' => 'fallido',
            'error' => 'Fallo en síntesis Python: ' . trim($salida_python),
            'tiempo' => $duracion_sintesis,
            'audio' => null
        ];
    }

    // Limpiar archivo temporal de texto
    if (file_exists($ruta_txt)) {
        unlink($ruta_txt);
    }
}

$logs[] = "--------------------------------------------------";
$logs[] = "[INFO] Suite de Pruebas Completada.";
$logs[] = "[INFO] Resultados Generales: Pasadas = $pruebas_exitosas, Fallidas = $pruebas_fallidas, Tiempo Promedio = " . ($pruebas_exitosas > 0 ? round($tiempo_sintesis_total / $pruebas_exitosas, 2) : 0) . "s";

mysqli_close($conexion);

echo json_encode([
    'exito' => true,
    'resumen' => [
        'totales' => count($claves_aleatorias),
        'pasados' => $pruebas_exitosas,
        'fallados' => $pruebas_fallidas,
        'tiempo_total' => round($tiempo_sintesis_total, 2),
        'tiempo_promedio' => $pruebas_exitosas > 0 ? round($tiempo_sintesis_total / $pruebas_exitosas, 2) : 0
    ],
    'casos' => $casos_ejecutados,
    'logs' => $logs
]);
?>
