// assets/js/theme.js - Nokia 1100 System Theme Switcher
// Por defecto: MODO CLARO. Cambio interactivo a MODO OSCURO con transición de onda circular.

(function () {
    'use strict';

    if (window.__NOKIA_THEME_LOADED__) return;
    window.__NOKIA_THEME_LOADED__ = true;

    function getSavedTheme() {
        try {
            var saved = localStorage.getItem('nokia1100_theme');
            if (saved === 'dark') return 'dark';
        } catch (e) {}
        return 'light'; // Por defecto SIEMPRE MODO CLARO
    }

    function updateIconsAndTooltips(isDark) {
        document.querySelectorAll('.theme-icon-dark').forEach(function (el) {
            el.classList.toggle('hidden', isDark);
        });
        document.querySelectorAll('.theme-icon-light').forEach(function (el) {
            el.classList.toggle('hidden', !isDark);
        });

        var title = isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro';
        document.querySelectorAll('#themeToggleBtn, .sys-theme-toggle').forEach(function (btn) {
            btn.setAttribute('aria-label', title);
            btn.setAttribute('title', title);
        });
    }

    function applyTheme(theme) {
        var isDark = (theme === 'dark');
        var html = document.documentElement;
        var body = document.body;

        if (isDark) {
            html.classList.add('dark');
            html.setAttribute('data-theme', 'dark');
            if (body) body.classList.add('dark');
        } else {
            html.classList.remove('dark');
            html.setAttribute('data-theme', 'light');
            if (body) body.classList.remove('dark');
        }

        try {
            localStorage.setItem('nokia1100_theme', isDark ? 'dark' : 'light');
        } catch (e) {}

        updateIconsAndTooltips(isDark);

        try {
            window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: theme, isDark: isDark } }));
        } catch (e) {}
    }

    window.toggleTheme = function (e) {
        var isCurrentlyDark = document.documentElement.classList.contains('dark');
        var nextTheme = isCurrentlyDark ? 'light' : 'dark';

        // Coordenadas para la animación de onda circular
        var x = window.innerWidth - 45;
        var y = 29;

        if (e) {
            if (typeof e.clientX === 'number' && typeof e.clientY === 'number' && (e.clientX !== 0 || e.clientY !== 0)) {
                x = e.clientX;
                y = e.clientY;
            } else {
                var btn = e.target ? e.target.closest('#themeToggleBtn, .sys-theme-toggle') : null;
                if (btn && typeof btn.getBoundingClientRect === 'function') {
                    var r = btn.getBoundingClientRect();
                    x = r.left + r.width / 2;
                    y = r.top + r.height / 2;
                }
            }
        }

        var endRadius = Math.hypot(
            Math.max(x, window.innerWidth - x),
            Math.max(y, window.innerHeight - y)
        );

        document.documentElement.style.setProperty('--theme-x', x + 'px');
        document.documentElement.style.setProperty('--theme-y', y + 'px');
        document.documentElement.style.setProperty('--theme-r', endRadius + 'px');

        if (typeof document.startViewTransition === 'function' && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.startViewTransition(function () {
                applyTheme(nextTheme);
            });
        } else {
            applyTheme(nextTheme);
        }
    };

    // Aplicar estado inicial
    var currentTheme = getSavedTheme();
    applyTheme(currentTheme);

    document.addEventListener('DOMContentLoaded', function () {
        updateIconsAndTooltips(document.documentElement.classList.contains('dark'));
    });

    // Delegación de clic para alternar tema
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('#themeToggleBtn, .sys-theme-toggle');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            window.toggleTheme(e);
        }
    });
})();
