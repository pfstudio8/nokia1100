// assets/js/pages/filtros.js
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('input[name="marca[]"]').forEach(el => {
        el.addEventListener('change', () => {
            const form = document.getElementById('filterForm');
            if (form) form.submit();
        });
    });
});
