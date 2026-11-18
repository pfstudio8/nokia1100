// assets/js/pages/purchase_history.js
function toggleDetails(id) {
    const row = document.getElementById('detail-' + id);
    if (row) {
        row.classList.toggle('hidden');
    }
}

async function receivePurchase(id) {
    if (typeof showConfirmModal !== 'function') return;
    const isConfirmed = await showConfirmModal(
        'Recibir Pedido',
        'Esto actualizará el stock en el inventario. ¿Confirmás?',
        'Sí, recibir', 'Cancelar', false
    );

    if (isConfirmed) {
        fetch('index.php?action=receive_purchase', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                id_compra: id,
                csrf_token: window.CSRF_TOKEN || ''
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (typeof showToast === 'function') showToast(data.message, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                if (typeof showToast === 'function') showToast(data.message, 'error');
            }
        });
    }
}

async function cancelPurchase(id) {
    if (typeof showConfirmModal !== 'function') return;
    const isConfirmed = await showConfirmModal(
        'Cancelar Pedido',
        'Esta acción no se puede deshacer. ¿Confirmás la cancelación?',
        'Sí, cancelar', 'Volver', true
    );

    if (isConfirmed) {
        fetch('index.php?action=cancel_purchase', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                id_compra: id,
                csrf_token: window.CSRF_TOKEN || ''
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (typeof showToast === 'function') showToast(data.message, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                if (typeof showToast === 'function') showToast(data.message, 'error');
            }
        });
    }
}
