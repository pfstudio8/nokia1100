// assets/js/pages/new_sale.js
let cart = [];

// Escapa texto para insertarlo de forma segura en HTML (previene XSS)
function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, ch => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[ch]));
}

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

    // Auto-seleccionar producto si viene por parámetro de URL (ej. desde Catálogo Rápido)
    const urlParams = new URLSearchParams(window.location.search);
    const prodId = urlParams.get('product_id');
    if (prodId) {
        const select = document.getElementById('id_producto');
        if (select) {
            select.value = prodId;
            select.dispatchEvent(new Event('change'));
        }
    }
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
        const stockUrl = (window.BASE_URL ? window.BASE_URL : '../..') + `/api/check_stock.php?id_producto=${id}`;
        const response = await fetch(stockUrl);
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

    // Persistir carrito en localStorage siempre (evita bug de resurrección al borrar el último elemento)
    localStorage.setItem('nokia_sales_cart', JSON.stringify(cart));

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
            <td class="p-4 text-sm font-medium text-text-main">${escapeHtml(item.nombre)}</td>
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
}

let currentTotal = 0;

function formatArs(num) {
    return Number(num || 0).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function getEffectiveCashAmountToPay() {
    const mode = document.getElementById('modal_modo_pago')?.value || 'unico';
    if (mode === 'unico') {
        const method = document.getElementById('modal_metodo_pago')?.value || 'Efectivo';
        return method === 'Efectivo' ? currentTotal : 0;
    }
    const m1 = document.getElementById('combo_metodo_1')?.value || 'Efectivo';
    const m2 = document.getElementById('combo_metodo_2')?.value || 'Transferencia';
    const v1 = parseFloat(document.getElementById('combo_monto_1')?.value) || 0;
    const v2 = parseFloat(document.getElementById('combo_monto_2')?.value) || 0;
    let cash = 0;
    if (m1 === 'Efectivo') cash += v1;
    if (m2 === 'Efectivo') cash += v2;
    return Math.round(cash * 100) / 100;
}

function openPaymentModal() {
    if (cart.length === 0) { showToast('El carrito está vacío', 'warning'); return; }
    
    // Update modal UI with cart details
    currentTotal = Math.round(cart.reduce((sum, item) => sum + (item.precio * item.cantidad), 0) * 100) / 100;
    
    document.getElementById('modal-items-count').textContent = cart.length === 1 ? '1 artículo' : `${cart.length} artículos`;
    document.getElementById('modal-total-amount').textContent = `$${formatArs(currentTotal)}`;
    
    const firstItem = cart[0];
    document.getElementById('modal-first-item-name').textContent = firstItem.nombre;
    const additionalText = cart.length > 1 ? ` (+${cart.length - 1} más)` : '';
    document.getElementById('modal-first-item-desc').textContent = `${firstItem.cantidad} x $${formatArs(firstItem.precio)}${additionalText}`;
    document.getElementById('modal-first-item-price').textContent = `$${formatArs(firstItem.precio * firstItem.cantidad)}`;
    
    // Reset state to single payment in Efectivo
    setPaymentMode('unico');
    selectPaymentMethod('Efectivo');
    document.getElementById('monto_recibido').value = currentTotal;
    generateQuickSuggestions(currentTotal);
    calculateChange();
    
    const modal = document.getElementById('payment-modal');
    modal.classList.remove('hidden');
}

function closePaymentModal() {
    const modal = document.getElementById('payment-modal');
    modal.classList.add('hidden');
}

function setPaymentMode(mode) {
    const modeInput = document.getElementById('modal_modo_pago');
    if (modeInput) modeInput.value = mode;

    const btnSingle = document.getElementById('btn-mode-single');
    const btnCombined = document.getElementById('btn-mode-combined');
    const singleBlock = document.getElementById('single-payment-block');
    const combinedBlock = document.getElementById('combined-payment-block');

    if (mode === 'combinado') {
        btnCombined.className = 'px-3 py-1 rounded-md bg-primary text-background transition-all flex items-center gap-1';
        btnSingle.className = 'px-3 py-1 rounded-md text-text-muted hover:text-text-main transition-all';
        singleBlock.classList.add('hidden');
        combinedBlock.classList.remove('hidden');

        // Inicializar valores por defecto en pago combinado (50% y 50%)
        const m1 = document.getElementById('combo_metodo_1');
        const m2 = document.getElementById('combo_metodo_2');
        if (m1 && m2 && m1.value === m2.value) {
            m2.value = m1.value === 'Efectivo' ? 'Transferencia' : 'Efectivo';
        }
        const half1 = Math.round((currentTotal / 2) * 100) / 100;
        const half2 = Math.round((currentTotal - half1) * 100) / 100;
        document.getElementById('combo_monto_1').value = half1 > 0 ? half1.toFixed(2) : '';
        document.getElementById('combo_monto_2').value = half2 > 0 ? half2.toFixed(2) : '';

        validateCombinedPayment();
    } else {
        btnSingle.className = 'px-3 py-1 rounded-md bg-primary text-background transition-all';
        btnCombined.className = 'px-3 py-1 rounded-md text-text-muted hover:text-text-main transition-all flex items-center gap-1';
        singleBlock.classList.remove('hidden');
        combinedBlock.classList.add('hidden');

        selectPaymentMethod(document.getElementById('modal_metodo_pago').value || 'Efectivo');
    }
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

    const singleNote = document.getElementById('single-caja-note');
    if (singleNote) {
        if (method === 'Efectivo') {
            singleNote.innerHTML = `<span class="material-symbols-outlined text-[13px] text-primary">point_of_sale</span><span>Ingresa al conteo/arqueo de caja en Efectivo: <strong>$${formatArs(currentTotal)}</strong></span>`;
        } else {
            singleNote.innerHTML = `<span class="material-symbols-outlined text-[13px] text-text-muted">info</span><span>Pago por <strong>${escapeHtml(method)}</strong>: se registra en la venta, pero <strong>no entra</strong> en el conteo físico de caja.</span>`;
        }
    }
    
    // Show/hide cash liquidation block
    const cashBlock = document.getElementById('cash-liquidation-block');
    const confirmBtn = document.getElementById('btn-confirm-sale');
    
    if (method === 'Efectivo') {
        cashBlock.style.display = 'block';
        const recInput = document.getElementById('monto_recibido');
        if (!recInput.value || parseFloat(recInput.value) < currentTotal) {
            recInput.value = currentTotal;
        }
        generateQuickSuggestions(currentTotal);
        calculateChange();
    } else {
        cashBlock.style.display = 'none';
        confirmBtn.disabled = false;
    }
}

function onCombinedMethodChange(which) {
    const m1 = document.getElementById('combo_metodo_1');
    const m2 = document.getElementById('combo_metodo_2');
    if (m1 && m2 && m1.value === m2.value) {
        const options = ['Efectivo', 'Transferencia', 'Tarjeta'];
        const alt = options.find(o => o !== m1.value) || 'Transferencia';
        if (which === 1) {
            m2.value = alt;
        } else {
            m1.value = alt;
        }
    }
    validateCombinedPayment();
}

function onCombinedAmountInput(which) {
    const input1 = document.getElementById('combo_monto_1');
    const input2 = document.getElementById('combo_monto_2');
    const v1 = parseFloat(input1.value);
    const v2 = parseFloat(input2.value);

    // Autocompletar el monto restante en el otro medio si el monto ingresado es menor al total
    if (which === 1 && !isNaN(v1) && v1 > 0 && v1 < currentTotal) {
        const rem = Math.round((currentTotal - v1) * 100) / 100;
        input2.value = rem.toFixed(2);
    } else if (which === 2 && !isNaN(v2) && v2 > 0 && v2 < currentTotal) {
        const rem = Math.round((currentTotal - v2) * 100) / 100;
        input1.value = rem.toFixed(2);
    }

    validateCombinedPayment();
}

function validateCombinedPayment() {
    const m1 = document.getElementById('combo_metodo_1')?.value || 'Efectivo';
    const m2 = document.getElementById('combo_metodo_2')?.value || 'Transferencia';
    const v1 = Math.round((parseFloat(document.getElementById('combo_monto_1')?.value) || 0) * 100) / 100;
    const v2 = Math.round((parseFloat(document.getElementById('combo_monto_2')?.value) || 0) * 100) / 100;
    const sum = Math.round((v1 + v2) * 100) / 100;
    const diff = Math.round((sum - currentTotal) * 100) / 100;

    const sumEl = document.getElementById('combo-sum-amount');
    const badgeEl = document.getElementById('combo-status-badge');
    const msgEl = document.getElementById('combo-validation-msg');
    const cajaNoteEl = document.getElementById('combo-caja-note');
    const confirmBtn = document.getElementById('btn-confirm-sale');
    const cashBlock = document.getElementById('cash-liquidation-block');

    if (sumEl) {
        sumEl.textContent = `$${formatArs(sum)} / $${formatArs(currentTotal)}`;
    }

    let isValid = true;
    let errorMsg = '';

    if (m1 === m2) {
        isValid = false;
        errorMsg = 'Los 2 medios de pago deben ser diferentes.';
    } else if (v1 <= 0 || v2 <= 0) {
        isValid = false;
        errorMsg = 'Cada medio de pago debe tener un monto mayor a $0.00.';
    } else if (Math.abs(diff) > 0.01) {
        isValid = false;
        if (diff < 0) {
            errorMsg = `Faltan $${formatArs(Math.abs(diff))} para alcanzar el total de la venta ($${formatArs(currentTotal)}).`;
        } else {
            errorMsg = `La suma excede el total de la venta por $${formatArs(diff)}.`;
        }
    }

    const cashPortion = getEffectiveCashAmountToPay();
    if (cajaNoteEl) {
        if (cashPortion > 0) {
            const nonCashPortion = Math.round((currentTotal - cashPortion) * 100) / 100;
            const otherMethod = m1 === 'Efectivo' ? m2 : m1;
            cajaNoteEl.innerHTML = `<span class="material-symbols-outlined text-[13px]">point_of_sale</span><span>Arqueo de caja: suma solo <strong>$${formatArs(cashPortion)}</strong> (Efectivo). <strong>$${formatArs(nonCashPortion)}</strong> (${escapeHtml(otherMethod)}) no entra en caja.</span>`;
        } else {
            cajaNoteEl.innerHTML = `<span class="material-symbols-outlined text-[13px]">info</span><span>Sin efectivo: ambos pagos se registran en la venta pero <strong>no suman</strong> al conteo físico de caja.</span>`;
        }
    }

    if (!isValid) {
        if (badgeEl) {
            badgeEl.textContent = 'Monto inválido';
            badgeEl.className = 'text-[10px] px-2 py-0.5 rounded font-bold uppercase tracking-wider bg-red-500/10 border border-red-500/20 text-red-400';
        }
        if (msgEl) {
            msgEl.textContent = errorMsg;
            msgEl.className = 'text-[11px] text-red-400 font-medium';
        }
        cashBlock.style.display = 'none';
        confirmBtn.disabled = true;
        return false;
    }

    if (badgeEl) {
        badgeEl.textContent = 'Suma exacta ✓';
        badgeEl.className = 'text-[10px] px-2 py-0.5 rounded font-bold uppercase tracking-wider bg-green-500/10 border border-green-500/20 text-green-400';
    }
    if (msgEl) {
        msgEl.textContent = `${m1}: $${formatArs(v1)} + ${m2}: $${formatArs(v2)} = $${formatArs(currentTotal)}`;
        msgEl.className = 'text-[11px] text-green-400 font-medium';
    }

    if (cashPortion > 0) {
        cashBlock.style.display = 'block';
        document.getElementById('monto_recibido').value = cashPortion;
        generateQuickSuggestions(cashPortion);
        calculateChange();
    } else {
        cashBlock.style.display = 'none';
        confirmBtn.disabled = false;
    }

    return true;
}

function generateQuickSuggestions(total) {
    const container = document.getElementById('quick-suggestions');
    if (!container) return;
    container.innerHTML = '';
    if (total <= 0) return;
    
    const suggestions = [total];
    
    let magnitude = Math.pow(10, Math.floor(Math.log10(total)));
    if (magnitude < 1000) magnitude = 1000;
    
    let nextRound = Math.ceil(total / (magnitude/10)) * (magnitude/10);
    if (nextRound === total) nextRound += (magnitude/10);
    suggestions.push(nextRound);
    
    let nextBigRound = Math.ceil(total / magnitude) * magnitude;
    if (nextBigRound === total || nextBigRound <= nextRound) nextBigRound += magnitude;
    suggestions.push(nextBigRound);
    
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
    const cashRequired = getEffectiveCashAmountToPay();
    if (cashRequired <= 0) return;
    
    const received = parseFloat(document.getElementById('monto_recibido').value) || 0;
    const changeAmountEl = document.getElementById('modal-change-amount');
    const changeHint = document.getElementById('change-hint');
    const iconContainer = document.getElementById('change-icon-container');
    const confirmBtn = document.getElementById('btn-confirm-sale');
    const statusBadge = document.getElementById('cash-status-badge');
    
    if (received + 0.005 < cashRequired) {
        const remaining = cashRequired - received;
        changeAmountEl.textContent = `$${formatArs(remaining)}`;
        changeAmountEl.classList.remove('text-green-400');
        changeAmountEl.classList.add('text-red-400');
        
        changeHint.textContent = `Faltan $${formatArs(remaining)} en efectivo`;
        iconContainer.className = 'w-8 h-8 rounded-full bg-red-500/10 flex items-center justify-center text-red-400 transition-colors';
        iconContainer.innerHTML = '<span class="material-symbols-outlined text-[16px]">warning</span>';
        
        statusBadge.textContent = 'Falta monto';
        statusBadge.className = 'text-[10px] px-2 py-0.5 rounded-md font-bold uppercase tracking-wider bg-red-500/10 border border-red-500/20 text-red-400';
        confirmBtn.disabled = true;
    } else {
        const change = Math.max(0, received - cashRequired);
        changeAmountEl.textContent = `$${formatArs(change)}`;
        changeAmountEl.classList.remove('text-red-400');
        changeAmountEl.classList.add('text-green-400');
        
        changeHint.textContent = `¡Entregar cambio de $${formatArs(change)}!`;
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
    
    const mode = document.getElementById('modal_modo_pago')?.value || 'unico';
    const descripcion = document.getElementById('venta_descripcion') ? document.getElementById('venta_descripcion').value : '';
    let metodoPago = document.getElementById('modal_metodo_pago').value;
    let pagos = [];

    if (mode === 'combinado') {
        if (!validateCombinedPayment()) {
            showToast('Verifique los montos del pago combinado antes de confirmar.', 'error');
            return;
        }
        const m1 = document.getElementById('combo_metodo_1').value;
        const m2 = document.getElementById('combo_metodo_2').value;
        const v1 = Math.round((parseFloat(document.getElementById('combo_monto_1').value) || 0) * 100) / 100;
        const v2 = Math.round((parseFloat(document.getElementById('combo_monto_2').value) || 0) * 100) / 100;
        pagos = [
            { metodo: m1, monto: v1 },
            { metodo: m2, monto: v2 }
        ];
        metodoPago = `${m1} + ${m2}`;
    } else {
        pagos = [
            { metodo: metodoPago, monto: currentTotal }
        ];
    }

    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<span class="material-symbols-outlined text-[18px] animate-spin">refresh</span> Procesando...';

    try {
        const response = await fetch('new_sale.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                items: cart, 
                metodo_pago: metodoPago,
                pagos: pagos,
                descripcion: descripcion,
                csrf_token: window.CSRF_TOKEN || ''
            })
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

function showSuccessModal(id_venta) {
    const modal = document.getElementById('success-modal');
    const printBtn = document.getElementById('btn-print-invoice');
    
    // Configurar el botón de imprimir para que vaya a la factura
    printBtn.onclick = () => {
        window.open(`index.php?action=invoice&id=${id_venta}`, '_blank');
    };
    
    modal.classList.remove('hidden');
}

function closeSuccessModal() {
    const modal = document.getElementById('success-modal');
    modal.classList.add('hidden');
    // Ya que se cerró el modal, asumimos que el usuario quiere hacer una nueva venta
    // Resetear formulario si es necesario (carrito ya fue vaciado)
    document.getElementById('venta_descripcion').value = '';
    const confirmBtn = document.getElementById('btn-confirm-sale');
    confirmBtn.disabled = false;
    confirmBtn.innerHTML = '<span class="material-symbols-outlined text-[18px]">receipt_long</span> Confirmar Venta';
}
