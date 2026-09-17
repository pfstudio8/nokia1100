// assets/js/pages/purchase_history.js
function toggleDetails(id) {
    const row = document.getElementById('detail-' + id);
    if (row) {
        row.classList.toggle('hidden');
    }
}

async function receivePurchase(id) {
    let isConfirmed = false;
    if (typeof showConfirmModal === 'function') {
        isConfirmed = await showConfirmModal(
            'Recibir Pedido',
            'Esto actualizará el stock en el inventario. ¿Confirmás?',
            'Sí, recibir', 'Cancelar', false
        );
    } else {
        isConfirmed = confirm('¿Está seguro que desea marcar este pedido como recibido? Esto actualizará el stock en el inventario.');
    }

    if (isConfirmed) {
        fetch('index.php?action=receive_purchase', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_compra: id })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (typeof showToast === 'function') showToast(data.message, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                if (typeof showToast === 'function') showToast(data.message, 'error');
                else alert('Error: ' + data.message);
            }
        });
    }
}

async function cancelPurchase(id) {
    let isConfirmed = false;
    if (typeof showConfirmModal === 'function') {
        isConfirmed = await showConfirmModal(
            'Cancelar Pedido',
            'Esta acción no se puede deshacer. ¿Confirmás la cancelación?',
            'Sí, cancelar', 'Volver', true
        );
    } else {
        isConfirmed = confirm('¿Está seguro que desea cancelar este pedido? Esta acción no se puede deshacer.');
    }

    if (isConfirmed) {
        fetch('index.php?action=cancel_purchase', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_compra: id })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (typeof showToast === 'function') showToast(data.message, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                if (typeof showToast === 'function') showToast(data.message, 'error');
                else alert('Error: ' + data.message);
            }
        });
    }
}
