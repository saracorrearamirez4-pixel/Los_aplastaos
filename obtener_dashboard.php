<?php
session_start();
header('Content-Type: application/json');
require 'conexion.php';

if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'mensaje' => 'No autorizado.']);
    exit;
}

$ventasHoy = $pdo->query(
    "SELECT COALESCE(SUM(d.cantidad * d.precio_unitario), 0) AS total
     FROM detalle_pedido d
     JOIN pedidos p ON p.id_pedido = d.id_pedido
     WHERE DATE(p.fecha_hora) = CURDATE() AND p.estado != 'cancelado'"
)->fetch()['total'];

$pedidosActivos = $pdo->query(
    "SELECT COUNT(*) AS total FROM pedidos
     WHERE estado IN ('pendiente','listo') AND DATE(fecha_hora) = CURDATE()"
)->fetch()['total'];

$domiciliosActivos = $pdo->query(
    "SELECT COUNT(*) AS total FROM pedidos
     WHERE tipo_pedido = 'domicilio' AND estado IN ('pendiente','listo') AND DATE(fecha_hora) = CURDATE()"
)->fetch()['total'];

$pendientesCocina = $pdo->query(
    "SELECT COUNT(*) AS total FROM pedidos
     WHERE estado = 'pendiente' AND DATE(fecha_hora) = CURDATE()"
)->fetch()['total'];

$arqueo = $pdo->query(
    "SELECT p.forma_pago, COALESCE(SUM(sub.subtotal), 0) AS total
     FROM pedidos p
     JOIN (
        SELECT id_pedido, SUM(cantidad * precio_unitario) AS subtotal
        FROM detalle_pedido GROUP BY id_pedido
     ) sub ON sub.id_pedido = p.id_pedido
     WHERE DATE(p.fecha_hora) = CURDATE() AND p.estado != 'cancelado'
     GROUP BY p.forma_pago"
)->fetchAll(PDO::FETCH_KEY_PAIR);

$efectivo      = $arqueo['Efectivo'] ?? 0;
$transferencia = $arqueo['Transferencia'] ?? 0;
$tarjeta       = $arqueo['Tarjeta'] ?? 0;

echo json_encode([
    'ok'                 => true,
    'ventas_hoy'         => (float) $ventasHoy,
    'pedidos_activos'    => (int) $pedidosActivos,
    'domicilios_activos' => (int) $domiciliosActivos,
    'pendientes_cocina'  => (int) $pendientesCocina,
    'arqueo' => [
        'efectivo'      => (float) $efectivo,
        'transferencia' => (float) $transferencia,
        'tarjeta'       => (float) $tarjeta,
        'total'         => (float) $efectivo + (float) $transferencia + (float) $tarjeta,
    ],
]);
