<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$dataset = isset($_GET['dataset']) ? $_GET['dataset'] : 'seguros';

if ($dataset === 'seguros') {
    procesarSeguros();
} elseif ($dataset === 'inmuebles') {
    procesarInmuebles();
} else {
    echo json_encode(['exito' => false, 'mensaje' => 'Dataset no válido']);
}

function procesarSeguros() {
    $ruta = __DIR__ . '/../DatosSeguros.csv';
    if (!file_exists($ruta)) {
        echo json_encode(['exito' => false, 'mensaje' => 'No se encontró DatosSeguros.csv']);
        exit;
    }

    $filas = [];
    if (($gestor = fopen($ruta, "r")) !== FALSE) {
        $cabecera = fgetcsv($gestor, 1000, ",");
        // Limpiar cabeceras de posibles caracteres extraños
        foreach ($cabecera as &$col) {
            $col = trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $col));
        }
        
        while (($datos = fgetcsv($gestor, 1000, ",")) !== FALSE) {
            if (count($datos) < count($cabecera)) continue;
            $fila = array_combine($cabecera, $datos);
            
            // Tratamiento de inconsistencias como en PROYECTO FINAL.PY
            // por -> no en fumador
            if (isset($fila['fumador'])) {
                $fila['fumador'] = strtolower(trim($fila['fumador']));
                if ($fila['fumador'] === 'по' || $fila['fumador'] === 'no' || $fila['fumador'] === '') {
                    $fila['fumador'] = 'no';
                } elseif ($fila['fumador'] === 'yes' || $fila['fumador'] === 'si') {
                    $fila['fumador'] = 'yes';
                }
            }
            
            $filas[] = $fila;
        }
        fclose($gestor);
    }

    $total_registros = count($filas);
    if ($total_registros === 0) {
        echo json_encode(['exito' => false, 'mensaje' => 'Archivo vacío']);
        exit;
    }

    // Cálculos de KPIs
    $suma_edad = 0;
    $suma_imc = 0;
    $suma_valor = 0;
    $cant_fumadores = 0;
    
    // Agrupamientos
    $fumadores_stats = ['yes' => ['costo' => 0, 'cant' => 0], 'no' => ['costo' => 0, 'cant' => 0]];
    $regiones_stats = [];
    $grupos_edad_stats = [
        '18-30' => ['costo' => 0, 'cant' => 0],
        '31-45' => ['costo' => 0, 'cant' => 0],
        '46-60' => ['costo' => 0, 'cant' => 0],
        '60+'   => ['costo' => 0, 'cant' => 0]
    ];
    $imc_stats = [
        'Bajo peso' => ['costo' => 0, 'cant' => 0],
        'Normal' => ['costo' => 0, 'cant' => 0],
        'Sobrepeso' => ['costo' => 0, 'cant' => 0],
        'Obesidad' => ['costo' => 0, 'cant' => 0]
    ];

    foreach ($filas as $f) {
        $edad = intval($f['edad']);
        $imc = floatval($f['imc']);
        $valor = floatval($f['valor_seguro']);
        $fumador = $f['fumador'];
        $region = trim($f['region']);

        $suma_edad += $edad;
        $suma_imc += $imc;
        $suma_valor += $valor;

        if ($fumador === 'yes') {
            $cant_fumadores++;
        }

        // Stats fumadores
        $fumadores_stats[$fumador]['costo'] += $valor;
        $fumadores_stats[$fumador]['cant']++;

        // Stats regiones
        if (!isset($regiones_stats[$region])) {
            $regiones_stats[$region] = ['costo' => 0, 'cant' => 0];
        }
        $regiones_stats[$region]['costo'] += $valor;
        $regiones_stats[$region]['cant']++;

        // Stats grupos edad
        $grupo_edad = '60+';
        if ($edad <= 30) $grupo_edad = '18-30';
        elseif ($edad <= 45) $grupo_edad = '31-45';
        elseif ($edad <= 60) $grupo_edad = '46-60';

        $grupos_edad_stats[$grupo_edad]['costo'] += $valor;
        $grupos_edad_stats[$grupo_edad]['cant']++;

        // Stats IMC
        $cat_imc = 'Obesidad';
        if ($imc < 18.5) $cat_imc = 'Bajo peso';
        elseif ($imc < 25.0) $cat_imc = 'Normal';
        elseif ($imc < 30.0) $cat_imc = 'Sobrepeso';

        $imc_stats[$cat_imc]['costo'] += $valor;
        $imc_stats[$cat_imc]['cant']++;
    }

    // Formatear resultados
    $promedios = [
        'edad' => round($suma_edad / $total_registros, 1),
        'imc' => round($suma_imc / $total_registros, 2),
        'valor_seguro' => round($suma_valor / $total_registros, 2),
        'porcentaje_fumadores' => round(($cant_fumadores / $total_registros) * 100, 1)
    ];

    // Formatear gráficos
    $chart_fumadores = [
        'labels' => ['Fumador (Sí)', 'No Fumador (No)'],
        'promedios' => [
            round($fumadores_stats['yes']['costo'] / max(1, $fumadores_stats['yes']['cant']), 2),
            round($fumadores_stats['no']['costo'] / max(1, $fumadores_stats['no']['cant']), 2)
        ]
    ];

    $chart_regiones = [
        'labels' => [],
        'promedios' => [],
        'cantidades' => []
    ];
    foreach ($regiones_stats as $reg => $st) {
        $chart_regiones['labels'][] = $reg;
        $chart_regiones['promedios'][] = round($st['costo'] / max(1, $st['cant']), 2);
        $chart_regiones['cantidades'][] = $st['cant'];
    }

    $chart_edad = [
        'labels' => array_keys($grupos_edad_stats),
        'promedios' => []
    ];
    foreach ($grupos_edad_stats as $grp => $st) {
        $chart_edad['promedios'][] = round($st['costo'] / max(1, $st['cant']), 2);
    }

    $chart_imc = [
        'labels' => array_keys($imc_stats),
        'promedios' => []
    ];
    foreach ($imc_stats as $cat => $st) {
        $chart_imc['promedios'][] = round($st['costo'] / max(1, $st['cant']), 2);
    }

    echo json_encode([
        'exito' => true,
        'dataset' => 'seguros',
        'kpis' => [
            ['titulo' => 'Total Registros', 'valor' => number_format($total_registros), 'unidad' => 'pacientes'],
            ['titulo' => 'Costo Promedio', 'valor' => '$' . number_format($promedios['valor_seguro'], 2), 'unidad' => 'USD'],
            ['titulo' => 'IMC Promedio', 'valor' => $promedios['imc'], 'unidad' => 'kg/m²'],
            ['titulo' => 'Tasa Tabaquismo', 'valor' => $promedios['porcentaje_fumadores'] . '%', 'unidad' => 'de la muestra']
        ],
        'charts' => [
            'fumadores' => $chart_fumadores,
            'regiones' => $chart_regiones,
            'edad' => $chart_edad,
            'imc' => $chart_imc
        ]
    ]);
}

