<?php
// ===========================
// datos.php — ENDPOINT DE DATOS (CSV por defecto / JSON opcional)
// Control Internacional Del Ecuador — UAE
// ===========================
// Entrega los datos del sistema para que otras herramientas los lean.
//
// Uso:
//   datos.php?token=UAE-ControlInstrumentos-2026                                → Descarga CSV (instrumentos)
//   datos.php?token=UAE-ControlInstrumentos-2026&recurso=asignaciones           → Descarga CSV (asignaciones)
//   datos.php?token=UAE-ControlInstrumentos-2026&recurso=vencimientos           → Descarga CSV (vencimientos)
//   datos.php?token=UAE-ControlInstrumentos-2026&formato=json                   → Muestra JSON en pantalla
//
// Recursos disponibles: instrumentos, asignaciones, vencimientos
// Por la LOPDP, este endpoint NO expone datos personales sensibles.

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/config/Database.php';

ini_set("display_errors", "0");         // ningún aviso de PHP debe mezclarse con los datos
ini_set("serialize_precision", "-1");   // evita decimales largos en el JSON
header("Access-Control-Allow-Origin: *");

// ── 1. Seguridad: token ──
if (($_GET["token"] ?? "") !== API_TOKEN) {
    header("Content-Type: application/json; charset=utf-8");
    http_response_code(401);
    echo json_encode(["error" => "Token inválido o ausente"]);
    exit;
}

// ── 2. Consultas disponibles (solo lectura) ──
$consultas = [
    "instrumentos" => "
        SELECT id, serie, nombre, categoria, estado, fecha_vencimiento_calibracion
        FROM instrumentos
        ORDER BY id ASC
    ",

    "asignaciones" => "
        SELECT a.id, i.serie, i.nombre AS instrumento, a.tecnico, a.proyecto,
               a.fecha_salida, a.fecha_devolucion_est, a.estado
        FROM asignaciones a
        JOIN instrumentos i ON a.instrumento_id = i.id
        ORDER BY a.id DESC
    ",

    "vencimientos" => "
        SELECT id, serie, nombre, categoria, fecha_vencimiento_calibracion,
               DATEDIFF(fecha_vencimiento_calibracion, CURDATE()) AS dias_restantes
        FROM instrumentos
        WHERE fecha_vencimiento_calibracion IS NOT NULL
        ORDER BY dias_restantes ASC
    ",
];

// Columnas que deben entregarse como enteros
$enteros = ["id", "dias_restantes"];

$recurso = $_GET["recurso"] ?? "instrumentos";
if (!isset($consultas[$recurso])) {
    header("Content-Type: application/json; charset=utf-8");
    http_response_code(404);
    echo json_encode([
        "error"       => "Recurso no válido",
        "disponibles" => array_keys($consultas),
    ]);
    exit;
}

try {
    $pdo   = Database::getInstance()->getConnection();
    $filas = $pdo->query($consultas[$recurso])->fetchAll(PDO::FETCH_ASSOC);

    foreach ($filas as &$fila) {
        foreach ($enteros as $campo) {
            if (isset($fila[$campo])) {
                $fila[$campo] = (int) $fila[$campo];
            }
        }

        // Estado de calibración calculado (solo para vencimientos)
        if ($recurso === "vencimientos") {
            $d = (int) $fila["dias_restantes"];
            if ($d <= 7) {
                $fila["estado_calibracion"] = "Critico";
            } elseif ($d <= 30) {
                $fila["estado_calibracion"] = "Alerta";
            } else {
                $fila["estado_calibracion"] = "OK";
            }
        }
    }
    unset($fila);

    // ── 3. DEFINIR FORMATO (Por defecto es 'csv') ──
    // Si en la URL no dice "formato=json", entonces asume CSV.
    $formato = $_GET["formato"] ?? "csv";

    if ($formato === "json") {
        // ── 3a. Salida JSON (Para Postman, Power BI, etc.) ──
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode([
            "recurso"   => $recurso,
            "generado"  => date("Y-m-d H:i:s"),
            "registros" => count($filas),
            "datos"     => $filas,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    } else {
        // ── 3b. Salida CSV (Descarga directa para Excel / Google Sheets) ──
        header("Content-Type: text/csv; charset=utf-8");
        header("Content-Disposition: attachment; filename=\"{$recurso}_" . date('Y-m-d') . ".csv\"");
        
        $salida = fopen("php://output", "w");
        
        // Agregar BOM UTF-8 para que Excel reconozca tildes y eñes correctamente
        fwrite($salida, "\xEF\xBB\xBF"); 

        if (!empty($filas)) {
            fputcsv($salida, array_keys($filas[0]), ",", "\"", "");
            foreach ($filas as $fila) {
                fputcsv($salida, $fila, ",", "\"", "");
            }
        }
        fclose($salida);
        exit;
    }

} catch (Exception $e) {
    header("Content-Type: application/json; charset=utf-8");
    http_response_code(500);
    echo json_encode(["error" => "Error al consultar la base de datos"]);
}