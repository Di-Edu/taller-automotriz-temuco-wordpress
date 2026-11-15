/* Taller Agenda — calendario de disponibilidad (sin dependencias). */
(function () {
  'use strict';

  var DOW = ['Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá', 'Do'];
  var fmtMonth = new Intl.DateTimeFormat('es-CL', { month: 'long', year: 'numeric', timeZone: 'UTC' });
  var fmtDay = new Intl.DateTimeFormat('es-CL', { weekday: 'long', day: 'numeric', month: 'long', timeZone: 'UTC' });

  function utc(iso) { return new Date(iso + 'T00:00:00Z'); }
  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function addMonths(ym, n) {
    var y = +ym.slice(0, 4), m = +ym.slice(5, 7) - 1 + n;
    y += Math.floor(m / 12); m = ((m % 12) + 12) % 12;
    return y + '-' + pad(m + 1);
  }
  function el(tag, cls, text) {
    var e = document.createElement(tag);
    if (cls) e.className = cls;
    if (text != null) e.textContent = text;
    return e;
  }

  function init(form) {
    var min = form.dataset.min, max = form.dataset.max, today = form.dataset.today;
    var fecha = form.querySelector('[name=tta_fecha]');
    var hora = form.querySelector('[name=tta_hora]');
    var branchSel = form.querySelector('select[name=tta_sucursal]');
    var branchHidden = form.querySelector('input[name=tta_sucursal]');
    var grid = form.querySelector('.tta-cal-grid');
    var title = form.querySelector('.tta-cal-title');
    var navs = form.querySelectorAll('.tta-cal-nav');
    var slotsBox = form.querySelector('.tta-slots');
    var cache = {};
    var wantedHour = hora.value;
    var view = (fecha.value && fecha.value >= min ? fecha.value : min).slice(0, 7);
    var reqId = 0;

    function branch() { return branchSel ? branchSel.value : (branchHidden ? branchHidden.value : ''); }

    function get(url, params) {
      var u = new URL(url, location.href);
      Object.keys(params).forEach(function (k) { if (params[k] !== '') u.searchParams.set(k, params[k]); });
      return fetch(u.toString(), { credentials: 'same-origin' }).then(function (r) {
        if (!r.ok) throw new Error(r.status);
        return r.json();
      });
    }

    function hint(text, isError) {
      slotsBox.innerHTML = '';
      slotsBox.appendChild(el('p', isError ? 'tta-hint tta-inline-err' : 'tta-hint', text));
    }

    function renderMonth() {
      var ym = view, key = ym + '|' + branch(), my = ++reqId;
      title.textContent = fmtMonth.format(utc(ym + '-01'));
      navs[0].disabled = ym <= min.slice(0, 7);
      navs[1].disabled = ym >= max.slice(0, 7);
      grid.classList.add('is-loading');
      var p = cache[key] ? Promise.resolve(cache[key]) : get(form.dataset.restMes, { mes: ym, sucursal: branch() });
      p.then(function (data) {
        cache[key] = data;
        if (my !== reqId) return;
        draw(ym, data.dias || {});
      }).catch(function () {
        if (my !== reqId) return;
        grid.innerHTML = '';
        grid.appendChild(el('p', 'tta-inline-err', 'No se pudo cargar la disponibilidad. Intenta de nuevo o agenda por WhatsApp.'));
      }).then(function () { grid.classList.remove('is-loading'); });
    }

    function draw(ym, dias) {
      grid.innerHTML = '';
      DOW.forEach(function (d) { grid.appendChild(el('span', 'tta-dow', d)); });
      var first = utc(ym + '-01');
      var offset = (first.getUTCDay() + 6) % 7;
      var days = new Date(Date.UTC(first.getUTCFullYear(), first.getUTCMonth() + 1, 0)).getUTCDate();
      for (var i = 0; i < offset; i++) grid.appendChild(el('span'));

      for (var d = 1; d <= days; d++) {
        var iso = ym + '-' + pad(d);
        var info = dias[iso];
        var state, label;
        if (iso < min || iso > max) { state = 'out'; label = 'No disponible'; }
        else if (!info) { state = 'closed'; label = 'Sin atención'; }
        else if (!info.libres) { state = 'full'; label = 'Completo'; }
        else if (info.libres / info.total <= 0.34) { state = 'few'; label = info.libres === 1 ? '1 hora' : info.libres + ' horas'; }
        else { state = 'free'; label = info.libres + ' horas'; }

        var b = el('button', 'tta-day is-' + state + (iso === today ? ' is-today' : ''));
        b.type = 'button';
        b.dataset.date = iso;
        b.disabled = state === 'out' || state === 'closed' || state === 'full';
        b.setAttribute('aria-pressed', iso === fecha.value ? 'true' : 'false');
        b.setAttribute('aria-label', fmtDay.format(utc(iso)) + ': ' + (state === 'free' || state === 'few' ? label + ' disponibles' : label));
        b.appendChild(el('b', '', String(d)));
        if (state !== 'out') b.appendChild(el('small', '', state === 'closed' ? 'Cerrado' : label));
        grid.appendChild(b);
      }

      // Si el día elegido quedó sin cupos (o es de otra sucursal), se limpia la selección.
      if (fecha.value && fecha.value.slice(0, 7) === ym) {
        var cur = grid.querySelector('[data-date="' + fecha.value + '"]');
        if (cur && !cur.disabled) loadSlots(fecha.value);
        else if (cur) { fecha.value = ''; hora.value = ''; hint('Ese día ya no tiene horarios disponibles. Elige otro.'); }
      }
    }

    function selectDay(iso) {
      fecha.value = iso;
      hora.value = '';
      grid.querySelectorAll('.tta-day').forEach(function (b) {
        b.setAttribute('aria-pressed', b.dataset.date === iso ? 'true' : 'false');
      });
      loadSlots(iso);
    }

    function loadSlots(iso) {
      hint('Cargando horarios…');
      get(form.dataset.restHorarios, { fecha: iso, sucursal: branch() }).then(function (d) {
        if (fecha.value !== iso) return;
        var list = (d && d.horarios) || [];
        slotsBox.innerHTML = '';
        slotsBox.appendChild(el('p', 'tta-slots-title', 'Horarios para el ' + fmtDay.format(utc(iso))));
        if (!list.length) { hint('No hay horarios este día. Elige otro.'); return; }
        var wrap = el('div', 'tta-slot-grid');
        list.forEach(function (s) {
          var b = el('button', 'tta-slot');
          b.type = 'button';
          b.dataset.hora = s.hora;
          b.disabled = !s.disponible;
          b.setAttribute('aria-pressed', 'false');
          b.appendChild(document.createTextNode(s.hora));
          b.appendChild(el('small', '', s.disponible ? 'Disponible' : 'Ocupado'));
          b.setAttribute('aria-label', s.hora + (s.disponible ? ' disponible' : ' ocupado'));
          if (s.disponible && s.hora === wantedHour) { b.setAttribute('aria-pressed', 'true'); hora.value = s.hora; }
          wrap.appendChild(b);
        });
        wantedHour = '';
        slotsBox.appendChild(wrap);
      }).catch(function () { hint('No se pudieron cargar los horarios.', true); });
    }

    grid.addEventListener('click', function (e) {
      var b = e.target.closest('.tta-day');
      if (b && !b.disabled) selectDay(b.dataset.date);
    });

    slotsBox.addEventListener('click', function (e) {
      var b = e.target.closest('.tta-slot');
      if (!b || b.disabled) return;
      hora.value = b.dataset.hora;
      slotsBox.querySelectorAll('.tta-slot').forEach(function (x) { x.setAttribute('aria-pressed', x === b ? 'true' : 'false'); });
    });

    navs.forEach(function (n) {
      n.addEventListener('click', function () { view = addMonths(view, +n.dataset.dir); renderMonth(); });
    });

    if (branchSel) {
      branchSel.addEventListener('change', function () {
        fecha.value = ''; hora.value = '';
        hint('Primero elige un día disponible en el calendario.');
        renderMonth();
      });
    }

    form.addEventListener('submit', function (e) {
      var msg = !fecha.value ? 'Elige un día en el calendario.' : (!hora.value ? 'Elige un horario disponible.' : '');
      if (!msg) return;
      e.preventDefault();
      if (fecha.value) {
        var err = slotsBox.querySelector('.tta-inline-err') || slotsBox.appendChild(el('p', 'tta-inline-err'));
        err.textContent = msg;
      } else {
        hint(msg, true);
      }
      form.querySelector('.tta-cal').scrollIntoView({ behavior: 'smooth', block: 'center' });
    });

    renderMonth();
  }

  document.querySelectorAll('.tta-form[data-rest-mes]').forEach(init);
})();
