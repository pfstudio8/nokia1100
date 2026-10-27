// assets/js/pages/clients_list.js
function openAddClientModal() {
    const modal = document.getElementById('add-client-modal');
    if (!modal) return;
    const card = modal.querySelector('.glass-card');
    modal.classList.remove('opacity-0', 'pointer-events-none');
    if (card) {
        card.classList.remove('scale-95');
        card.classList.add('scale-100');
    }
}

function closeAddClientModal() {
    const modal = document.getElementById('add-client-modal');
    if (!modal) return;
    const card = modal.querySelector('.glass-card');
    if (card) {
        card.classList.remove('scale-100');
        card.classList.add('scale-95');
    }
    modal.classList.add('opacity-0', 'pointer-events-none');
}

// Envía la baja por POST (el token CSRF viaja en el cuerpo, nunca en la URL)
function submitClientDelete(id, csrfToken) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'index.php';
    form.style.display = 'none';
    [['action', 'delete'], ['id_cliente', String(id)], ['csrf_token', csrfToken || '']].forEach(([name, value]) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        form.appendChild(input);
    });
    document.body.appendChild(form);
    form.submit();
}

async function confirmDelete(id, nombre, csrfToken) {
    const message = `¿Estás seguro de que deseas eliminar al cliente "${nombre}"? Esta acción no se puede deshacer y fallará si posee órdenes de taller activas.`;
    const confirmed = (typeof showConfirmModal === 'function')
        ? await showConfirmModal('Eliminar Cliente', message, 'Sí, eliminar', 'Cancelar', true)
        : confirm(message);
    if (confirmed) {
        submitClientDelete(id, csrfToken);
    }
}

// Delegación: los datos se leen de atributos data-* (sin interpolar texto de usuario en JS inline)
document.addEventListener('click', (e) => {
    const btn = e.target.closest('.js-delete-client');
    if (!btn) return;
    e.preventDefault();
    confirmDelete(btn.dataset.id, btn.dataset.nombre, btn.dataset.csrf);
});
