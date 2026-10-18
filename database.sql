-- =============================================================================
-- BASE DE DATOS: RINCÓN 3D
-- Compatible con XAMPP (Local) y Hostinger (Producción / MySQL 5.7+ / MariaDB)
-- =============================================================================

-- Para hosting compartido (InfinityFree / Hostinger), la base de datos ya está creada.
-- Se importan las tablas directamente en tu base de datos activa.

-- -----------------------------------------------------------------------------
-- 1. TABLA: configuracion (Ajustes en vivo que modifican el Frontend)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `configuracion` (
  `clave` VARCHAR(50) NOT NULL PRIMARY KEY,
  `valor` TEXT NOT NULL,
  `descripcion` VARCHAR(255) NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `configuracion` (`clave`, `valor`, `descripcion`) VALUES
('store_name', 'Rincón 3D', 'Nombre comercial de la tienda'),
('store_tagline', 'Impresiones 3D & Diseños Personalizados', 'Subtítulo o eslogan'),
('whatsapp_phone', '5491122334455', 'Teléfono donde llegan los pedidos de WhatsApp'),
('currency_symbol', '$', 'Símbolo de moneda'),
('shipping_cost', '3500', 'Costo de envío a domicilio'),
('free_shipping_threshold', '35000', 'Monto mínimo para envío gratis'),
('bank_alias', 'RINCON.3D.MP', 'Alias bancario para transferencias'),
('bank_cbu', '0000003100085469213456', 'CBU bancario'),
('bank_holder', 'Elisa - Rincón 3D', 'Titular de la cuenta'),
('bank_discount_percent', '10', 'Porcentaje de descuento por pago con transferencia'),
('banner_visible', '1', '1 para mostrar el banner de marca, 0 para ocultarlo'),
('banner_title', 'Arte, diseño y tecnología en cada rincón', 'Título principal del banner'),
('banner_description', 'Fabricamos piezas únicas con filamentos biodegradables de primera calidad. Desde recuerdos personalizados en litofanía y mates térmicos hasta iluminación ambiental y decoración.', 'Descripción del banner')
ON DUPLICATE KEY UPDATE `valor` = VALUES(`valor`);

-- -----------------------------------------------------------------------------
-- 2. TABLA: usuarios (Administradores del panel)
-- Usuario por defecto: admin / admin123
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `rol` ENUM('admin', 'editor') DEFAULT 'admin',
  `activo` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `usuarios` (`id`, `username`, `password_hash`, `nombre`, `email`, `rol`) VALUES
(1, 'elisa', '$2y$10$GY9Q8pNw.XxPz4nwNpwyZenRnWOxC0/F47QrU/nUTa/DrzrTt4yJW', 'Elisa - Rincón 3D', 'elisa@rincon3d.com', 'admin')
ON DUPLICATE KEY UPDATE `username` = VALUES(`username`), `password_hash` = VALUES(`password_hash`);

-- -----------------------------------------------------------------------------
-- 3. TABLA: categorias
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categorias` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `slug` VARCHAR(50) NOT NULL UNIQUE,
  `nombre` VARCHAR(100) NOT NULL,
  `icono` VARCHAR(50) DEFAULT '✦',
  `orden` INT DEFAULT 0,
  `activo` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categorias` (`id`, `slug`, `nombre`, `icono`, `orden`) VALUES
(1, 'iluminacion', 'Iluminación & Lámparas', '💡', 1),
(2, 'mates', 'Mates & Bazar', '🧉', 2),
(3, 'decoracion', 'Decoración & Plantas', '🪴', 3),
(4, 'organizacion', 'Organización & Oficina', '📦', 4),
(5, 'figuras', 'Figuras & Coleccionables', '🐉', 5)
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- -----------------------------------------------------------------------------
-- 4. TABLA: productos
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `productos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `categoria_id` INT NOT NULL,
  `titulo` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(220) NOT NULL,
  `descripcion` TEXT NOT NULL,
  `precio` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `badge` VARCHAR(50) NULL,
  `customizable` TINYINT(1) DEFAULT 0,
  `tiempo_produccion` VARCHAR(100) DEFAULT '24 a 48 hs',
  `activo` TINYINT(1) DEFAULT 1,
  `destacado` TINYINT(1) DEFAULT 0,
  `orden` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_prod_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. TABLA: producto_imagenes (Galería / Pasarela de fotos de cada producto)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `producto_imagenes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `producto_id` INT NOT NULL,
  `url_imagen` VARCHAR(255) NOT NULL,
  `orden` INT DEFAULT 0,
  `es_principal` TINYINT(1) DEFAULT 0,
  CONSTRAINT `fk_img_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 6. TABLA: producto_colores (Colores disponibles de filamento)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `producto_colores` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `producto_id` INT NOT NULL,
  `nombre_color` VARCHAR(80) NOT NULL,
  `codigo_hex` VARCHAR(20) DEFAULT '#651226',
  CONSTRAINT `fk_col_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 7. TABLA: producto_especificaciones (Ficha técnica de impresión 3D)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `producto_especificaciones` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `producto_id` INT NOT NULL,
  `clave` VARCHAR(100) NOT NULL,
  `valor` VARCHAR(255) NOT NULL,
  CONSTRAINT `fk_spec_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 8. TABLA: pedidos (Registro de compras realizadas por la web y WhatsApp)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pedidos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo_pedido` VARCHAR(30) NOT NULL UNIQUE,
  `cliente_nombre` VARCHAR(120) NOT NULL,
  `cliente_telefono` VARCHAR(50) NOT NULL,
  `modalidad_entrega` ENUM('envio', 'retiro') DEFAULT 'envio',
  `direccion` TEXT NULL,
  `forma_pago` ENUM('transferencia', 'efectivo') DEFAULT 'transferencia',
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `costo_envio` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `descuento` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `notas` TEXT NULL,
  `estado` ENUM('nuevo', 'en_produccion', 'listo', 'entregado', 'cancelado') DEFAULT 'nuevo',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 9. TABLA: pedido_items
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pedido_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pedido_id` INT NOT NULL,
  `producto_id` INT NULL,
  `titulo` VARCHAR(200) NOT NULL,
  `color` VARCHAR(80) NULL,
  `personalizacion` TEXT NULL,
  `precio_unitario` DECIMAL(12,2) NOT NULL,
  `cantidad` INT NOT NULL DEFAULT 1,
  `subtotal` DECIMAL(12,2) NOT NULL,
  CONSTRAINT `fk_item_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- DATOS SEMILLA: PRODUCTOS INICIALES
-- -----------------------------------------------------------------------------
INSERT INTO `productos` (`id`, `categoria_id`, `titulo`, `slug`, `descripcion`, `precio`, `badge`, `customizable`, `tiempo_produccion`, `activo`, `destacado`, `orden`) VALUES
(1, 1, 'Lámpara Litofanía Lunar Personalizada', 'lampara-litofania-lunar-personalizada', 'Lámpara cilíndrica impresa en 3D con tecnología de litofanía de alta precisión sobre base de madera lustrada. Al encenderse, proyecta con calidez tus fotografías familiares o momentos favoritos con increíble relieve. Incluye luz LED cálida de bajo consumo y conexión USB.', 18500.00, 'Más Vendido', 1, '48 a 72 hs', 1, 1, 1),
(2, 2, 'Mate Geométrico Facetado 3D con Bombilla', 'mate-geometrico-facetado-3d-con-bombilla', 'Mate con diseño poligonal facetado moderno y textura agradable al tacto. Incluye vaso interior térmico de polímero apto alimentos (BPA Free), no transmite calor a las manos y es sumamente fácil de limpiar con el método sacayerba. Incluye bombilla de acero inoxidable a tono.', 9200.00, 'Exclusivo', 1, '24 a 48 hs', 1, 1, 2),
(3, 3, 'Maceta Origami Geométrica con Drenaje', 'maceta-origami-geometrica-con-drenaje', 'Maceta decorativa inspirada en el plegado de papel origami para suculentas, cactus o plantas de interior. Cuenta con plato recolector de agua oculto integrado y orificio de drenaje para mantener las raíces oxigenadas sin manchar superficies.', 6800.00, 'Tendencia', 0, '24 hs', 1, 0, 3),
(4, 1, 'Lámpara Espiral Vortex Iluminación Cálida', 'lampara-espiral-vortex-iluminacion-calida', 'Lámpara de diseño paramétrico con curvas fluidas en espiral que tamizan la luz de manera hipnótica. Perfecta para mesas de luz, rincones de lectura o living. Aporta calidez y un punto focal contemporáneo a cualquier ambiente.', 16400.00, 'Edición Especial', 0, '48 hs', 1, 1, 4),
(5, 4, 'Organizador Modular Hexagonal de Escritorio', 'organizador-modular-hexagonal-de-escritorio', 'Set modular de 3 piezas hexagonales combinables: cubículo alto para lapiceras y pinceles, cubículo medio con soporte para smartphone y ranura para notas, y bandeja baja para clips o pendrives. Mantén tu espacio ordenado con distinción.', 8900.00, 'Práctico', 1, '24 a 48 hs', 1, 0, 5),
(6, 5, 'Dragón Articulado Cristal Fidget de Colección', 'dragon-articulado-cristal-fidget-de-coleccion', 'Espectacular dragón articulado impreso en una sola pieza continua mediante técnica Print-in-Place. Cada una de sus escamas y vértebras tiene movimiento fluido y satisfactorio. Ideal como objeto antiestrés, elemento de colección o regalo inolvidable.', 11500.00, 'Coleccionable', 0, '48 hs', 1, 1, 6),
(7, 4, 'Soporte Gamer Escultórico para Auriculares', 'soporte-gamer-escultorico-para-auriculares', 'Soporte ergonómico con curvas estilizadas que cuida la diadema y almohadillas de tus auriculares sin marcarlos. Posee una base sólida y canal posterior para enrollar el cable sobrante. Aporta orden y sofisticación a tu estación de trabajo o juegos.', 12800.00, 'Setup Pro', 1, '24 a 48 hs', 1, 0, 7)
ON DUPLICATE KEY UPDATE `titulo` = VALUES(`titulo`);

-- Imágenes por producto
INSERT INTO `producto_imagenes` (`producto_id`, `url_imagen`, `orden`, `es_principal`) VALUES
(1, 'assets/img/lamp_litofania_1.jpg', 1, 1),
(1, 'assets/img/lamp_litofania_2.jpg', 2, 0),
(2, 'assets/img/mate_geometrico_1.jpg', 1, 1),
(2, 'assets/img/mate_geometrico_2.jpg', 2, 0),
(3, 'assets/img/maceta_origami_1.jpg', 1, 1),
(3, 'assets/img/maceta_origami_2.jpg', 2, 0),
(4, 'assets/img/lampara_vortex_1.jpg', 1, 1),
(4, 'assets/img/lampara_vortex_2.jpg', 2, 0),
(5, 'assets/img/organizador_hex_1.jpg', 1, 1),
(5, 'assets/img/organizador_hex_2.jpg', 2, 0),
(6, 'assets/img/figura_dragon_1.jpg', 1, 1),
(6, 'assets/img/figura_dragon_2.jpg', 2, 0),
(7, 'assets/img/soporte_auricular_1.jpg', 1, 1),
(7, 'assets/img/soporte_auricular_2.jpg', 2, 0);

-- Colores por producto
INSERT INTO `producto_colores` (`producto_id`, `nombre_color`, `codigo_hex`) VALUES
(1, 'Blanco Cálido Litofánico', '#FFFDF9'),
(1, 'Marfil Clásico', '#F7EBD0'),
(1, 'Rosa Suave Difuso', '#F5E5E5'),
(2, 'Borgoña Rincón 3D', '#651226'),
(2, 'Rosa Empolvado', '#F3D8D7'),
(2, 'Negro Satinado', '#1E1E1E'),
(2, 'Oro Rosa Seda', '#C5983F'),
(3, 'Blanco Mármol', '#FAFAFA'),
(3, 'Rosa Pastel', '#F3D8D7'),
(3, 'Verde Salvia', '#8A9A86'),
(3, 'Borgoña Mate', '#651226'),
(4, 'Borgoña Rincón 3D', '#651226'),
(4, 'Rosa Nude Cálido', '#F5E5E5'),
(4, 'Blanco Translúcido', '#FFFFFF'),
(5, 'Dúo Borgoña & Rosa', '#651226'),
(5, 'Blanco Seda', '#FFFFFF'),
(5, 'Gris Grafito Minimal', '#4A4A4A'),
(6, 'Seda Borgoña & Cobre', '#651226'),
(6, 'Seda Rosa & Dorado', '#C5983F'),
(6, 'Negro Obsidiana', '#1C1C1C'),
(7, 'Borgoña Rincón 3D', '#651226'),
(7, 'Negro Mate', '#1E1E1E'),
(7, 'Blanco Puro', '#FFFFFF');

-- Especificaciones técnicas
INSERT INTO `producto_especificaciones` (`producto_id`, `clave`, `valor`) VALUES
(1, 'Material', 'PLA Premium Biodegradable translúcido'),
(1, 'Dimensiones', '18 cm de alto × 10 cm de diámetro'),
(1, 'Base', 'Madera natural con acabado ecológico'),
(1, 'Iluminación', 'Módulo LED cálido USB con interruptor'),
(1, 'Tiempo de fabricación', '48 a 72 horas hábiles'),
(2, 'Material exterior', 'PLA de alta resistencia con estrías poligonales'),
(2, 'Vaso interior', 'Polímero térmico removible libre de BPA'),
(2, 'Accesorios', 'Bombilla metálica con resorte desarmable'),
(2, 'Capacidad', '40 g de yerba mate'),
(3, 'Material', 'PLA ecológico no tóxico'),
(3, 'Dimensiones', '12 cm de alto × 13 cm de boca'),
(3, 'Sistema de riego', 'Platillo recolector desmontable invisible'),
(4, 'Material', 'PLA translúcido de grado óptico'),
(4, 'Dimensiones', '22 cm de altura × 12 cm de diámetro'),
(4, 'Portalámparas', 'Rosca universal E27 con lámpara LED cálida incluida'),
(5, 'Módulos', '3 bloques hexagonales independientes'),
(5, 'Material', 'PLA reforzado con terminación satinada'),
(5, 'Base', 'Gomas protectoras anti-rayaduras'),
(6, 'Largo total', '45 cm desplegado'),
(6, 'Articulación', 'Más de 30 segmentos móviles'),
(6, 'Material', 'PLA Silk con reflejos tornasolados'),
(7, 'Altura', '26 cm'),
(7, 'Estructura', 'Relleno giroidal de alta resistencia'),
(7, 'Compatibilidad', 'Universal para cualquier auricular');
