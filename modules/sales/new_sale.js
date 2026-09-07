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

let currentTotal = 0;

function openPaymentModal() {
    if (cart.length === 0) { showToast('El carrito está vacío', 'warning'); return; }
    
    // Update modal UI with cart details
    const totalEl = document.getElementById('cart-total');
    currentTotal = cart.reduce((sum, item) => sum + (item.precio * item.cantidad), 0);
    
    document.getElementById('modal-items-count').textContent = cart.length === 1 ? '1 artículo' : `${cart.length} artículos`;
    document.getElementById('modal-total-amount').textContent = `$${currentTotal.toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    
    const firstItem = cart[0];
    document.getElementById('modal-first-item-name').textContent = firstItem.nombre;
    const additionalText = cart.length > 1 ? ` (+${cart.length - 1} más)` : '';
    document.getElementById('modal-first-item-desc').textContent = `${firstItem.cantidad} x $${firstItem.precio.toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}${additionalText}`;
    document.getElementById('modal-first-item-price').textContent = `$${(firstItem.precio * firstItem.cantidad).toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    
    // Reset state
    selectPaymentMethod('Efectivo');
    document.getElementById('monto_recibido').value = '';
    calculateChange();
    generateQuickSuggestions(currentTotal);
    
    const modal = document.getElementById('payment-modal');
    modal.classList.remove('hidden');
}

function closePaymentModal() {
    const modal = document.getElementById('payment-modal');
    modal.classList.add('hidden');
}

function selectPaymentMethod(method) {
    document.getElementById('modal_metodo_pago').value = method;
    
    // Reset visual state of all buttons
    const buttons = document.querySelectorAll('.payment-method-btn');
    buttons.forEach(btn => {
        btn.classList.remove('border-primary', 'bg-primary/10', 'text-primary');
        btn.classList.add('border-border/50', 'bg-background', 'text-text-muted');
    });
    
    // Set active state for selected button
    const activeBtn = document.getElementById(`btn-pay-${method.toLowerCase().replace(/ /g, '')}`);
    if (activeBtn) {
        activeBtn.classList.remove('border-border/50', 'bg-background', 'text-text-muted');
        activeBtn.classList.add('border-primary', 'bg-primary/10', 'text-primary');
    }
    
    // Show/hide cash liquidation block
    const cashBlock = document.getElementById('cash-liquidation-block');
    const confirmBtn = document.getElementById('btn-confirm-sale');
    
    if (method === 'Efectivo') {
        cashBlock.style.display = 'block';
        calculateChange(); // Will disable/enable confirm btn based on amount
    } else {
        cashBlock.style.display = 'none';
        confirmBtn.disabled = false; // Always enable for non-cash
    }
}

function generateQuickSuggestions(total) {
    const container = document.getElementById('quick-suggestions');
    container.innerHTML = '';
    
    const suggestions = [total];
    
    // Add next round numbers (e.g. if 15500 -> 16000, 20000)
    let magnitude = Math.pow(10, Math.floor(Math.log10(total)));
    if (magnitude < 1000) magnitude = 1000;
    
    let nextRound = Math.ceil(total / (magnitude/10)) * (magnitude/10);
    if (nextRound === total) nextRound += (magnitude/10);
    suggestions.push(nextRound);
    
    let nextBigRound = Math.ceil(total / magnitude) * magnitude;
    if (nextBigRound === total || nextBigRound <= nextRound) nextBigRound += magnitude;
    suggestions.push(nextBigRound);
    
    // Ensure unique values
    const uniqueSuggestions = [...new Set(suggestions)];
    
    uniqueSuggestions.forEach((amount, index) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.onclick = () => setReceivedAmount(amount);
        btn.className = 'px-3 py-1.5 rounded-lg border border-border/50 bg-background hover:bg-surface-hover text-[11px] font-semibold text-text-muted hover:text-text-main transition-colors flex items-center gap-1';
        
        const label = index === 0 ? '(Exacto)' : '';
        btn.innerHTML = `$${amount.toLocaleString('es-AR')} <span class="opacity-50 text-[9px] font-normal">${label}</span>`;
        container.appendChild(btn);
    });
}

function setReceivedAmount(amount) {
    document.getElementById('monto_recibido').value = amount;
    calculateChange();
}

function calculateChange() {
    const method = document.getElementById('modal_metodo_pago').value;
    if (method !== 'Efectivo') return;
    
    const received = parseFloat(document.getElementById('monto_recibido').value) || 0;
    const changeAmountEl = document.getElementById('modal-change-amount');
    const changeHint = document.getElementById('change-hint');
    const iconContainer = document.getElementById('change-icon-container');
    const confirmBtn = document.getElementById('btn-confirm-sale');
    const statusBadge = document.getElementById('cash-status-badge');
    
    if (received < currentTotal) {
        const remaining = currentTotal - received;
        changeAmountEl.textContent = `$${remaining.toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        changeAmountEl.classList.remove('text-green-400');
        changeAmountEl.classList.add('text-red-400');
        
        changeHint.textContent = `Faltan $${remaining.toLocaleString('es-AR')}`;
        iconContainer.className = 'w-8 h-8 rounded-full bg-red-500/10 flex items-center justify-center text-red-400 transition-colors';
        iconContainer.innerHTML = '<span class="material-symbols-outlined text-[16px]">warning</span>';
        
        statusBadge.textContent = 'Falta monto';
        statusBadge.className = 'text-[10px] px-2 py-0.5 rounded-md font-bold uppercase tracking-wider bg-red-500/10 border border-red-500/20 text-red-400';
        confirmBtn.disabled = true;
    } else {
        const change = received - currentTotal;
        changeAmountEl.textContent = `$${change.toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        changeAmountEl.classList.remove('text-red-400');
        changeAmountEl.classList.add('text-green-400');
        
        changeHint.textContent = `¡Entregar cambio de $${change.toLocaleString('es-AR')}!`;
        iconContainer.className = 'w-8 h-8 rounded-full bg-green-500/10 flex items-center justify-center text-green-400 transition-colors';
        iconContainer.innerHTML = '<span class="material-symbols-outlined text-[16px]">trending_down</span>';
        
        statusBadge.textContent = 'Monto cubierto ✓';
        statusBadge.className = 'text-[10px] px-2 py-0.5 rounded-md font-bold uppercase tracking-wider bg-green-500/10 border border-green-500/20 text-green-400';
        confirmBtn.disabled = false;
    }
}

async function submitSaleFromModal() {
    const confirmBtn = document.getElementById('btn-confirm-sale');
    if (confirmBtn.disabled) return;
    
    const metodoPago = document.getElementById('modal_metodo_pago').value;

    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<span class="material-symbols-outlined text-[18px] animate-spin">refresh</span> Procesando...';

    try {
        const response = await fetch('new_sale.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ items: cart, metodo_pago: metodoPago })
        });
        
        const data = await response.json();
        
        if (data.success) {
            cart = [];
            localStorage.removeItem('nokia_sales_cart');
            updateCartTable();
            closePaymentModal();
            showSuccessModal(data.id_venta);
        } else {
            showToast(data.message, 'error');
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<span class="material-symbols-outlined text-[18px]">receipt_long</span> Confirmar Venta';
        }
    } catch (err) {
        showToast('Error de conexión al procesar la venta', 'error');
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = '<span class="material-symbols-outlined text-[18px]">receipt_long</span> Confirmar Venta';
    }
}
