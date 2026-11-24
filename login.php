<?php
session_start();
header('Content-Type: application/json');
require 'conexion.php';

$usuario    = trim($_POST['usuario'] ?? '');
$rolElegido = trim($_POST['rol'] ?? '');
$clave      = $_POST['clave'] ?? '';

if ($usuario === '' || $rolElegido === '' || $clave === '') {
    echo json_encode(['ok' => false, 'mensaje' => 'Completa todos los campos.']);
    exit;
}

$sql = "SELECT u.id_usuario, u.nombre_completo, u.usuario, u.contrasena, u.activo, r.codigo_rol
        FROM usuarios u
        JOIN roles r ON r.id_rol = u.id_rol
        WHERE u.usuario = :usuario
        LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute(['usuario' => $usuario]);
$fila = $stmt->fetch();

if (!$fila || !$fila['activo'] || !password_verify($clave, $fila['contrasena'])) {
    echo json_encode(['ok' => false, 'mensaje' => 'Usuario o contraseña incorrectos.']);
    exit;
}

if ($fila['codigo_rol'] !== $rolElegido) {
    echo json_encode(['ok' => false, 'mensaje' => 'Ese usuario no tiene el rol seleccionado.']);
    exit;
}

$_SESSION['id_usuario']      = $fila['id_usuario'];
$_SESSION['usuario']         = $fila['usuario'];
$_SESSION['nombre_completo'] = $fila['nombre_completo'];
$_SESSION['rol']             = $fila['codigo_rol'];

$destinos = [
    'admin'     => 'admin.html',
    'cocina'    => 'cocina.html',
    'salon'     => 'menu.html',
    'domicilio' => 'domicilios.html',
];

echo json_encode(['ok' => true, 'redirigir' => $destinos[$fila['codigo_rol']] ?? 'index.html']);
