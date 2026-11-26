// assets/js/tailwind_config.js
// Configuración de tokens y paleta oficial Nokia 1100 para Tailwind CSS
tailwind.config = {
    darkMode: "class",
    theme: {
        extend: {
            colors: {
                background: "var(--background, #eeebe3)",
                paper: "var(--paper, #eeebe3)",
                surface: "var(--surface, #f8f6f0)",
                "surface-hover": "var(--surface-hover, #e5e1d6)", 
                card: "var(--card, #f8f6f0)",
                sunk: "var(--sunk, #e5e1d6)",
                primary: "var(--primary-color, #15171a)",
                "primary-hover": "var(--primary-hover, #30363a)",
                lcd: "var(--lcd, #c6e24a)",
                secondary: "var(--secondary-color, #c6e24a)",
                border: "var(--border, #d3cebf)",
                "border-dark": "var(--line-2, #b5af9c)",
                "text-main": "var(--text-main, #15171a)",
                "text-muted": "var(--text-muted, #5c6165)",
                graphite: "var(--graphite, #15181a)",
                "graphite-2": "var(--graphite-2, #222729)",
                "on-dark": "var(--on-dark, #e9e6db)",
                "on-dark-2": "var(--on-dark-2, #9ba19f)",
                success: "var(--ok, #1d6a38)",
                "success-bg": "var(--ok-bg, #dfeee3)",
                error: "var(--bad, #a82a1d)",
                "error-bg": "var(--bad-bg, #f6dcd7)",
                warn: "var(--warn, #8a5200)",
                "warn-bg": "var(--warn-bg, #f6e8c8)",
                focus: "var(--focus, #1f4e8c)"
            },
            fontFamily: {
                sans: ["'IBM Plex Sans'", "system-ui", "-apple-system", "sans-serif"],
                display: ["'Bricolage Grotesque'", "system-ui", "sans-serif"],
                mono: ["'IBM Plex Mono'", "ui-monospace", "Menlo", "monospace"],
            },
            borderRadius: {
                DEFAULT: "2px",
                none: "0",
                sm: "2px",
                md: "2px",
                lg: "2px",
                xl: "4px",
                "2xl": "4px",
                "3xl": "6px",
                full: "9999px"
            }
        },
    },
};
