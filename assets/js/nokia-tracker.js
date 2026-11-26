/* ==========================================================================
   Nokia 1100 · Línea de seguimiento v2.0   →   window.NKTracker
   Sin dependencias. Requiere nokia-tracker.css.

   Markup mínimo (el JS arma número, nodo, ícono y tilde):

     <div class="nkt" id="tracker" data-nkt data-current="4"
          aria-label="Estado de tu equipo"
          data-deliver-title="¡Entregado!" data-deliver-text="Tu equipo ya está en tus manos.">
       <ol class="nkt__steps">
         <li class="nkt__step" data-icon="received"><span class="nkt__label">Recibido</span></li>
         <li class="nkt__step" data-icon="diag"><span class="nkt__label">En diagnóstico</span></li>
         <li class="nkt__step" data-icon="repair"><span class="nkt__label">En reparación</span></li>
         <li class="nkt__step" data-icon="ready"><span class="nkt__label">Listo</span></li>
         <li class="nkt__step" data-icon="delivered"><span class="nkt__label">Entregado</span></li>
       </ol>
     </div>

   Íconos: received · diag · repair · ready · delivered
   (sin data-icon se asignan en ese orden). La escena de la mano con el
   paquete aparece sola cuando data-current es el último paso.
   data-deliver="off" la desactiva.

   API:
     NKTracker.set(el, 4)     // avanza al paso 4 animando solo el tramo nuevo
     NKTracker.replay(el)     // vuelve a reproducir desde cero
   ========================================================================== */
