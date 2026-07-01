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

async function confirmDelete(id, nombre) {
    if (typeof showConfirmModal === 'function') {
        const confirmed = await showConfirmModal(
            'Eliminar Cliente',
            `¿Estás seguro de que deseas eliminar al cliente <strong>${nombre}</strong>? Esta acción no se puede deshacer y fallará si posee órdenes de taller activas.`,
            'Sí, eliminar', 'Cancelar', true
        );
        if (confirmed) {
            window.location.href = `clients.php?delete=${id}`;
        }
    } else {
        if (confirm(`¿Estás seguro de que deseas eliminar al cliente "${nombre}"?`)) {
            window.location.href = `clients.php?delete=${id}`;
        }
    }
}
