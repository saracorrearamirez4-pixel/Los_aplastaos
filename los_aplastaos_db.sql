-- =====================================================================
--  Base de datos: los_aplastaos
--  Proyecto PIA - "Los Aplastao's"
--  Generada a partir de los archivos reales: index.html, menu.html,
--  cocina.html, domicilios.html, admin.html y procesar_pedido.php
--  Compatible con XAMPP (MySQL / MariaDB) - importar desde phpMyAdmin
-- =====================================================================

DROP DATABASE IF EXISTS los_aplastaos;
CREATE DATABASE los_aplastaos
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE los_aplastaos;

-- ---------------------------------------------------------------------
-- 1. ROLES
--    Los valores coinciden EXACTO con el <select id="role"> de index.html
--    (admin, cocina, salon, domicilio) para que el login pueda comparar
--    directamente lo que el usuario selecciona con lo guardado en la BD.
-- ---------------------------------------------------------------------
CREATE TABLE roles (
  id_rol     INT AUTO_INCREMENT PRIMARY KEY,
  codigo_rol VARCHAR(20) NOT NULL UNIQUE,  -- admin | cocina | salon | domicilio
  nombre_rol VARCHAR(40) NOT NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. USUARIOS
--    Corresponde al formulario de index.html: usuario, rol y contraseña.
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
  id_usuario      INT AUTO_INCREMENT PRIMARY KEY,
  nombre_completo VARCHAR(100) NOT NULL,
  usuario         VARCHAR(50)  NOT NULL UNIQUE,
  contrasena      VARCHAR(255) NOT NULL,  -- SIEMPRE con password_hash() de PHP, nunca texto plano
  id_rol          INT NOT NULL,
  activo          TINYINT(1) NOT NULL DEFAULT 1,
  fecha_creacion  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. CATEGORÍAS DEL MENÚ
--    Coinciden con los 3 bloques de menu.html: Aplastaos, Bebidas, Adiciones
-- ---------------------------------------------------------------------
CREATE TABLE categorias (
  id_categoria     INT AUTO_INCREMENT PRIMARY KEY,
  nombre_categoria VARCHAR(50) NOT NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. PRODUCTOS
--    codigo_form = el atributo "name" del <input> en menu.html, para que
--    procesar_pedido.php pueda recorrer $_POST y encontrar el producto
--    directamente por ese código, sin tener que hardcodear cada nombre.
-- ---------------------------------------------------------------------
CREATE TABLE productos (
  id_producto     INT AUTO_INCREMENT PRIMARY KEY,
  id_categoria    INT NOT NULL,
  codigo_form     VARCHAR(60) NOT NULL UNIQUE,  -- name del input en el HTML
  nombre_producto VARCHAR(100) NOT NULL,
  descripcion     VARCHAR(255),
  precio          DECIMAL(10,2) NOT NULL,
  disponible      TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. CLIENTES
--    Para domicilios.html (Cliente, Teléfono, Dirección, Sector)
-- ---------------------------------------------------------------------
CREATE TABLE clientes (
  id_cliente     INT AUTO_INCREMENT PRIMARY KEY,
  nombre_cliente VARCHAR(100) NOT NULL,
  telefono       VARCHAR(20),
  direccion      VARCHAR(200),
  sector         VARCHAR(100)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6. PEDIDOS
--    Cubre lo que envía menu.html (mesa + forma de pago) y lo que se
--    muestra en cocina.html / domicilios.html (estado, cliente).
-- ---------------------------------------------------------------------
CREATE TABLE pedidos (
  id_pedido      INT AUTO_INCREMENT PRIMARY KEY,
  codigo_comanda VARCHAR(20) NOT NULL UNIQUE,   -- ej: #001, #002 (como en cocina.html)
  id_usuario     INT NULL,                      -- mesero que tomó el pedido
  tipo_pedido    ENUM('salon','domicilio') NOT NULL DEFAULT 'salon',
  mesa           TINYINT NULL,                  -- 1 a 8, como en el <select> de menu.html
  id_cliente     INT NULL,                      -- solo si tipo_pedido = domicilio
  forma_pago     ENUM('Efectivo','Transferencia','Tarjeta') NOT NULL,
  estado         ENUM('pendiente','listo','entregado','cancelado') NOT NULL DEFAULT 'pendiente',
  fecha_hora     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
  FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 7. DETALLE DEL PEDIDO
--    Un registro por cada producto con cantidad > 0 que el mesero marcó
--    en menu.html.
-- ---------------------------------------------------------------------
CREATE TABLE detalle_pedido (
  id_detalle      INT AUTO_INCREMENT PRIMARY KEY,
  id_pedido       INT NOT NULL,
  id_producto     INT NOT NULL,
  cantidad        INT NOT NULL DEFAULT 1,
  precio_unitario DECIMAL(10,2) NOT NULL,   -- se copia el precio al momento del pedido
  subtotal        DECIMAL(10,2) GENERATED ALWAYS AS (cantidad * precio_unitario) STORED,
  FOREIGN KEY (id_pedido)   REFERENCES pedidos(id_pedido),
  FOREIGN KEY (id_producto) REFERENCES productos(id_producto)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 8. ARQUEO DE CAJA
--    Corresponde exactamente a la tabla de admin.html:
--    Efectivo, Transferencias, Tarjeta, Total.
-- ---------------------------------------------------------------------
CREATE TABLE arqueo_caja (
  id_arqueo      INT AUTO_INCREMENT PRIMARY KEY,
  fecha          DATE NOT NULL UNIQUE,
  id_usuario     INT NULL,  -- administrador que cierra la caja
  total_efectivo DECIMAL(10,2) NOT NULL DEFAULT 0,
  total_transferencia DECIMAL(10,2) NOT NULL DEFAULT 0,
  total_tarjeta  DECIMAL(10,2) NOT NULL DEFAULT 0,
  total_general  DECIMAL(10,2) GENERATED ALWAYS AS
                 (total_efectivo + total_transferencia + total_tarjeta) STORED,
  cerrado        TINYINT(1) NOT NULL DEFAULT 0,
  fecha_cierre   DATETIME NULL,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB;

-- =====================================================================
--  ÍNDICES
-- =====================================================================
CREATE INDEX idx_pedidos_estado ON pedidos(estado);
CREATE INDEX idx_pedidos_tipo   ON pedidos(tipo_pedido);
CREATE INDEX idx_detalle_pedido ON detalle_pedido(id_pedido);

-- =====================================================================
--  DATOS INICIALES
-- =====================================================================

INSERT INTO roles (codigo_rol, nombre_rol) VALUES
('admin', 'Administrador / Dueño'),
('cocina', 'Cocina'),
('salon', 'Mesero'),
('domicilio', 'Domiciliario');

INSERT INTO categorias (nombre_categoria) VALUES
('Aplastaos'), ('Bebidas'), ('Adiciones');

-- Usuarios de prueba (contraseñas ya cifradas con bcrypt, compatibles con password_verify de PHP)
-- admin/admin123  cocina1/cocina123  mesero1/mesero123  domi1/domi123
INSERT INTO usuarios (nombre_completo, usuario, contrasena, id_rol) VALUES
('Administrador Los Aplastaos','admin','$2b$10$xqnts0smPnjtBzceg2K5buowq859San8GmiDUCtQmlKJs1QeWEuf2',(SELECT id_rol FROM roles WHERE codigo_rol='admin')),
('Cocina Los Aplastaos','cocina1','$2b$10$6Vi/mYD0dvc4VUOr4Uip2OmO0LaQhkb3ViY9p5taDLGBpZs9HeJI6',(SELECT id_rol FROM roles WHERE codigo_rol='cocina')),
('Mesero Los Aplastaos','mesero1','$2b$10$DEpreVZ5jqX7qKIY4jVBQ.0IDRDcw.AmgIQ/HmFu0p1KJTYVXNORC',(SELECT id_rol FROM roles WHERE codigo_rol='salon')),
('Domiciliario Los Aplastaos','domi1','$2b$10$yG9nbf/3KQqJgcc6UEstyeIM2DTsYNGMwoOHeI5VBizSlbUKd9BiW',(SELECT id_rol FROM roles WHERE codigo_rol='domicilio'));

-- Productos: copiados exactamente de menu.html (nombre, precio, código de input)
INSERT INTO productos (id_categoria, codigo_form, nombre_producto, descripcion, precio) VALUES
-- Aplastaos
((SELECT id_categoria FROM categorias WHERE nombre_categoria='Aplastaos'), 'aplastao_super',     'Aplastao Súper',       'Pollo, cañón, jamón, tocineta', 11000),
((SELECT id_categoria FROM categorias WHERE nombre_categoria='Aplastaos'), 'aplastao_mixto',     'Aplastao Mixto',       'Pollo y cañón', 8000),
((SELECT id_categoria FROM categorias WHERE nombre_categoria='Aplastaos'), 'aplastacon',         'Aplastacón',           'Nuestra nueva propuesta innovadora', 15000),
((SELECT id_categoria FROM categorias WHERE nombre_categoria='Aplastaos'), 'aplastao_especial',  'Aplastao Especial',    'Con tocineta crujiente y queso extra', 16000),
((SELECT id_categoria FROM categorias WHERE nombre_categoria='Aplastaos'), 'aplastao_hawaiano',  'Aplastao Hawaiano',    'Aplastado con piña dulce y jamón', 14500),
((SELECT id_categoria FROM categorias WHERE nombre_categoria='Aplastaos'), 'aplastao_doble',     'Aplastao Doble Carne', 'Doble porción de carne smashed', 18000),
-- Bebidas
((SELECT id_categoria FROM categorias WHERE nombre_categoria='Bebidas'), 'bebida_gaseosa_personal', 'Gaseosa Personal',       'Coca-Cola / Postobón 400ml', 4000),
((SELECT id_categoria FROM categorias WHERE nombre_categoria='Bebidas'), 'bebida_agua',              'Agua Embotellada',       'Agua con o sin gas 500ml', 3000),
((SELECT id_categoria FROM categorias WHERE nombre_categoria='Bebidas'), 'bebida_jugo_agua',         'Jugo Natural en Agua',   'Sabores de temporada', 5000),
((SELECT id_categoria FROM categorias WHERE nombre_categoria='Bebidas'), 'bebida_jugo_leche',        'Jugo Natural en Leche',  'Sabores de temporada con leche', 6000),
((SELECT id_categoria FROM categorias WHERE nombre_categoria='Bebidas'), 'bebida_te_helado',         'Té Helado',              'Té frío de limón o durazno', 4500),
((SELECT id_categoria FROM categorias WHERE nombre_categoria='Bebidas'), 'bebida_limonada_natural',  'Limonada Natural',       'Limonada recién exprimida', 5000),
((SELECT id_categoria FROM categorias WHERE nombre_categoria='Bebidas'), 'bebida_limonada_cerezada', 'Limonada Cerezada',      'Con toque especial de cereza', 6500),
((SELECT id_categoria FROM categorias WHERE nombre_categoria='Bebidas'), 'bebida_limonada_coco',     'Limonada de Coco',      'Cremosa combinación con coco', 7000),
((SELECT id_categoria FROM categorias WHERE nombre_categoria='Bebidas'), 'bebida_cerveza_nacional',  'Cerveza Nacional',       'Lata o botella bien fría', 6000),
((SELECT id_categoria FROM categorias WHERE nombre_categoria='Bebidas'), 'bebida_cerveza_importada', 'Cerveza Importada',      'Marcas seleccionadas', 9000),
-- Adiciones
((SELECT id_categoria FROM categorias WHERE nombre_categoria='Adiciones'), 'adicion_queso',    'Adición de Queso',            'Porción extra de queso derretido', 3000),
((SELECT id_categoria FROM categorias WHERE nombre_categoria='Adiciones'), 'adicion_tocineta', 'Adición de Tocineta',         'Lonjas de tocineta crocante', 4000),
((SELECT id_categoria FROM categorias WHERE nombre_categoria='Adiciones'), 'adicion_carne',    'Adición de Carne',            'Porción adicional de carne smash', 5000),
((SELECT id_categoria FROM categorias WHERE nombre_categoria='Adiciones'), 'adicion_papas',    'Adición de Papas Fritas',     'Porción individual de papas', 4500),
((SELECT id_categoria FROM categorias WHERE nombre_categoria='Adiciones'), 'adicion_salsas',   'Adición de Salsas de la Casa','Salsa de ajo, rosada o especial', 2000);

-- =====================================================================
--  FIN DEL SCRIPT
-- =====================================================================
