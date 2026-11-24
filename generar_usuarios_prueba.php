<?php
// =====================================================================
//  Ejecuta este archivo UNA sola vez desde el navegador:
//  http://localhost/PIA/generar_usuarios_prueba.php
//  Crea (o actualiza) usuarios de prueba, uno por cada rol, con la
//  contraseña ya cifrada correctamente con password_hash().
//  BÓRRALO después de usarlo, no debe quedar publicado.
// =====================================================================

require 'conexion.php';

$usuariosPrueba = [
    ['nombre' => 'Administrador Los Aplastaos', 'usuario' => 'admin',   'clave' => 'admin123',  'rol' => 'admin'],
    ['nombre' => 'Cocina Los Aplastaos',        'usuario' => 'cocina1', 'clave' => 'cocina123', 'rol' => 'cocina'],
    ['nombre' => 'Mesero Los Aplastaos',        'usuario' => 'mesero1', 'clave' => 'mesero123', 'rol' => 'salon'],
    ['nombre' => 'Domiciliario Los Aplastaos',  'usuario' => 'domi1',   'clave' => 'domi123',   'rol' => 'domicilio'],
];

$stmtRol = $pdo->prepare("SELECT id_rol FROM roles WHERE codigo_rol = :codigo");
$stmtInsert = $pdo->prepare(
    "INSERT INTO usuarios (nombre_completo, usuario, contrasena, id_rol)
     VALUES (:nombre, :usuario, :clave, :id_rol)
     ON DUPLICATE KEY UPDATE contrasena = :clave2, id_rol = :id_rol2"
);

echo "<h2>Usuarios de prueba</h2><ul>";

foreach ($usuariosPrueba as $u) {
    $stmtRol->execute(['codigo' => $u['rol']]);
    $idRol = $stmtRol->fetchColumn();

    if (!$idRol) {
        echo "<li>⚠️ No existe el rol '{$u['rol']}' en la tabla roles.</li>";
        continue;
    }

    $hash = password_hash($u['clave'], PASSWORD_DEFAULT);

    $stmtInsert->execute([
        'nombre'  => $u['nombre'],
        'usuario' => $u['usuario'],
        'clave'   => $hash,
        'id_rol'  => $idRol,
        'clave2'  => $hash,
        'id_rol2' => $idRol,
    ]);

    echo "<li><strong>{$u['usuario']}</strong> / {$u['clave']} &nbsp;→&nbsp; rol: {$u['rol']}</li>";
}

echo "</ul><p><strong>Borra este archivo (generar_usuarios_prueba.php) ahora que ya lo usaste.</strong></p>";
