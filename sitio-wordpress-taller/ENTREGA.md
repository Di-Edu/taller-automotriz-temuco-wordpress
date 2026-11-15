# Checklist de entrega al cliente

Al cerrar el proyecto se entregan **todos los accesos** para que el cliente sea dueño de su sitio y no dependa del desarrollador. Las contraseñas se entregan por un canal seguro (gestor de contraseñas compartido o enlace de un solo uso), **nunca** en este repositorio.

## 1. Hosting
- [ ] URL del panel del hosting (cPanel / Plesk / panel del proveedor)
- [ ] Usuario y contraseña del panel — **cuenta a nombre del cliente**
- [ ] Datos de facturación y fecha de renovación del plan

## 2. Dominio
- [ ] Registrador (NIC Chile para `.cl` u otro) y cuenta a nombre del cliente
- [ ] Fecha de vencimiento del dominio
- [ ] Configuración DNS documentada

## 3. WordPress
- [ ] URL de acceso: `https://dominio.cl/wp-admin`
- [ ] Usuario **Administrador** del cliente (correo del cliente)
- [ ] Usuario del desarrollador degradado o eliminado, según acuerdo
- [ ] Correo de administración (Ajustes → Generales) apuntando al cliente

## 4. Base de datos y archivos
- [ ] Nombre de la base de datos, usuario y contraseña (están en `wp-config.php`)
- [ ] Acceso a phpMyAdmin desde el panel
- [ ] Cuenta FTP/SFTP: servidor, puerto, usuario y contraseña
- [ ] Respaldo completo (archivos + base de datos) entregado el día de la entrega

## 5. Correo y servicios
- [ ] Cuenta SMTP usada para los avisos de citas
- [ ] Google Search Console verificado y con el cliente como propietario
- [ ] Perfil de Empresa en Google con enlace al sitio y a "Agendar hora"
- [ ] Google Analytics 4 / Tag Manager (si se configuró) con el cliente como administrador

## 6. Capacitación
- [ ] Sesión breve: editar textos (Personalizar), editar servicios, gestionar citas, bloquear feriados
- [ ] Guía rápida del README entregada

## 7. Verificación final
- [ ] SSL activo (candado) y redirección http → https
- [ ] Formulario de agenda probado: llega el correo al taller y al cliente
- [ ] Botones de WhatsApp abren el número correcto
- [ ] Títulos y meta descripciones revisados en las 8 páginas de servicio
- [ ] PageSpeed Insights móvil revisado
- [ ] Sitemap enviado a Search Console (`/wp-sitemap.xml`)
