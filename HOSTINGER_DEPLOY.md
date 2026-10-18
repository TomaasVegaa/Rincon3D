# 🚀 Guía de Despliegue en Hostinger - Rincón 3D

Este proyecto está 100% optimizado y listo para funcionar en **Hostinger** (hPanel, Apache/LiteSpeed, PHP 8.x y MySQL/MariaDB).

---

## 📋 Pasos para Subir a Hostinger

### 1. Crear la Base de Datos en Hostinger
1. Entra a tu panel de control de **Hostinger (hPanel)**.
2. Ve a la sección **Bases de datos** ➔ **Bases de datos MySQL**.
3. Crea una nueva base de datos:
   - **Nombre de la base:** por ejemplo `rincon3d` (Hostinger le pondrá un prefijo como `u123456789_rincon3d`).
   - **Usuario:** por ejemplo `rinconuser`.
   - **Contraseña:** Elige una contraseña segura y anótala.
4. Guarda los datos generados.

---

### 2. Importar la Estructura y Datos (`database.sql`)
1. En la misma pantalla de bases de datos de Hostinger, haz clic en **Entrar a phpMyAdmin** al lado de tu base de datos recién creada.
2. En la parte superior de phpMyAdmin, haz clic en la pestaña **Importar**.
3. Haz clic en **Seleccionar archivo** y elige el archivo `database.sql` de tu proyecto.
4. Baja hasta el final y haz clic en **Importar** (o **Continuar**).
5. ¡Listo! Se habrán creado todas las tablas con los 7 productos iniciales, fotos, categorías y ajustes.

---

### 3. Subir los Archivos de la Web
1. En el hPanel de Hostinger, ve a **Archivos** ➔ **Administrador de archivos** (o conéctate por **FTP con FileZilla**).
2. Entra a la carpeta `public_html/`.
3. Sube todos los archivos y carpetas del proyecto:
   ```text
   public_html/
   ├── admin/
   ├── api/
   ├── assets/
   ├── data/
   ├── config.php
   ├── db.php
   ├── index.php
   └── database.sql
   ```

---

### 4. Conectar `db.php` con Hostinger
Abre el archivo `db.php` desde el editor del Administrador de archivos de Hostinger y completa los datos de tu base:

```php
// En db.php (Líneas 23 a 28 aprox)
define('DB_HOST', 'localhost'); // En Hostinger siempre es localhost
define('DB_NAME', 'u123456789_rincon3d');   // Tu nombre de BD de Hostinger
define('DB_USER', 'u123456789_rinconuser'); // Tu usuario de BD de Hostinger
define('DB_PASS', 'Tu_Contraseña_Aqui');     // La contraseña que creaste
```

Guarda los cambios.

---

### 5. Acceder y Gestionar

* **Tienda pública (Frontend):** `https://tudominio.com/`
* **Panel de Administración (Backend):** `https://tudominio.com/admin/`
  * **Usuario:** `admin`
  * **Contraseña:** `admin123`

---

## 🛠️ ¿Qué puedes hacer desde el Panel Admin?
* **Productos:** Crear, pausar, editar precios, descripciones, especificaciones y subir nuevas fotos.
* **Pedidos:** Ver todos los pedidos que tus clientes armaron en el carrito y enviaron a WhatsApp, con su estado de fabricación 3D.
* **Ajustes:** Cambiar el número de WhatsApp receptor en 1 clic, activar/desactivar banner, cambiar datos de transferencia bancaria y costos de envío.
