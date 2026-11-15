# Sitio web WordPress — Servicio automotriz en Temuco

Sitio WordPress completo y **100% autoadministrable** para un taller / servicio automotriz en Temuco, Chile. Está pensado para convertir visitas en clientes y para posicionarse en búsquedas locales ("frenos Temuco", "scanner automotriz Temuco", etc.).

Incluye un **tema a medida** (`taller-temuco`) y un **plugin de agenda** (`taller-agenda`). No depende de constructores visuales pesados ni de servicios externos de pago.

### ▶ [Ver demo en vivo](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/Di-Edu/taller-automotriz-temuco-wordpress/main/sitio-wordpress-taller/blueprint.json)

La demo instala el tema y el plugin en un WordPress temporal dentro del navegador (WordPress Playground), sin instalar nada. Tarda unos 20 segundos y ya queda con sesión de administrador, así que puedes probar el Home, el mapa de sucursales, las páginas de servicio, agendar una hora en el calendario y verla en **Citas → Calendario** del escritorio. La demo incluye una segunda sucursal, citas de ejemplo para que se vean días ocupados y completos, y una **tienda WooCommerce con productos de ejemplo** (prueba comprar el "Pack Mantención 10.000 km" con retiro en sucursal). La primera carga tarda un poco más porque instala WooCommerce. Los datos se borran al cerrar la pestaña.

---

## Cómo cumple cada requisito

| Requisito | Solución |
|---|---|
| WordPress 100% autoadministrable, sin plataformas externas | Textos del Home, datos de contacto, WhatsApp, colores, testimonios y cifras se editan en **Apariencia → Personalizar** con vista previa en vivo. Las páginas de servicio se editan con el editor de bloques. Las citas se gestionan en el escritorio de WordPress. |
| Home optimizado para conversión | Portada con propuesta de valor + 2 CTA (agendar / WhatsApp), accesos rápidos por servicio, cifras de confianza, "por qué elegirnos", proceso en 3 pasos, testimonios, cobertura + mapa y CTA final. En móvil: barra fija inferior con **Llamar · WhatsApp · Agendar**. |
| 6–8 páginas de servicio con SEO local | Al activar el tema se crean **8 páginas** con contenido orientado a Temuco y alrededores (Padre Las Casas, Labranza, Lautaro…): mantención, frenos, scanner, suspensión y dirección, alineación y balanceo, embrague, aire acondicionado, pre revisión técnica. Cada una con H1 "servicio + en Temuco", título SEO, meta descripción, preguntas frecuentes, enlazado interno y schema `Service` + `BreadcrumbList`. |
| Botón de WhatsApp | Botón flotante en todas las páginas + botones en portada, servicios y CTA, con **mensaje prellenado según el servicio**. Número y mensaje configurables. |
| Sistema de agenda simple | Plugin **Taller Agenda**: shortcode `[taller_agenda]` con **calendario mensual de disponibilidad** (cada día en verde = disponible, ámbar = pocos cupos, rojo = completo, gris = sin atención) y horarios del día marcados como libres u ocupados, en tiempo real. Cupos por horario y **por sucursal**, días de atención, feriados bloqueados, aviso por correo al taller y al cliente. En el escritorio: **Citas → Calendario** (vista mensual con las citas de cada día y los horarios libres), listado con estados (pendiente / confirmada / atendida / cancelada), botón "Confirmar por WhatsApp" y widget de próximas citas. Anti-spam con honeypot, nonce y límite por IP. |
| Ubicación y sucursales (adicional) | Menú **Sucursales**: el cliente agrega cada local con dirección, horario, teléfono y WhatsApp, y lo ubica **haciendo clic en un mapa** o con "Buscar dirección". Aparecen solas en un **mapa interactivo** (OpenStreetMap, sin API key ni costo) en el Home y en Contacto, con botones **Cómo llegar** (Google Maps), **Waze**, Llamar y WhatsApp por sucursal. Acceso rápido "Cómo llegar / Ver mapa" en la barra superior y botón **Ubicación** en la barra móvil. Cada sucursal se declara en los datos estructurados de Google, y la agenda permite elegir sucursal. |
| SEO básico | Caja **SEO (Google)** en cada página con título y meta descripción (contador de caracteres), Open Graph, etiquetas geo, schema `AutoRepair` (negocio local), URLs amigables `/%postname%/`. Si se instala Yoast o Rank Math, el módulo se desactiva solo para no duplicar. |
| Velocidad de carga | Sin jQuery ni fuentes externas (fuente del sistema), íconos SVG en línea, JS ≈4 KB con `defer`, CSS único ≈20 KB, se eliminan emojis y scripts innecesarios de WordPress, CSS de bloques solo cuando se usan, el mapa (Leaflet) se descarga solo al llegar a esa sección, el JS/CSS de la agenda solo en la página de agenda, imágenes `lazy` + `decoding=async`, precarga de la imagen principal. |
| Diseño profesional, moderno y responsive | Diseño mobile-first, accesible (skip-link, foco visible, `aria-*`, `prefers-reduced-motion`), menú desplegable, tarjetas y CTA consistentes. |
| Tienda online y control de stock (adicional, 100% gratuito) | Integración con **WooCommerce** (gratis): tienda con el diseño del tema, carrito con contador en el encabezado, precios en CLP, stock con alerta de stock bajo, productos destacados en el Home y botón **"Consultar por WhatsApp"** en cada producto. Las sucursales se publican solas como puntos de **Retiro en sucursal** en el checkout. **Control de stock del taller:** en cada cita se registran los repuestos usados y se descuentan del inventario al marcarla "Atendida" (y se devuelven si cambia el estado o se elimina), así el stock refleja las ventas online y lo usado en el taller. Al comprar un **pack de servicio** la página de confirmación invita a agendar la hora. Los archivos de WooCommerce solo se cargan en las páginas de tienda. |
| Entrega de accesos | Ver [`ENTREGA.md`](ENTREGA.md): checklist de traspaso de hosting, WordPress, base de datos, dominio y correo. |

