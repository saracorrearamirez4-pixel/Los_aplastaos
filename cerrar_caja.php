<?php
session_start();
header('Content-Type: application/json');
require 'conexion.php';

if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'mensaje' => 'No autorizado.']);
    exit;
}

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

$stmt = $pdo->prepare(
    "INSERT INTO arqueo_caja (fecha, id_usuario, total_efectivo, total_transferencia, total_tarjeta, cerrado, fecha_cierre)
     VALUES (CURDATE(), :id_usuario, :efectivo, :transferencia, :tarjeta, 1, NOW())
     ON DUPLICATE KEY UPDATE
        total_efectivo = :efectivo2,
        total_transferencia = :transferencia2,
        total_tarjeta = :tarjeta2,
        cerrado = 1,
        fecha_cierre = NOW()"
);
$stmt->execute([
    'id_usuario'     => $_SESSION['id_usuario'],
    'efectivo'       => $efectivo,
    'transferencia'  => $transferencia,
    'tarjeta'        => $tarjeta,
    'efectivo2'      => $efectivo,
    'transferencia2' => $transferencia,
    'tarjeta2'       => $tarjeta,
]);

echo json_encode(['ok' => true]);
