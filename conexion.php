<?php
// =====================================================================
//  Conexión a la base de datos (PDO)
//  Si tu XAMPP usa un usuario/clave distinto para MySQL, cámbialos aquí.
// =====================================================================

$host    = 'localhost';
$db      = 'los_aplastaos';
$user    = 'root';
$pass    = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$opciones = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $opciones);
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    die(json_encode([
        'ok' => false,
        'mensaje' => 'No se pudo conectar a la base de datos. Verifica que MySQL esté activo en XAMPP y que la base "los_aplastaos" exista. Detalle: ' . $e->getMessage(),
    ]));
}