(function (global) {
  'use strict';

  var SPARKS = 8;
  var ORDER = ['received', 'diag', 'repair', 'ready', 'delivered'];

  /* Sello (insignia de 16 puntas), calculado una vez */
  function sealPath() {
    var d = '';
    for (var k = 0; k < 16; k++) {
      var r = k % 2 ? 8.4 : 10;
      var a = (k * 22.5 - 90) * Math.PI / 180;
      d += (k ? 'L' : 'M') + (12 + r * Math.cos(a)).toFixed(2) + ' ' + (12 + r * Math.sin(a)).toFixed(2);
    }
    return d + 'Z';
  }

  var ICONS = {
    received:
      '<g class="nkt__tray"><path d="M3.5 13.5l2.8-8h11.4l2.8 8v5.5h-17z"/><path d="M3.5 13.5h5.6l1 2.4h3.8l1-2.4h5.6"/></g>',
    diag:
      '<circle cx="10.5" cy="10.5" r="6.5"/><path d="M15.5 15.5L21 21"/>' +
      '<path class="nkt__ecg" d="M6.6 10.8h2l1.4-2.8 1.8 5 1.2-2.2h1.4" pathLength="1"/>',
    repair:
      '<path class="nkt__wr" d="M20.6 6.4l-3.3 3.3-2.7-.6-.6-2.7 3.3-3.3a5.2 5.2 0 0 0-6.9 6.5L3.6 17.8a2 2 0 0 0 2.8 2.8l7.4-7.4a5.2 5.2 0 0 0 6.8-6.8z"/>',
    ready:
      '<rect x="4" y="3.5" width="16" height="17" rx="2"/>' +
      '<path class="nkt__a1" d="M7.8 9.2l1.7 1.7 3.2-3.4" pathLength="1"/><path d="M14.6 9.4H17M7.8 15.6H17"/>',
    delivered:
      '<path d="' + sealPath() + '"/><path class="nkt__a1" d="M8.4 12.4l2.5 2.5 4.7-5" pathLength="1"/>'
  };

  var CHECK = '<svg class="nkt__chk" viewBox="0 0 24 24" aria-hidden="true"><path d="M5.5 12.8l4.3 4.3 8.7-9.6" pathLength="1"/></svg>';

  function reduced() {
    return !!(global.matchMedia && global.matchMedia('(prefers-reduced-motion: reduce)').matches);
  }

  function stepsOf(el) {
    return Array.prototype.slice.call(el.querySelectorAll('.nkt__step'));
  }

  function pad(n) { return n < 10 ? '0' + n : '' + n; }

  function srState(li, state) {
    var sr = li.querySelector('.nkt__sr');
    if (!sr) {
      sr = document.createElement('span');
      sr.className = 'nkt__sr';
      li.appendChild(sr);
    }
    sr.textContent = ' (' + state + ')';
  }

  /* Escena: una mano trae un paquete y lo apoya sobre el felpudo */
  function buildScene(el) {
    var title = el.getAttribute('data-deliver-title') || '¡Entregado!';
    var text = el.getAttribute('data-deliver-text') || 'Tu equipo ya está en tus manos.';
    var dust = '';
    var dx = [-38, -22, -8, 8, 22, 38];
    var dy = [-6, -14, -20, -20, -14, -6];
    for (var i = 0; i < dx.length; i++) {
      dust += '<rect class="dust" x="158" y="111" width="4" height="4" style="--dx:' + dx[i] + 'px;--dy:' + dy[i] + 'px"/>';
    }
    var box = document.createElement('div');
    box.className = 'nkt__deliver';
    box.setAttribute('aria-hidden', 'true');
    box.innerHTML =
      '<div class="nkt__deliver-clip"><div class="nkt__deliver-row">' +
        '<svg class="nkt__scene" viewBox="0 0 320 150" aria-hidden="true" focusable="false">' +
          '<path class="g-line" d="M8 124H312"/>' +
          '<rect class="g-mat" x="104" y="116" width="112" height="8"/>' +
          '<g class="d-dust">' + dust + '</g>' +
          '<g class="pk">' +
            '<rect class="bx" x="134" y="70" width="52" height="46"/>' +
            '<rect class="tp" x="154" y="70" width="12" height="20"/>' +
            '<rect class="lb" x="141" y="98" width="15" height="9"/>' +
            '<path class="ln" d="M161 100h18M161 105h11"/>' +
          '</g>' +
          '<g class="pk-ok"><rect class="ok-bg" x="172" y="56" width="22" height="22"/>' +
            '<path class="ok-ck" d="M177 67.5l4 4 8-8"/></g>' +
          '<g class="hd">' +
            '<rect class="sl" x="238" y="111" width="104" height="36"/>' +
            '<rect class="cf" x="238" y="111" width="9" height="36"/>' +
            '<rect class="pm" x="196" y="86" width="14" height="38" rx="7" transform="rotate(-28 203 122)"/>' +
            '<rect class="pm" x="118" y="116" width="66" height="6.5" rx="3.25"/>' +
            '<rect class="pm" x="111" y="122.5" width="73" height="6.5" rx="3.25"/>' +
            '<rect class="pm" x="118" y="129" width="66" height="6.5" rx="3.25"/>' +
            '<rect class="pm" x="130" y="135.5" width="54" height="6.5" rx="3.25"/>' +
            '<rect class="pm" x="172" y="116" width="68" height="26"/>' +
          '</g>' +
        '</svg>' +
        '<div class="nkt__deliver-txt"><strong></strong><span></span></div>' +
      '</div></div>';
    box.querySelector('strong').textContent = title;
    box.querySelector('.nkt__deliver-txt span').textContent = text;
    el.appendChild(box);
    return box;
  }

  /* Arma número, nodo (ícono + tilde + destellos) y escena si faltan */
  function decorate(el) {
    stepsOf(el).forEach(function (li, i) {
      if (!li.querySelector('.nkt__node')) {
        var name = li.getAttribute('data-icon') || ORDER[i] || 'ready';
        var num = document.createElement('span');
        num.className = 'nkt__num';
        num.setAttribute('aria-hidden', 'true');
        num.textContent = pad(i + 1);

        var node = document.createElement('span');
        node.className = 'nkt__node';
        node.setAttribute('aria-hidden', 'true');
        node.innerHTML =
          '<svg class="nkt__ico" viewBox="0 0 24 24" aria-hidden="true">' + (ICONS[name] || ICONS.ready) + '</svg>' + CHECK;
        for (var k = 0; k < SPARKS; k++) {
          var s = document.createElement('i');
          s.className = 'nkt__spark';
          s.style.setProperty('--a', (k * 360 / SPARKS) + 'deg');
          node.appendChild(s);
        }
        li.insertBefore(node, li.firstChild);
        li.insertBefore(num, li.firstChild);
      }
    });
    if (el.getAttribute('data-deliver') !== 'off' && !el.querySelector('.nkt__deliver')) {
      el._nktDeliver = buildScene(el);
    } else {
      el._nktDeliver = el.querySelector('.nkt__deliver');
    }
  }

  /* Calcula clases y variables. prev = hasta qué paso ya estaba mostrado. */
  function apply(el, cur, prev) {
    var list = stepsOf(el);
    var n = list.length;
    cur = Math.max(1, Math.min(n, cur));
    prev = Math.max(0, Math.min(n, prev));

    el.style.setProperty('--n', n);
    el.style.setProperty('--cur', cur);
    el.style.setProperty('--prev', prev);

    list.forEach(function (li, i) {
      var s = i + 1;
      li.style.setProperty('--s', s);
      li.classList.remove('is-done', 'is-current', 'is-pending', 'is-new', 'is-reach');
      if (s < cur) li.classList.add('is-done');
      else if (s === cur) li.classList.add('is-current');
      else li.classList.add('is-pending');
      if (s >= 2 && s <= cur) li.classList.add('is-reach');
      if (s <= cur && s > prev) li.classList.add('is-new');

      if (s === cur) li.setAttribute('aria-current', 'step');
      else li.removeAttribute('aria-current');
      srState(li, s < cur ? 'completado' : s === cur ? 'paso actual' : 'pendiente');
    });

    var final = cur === n;
    el.classList.toggle('is-final', final);
    if (el._nktDeliver) el._nktDeliver.setAttribute('aria-hidden', final ? 'false' : 'true');
    el._nktCur = cur;
  }

  function play(el) {
    el.classList.remove('is-play');
    void el.offsetWidth;                    // reinicia las animaciones
    el.classList.add('is-play');
  }

  function init(el) {
    if (el._nktReady) return;
    el._nktReady = true;
    decorate(el);

    var cur = parseInt(el.getAttribute('data-current'), 10) || 1;
    apply(el, cur, 0);

    if (reduced()) { el.classList.add('is-play'); return; }

    el.classList.add('is-armed');           // lo nuevo espera oculto
    if ('IntersectionObserver' in global) {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
          if (e.isIntersecting) { io.disconnect(); play(el); }
        });
      }, { threshold: 0.45 });
      io.observe(el);
    } else {
      play(el);
    }
  }

  function resolve(target) {
    return typeof target === 'string' ? document.querySelector(target) : target;
  }

  function set(target, step) {
    var el = resolve(target);
    if (!el) return;
    if (!el._nktReady) init(el);
    var prev = el._nktCur || 0;
    var next = parseInt(step, 10) || 1;
    // si retrocede, no hay tramo nuevo que animar
    apply(el, next, next > prev ? prev : next);
    el.setAttribute('data-current', el._nktCur);
    play(el);
  }

  function replay(target) {
    var el = resolve(target);
    if (!el) return;
    if (!el._nktReady) init(el);
    apply(el, el._nktCur || 1, 0);
    play(el);
  }

  function boot() {
    Array.prototype.forEach.call(document.querySelectorAll('[data-nkt]'), init);
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();

  global.NKTracker = { init: init, set: set, replay: replay };
  if (global.NK) global.NK.tracker = global.NKTracker;
})(window);