Bonus: los clics en WhatsApp / llamar / agendar se envían como eventos a Google Analytics 4 o Tag Manager si el cliente los instala (atributo `data-track`).

---

## Estructura

```
sitio-wordpress-taller/
├── docker-compose.yml                 # Entorno local
├── ENTREGA.md                         # Checklist de entrega de accesos
└── wp-content/
    ├── themes/taller-temuco/
    │   ├── front-page.php             # Home de conversión
    │   ├── page-servicio.php          # Plantilla de servicio (SEO local)
    │   ├── page-contacto.php          # Contacto + mapa de sucursales
    │   ├── page.php · index.php · 404.php
    │   ├── header.php · footer.php    # Menú, WhatsApp flotante, barra móvil
    │   ├── template-parts/            # CTA, mapa diferido, migas de pan
    │   ├── inc/
    │   │   ├── customizer.php         # Opciones autoadministrables
    │   │   ├── branches.php           # Sucursales + mapa interactivo
    │   │   ├── woocommerce.php        # Tienda: carrito, retiro en sucursal, WhatsApp por producto
    │   │   ├── seo.php                # Títulos, meta descripciones, OG, schema
    │   │   ├── performance.php        # Optimización de carga
    │   │   ├── setup-content.php      # Crea páginas, servicios y menú al activar
    │   │   └── helpers.php
    │   └── assets/ (css, js)
    └── plugins/taller-agenda/
        ├── taller-agenda.php
        ├── includes/ (settings, booking, form, admin + calendario, stock de repuestos)
        └── assets/ (agenda.js, agenda.css: calendario de disponibilidad)
```

---

## Probarlo en local (Docker)

