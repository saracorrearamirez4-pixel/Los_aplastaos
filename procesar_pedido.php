<?php
session_start();
header('Content-Type: application/json');
require 'conexion.php';

// Solo un mesero (o el administrador) puede registrar pedidos de salón
if (!isset($_SESSION['id_usuario']) || !in_array($_SESSION['rol'], ['salon', 'admin'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'mensaje' => 'Debes iniciar sesión como mesero para enviar pedidos.']);
    exit;
}

$mesa = $_POST['mesa'] ?? '';
$pago = $_POST['pago'] ?? '';

if ($mesa === '' || $pago === '') {
    echo json_encode(['ok' => false, 'mensaje' => 'Selecciona la mesa y la forma de pago.']);
    exit;
}

// Traer el catálogo real de productos (codigo_form => id/precio) para no
// confiar en nada que venga directamente del formulario
$productos = $pdo->query(
    "SELECT id_producto, codigo_form, precio FROM productos WHERE disponible = 1"
)->fetchAll();

$catalogo = [];
foreach ($productos as $p) {
    $catalogo[$p['codigo_form']] = $p;
}

// Recorrer el POST y quedarnos solo con los productos válidos y cantidad > 0
$items = [];
foreach ($_POST as $campo => $valor) {
    if (isset($catalogo[$campo]) && (int) $valor > 0) {
        $items[] = [
            'id_producto'     => $catalogo[$campo]['id_producto'],
            'cantidad'        => (int) $valor,
            'precio_unitario' => $catalogo[$campo]['precio'],
        ];
    }
}

if (empty($items)) {
    echo json_encode(['ok' => false, 'mensaje' => 'Selecciona al menos un producto antes de enviar el pedido.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Insertar el pedido con un código temporal único (se reemplaza abajo)
    $stmtPedido = $pdo->prepare(
        "INSERT INTO pedidos (codigo_comanda, id_usuario, tipo_pedido, mesa, forma_pago, estado)
         VALUES (:codigo_temp, :id_usuario, 'salon', :mesa, :pago, 'pendiente')"
    );
    $stmtPedido->execute([
        'codigo_temp' => uniqid('tmp_'),
        'id_usuario'  => $_SESSION['id_usuario'],
        'mesa'        => $mesa,
        'pago'        => $pago,
    ]);
    $idPedido = (int) $pdo->lastInsertId();

    // 2. Generar el código definitivo de comanda, ej: #007
    $codigoFinal = '#' . str_pad((string) $idPedido, 3, '0', STR_PAD_LEFT);
    $pdo->prepare("UPDATE pedidos SET codigo_comanda = :codigo WHERE id_pedido = :id")
        ->execute(['codigo' => $codigoFinal, 'id' => $idPedido]);

    // 3. Insertar cada producto del pedido
    $stmtDetalle = $pdo->prepare(
        "INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio_unitario)
         VALUES (:id_pedido, :id_producto, :cantidad, :precio)"
    );
    foreach ($items as $item) {
        $stmtDetalle->execute([
            'id_pedido'   => $idPedido,
            'id_producto' => $item['id_producto'],
            'cantidad'    => $item['cantidad'],
            'precio'      => $item['precio_unitario'],
        ]);
    }

    $pdo->commit();

    http_response_code(200);
    echo json_encode(['ok' => true, 'codigo' => $codigoFinal]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error al guardar el pedido: ' . $e->getMessage()]);
}
