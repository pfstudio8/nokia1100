// assets/js/pages/sales_list.js
// Logica de animación antigua eliminada.

window.confirmRollback = async function(id_venta) {
    let isConfirmed = false;
    
    if (typeof window.showConfirmModal === 'function') {
        isConfirmed = await window.showConfirmModal(
            '¿Anular Venta #TX-' + id_venta + '?',
            'Esta acción devolverá el stock al inventario y no se puede deshacer.',
            'Anular Venta',
            'Cancelar',
            true
        );
    } else {
        isConfirmed = confirm('¿Estás seguro de que deseas anular la venta #TX-' + id_venta + '?\n\nEsta acción devolverá el stock al inventario y no se puede deshacer.');
    }

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
                } else {
                    alert(data.message);
                }
                setTimeout(() => location.reload(), 1500);
            } else {
                if (typeof window.showToast === 'function') {
                    window.showToast(data.message, 'error');
                } else {
                    alert('Error: ' + data.message);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Ocurrió un error al procesar la solicitud.');
        });
    }
};
