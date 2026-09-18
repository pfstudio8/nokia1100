// assets/js/pages/tailwind_config.js
tailwind.config = {
    darkMode: "class",
    theme: {
        extend: {
            colors: {
                background: "#181E26", // Base
                surface: "#1F2937",    // Surface
                card: "#263347",       // Card
                "surface-hover": "#263347", 
                primary: "#0EA5A0",    // Acento
                secondary: "#5EEAD4",  // Acento 2
                success: "#4ADE80",    // Éxito
                error: "#F87171",      // Alerta
                border: "#374151",
                "text-main": "#FAFAFA",
                "text-muted": "#9CA3AF",
            },
            fontFamily: {
                sans: ["Inter", "sans-serif"],
                display: ["Outfit", "sans-serif"],
            },
        },
    },
};