function procesarInmuebles() {
    $ruta = __DIR__ . '/../Data_Caso_Propuesto.csv';
    if (!file_exists($ruta)) {
        echo json_encode(['exito' => false, 'mensaje' => 'No se encontró Data_Caso_Propuesto.csv']);
        exit;
    }

    $filas = [];
    if (($gestor = fopen($ruta, "r")) !== FALSE) {
        $cabecera = fgetcsv($gestor, 1000, ",");
        foreach ($cabecera as &$col) {
            $col = trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $col));
        }

        while (($datos = fgetcsv($gestor, 2000, ",")) !== FALSE) {
            if (count($datos) < count($cabecera)) continue;
            $filas[] = array_combine($cabecera, $datos);
        }
        fclose($gestor);
    }

    $total_registros = count($filas);
    if ($total_registros === 0) {
        echo json_encode(['exito' => false, 'mensaje' => 'Archivo vacío']);
        exit;
    }

    $suma_precio = 0;
    $suma_terreno = 0;
    $suma_construida = 0;
    
    $ciudades = [];
    $tipos_inmueble = [];
    $estratos_stats = [];
    $precios_por_tipo = [];

    foreach ($filas as $f) {
        $precio = floatval($f['Precio']);
        $terreno = floatval($f['Area Terreno']);
        $construida = floatval($f['Area Construida']);
        $ciudad = strtoupper(trim($f['Ciudad']));
        $tipo = strtoupper(trim($f['Tipo de Inmueble']));
        $estrato = strtoupper(trim($f['Estrato']));

        $suma_precio += $precio;
        $suma_terreno += $terreno;
        $suma_construida += $construida;

        // Contar por ciudad
        if (!isset($ciudades[$ciudad])) $ciudades[$ciudad] = 0;
        $ciudades[$ciudad]++;

        // Agrupación de estratos
        if (!isset($estratos_stats[$estrato])) {
            $estratos_stats[$estrato] = ['precio' => 0, 'cant' => 0];
        }
        $estratos_stats[$estrato]['precio'] += $precio;
        $estratos_stats[$estrato]['cant']++;

        // Precio promedio por tipo de inmueble
        if (!isset($precios_por_tipo[$tipo])) {
            $precios_por_tipo[$tipo] = ['precio' => 0, 'cant' => 0];
        }
        $precios_por_tipo[$tipo]['precio'] += $precio;
        $precios_por_tipo[$tipo]['cant']++;
    }

    // Top ciudades
    arsort($ciudades);
    $top_ciudades_labels = [];
    $top_ciudades_valores = [];
    $count = 0;
    $otros_cant = 0;
    foreach ($ciudades as $c => $val) {
        if ($count < 6) {
            $top_ciudades_labels[] = $c;
            $top_ciudades_valores[] = $val;
        } else {
            $otros_cant += $val;
        }
        $count++;
    }
    if ($otros_cant > 0) {
        $top_ciudades_labels[] = 'OTROS';
        $top_ciudades_valores[] = $otros_cant;
    }

    // Estratos
    $chart_estratos = [
        'labels' => [],
        'promedios' => []
    ];
    // Ordenar estratos lógicamente si es posible o alfabéticamente
    ksort($estratos_stats);
    foreach ($estratos_stats as $est => $st) {
        if ($est === '') $est = 'NO ESPECIFICADO';
        $chart_estratos['labels'][] = $est;
        $chart_estratos['promedios'][] = round($st['precio'] / max(1, $st['cant']), 0);
    }

    // Tipos de inmuebles
    $chart_tipos = [
        'labels' => [],
        'promedios' => [],
        'cantidades' => []
    ];
    arsort($precios_por_tipo);
    $count_t = 0;
    foreach ($precios_por_tipo as $tipo => $st) {
        if ($count_t < 8) { // Top 8 tipos
            $chart_tipos['labels'][] = $tipo;
            $chart_tipos['promedios'][] = round($st['precio'] / max(1, $st['cant']), 0);
            $chart_tipos['cantidades'][] = $st['cant'];
        }
        $count_t++;
    }

    $precio_promedio = $suma_precio / $total_registros;
    $area_terreno_promedio = $suma_terreno / $total_registros;
    
    // Ciudad principal
    reset($ciudades);
    $principal_ciudad = key($ciudades);

    echo json_encode([
        'exito' => true,
        'dataset' => 'inmuebles',
        'kpis' => [
            ['titulo' => 'Total Inmuebles', 'valor' => number_format($total_registros), 'unidad' => 'propiedades'],
            ['titulo' => 'Precio Promedio', 'valor' => '$' . number_format($precio_promedio, 0), 'unidad' => 'COP'],
            ['titulo' => 'Área Terr. Promedio', 'valor' => number_format($area_terreno_promedio, 1), 'unidad' => 'm²'],
            ['titulo' => 'Foco Geográfico', 'valor' => $principal_ciudad, 'unidad' => 'ciudad principal']
        ],
        'charts' => [
            'ciudades' => [
                'labels' => $top_ciudades_labels,
                'valores' => $top_ciudades_valores
            ],
            'estratos' => $chart_estratos,
            'tipos' => $chart_tipos
        ]
    ]);
}
?>
