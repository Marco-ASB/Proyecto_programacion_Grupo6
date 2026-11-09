<?php
// ===========================
// config.php — CONFIGURACIÓN PRIVADA
// Control Internacional Del Ecuador — UAE
// ===========================
// ESTE ARCHIVO NO SE SUBE A GITHUB (está en .gitignore).
// Contiene las credenciales de la base de datos y el token del API.
//
// Si necesitás una plantilla pública, mirá config.ejemplo.php

// ── Base de datos (AwardSpace) ──
define("DB_HOST", "fdb1029.awardspace.net");
define("DB_NAME", "4768846_proyecto");
define("DB_USER", "4768846_proyecto");
define("DB_PASS", "MarcoSatan18@");
define("DB_CHARSET", "utf8mb4");

// ── API de datos (datos.php) ──
// Token de acceso al endpoint. Cambialo por uno propio y secreto.
// Con este token, herramientas como Power BI, Looker, Postman o Google Sheets
// podrán leer los datos del sistema.
define("API_TOKEN", "UAE-ControlInstrumentos-2026");

// ── Zona horaria de Ecuador ──
date_default_timezone_set("America/Guayaquil");