// assets/js/pages/sales_list.js
// Logica de animación antigua eliminada.

window.confirmRollback = async function(id_venta) {
    if (typeof window.showConfirmModal !== 'function') return;
    const isConfirmed = await window.showConfirmModal(
        '¿Anular Venta #TX-' + id_venta + '?',
        'Esta acción devolverá el stock al inventario y no se puede deshacer.',
        'Anular Venta',
        'Cancelar',
        true
    );

    if (isConfirmed) {
        const apiUrl = (window.BASE_URL ? window.BASE_URL : '/nokia1100') + '/api/rollback_sale.php';
        fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ 
                id_venta: id_venta,
                csrf_token: window.CSRF_TOKEN || ''
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (typeof window.showToast === 'function') {
                    window.showToast(data.message, 'success');
                }
                setTimeout(() => location.reload(), 1500);
            } else {
                if (typeof window.showToast === 'function') {
                    window.showToast(data.message, 'error');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (typeof window.showToast === 'function') {
                window.showToast('Ocurrió un error al procesar la solicitud.', 'error');
            }
        });
    }
};
