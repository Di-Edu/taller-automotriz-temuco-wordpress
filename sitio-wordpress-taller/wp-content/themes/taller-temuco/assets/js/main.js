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

  // Mapa de sucursales (Leaflet + OpenStreetMap). Leaflet se descarga solo cuando
  // el mapa está por aparecer en pantalla, así no afecta la carga inicial.
  var leafletQueue = null;
  function loadLeaflet(box, cb) {
    if (window.L) return cb();
    if (leafletQueue) return leafletQueue.push(cb);
    leafletQueue = [cb];
    var css = document.createElement('link');
    css.rel = 'stylesheet';
    css.href = box.dataset.leafletCss;
    css.integrity = box.dataset.leafletCssSri;
    css.crossOrigin = '';
    document.head.appendChild(css);
    var js = document.createElement('script');
    js.src = box.dataset.leafletJs;
    js.integrity = box.dataset.leafletJsSri;
    js.crossOrigin = '';
    js.onload = function () { leafletQueue.forEach(function (f) { f(); }); };
    js.onerror = function () {
      box.querySelector('.branch-map-placeholder').textContent = 'No se pudo cargar el mapa. Usa "Cómo llegar".';
    };
    document.head.appendChild(js);
  }

  function esc(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function initMap(box) {
    var points = JSON.parse(box.dataset.points || '[]');
    var ph = box.querySelector('.branch-map-placeholder');
    if (!points.length) {
      ph.textContent = 'Ubicación por confirmar. Usa "Cómo llegar".';
      return;
    }
    loadLeaflet(box, function () {
      ph.remove();
      var map = L.map(box, { scrollWheelZoom: false, tap: true });
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
      }).addTo(map);
      var markers = {};
      var bounds = [];
      points.forEach(function (p) {
        var m = L.marker([p.lat, p.lng], { title: p.name }).addTo(map);
        m.bindPopup('<strong>' + esc(p.name) + '</strong><br>' + esc(p.addr) +
          '<br><a href="' + esc(p.dir) + '" target="_blank" rel="noopener">Cómo llegar →</a>');
        markers[p.i] = m;
        bounds.push([p.lat, p.lng]);
      });
      if (bounds.length > 1) {
        map.fitBounds(bounds, { padding: [40, 40] });
      } else {
        map.setView(bounds[0], 16);
        markers[points[0].i].openPopup();
      }
      // Habilita el zoom con la rueda solo después de hacer clic en el mapa (evita "atrapar" el scroll).
      map.once('focus click', function () { map.scrollWheelZoom.enable(); });

      var wrap = box.closest('.branches');
      wrap.querySelectorAll('.branch-focus').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var m = markers[btn.dataset.i];
          if (!m) return;
          map.setView(m.getLatLng(), 16);
          m.openPopup();
          wrap.querySelectorAll('.branch').forEach(function (li) {
            li.classList.toggle('is-active', li.dataset.i === btn.dataset.i);
          });
          if (window.innerWidth < 900) box.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
      });
    });
  }

  var maps = document.querySelectorAll('.branch-map');
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { io.unobserve(e.target); initMap(e.target); }
      });
    }, { rootMargin: '300px' });
    maps.forEach(function (m) { io.observe(m); });
  } else {
    maps.forEach(initMap);
  }

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
