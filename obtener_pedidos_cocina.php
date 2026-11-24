<?php
session_start();
header('Content-Type: application/json');
require 'conexion.php';

if (!isset($_SESSION['id_usuario']) || !in_array($_SESSION['rol'], ['cocina', 'admin'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'mensaje' => 'No autorizado.']);
    exit;
}

$pedidos = $pdo->query(
    "SELECT id_pedido, codigo_comanda, tipo_pedido, mesa, forma_pago, fecha_hora
     FROM pedidos
     WHERE estado = 'pendiente'
     ORDER BY fecha_hora ASC"
)->fetchAll();

$stmtDetalle = $pdo->prepare(
    "SELECT d.cantidad, pr.nombre_producto
     FROM detalle_pedido d
     JOIN productos pr ON pr.id_producto = d.id_producto
     WHERE d.id_pedido = :id"
);

foreach ($pedidos as &$pedido) {
    $stmtDetalle->execute(['id' => $pedido['id_pedido']]);
    $pedido['productos'] = $stmtDetalle->fetchAll();
}
unset($pedido);

echo json_encode(['ok' => true, 'pedidos' => $pedidos]);
