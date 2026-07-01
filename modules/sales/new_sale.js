// assets/js/pages/new_sale.js
let cart = [];

// Cargar carrito desde localStorage al iniciar
try {
    const cachedCart = localStorage.getItem('nokia_sales_cart');
    if (cachedCart) {
        cart = JSON.parse(cachedCart) || [];
    }
} catch (e) {
    cart = [];
}

document.addEventListener('DOMContentLoaded', () => {
    updateCartTable();
});

async function addToCart() {
    const select  = document.getElementById('id_producto');
    const option  = select.options[select.selectedIndex];
    const cantInput = document.getElementById('cantidad');

    if (!select.value) { showToast('Por favor seleccione un producto', 'warning'); return; }

    const id      = select.value;
    const nombre  = option.dataset.nombre;
    const precio  = parseFloat(option.dataset.precio);
    const cantidad = parseInt(cantInput.value);

    if (cantidad <= 0) { showToast('La cantidad debe ser mayor a 0', 'error'); return; }

    // Validación de stock en tiempo real mediante API
    try {
        const response = await fetch(`../../api/check_stock.php?id_producto=${id}`);
        if (!response.ok) {
            showToast('Error al consultar stock del artículo en tiempo real', 'error');
            return;
        }
        const data = await response.json();
        const stock = data.stock;

        const existingItem     = cart.find(i => i.id === id);
        const currentQtyInCart = existingItem ? existingItem.cantidad : 0;

        if (currentQtyInCart + cantidad > stock) {
            showToast(`Stock insuficiente. Stock actual en BD: ${stock}`, 'error'); 
            return;
        }

        if (existingItem) { existingItem.cantidad += cantidad; }
        else              { cart.push({ id, nombre, precio, cantidad }); }

        updateCartTable();
        select.value    = '';
        cantInput.value = 1;
        showToast(`${nombre.split(' ')[0]} agregado al carrito`, 'success');
    } catch (err) {
        showToast('Error de red al consultar el stock', 'error');
    }
}

function removeFromCart(index) { cart.splice(index, 1); updateCartTable(); }

function updateCartTable() {
    const tbody  = document.getElementById('cart-body');
    const totalEl = document.getElementById('cart-total');
    tbody.innerHTML = '';
    let total = 0;

    if (cart.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="p-8 text-center text-text-muted text-sm border-none">El carrito está vacío</td></tr>';
        totalEl.textContent = '$0.00';
        return;
    }

    cart.forEach((item, index) => {
        const subtotal = item.precio * item.cantidad;
        total += subtotal;
        const row = document.createElement('tr');
        row.className = 'hover:bg-surface/30 transition-colors';
        row.innerHTML = `
            <td class="p-4 text-sm font-medium text-text-main">${item.nombre}</td>
            <td class="p-4 text-sm text-right text-text-muted">$${item.precio.toFixed(2)}</td>
            <td class="p-4 text-sm text-center"><span class="bg-surface border border-border px-3 py-1 rounded-full text-text-muted">${item.cantidad}</span></td>
            <td class="p-4 text-sm text-right font-medium text-text-main">$${subtotal.toFixed(2)}</td>
            <td class="p-4 text-center">
                <button class="text-red-400 hover:text-red-300 hover:bg-red-400/10 p-2 rounded-xl transition-colors inline-flex" onclick="removeFromCart(${index})">
                    <span class="material-symbols-outlined text-[18px]">delete</span>
                </button>
            </td>`;
        tbody.appendChild(row);
    });
    totalEl.textContent = '$' + total.toFixed(2);
    // Persistir carrito en localStorage
    localStorage.setItem('nokia_sales_cart', JSON.stringify(cart));
}

async function submitSale() {
    if (cart.length === 0) { showToast('El carrito está vacío', 'warning'); return; }

    const confirmed = await showConfirmModal(
        'Confirmar Venta',
        `¿Registrar la venta por <strong>${document.getElementById('cart-total').textContent}</strong>?`,
        'Sí, registrar', 'Cancelar', false
    );
    if (!confirmed) return;

    const metodoPago = document.getElementById('metodo_pago').value;

    fetch('new_sale.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ items: cart, metodo_pago: metodoPago })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            cart = [];
            // Limpiar caché
            localStorage.removeItem('nokia_sales_cart');
            updateCartTable();
            showSuccessModal(data.id_venta);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(() => showToast('Error de conexión al procesar la venta', 'error'));
}
