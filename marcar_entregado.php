<?php
session_start();
header('Content-Type: application/json');
require 'conexion.php';

if (!isset($_SESSION['id_usuario']) || !in_array($_SESSION['rol'], ['domicilio', 'admin'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'mensaje' => 'No autorizado.']);
    exit;
}

$idPedido = $_POST['id_pedido'] ?? null;

if (!$idPedido) {
    echo json_encode(['ok' => false, 'mensaje' => 'Falta el id del pedido.']);
    exit;
}

$pdo->prepare("UPDATE pedidos SET estado = 'entregado' WHERE id_pedido = :id")
    ->execute(['id' => $idPedido]);

echo json_encode(['ok' => true]);
