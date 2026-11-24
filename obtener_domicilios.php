<?php
session_start();
header('Content-Type: application/json');
require 'conexion.php';

if (!isset($_SESSION['id_usuario']) || !in_array($_SESSION['rol'], ['domicilio', 'admin'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'mensaje' => 'No autorizado.']);
    exit;
}

$domicilios = $pdo->query(
    "SELECT p.id_pedido, p.codigo_comanda, c.nombre_cliente, c.telefono, c.direccion, c.sector
     FROM pedidos p
     LEFT JOIN clientes c ON c.id_cliente = p.id_cliente
     WHERE p.tipo_pedido = 'domicilio' AND p.estado = 'listo'
     ORDER BY p.fecha_hora ASC"
)->fetchAll();

echo json_encode(['ok' => true, 'domicilios' => $domicilios]);
