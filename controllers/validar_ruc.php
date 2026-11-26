<?php
/**
 * controlador/validar_ruc.php
 * Valida algorítmicamente un RUC o Cédula ecuatoriana.
 * Respuesta JSON: { status, message, razon_social }
 */

header('Content-Type: application/json; charset=utf-8');

// Capturamos el parámetro
$ruc = trim($_POST['ruc'] ?? '');

// 1. Validar que no esté vacío y tenga 13 dígitos (o 10 si permites cédulas)
if (!preg_match('/^\d{13}$/', $ruc)) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'El RUC debe tener exactamente 13 dígitos numéricos.'
    ]);
    exit;
}

$tercerDigito = (int)$ruc[2];
$esValido = false;
$tipo = '';

// 2. Validación Algorítmica
if ($tercerDigito === 9 || $tercerDigito === 6) {
    // Módulo 11 (Sociedades y Entidades Públicas)
    $tipo = 'RUC (Sociedad/Público)';
    $coeficientes = [4, 3, 2, 7, 6, 5, 4, 3, 2];
    $suma = 0;
    for ($i = 0; $i < 9; $i++) {
        $suma += (int)$ruc[$i] * $coeficientes[$i];
    }
    $residuo = $suma % 11;
    $digitoVerificador = ($residuo === 0) ? 0 : (11 - $residuo);
    $esValido = ((int)$ruc[9] === $digitoVerificador);

} elseif ($tercerDigito >= 0 && $tercerDigito <= 5) {
    // Módulo 10 (Personas Naturales)
    $tipo = 'RUC (Persona Natural)';
    $coeficientes = [2, 1, 2, 1, 2, 1, 2, 1, 2];
    $suma = 0;
    for ($i = 0; $i < 9; $i++) {
        $valor = (int)$ruc[$i] * $coeficientes[$i];
        if ($valor > 9) {
            $valor -= 9;
        }
        $suma += $valor;
    }
    $residuo = $suma % 10;
    $digitoVerificador = ($residuo === 0) ? 0 : (10 - $residuo);
    $esValido = ((int)$ruc[9] === $digitoVerificador);
}

// 3. Respuesta
if ($esValido) {
    echo json_encode([
        'status'       => 'success',
        'message'      => "$tipo válido.",
        'razon_social' => null // Ya no extraemos razón social del SRI
    ]);
} else {
    echo json_encode([
        'status'  => 'error',
        'message' => 'El RUC ingresado no es válido (dígito verificador incorrecto).'
    ]);
}
exit;
