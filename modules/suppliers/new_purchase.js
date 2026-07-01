// assets/js/pages/new_purchase.js
let cart = [];

function addToCart() {
    const nombreInput = document.getElementById('product_nombre');
    const marcaInput = document.getElementById('product_marca');
    const modeloInput = document.getElementById('product_modelo');
    const costoInput = document.getElementById('costo');
    const cantidadInput = document.getElementById('cantidad');
    
    if (!nombreInput.value || !costoInput.value || parseFloat(costoInput.value) <= 0) {
        showPurchaseFeedback('Nombre del producto y un costo unitario válido son obligatorios', 'warning');
        return;
    }

    const nombre = nombreInput.value.trim();
    const marca = marcaInput.value.trim();
    const modelo = modeloInput.value.trim();
    const costo = parseFloat(costoInput.value);
    const cantidad = parseInt(cantidadInput.value);

    const displayName = `${nombre}${marca ? ' - ' + marca : ''}${modelo ? ' ' + modelo : ''}`;

    cart.push({ nombre, marca, modelo, displayName, costo, cantidad });
    updateCartTable();
    
    nombreInput.value = '';
    marcaInput.value = '';
    modeloInput.value = '';
    costoInput.value = '0';
    cantidadInput.value = 1;
}

function removeFromCart(index) {
    cart.splice(index, 1);
    updateCartTable();
}

function updateCartTable() {
    const tbody = document.getElementById('cart-body');
    const totalDisplay = document.getElementById('cart-total');
    tbody.innerHTML = '';
    let total = 0;

    if (cart.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="p-8 text-center text-text-muted text-sm border-none">Aún no hay productos en la orden de compra</td></tr>';
        totalDisplay.textContent = '$0.00';
        return;
    }

    cart.forEach((item, index) => {
        const subtotal = item.costo * item.cantidad;
        total += subtotal;
        const row = document.createElement('tr');
        row.className = 'hover:bg-surface/30 transition-colors';
        row.innerHTML = `
            <td class="p-4 text-sm font-medium text-text-main">${item.displayName}</td>
            <td class="p-4 text-sm text-right text-text-muted">$${item.costo.toFixed(2)}</td>
            <td class="p-4 text-sm text-center"><span class="bg-surface border border-border px-3 py-1 rounded-full text-text-muted">${item.cantidad}</span></td>
            <td class="p-4 text-sm text-right font-medium text-text-main">$${subtotal.toFixed(2)}</td>
            <td class="p-4 text-center">
                <button class="text-red-400 hover:text-red-300 hover:bg-red-400/10 p-2 rounded-xl transition-colors inline-flex" onclick="removeFromCart(${index})">
                    <span class="material-symbols-outlined text-[18px]">delete</span>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
    totalDisplay.textContent = '$' + total.toFixed(2);
}

function submitPurchase() {
    const idProveedor = document.getElementById('id_proveedor').value;
    const descripcion = document.getElementById('descripcion').value;
    const tiempoEntrega = document.getElementById('tiempo_entrega').value;
    const iva = parseFloat(document.getElementById('iva').value) || 0;
    const autorizadoPor = document.getElementById('autorizado_por').value;

    if (!idProveedor) { showPurchaseFeedback('Seleccione un proveedor principal', 'error'); return; }
    if (cart.length === 0) { showPurchaseFeedback('Agregue productos a la orden de compra', 'warning'); return; }

    fetch('new_purchase.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            id_proveedor: idProveedor, 
            items: cart,
            descripcion: descripcion,
            tiempo_entrega: tiempoEntrega,
            iva: iva,
            autorizado_por: autorizadoPor
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showPurchaseFeedback('Compra registrada y stock actualizado con éxito', 'success');
            setTimeout(() => {
                const baseUrl = window.BASE_URL || '';
                window.location.href = baseUrl + '/modules/suppliers/suppliers.php';
            }, 800);
        } else {
            showPurchaseFeedback('Error al registrar compra: ' + data.message, 'error');
        }
    });
}

function showPurchaseFeedback(message, type) {
    if (typeof showToast === 'function') {
        showToast(message, type);
    }
}
