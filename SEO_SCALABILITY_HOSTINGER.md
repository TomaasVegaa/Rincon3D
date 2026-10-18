# 📈 Plan de Escalabilidad SEO para Rincón 3D (Hostinger)

Este documento detalla la arquitectura de **Posicionamiento Orgánico (SEO)** implementada en el código para que, al apuntar tu dominio definitivo en **Hostinger**, la tienda indexe en los primeros resultados de Google.

---

## 🏗️ 1. Arquitectura de URLs Amigables (Clean Slugs)

### El problema de las tiendas convencionales:
Si los productos solo se abren dentro de una ventana modal en JavaScript, los motores de búsqueda como Googlebot no pueden indexarlos de forma independiente ni posicionar cada pieza por su nombre.

### La solución implementada:
Cada producto cuenta con una URL única, limpia y rastreable:
* **Estructura:** `https://tudominio.com/producto/{slug}`
* **Ejemplo real:** `https://tudominio.com/producto/lampara-litofania-lunar-personalizada`
* **Categorías:** `https://tudominio.com/categoria/iluminacion`

En el archivo [.htaccess](file:///c:/xampp/htdocs/elisa/.htaccess), el servidor Apache de Hostinger reescribe estas rutas internamente hacia [producto.php](file:///c:/xampp/htdocs/elisa/producto.php) sin redirecciones lentas.

---

## 🏷️ 2. Datos Estructurados Schema.org / JSON-LD (Rich Snippets)

Google utiliza microdatos para mostrar **fichas enriquecidas con precio, disponibilidad e imagen** directamente en la página de resultados de búsqueda.

En [producto.php](file:///c:/xampp/htdocs/elisa/producto.php) ya está inyectado el estándar oficial de comercio electrónico:

```json
{
  "@context": "https://schema.org/",
  "@type": "Product",
  "name": "Lámpara Litofanía Lunar Personalizada",
  "image": ["https://tudominio.com/assets/img/lamp_litofania_1.jpg"],
  "description": "Lámpara cilíndrica impresa en 3D con tecnología de litofanía...",
  "brand": {
    "@type": "Brand",
    "name": "Rincón 3D"
  },
  "offers": {
    "@type": "Offer",
    "priceCurrency": "ARS",
    "price": "18500",
    "availability": "https://schema.org/InStock",
    "url": "https://tudominio.com/producto/lampara-litofania-lunar-personalizada"
  }
}
```

En [index.php](file:///c:/xampp/htdocs/elisa/index.php) se implementó el schema `@type: Store` para reforzar la autoridad de marca local en búsquedas como *"impresión 3D cerca de mí"*.

---

## 🗺️ 3. Mapa del Sitio Dinámico (`sitemap.xml`)

Creamos [sitemap.php](file:///c:/xampp/htdocs/elisa/sitemap.php), enlazado mediante `.htaccess` como **`/sitemap.xml`**:
* **Se actualiza automáticamente:** Cuando agregas o editas un producto desde el panel de administración, el sitemap se actualiza al instante sin intervención manual.
* **Soporte de Google Images:** Cada URL incluye la etiqueta `<image:image>` con el enlace y título de la fotografía para posicionar en la pestaña **Google Imágenes**.
* **Prioridad ponderada:** Asigna prioridad `1.0` a la portada y `0.9` a las páginas de productos.

---

## 🤖 4. Control de Rastreo ([robots.txt](file:///c:/xampp/htdocs/elisa/robots.txt))

Permite a los motores rastrear las imágenes, estilos y páginas comerciales, mientras protege las áreas privadas:
* **Indexa:** Portada, productos, categorías, fotos.
* **Bloquea:** `/admin/`, `/api/`, `/data/` y archivos de configuración.
* **Apunta directamente al sitemap:** `Sitemap: https://tudominio.com/sitemap.xml`.

---

## 💬 5. Tarjetas Sociales (OpenGraph & WhatsApp)

Cuando tú o tus clientes compartan el enlace de un producto por **WhatsApp, Instagram o Facebook**, se desplegará una tarjeta visual con:
* Foto principal en alta resolución.
* Título del producto.
* Precio y beneficios (10% OFF en transferencia).
* Nombre de la tienda (*Rincón 3D*).

---

## ⚡ 6. Velocidad de Carga y Core Web Vitals (Hostinger)

Google premia a los sitios rápidos. En [.htaccess](file:///c:/xampp/htdocs/elisa/.htaccess) dejamos configurado:
1. **Compresión Gzip (`mod_deflate`):** Reduce hasta un 70% el peso de archivos HTML, CSS y JS antes de enviarlos al usuario.
2. **Caché de Navegador (`mod_expires`):**
   * Imágenes y fuentes: Guardadas en caché por **1 año**.
   * Hojas de estilo y JavaScript: Guardadas por **1 mes**.
3. **Imágenes diferidas (`loading="lazy"`):** El navegador solo descarga las imágenes a medida que el usuario hace scroll hacia abajo.

---

## 🎯 7. Pasos Finales al Subir a Hostinger

Una vez que tengas tu dominio en Hostinger (ejemplo: `rincon3d.com`):
1. **Google Search Console:**
   * Entra a [search.google.com/search-console](https://search.google.com/search-console).
   * Agrega tu propiedad con el dominio.
   * En la sección **Sitemaps**, envía: `https://tudominio.com/sitemap.xml`.
2. **Google Mi Negocio (Google Business Profile):**
   * Si tienes taller o entregas locales, crea el perfil con el nombre **Rincón 3D - Impresiones 3D y Diseños Personalizados** y enlaza tu web.
3. **Palabras Clave al Crear Productos desde el Admin:**
   * Al titular los productos, incluye palabras que la gente busca: *"Mate 3D Personalizado"*, *"Lámpara Litofanía Fotográfica"*, *"Maceta Origami Geométrica 3D"*.
