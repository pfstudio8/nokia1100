/* ==========================================================================
   Nokia 1100 · Loader circular v1.2   →   window.NKLoader
   Sin dependencias. Requiere nokia-loader.css.
   Configurado a 1,5s para LOGIN (900ms + 600ms) y 2,0s para LOGOUT (1000ms + 1000ms).
   ========================================================================== */
(function (global) {
  'use strict';

  var WORD = 'NOKIA1100';
  var FLAG = 'nkl-enter';

  var MODES = {
    login: {
      label: 'Cargando el sistema',
      doneLabel: 'Inicio de sesión correcto, entrando al sistema',
      ms: 1050,     // giro del círculo (1.05s)
      hold: 450     // cierre del anillo en verde LCD + pulso suave (0.45s) => Total 1,5s sin quedar en negro
    },
    logout: {
      label: 'Cerrando sesión',
      doneLabel: 'Sesión cerrada',
      ms: 1000,     // giro inverso (1.0s)
      hold: 1000    // liberación de letras + apagado CRT (1.0s) => Total 2,0s
    }
  };

  var FADE = 250;      // fade de salida
  var ENTER_MS = 450;  // duración de la entrada suave del dashboard
  var current = null;
  var prevOverflow = '';

  function wait(ms) {
    return new Promise(function (res) { setTimeout(res, ms); });
  }

  function prefersReduced() {
    return !!(global.matchMedia && global.matchMedia('(prefers-reduced-motion: reduce)').matches);
  }

  function build(mode, cfg) {
    var root = document.createElement('div');
    root.className = 'nkl nkl--' + mode;
    root.setAttribute('role', 'status');
    root.setAttribute('aria-live', 'polite');
    root.setAttribute('aria-label', cfg.label);

    var letters = '';
    for (var i = 0; i < WORD.length; i++) {
      letters += '<div class="nkl__ch" style="--i:' + i + ';--a:' + (i * 360 / WORD.length) + 'deg"><span>' + WORD.charAt(i) + '</span></div>';
    }

    var extra = mode === 'logout' ? '<div class="nkl__off" aria-hidden="true"></div>' : '';

    root.innerHTML =
      '<div class="nkl__stage">' +
        '<div class="nkl__orb">' +
          '<svg class="nkl__ring" viewBox="0 0 100 100" aria-hidden="true">' +
            '<circle class="nkl__track" cx="50" cy="50" r="30" pathLength="100"/>' +
            '<g class="nkl__spin"><circle class="nkl__arc" cx="50" cy="50" r="30" pathLength="100" transform="rotate(-90 50 50)"/></g>' +
          '</svg>' +
          '<div class="nkl__word" aria-hidden="true">' + letters + '</div>' +
        '</div>' +
        extra +
      '</div>';

    return root;
  }

  function teardown(state) {
    if (state.root.parentNode) state.root.parentNode.removeChild(state.root);
    if (current === state) {
      current = null;
      document.documentElement.style.overflow = prevOverflow;
    }
  }

  function hide(immediate) {
    var state = current;
    if (!state) return Promise.resolve();
    state.cancelled = true;
    if (immediate) { teardown(state); return Promise.resolve(); }
    state.root.classList.add('is-out');
    return wait(FADE).then(function () { teardown(state); });
  }

  /* Entrada rápida y elegante al dashboard */
  function enter() {
    var html = document.documentElement;
    html.classList.add('nkl-entering');
    setTimeout(function () { html.classList.remove('nkl-entering'); }, ENTER_MS);
  }

  function show(opts) {
    opts = opts || {};
    var mode = opts.mode === 'logout' ? 'logout' : 'login';
    var cfg = {};
    var k;
    for (k in MODES[mode]) cfg[k] = MODES[mode][k];
    for (k in opts) if (opts[k] != null) cfg[k] = opts[k];

    if (current) hide(true);

    var state = { root: build(mode, cfg), cancelled: false };
    current = state;
    prevOverflow = document.documentElement.style.overflow;
    document.documentElement.style.overflow = 'hidden';
    document.body.appendChild(state.root);
    void state.root.offsetWidth;                 // fuerza el reflow para que corra el fade-in
    state.root.classList.add('is-in');

    var reduced = prefersReduced();
    var minTime = wait(reduced ? Math.min(cfg.ms, 600) : cfg.ms);

    return Promise.all([minTime, Promise.resolve(cfg.until)])
      .then(function () {
        if (state.cancelled) return;
        state.root.setAttribute('aria-label', cfg.doneLabel);
        state.root.classList.add('is-done');     // arranca el final (contracción / apagado)
        return wait(reduced ? Math.min(cfg.hold, 400) : cfg.hold);
      })
      .then(function () {
        if (state.cancelled) return;
        if (typeof cfg.onDone === 'function') cfg.onDone();

        if (cfg.redirect) {
          if (mode === 'login') {
            try { global.sessionStorage.setItem(FLAG, '1'); } catch (e) {}
          }
          global.location.assign(cfg.redirect);  // el overlay queda hasta que cargue la nueva página
          return;
        }
        if (mode === 'login') {                  // sin navegación: el panel se abre desde el centro
          teardown(state);
          enter();
          return;
        }
        return hide(false);
      })
      .catch(function (err) {
        teardown(state);                         // si falló lo que esperábamos, no dejamos la pantalla trabada
        throw err;
      });
  }

  /* ---------- HTML declarativo ---------- */
  document.addEventListener('click', function (e) {
    var a = e.target.closest && e.target.closest('[data-nk-loader]');
    if (!a || e.defaultPrevented) return;
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button > 0) return;
    if (a.target && a.target !== '_self') return;
    var href = a.getAttribute('href');
    e.preventDefault();
    show({ mode: a.getAttribute('data-nk-loader'), redirect: href || null });
  });

  function auto() {
    var host = document.querySelector('[data-nk-loader-auto]');
    if (!host) return;
    show({
      mode: host.getAttribute('data-nk-loader-auto'),
      redirect: host.getAttribute('data-nk-redirect') || null
    });
  }

  /* ---------- Llegada desde el login: abrir el dashboard en iris ---------- */
  function arrive() {
    var came = false;
    try {
      came = global.sessionStorage.getItem(FLAG) === '1';
      if (came) global.sessionStorage.removeItem(FLAG);
    } catch (e) {}
    if (came) enter();
  }

  function init() { arrive(); auto(); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();

  // Si el usuario vuelve con "atrás" y el navegador restaura la página, sacamos el overlay.
  global.addEventListener('pageshow', function (e) { if (e.persisted) hide(true); });

  var api = { show: show, hide: hide, enter: enter, modes: MODES };
  global.NKLoader = api;
  if (global.NK) global.NK.loader = api;
})(window);

