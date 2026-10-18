# Rincón 3D - Tienda Online & Panel de Gestión 🖨️✨

Plataforma integral de comercio electrónico y panel de administración para estudio de diseño e impresión 3D, desarrollada en **PHP moderno**, **MySQL (PDO)**, **Vanilla CSS** y **JavaScript ES6+**.

---

## 🌟 Características Principales

### 🛍️ Frente de Tienda (Storefront)
- **Catálogo Dinámico e Interactivo:** Filtrado instantáneo por categorías dinámicas, buscador en vivo y ordenamiento por precio o destacados.
- **Galería Modal & Vistas Detalladas:** Previsualización rápida con carrusel de fotografías en alta resolución, selector de filamentos/colores y personalización de texto.
- **Checkout Optimizado vía WhatsApp:** Carrito reactivo con cálculo automático de envíos, bonificación por transferencia bancaria y generación de mensaje estructurado para WhatsApp.
- **Arquitectura SEO & SSR:**
  - Páginas de producto individuales indexables (`/producto.php?id=...`).
  - Metadatos enriquecidos **OpenGraph** y **Twitter Cards** para compartir en redes.
  - Datos estructurados **Schema.org JSON-LD** (`Product`, `Offer`, `WebSite`, `Organization`).
  - Generación dinámica de `sitemap.xml` (`sitemap.php`) con etiquetas `image:image` de Google.
  - Reglas de compresión Gzip, mod_rewrite y caché en `.htaccess`.

### 🛡️ Panel de Administración (`/admin`)
- **Acceso Seguro:** Autenticación protegida por sesiones PHP (`elisa` / `elisa123`).
- **CRUD Completo de Productos:**
  - Creación, edición, activación/pausa y eliminación de productos.
  - Carga y ordenamiento de múltiples imágenes.
  - Asignación de paleta de filamentos/colores y especificaciones técnicas.
- **CRUD Completo de Categorías (`/admin/categorias.php`):**
  - Creación y edición con selector de emojis/iconos y slug automático.
  - Conteo de productos asociados en tiempo real.
  - Activación/pausa de categorías sin romper productos existentes.
- **Gestión de Pedidos (`/admin/pedidos.php`):** Registro de pedidos y cambio de estado (`Pendiente`, `En producción`, `Listo para entrega`, `Completado`).
- **Ajustes de la Tienda (`/admin/configuracion.php`):** Configuración de WhatsApp comercial, CBU/Alias, costo de envío y umbral de envío gratis.

---

## 🚀 Requisitos e Instalación

### Requisitos:
- **PHP 7.4** o superior (recomendado PHP 8.x) con extensiones `pdo`, `pdo_mysql`, `json`, `mbstring`.
- **MySQL / MariaDB 5.7+**.
- Servidor web **Apache** con módulo `mod_rewrite` habilitado (XAMPP, Laragon, Hostinger, cPanel, etc.).

### Pasos de Instalación Local (XAMPP):
1. Clonar el repositorio dentro de tu directorio web:
   ```bash
   cd c:/xampp/htdocs
   git clone https://github.com/TomaasVegaa/Rincon3D.git
   ```
2. Crear la base de datos en phpMyAdmin:
   - Nombre: `rincon3d`
   - Cotejamiento: `utf8mb4_unicode_ci`
3. Importar el archivo `database.sql` incluido en la raíz.
4. Abrir la tienda en tu navegador:
   - **Tienda:** `http://localhost/Rincon3D/`
   - **Admin:** `http://localhost/Rincon3D/admin/`
   - **Diagnóstico:** `http://localhost/Rincon3D/check.php`

---

## 🗄️ Estructura del Proyecto

```text
├── admin/                     # Panel de administración
│   ├── admin.css             # Estilos del panel
│   ├── auth.php              # Control de sesión y credenciales
│   ├── categorias.php        # CRUD de categorías
│   ├── configuracion.php     # Ajustes generales
│   ├── index.php             # Lista de productos
│   ├── login.php             # Formulario de inicio de sesión
│   ├── logout.php            # Cierre de sesión
│   ├── pedidos.php           # Gestión de pedidos
│   └── producto.php          # Editor / Creador de productos
├── api/
│   └── save_order.php        # Endpoint de registro de pedidos
├── assets/
│   ├── css/style.css         # Estilos visuales de la tienda
│   ├── img/                  # Imágenes de productos y logotipos
│   └── js/app.js             # Lógica cliente (catálogo, filtros, carrito)
├── data/
│   └── products.json         # Respaldo JSON de productos
├── .htaccess                 # Reglas mod_rewrite, compresión y caché
├── .gitignore                # Archivos ignorados por Git
├── check.php                 # Script de diagnóstico de conexión y PHP
├── config.php                # Constantes, obtención de datos y catálogo
├── database.sql              # Estructura y datos iniciales de MySQL
├── db.php                    # Conexión PDO con detección automática local/hosting
├── index.php                 # Portada de la tienda
├── producto.php              # Landing individual de producto (SEO / SSR)
├── robots.txt                # Directivas para motores de búsqueda
├── sitemap.php               # Generador dinámico de mapa del sitio XML
├── HOSTINGER_DEPLOY.md       # Guía de despliegue en Hostinger
└── SEO_SCALABILITY_HOSTINGER.md # Plan de escalabilidad y SEO
```

---

## 🔒 Acceso por Defecto al Admin
- **Usuario:** `elisa`
- **Contraseña:** `elisa123`
*(Recomendado cambiar la contraseña en producción)*

---

## 📄 Licencia
Proyecto desarrollado para **Rincón 3D**. Todos los derechos reservados.
