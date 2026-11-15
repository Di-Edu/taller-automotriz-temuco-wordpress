# Sitio web WordPress — Servicio automotriz en Temuco

Sitio WordPress completo y **100% autoadministrable** para un taller / servicio automotriz en Temuco, Chile. Está pensado para convertir visitas en clientes y para posicionarse en búsquedas locales ("frenos Temuco", "scanner automotriz Temuco", etc.).

Incluye un **tema a medida** (`taller-temuco`) y un **plugin de agenda** (`taller-agenda`). No depende de constructores visuales pesados ni de servicios externos de pago.

---

## Cómo cumple cada requisito

| Requisito | Solución |
|---|---|
| WordPress 100% autoadministrable, sin plataformas externas | Textos del Home, datos de contacto, WhatsApp, colores, testimonios y cifras se editan en **Apariencia → Personalizar** con vista previa en vivo. Las páginas de servicio se editan con el editor de bloques. Las citas se gestionan en el escritorio de WordPress. |
| Home optimizado para conversión | Portada con propuesta de valor + 2 CTA (agendar / WhatsApp), accesos rápidos por servicio, cifras de confianza, "por qué elegirnos", proceso en 3 pasos, testimonios, cobertura + mapa y CTA final. En móvil: barra fija inferior con **Llamar · WhatsApp · Agendar**. |
| 6–8 páginas de servicio con SEO local | Al activar el tema se crean **8 páginas** con contenido orientado a Temuco y alrededores (Padre Las Casas, Labranza, Lautaro…): mantención, frenos, scanner, suspensión y dirección, alineación y balanceo, embrague, aire acondicionado, pre revisión técnica. Cada una con H1 "servicio + en Temuco", título SEO, meta descripción, preguntas frecuentes, enlazado interno y schema `Service` + `BreadcrumbList`. |
| Botón de WhatsApp | Botón flotante en todas las páginas + botones en portada, servicios y CTA, con **mensaje prellenado según el servicio**. Número y mensaje configurables. |
| Sistema de agenda simple | Plugin **Taller Agenda**: shortcode `[taller_agenda]`, horarios disponibles en tiempo real, cupos por bloque, días de atención, feriados bloqueados, aviso por correo al taller y al cliente, listado de citas con estados (pendiente / confirmada / atendida / cancelada), botón "Confirmar por WhatsApp" y widget de próximas citas. Anti-spam con honeypot, nonce y límite por IP. |
| SEO básico | Caja **SEO (Google)** en cada página con título y meta descripción (contador de caracteres), Open Graph, etiquetas geo, schema `AutoRepair` (negocio local), URLs amigables `/%postname%/`. Si se instala Yoast o Rank Math, el módulo se desactiva solo para no duplicar. |
| Velocidad de carga | Sin jQuery ni fuentes externas (fuente del sistema), íconos SVG en línea, JS ≈1 KB con `defer`, CSS único ≈15 KB, se eliminan emojis y scripts innecesarios de WordPress, CSS de bloques solo cuando se usan, mapa de Google cargado bajo demanda, imágenes `lazy` + `decoding=async`, precarga de la imagen principal. |
| Diseño profesional, moderno y responsive | Diseño mobile-first, accesible (skip-link, foco visible, `aria-*`, `prefers-reduced-motion`), menú desplegable, tarjetas y CTA consistentes. |
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
    │   ├── page-contacto.php          # Contacto + mapa
    │   ├── page.php · index.php · 404.php
    │   ├── header.php · footer.php    # Menú, WhatsApp flotante, barra móvil
    │   ├── template-parts/            # CTA, mapa diferido, migas de pan
    │   ├── inc/
    │   │   ├── customizer.php         # Opciones autoadministrables
    │   │   ├── seo.php                # Títulos, meta descripciones, OG, schema
    │   │   ├── performance.php        # Optimización de carga
    │   │   ├── setup-content.php      # Crea páginas, servicios y menú al activar
    │   │   └── helpers.php
    │   └── assets/ (css, js)
    └── plugins/taller-agenda/
        ├── taller-agenda.php
        └── includes/ (settings, booking, form, admin)
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
- **Ver y confirmar citas:** menú **Citas**. Cambia el estado y usa *Confirmar por WhatsApp*.
- **Bloquear feriados o vacaciones:** Citas → Ajustes → Días bloqueados.

## Requisitos técnicos

WordPress 6.2+ · PHP 7.4+ (recomendado 8.2) · MySQL 5.7+ / MariaDB 10.4+.
