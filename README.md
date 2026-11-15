# Proyectos

Este repositorio reúne dos proyectos:

## 1. [Sitio WordPress para servicio automotriz en Temuco](sitio-wordpress-taller/)

Sitio WordPress 100% autoadministrable, orientado a conversión y SEO local:

- Tema a medida **Taller Temuco**: Home de conversión, 8 páginas de servicio con SEO local, botón de WhatsApp, SEO básico (títulos, meta descripciones, schema `AutoRepair`) y optimización de velocidad.
- Plugin **Taller Agenda**: reserva de horas con calendario de disponibilidad (libre / ocupado) en tiempo real y calendario de citas en el escritorio de WordPress.
- Mapa interactivo de sucursales con "Cómo llegar" (Google Maps / Waze), administrable desde WordPress.
- Tienda online con WooCommerce (gratis): carrito, stock, retiro en sucursal y repuestos usados en cada cita descontados del inventario.
- Entorno local con Docker y checklist de entrega de accesos.

**▶ [Ver demo en vivo](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/Di-Edu/taller-automotriz-temuco-wordpress/main/sitio-wordpress-taller/blueprint.json)** (WordPress temporal en el navegador, sin instalar nada).

Detalle completo en [`sitio-wordpress-taller/README.md`](sitio-wordpress-taller/README.md).

## 2. Registro de Patentes

App web para registrar y buscar vehículos por patente (por ejemplo, en un estacionamiento), con contacto directo por WhatsApp.

- [`Patentes.html`](Patentes.html): versión sin servidor que guarda los datos en el navegador, con importación desde Google Forms (CSV) y respaldos.
- [`Version Online/`](Version%20Online/): versión en Google Apps Script que guarda en Google Sheets, con acceso por PIN. Instrucciones en [`GUIA DE INSTALACION.md`](Version%20Online/GUIA%20DE%20INSTALACION.md).