1. Instala [Docker Desktop](https://www.docker.com/products/docker-desktop/).
2. En esta carpeta:
   ```bash
   docker compose up -d
   ```
3. Abre http://localhost:8080 e instala WordPress (idioma Español de Chile).
4. **Plugins → Taller Agenda → Activar**.
5. **Apariencia → Temas → Taller Temuco → Activar**. Se crean automáticamente Inicio, Servicios + 8 servicios, Agendar hora, Contacto y el menú principal.
6. **Apariencia → Personalizar → "Taller: …"** para poner los datos reales del negocio.

## Instalar en el hosting

1. Instala WordPress desde el panel del hosting (cPanel / Softaculous / instalador del proveedor).
2. Sube `wp-content/themes/taller-temuco` a `wp-content/themes/` y `wp-content/plugins/taller-agenda` a `wp-content/plugins/` (por FTP o comprimiéndolos en .zip y usando *Añadir nuevo → Subir*).
3. Activa primero el plugin y luego el tema.
4. Configura: **Ajustes → Generales** (nombre, zona horaria America/Santiago), **Citas → Ajustes** (horarios, cupos, correo), **Apariencia → Personalizar** (datos, WhatsApp, textos, logo).
5. Recomendado: certificado SSL (Let's Encrypt), plugin de caché del hosting (LiteSpeed Cache o similar), SMTP para que los correos de citas no caigan en spam (p. ej. WP Mail SMTP), y alta del sitio en Google Search Console + Perfil de Empresa en Google.

## Guía rápida para el cliente

- **Cambiar textos del inicio, teléfono o WhatsApp:** Apariencia → Personalizar.
- **Editar un servicio:** Páginas → (servicio) → editar texto, foto destacada y caja "SEO (Google)".
- **Agregar un servicio nuevo:** Páginas → Añadir nueva → Atributos: Superior = *Servicios*, Plantilla = *Página de servicio (SEO local)*. Aparece solo en el Home, el pie de página y el formulario de agenda.
- **Ver y confirmar citas:** menú **Citas** (listado) o **Citas → Calendario** (vista mensual). Cambia el estado y usa *Confirmar por WhatsApp*.
- **Agregar una sucursal:** menú **Sucursales → Agregar sucursal**: completa los datos y haz clic en el mapa donde está el local. Aparece sola en el mapa del sitio y en la agenda.
- **Bloquear feriados o vacaciones:** Citas → Ajustes → Días bloqueados.
- **Agregar un producto:** Productos → Añadir nuevo. Precio, foto y, en "Inventario", activa *Gestionar inventario* e indica el stock. Marca la estrella ⭐ para destacarlo en el Home.
- **Registrar repuestos usados en una cita:** abre la cita → caja *Repuestos utilizados* → agrega producto y cantidad → marca la cita como *Atendida*. El stock se descuenta solo.
- **Packs de servicio:** crea el producto en la categoría *Packs de servicio* (slug `packs-de-servicio`); al comprarlo, el cliente ve el botón para agendar.

## Tienda: pagos y despacho (todo gratuito)

1. Instala **WooCommerce** (Plugins → Añadir nuevo). El tema detecta la tienda solo, agrega "Tienda" al menú y publica las sucursales como puntos de retiro.
2. **Pagos con tarjeta en Chile** (plugins gratuitos, solo se paga la comisión por venta):
   - **Transbank Webpay Plus** (plugin oficial *Transbank Webpay REST*): débito, crédito y prepago. Viene en modo integración (pruebas) y luego se pasa a producción con el código de comercio.
   - **Mercado Pago** (plugin oficial) o **Flow**.
   - Incluidos en WooCommerce: transferencia bancaria y pago al retirar.
3. **Despacho a domicilio:** WooCommerce → Ajustes → Envío → crea la zona "Temuco y alrededores" con *Tarifa plana* o *Envío gratuito* desde cierto monto.
4. **Moneda:** WooCommerce → Ajustes → General → Peso chileno, 0 decimales, separador de miles ".".
5. **Boleta electrónica (SII):** se puede conectar más adelante con un proveedor de facturación (Bsale, OpenFactura, LibreDTE); estos servicios tienen costo propio.

## Requisitos técnicos

WordPress 6.2+ · PHP 7.4+ (recomendado 8.2) · MySQL 5.7+ / MariaDB 10.4+.
