<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['ok' => false]);
    exit;
}

echo json_encode([
    'ok'              => true,
    'rol'             => $_SESSION['rol'],
    'usuario'         => $_SESSION['usuario'],
    'nombre_completo' => $_SESSION['nombre_completo'],
]);
