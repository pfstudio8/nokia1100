// assets/js/tailwind_config.js
// Configuración de tokens y paleta oficial Nokia 1100 para Tailwind CSS
tailwind.config = {
    darkMode: "class",
    theme: {
        extend: {
            colors: {
                background: "#eeebe3", // Papel cálido
                paper: "#eeebe3",
                surface: "#f8f6f0",    // Superficie
                "surface-hover": "#e5e1d6", 
                card: "#f8f6f0",       // Tarjetas
                sunk: "#e5e1d6",
                primary: "#15171a",    // Tinta / Negro primario
                "primary-hover": "#30363a",
                lcd: "#c6e24a",        // Verde LCD retro Nokia
                secondary: "#c6e24a",  // Acento secundario
                border: "#d3cebf",     // Línea suave
                "border-dark": "#b5af9c",
                "text-main": "#15171a", // Tinta cuerpo
                "text-muted": "#5c6165", // Tinta secundaria
                graphite: "#15181a",   // Grafito oscuro
                "graphite-2": "#222729",
                "on-dark": "#e9e6db",  // Texto sobre grafito
                "on-dark-2": "#9ba19f",
                success: "#1d6a38",    // Ok
                "success-bg": "#dfeee3",
                error: "#a82a1d",      // Bad
                "error-bg": "#f6dcd7",
                warn: "#8a5200",       // Warn
                "warn-bg": "#f6e8c8",
                focus: "#1f4e8c"
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
