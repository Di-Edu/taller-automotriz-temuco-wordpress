/* Taller Temuco — JS mínimo (≈1 KB), sin dependencias. */
(function () {
  'use strict';

  // Menú móvil
  var toggle = document.querySelector('.nav-toggle');
  var nav = document.getElementById('menu-principal');
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  // Mapa diferido: el iframe de Google Maps se carga solo al hacer clic
  document.querySelectorAll('.map[data-map-src]').forEach(function (box) {
    var btn = box.querySelector('.map-load');
    if (!btn) return;
    btn.addEventListener('click', function () {
      var f = document.createElement('iframe');
      f.src = box.getAttribute('data-map-src');
      f.loading = 'lazy';
      f.title = 'Mapa de ubicación';
      f.referrerPolicy = 'no-referrer-when-downgrade';
      box.appendChild(f);
      btn.remove();
    });
  });

  // Medición de conversiones: si el cliente agrega Google Analytics 4 o Tag Manager,
  // cada clic en WhatsApp / llamar / agendar se envía como evento automáticamente.
  document.addEventListener('click', function (e) {
    var el = e.target.closest('[data-track]');
    if (!el) return;
    var name = el.getAttribute('data-track');
    if (typeof window.gtag === 'function') {
      window.gtag('event', 'generate_lead', { method: name });
    }
    if (Array.isArray(window.dataLayer)) {
      window.dataLayer.push({ event: 'cta_click', cta: name });
    }
  });
})();
