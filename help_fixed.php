<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/classes/Layout.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

Layout::renderHead('NOKIA1100 | Ayuda Rápida');

// Elegir qué sidebar renderizar según el rol del usuario
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    Layout::renderAdminSidebar('ayuda');
} else {
    Layout::renderEmployeeSidebar('ayuda');
}
?>

<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen flex flex-col bg-background relative">
    <!-- Decoración de fondo suave -->
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[300px] bg-primary/5 rounded-full blur-[100px] pointer-events-none -z-10"></div>

    <div class="max-w-4xl mx-auto w-full relative z-10">
        <!-- HEADER -->
        <div class="mb-12">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-primary/10 border border-primary/20 text-primary text-xs font-bold uppercase tracking-widest mb-4">
                <span class="material-symbols-outlined text-[16px]">menu_book</span>
                Manual de Uso
            </div>
            <h1 class="text-3xl md:text-5xl font-display font-bold text-text-main tracking-tight flex items-center gap-3">
                Ayuda Rápida
            </h1>
            <p class="text-text-muted mt-3 text-lg max-w-2xl">Guía paso a paso para utilizar las funciones principales del sistema y sacar el máximo provecho a la plataforma.</p>
        </div>
        
        <div class="space-y-8">
            <!-- PASO 1 -->
            <section class="bg-card border border-border rounded-3xl p-6 md:p-8 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group">
                <div class="absolute -top-20 -right-20 w-48 h-48 bg-primary/5 rounded-full blur-3xl transition-all group-hover:bg-primary/10"></div>
                <div class="relative z-10">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center font-display font-bold text-xl border border-primary/20 shadow-inner">1</div>
                        <h2 class="text-2xl md:text-3xl font-display font-bold text-text-main">Inventario y Stock</h2>
                    </div>
                    <div class="md:ml-16 text-text-main leading-relaxed space-y-5">
                        <p class="text-text-muted text-base">El módulo de inventario permite administrar todos los productos y repuestos disponibles en el local.</p>
                        <ul class="space-y-3">
                            <li class="flex items-start gap-3">
                                <span class="material-symbols-outlined text-primary text-[20px] mt-0.5">check_circle</span>
                                <div><strong class="text-text-main">Consulta:</strong> En la vista principal puedes ver la disponibilidad de todos los repuestos y usar la barra de búsqueda para filtrar rápidamente.</div>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="material-symbols-outlined text-primary text-[20px] mt-0.5">edit_square</span>
                                <div><strong class="text-text-main">Actualizar Stock:</strong> Haz clic en el botón de "Editar" en cualquier producto para actualizar las cantidades, costo y precio de venta de acuerdo a los nuevos ingresos.</div>
                            </li>
                        </ul>

                        <!-- Tutorial: Agregar Producto (Movie Card Style Carousel) -->
                        <div class="mt-10 mb-8" id="tutorial-carousel">
                            <h3 class="text-xl font-display font-bold text-text-main mb-6 flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary">library_add</span>
                                ¿Cómo agregar un producto paso a paso?
                            </h3>
                            
                            <div class="overflow-hidden relative rounded-xl shadow-[0_25px_50px_rgba(0,0,0,0.6)] border border-border">
                                <div class="flex transition-transform duration-500 ease-in-out" id="carousel-track">
                                    <!-- Step 1 -->
                                    <div class="w-full shrink-0">
                                        <div class="flex flex-col md:flex-row bg-[#1e1b26] relative group">
                                            <div class="relative w-full md:w-[50%] min-h-[250px] md:min-h-[350px] overflow-hidden bg-[#15131b]">
                                                <div class="absolute inset-0 bg-cover bg-no-repeat transition-transform duration-700 hover:scale-105" style="background-image: url('<?php echo BASE_URL; ?>/assets/images/help/step1.png'); background-position: 90% 15%;"></div>
                                            </div>
                                            <div class="relative w-full md:w-[50%] p-6 md:p-10 z-10 flex flex-col justify-center">
                                                <div class="flex justify-between items-start mb-2">
                                                    <h4 class="text-2xl font-bold text-white tracking-tight flex items-center gap-3 font-display">
                                                        <span class="text-primary opacity-80 text-lg">01</span> Entrar al panel
                                                    </h4>
                                                    <span class="bg-primary text-white px-2 py-1 rounded text-xs font-bold tracking-wider shadow-sm">PASO 1</span>
                                                </div>
                                                <div class="flex gap-2 mb-4">
                                                    <span class="bg-primary/10 border border-primary/30 px-3 py-1 rounded-full text-[11px] font-medium text-[#d7d7d7]">Inventario</span>
                                                    <span class="bg-primary/10 border border-primary/30 px-3 py-1 rounded-full text-[11px] font-medium text-[#d7d7d7]">Acción</span>
                                                </div>
                                                <p class="text-[13px] leading-relaxed text-[#b4b4b4] text-justify mb-5">
                                                    Dirígete al módulo de Inventario desde el menú lateral. Una vez allí, busca el botón destacado con el símbolo de <strong class="text-white">"+ Añadir"</strong> ubicado en la esquina superior derecha. Esto abrirá el formulario de registro de nuevos ítems.
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Step 2 -->
                                    <div class="w-full shrink-0">
                                        <div class="flex flex-col md:flex-row bg-[#1e1b26] relative group">
                                            <div class="relative w-full md:w-[50%] min-h-[250px] md:min-h-[350px] overflow-hidden bg-[#15131b]">
                                                <div class="absolute inset-0 bg-cover bg-no-repeat transition-transform duration-700 hover:scale-105" style="background-image: url('<?php echo BASE_URL; ?>/assets/images/help/step2.png'); background-position: 0% 25%;"></div>
                                            </div>
                                            <div class="relative w-full md:w-[50%] p-6 md:p-10 z-10 flex flex-col justify-center">
                                                <div class="flex justify-between items-start mb-2">
                                                    <h4 class="text-2xl font-bold text-white tracking-tight flex items-center gap-3 font-display">
                                                        <span class="text-primary opacity-80 text-lg">02</span> Elegir categoría
                                                    </h4>
                                                    <span class="bg-primary text-white px-2 py-1 rounded text-xs font-bold tracking-wider shadow-sm">PASO 2</span>
                                                </div>
                                                <div class="flex gap-2 mb-4">
                                                    <span class="bg-primary/10 border border-primary/30 px-3 py-1 rounded-full text-[11px] font-medium text-[#d7d7d7]">Clasificación</span>
                                                </div>
                                                <p class="text-[13px] leading-relaxed text-[#b4b4b4] text-justify mb-5">
                                                    Lo primero que debes hacer en el formulario es <strong class="text-white">seleccionar una categoría</strong>. El sistema es inteligente y rellenará de forma automática el "Nombre del producto" dependiendo de lo que elijas, ahorrándote tiempo de tipeo.
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Step 3 -->
                                    <div class="w-full shrink-0">
                                        <div class="flex flex-col md:flex-row bg-[#1e1b26] relative group">
                                            <div class="relative w-full md:w-[50%] min-h-[250px] md:min-h-[350px] overflow-hidden bg-[#15131b]">
                                                <div class="absolute inset-0 bg-cover bg-no-repeat transition-transform duration-700 hover:scale-105" style="background-image: url('<?php echo BASE_URL; ?>/assets/images/help/step3.png'); background-position: 40% 95%;"></div>
                                            </div>
                                            <div class="relative w-full md:w-[50%] p-6 md:p-10 z-10 flex flex-col justify-center">
                                                <div class="flex justify-between items-start mb-2">
                                                    <h4 class="text-2xl font-bold text-white tracking-tight flex items-center gap-3 font-display">
                                                        <span class="text-primary opacity-80 text-lg">03</span> Completar y Guardar
                                                    </h4>
                                                    <span class="bg-primary text-white px-2 py-1 rounded text-xs font-bold tracking-wider shadow-sm">PASO 3</span>
                                                </div>
                                                <div class="flex gap-2 mb-4">
                                                    <span class="bg-primary/10 border border-primary/30 px-3 py-1 rounded-full text-[11px] font-medium text-[#d7d7d7]">Detalles</span>
                                                    <span class="bg-primary/10 border border-primary/30 px-3 py-1 rounded-full text-[11px] font-medium text-[#d7d7d7]">Finalizar</span>
                                                </div>
                                                <p class="text-[13px] leading-relaxed text-[#b4b4b4] text-justify mb-5">
                                                    Rellena los campos obligatorios como la <strong class="text-white">Marca, Modelo, Precio y Stock Inicial</strong>. Recuerda que los campos marcados como "(Opcional)" puedes dejarlos vacíos. Una vez terminado, presiona <strong class="text-white">"Registrar Producto"</strong> al final de la página.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Carousel Controls -->
                            <div class="flex justify-between items-center mt-6">
                                <button type="button" onclick="prevSlide()" id="btn-prev" class="bg-surface border border-border text-text-main px-4 py-2.5 rounded-xl flex items-center gap-2 hover:bg-surface-hover disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-sm font-bold">
                                    <span class="material-symbols-outlined text-[18px]">arrow_back</span> Anterior
                                </button>
                                <div class="flex gap-2">
                                    <div class="w-2 h-2 rounded-full bg-primary indicator transition-colors"></div>
                                    <div class="w-2 h-2 rounded-full bg-border indicator transition-colors"></div>
                                    <div class="w-2 h-2 rounded-full bg-border indicator transition-colors"></div>
                                </div>
                                <button type="button" onclick="nextSlide()" id="btn-next" class="bg-primary text-white px-4 py-2.5 rounded-xl flex items-center gap-2 hover:bg-primary-hover disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-sm font-bold shadow-md shadow-primary/20">
                                    Siguiente <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                                </button>
                            </div>
                        </div>


                    </div>
                </div>
            </section>
            
            <!-- PASO 2 -->
            <section class="bg-card border border-border rounded-3xl p-6 md:p-8 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group">
                <div class="absolute -top-20 -right-20 w-48 h-48 bg-secondary/5 rounded-full blur-3xl transition-all group-hover:bg-secondary/10"></div>
                <div class="relative z-10">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 rounded-2xl bg-secondary/10 text-secondary flex items-center justify-center font-display font-bold text-xl border border-secondary/20 shadow-inner">2</div>
                        <h2 class="text-2xl md:text-3xl font-display font-bold text-text-main">Clientes</h2>
                    </div>
                    <div class="md:ml-16 text-text-main leading-relaxed space-y-5">
                        <p class="text-text-muted text-base">Es fundamental registrar la información de tus clientes para agilizar el ingreso de reparaciones o procesos de facturación de ventas.</p>
                        <ul class="space-y-3">
                            <li class="flex items-start gap-3">
                                <span class="material-symbols-outlined text-secondary text-[20px] mt-0.5">person_add</span>
                                <div><strong class="text-text-main">Alta de Cliente:</strong> Ingresa al módulo de Clientes, haz clic en "Nuevo Cliente" y completa sus datos básicos (Nombre, Teléfono, Correo).</div>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="material-symbols-outlined text-secondary text-[20px] mt-0.5">quick_reference_all</span>
                                <div><strong class="text-text-main">Uso en otros módulos:</strong> Al cargar una nueva orden de reparación o una venta, podrás seleccionar rápidamente a un cliente previamente guardado, ahorrando mucho tiempo.</div>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="material-symbols-outlined text-secondary text-[20px] mt-0.5">history</span>
                                <div><strong class="text-text-main">Historial:</strong> Todos los movimientos, compras y reparaciones vinculadas a este cliente se asociarán automáticamente a su perfil para futuras consultas.</div>
                            </li>
                        </ul>

                        <!-- Tutorial: Agregar Cliente (Movie Card Style Carousel) -->
                        <div class="mt-10 mb-8" id="tutorial-carousel-2">
                            <h3 class="text-xl font-display font-bold text-text-main mb-6 flex items-center gap-2">
                                <span class="material-symbols-outlined text-secondary">person_add</span>
                                ¿Cómo registrar un cliente paso a paso?
                            </h3>
                            
                            <div class="overflow-hidden relative rounded-xl shadow-[0_25px_50px_rgba(0,0,0,0.6)] border border-border">
                                <div class="flex transition-transform duration-500 ease-in-out" id="carousel-track-2">
                                    <!-- Step 1 -->
                                    <div class="w-full shrink-0">
                                        <div class="flex flex-col md:flex-row bg-[#1e1b26] relative group">
                                            <div class="relative w-full md:w-[50%] min-h-[250px] md:min-h-[350px] overflow-hidden bg-[#15131b]">
                                                <div class="absolute inset-0 bg-cover bg-no-repeat transition-transform duration-700 hover:scale-105" style="background-image: url('<?php echo BASE_URL; ?>/assets/images/help/client1.png'); background-position: 90% 15%;"></div>
                                            </div>
                                            <div class="relative w-full md:w-[50%] p-6 md:p-10 z-10 flex flex-col justify-center">
                                                <div class="flex justify-between items-start mb-2">
                                                    <h4 class="text-2xl font-bold text-white tracking-tight flex items-center gap-3 font-display">
                                                        <span class="text-secondary opacity-80 text-lg">01</span> Entrar a Clientes
                                                    </h4>
                                                    <span class="bg-secondary text-white text-xs px-3 py-1 rounded-full font-bold uppercase tracking-wider">Paso 1</span>
                                                </div>
                                                <div class="flex gap-2 mb-6 mt-3">
                                                    <span class="px-3 py-1 rounded-full border border-white/10 text-xs font-medium text-white/60">Navegación</span>
                                                </div>
                                                <p class="text-white/80 leading-relaxed text-lg mb-6">Dirígete al módulo de Clientes desde el menú lateral. Haz clic en el botón <strong class="text-white">"+ Nuevo Cliente"</strong> ubicado en la esquina superior derecha.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Step 2 -->
                                    <div class="w-full shrink-0">
                                        <div class="flex flex-col md:flex-row bg-[#1e1b26] relative group">
                                            <div class="relative w-full md:w-[50%] min-h-[250px] md:min-h-[350px] overflow-hidden bg-[#15131b]">
                                                <div class="absolute inset-0 bg-cover bg-no-repeat transition-transform duration-700 hover:scale-105" style="background-image: url('<?php echo BASE_URL; ?>/assets/images/help/client2.png'); background-position: 80% 50%;"></div>
                                            </div>
                                            <div class="relative w-full md:w-[50%] p-6 md:p-10 z-10 flex flex-col justify-center">
                                                <div class="flex justify-between items-start mb-2">
                                                    <h4 class="text-2xl font-bold text-white tracking-tight flex items-center gap-3 font-display">
                                                        <span class="text-secondary opacity-80 text-lg">02</span> Rellenar y Guardar
                                                    </h4>
                                                    <span class="bg-secondary text-white text-xs px-3 py-1 rounded-full font-bold uppercase tracking-wider">Paso 2</span>
                                                </div>
                                                <div class="flex gap-2 mb-6 mt-3">
                                                    <span class="px-3 py-1 rounded-full border border-white/10 text-xs font-medium text-white/60">Formulario</span>
                                                </div>
                                                <p class="text-white/80 leading-relaxed text-lg mb-6">Completa los datos del cliente. El sistema requiere al menos un nombre para identificarlo. Cuando termines, haz clic en <strong class="text-white">"Guardar Cliente"</strong>.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Carousel Controls -->
                                <div class="absolute bottom-0 left-0 right-0 p-4 md:p-6 bg-gradient-to-t from-black/80 to-transparent flex justify-between items-center z-20">
                                    <button type="button" onclick="prevSlide2()" id="btn-prev-2" class="bg-white/10 text-white backdrop-blur-md px-4 py-2.5 rounded-xl flex items-center gap-2 hover:bg-white/20 disabled:opacity-30 disabled:cursor-not-allowed transition-colors text-sm font-bold border border-white/10">
                                        <span class="material-symbols-outlined text-[18px]">arrow_back</span> Anterior
                                    </button>
                                    <div class="flex gap-2">
                                        <div class="w-4 h-2 rounded-full bg-secondary indicator-2 transition-colors"></div>
                                        <div class="w-2 h-2 rounded-full bg-border indicator-2 transition-colors"></div>
                                    </div>
                                    <button type="button" onclick="nextSlide2()" id="btn-next-2" class="bg-secondary text-white px-4 py-2.5 rounded-xl flex items-center gap-2 hover:bg-secondary/80 disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-sm font-bold shadow-md shadow-secondary/20">
                                        Siguiente <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- PASO 3 -->
            <section class="bg-card border border-border rounded-3xl p-6 md:p-8 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group">
                <div class="absolute -top-20 -right-20 w-48 h-48 bg-[#0EA5A0]/5 rounded-full blur-3xl transition-all group-hover:bg-[#0EA5A0]/10"></div>
                <div class="relative z-10">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 rounded-2xl bg-[#0EA5A0]/10 text-[#0EA5A0] flex items-center justify-center font-display font-bold text-xl border border-[#0EA5A0]/20 shadow-inner">3</div>
                        <h2 class="text-2xl md:text-3xl font-display font-bold text-text-main">Taller de Reparaciones</h2>
                    </div>
                    <div class="md:ml-16 text-text-main leading-relaxed space-y-5">
                        <p class="text-text-muted text-base">Este es el corazón del servicio técnico. Aquí harás el seguimiento de cada dispositivo ingresado al local.</p>
                        <ul class="space-y-3">
                            <li class="flex items-start gap-3">
                                <span class="material-symbols-outlined text-[#0EA5A0] text-[20px] mt-0.5">build</span>
                                <div><strong class="text-text-main">Nueva Orden:</strong> Haz clic en "Nueva Reparación". Selecciona el cliente, ingresa el modelo del dispositivo, IMEI/Serial (si aplica) y la falla reportada de forma clara.</div>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="material-symbols-outlined text-[#0EA5A0] text-[20px] mt-0.5">sync</span>
                                <div>
                                    <strong class="text-text-main">Gestión de Estados:</strong> Cambia el estado a medida que avanza el trabajo:
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
                                        <div class="flex items-center gap-2 bg-surface p-2 rounded-lg border border-border"><span class="w-2 h-2 rounded-full bg-yellow-500"></span><span class="text-xs font-medium">Pendiente: En espera de revisión.</span></div>
                                        <div class="flex items-center gap-2 bg-surface p-2 rounded-lg border border-border"><span class="w-2 h-2 rounded-full bg-blue-500"></span><span class="text-xs font-medium">En Proceso: Técnico trabajando.</span></div>
                                        <div class="flex items-center gap-2 bg-surface p-2 rounded-lg border border-border"><span class="w-2 h-2 rounded-full bg-[#0EA5A0]"></span><span class="text-xs font-medium">Listo: Esperando retiro.</span></div>
                                        <div class="flex items-center gap-2 bg-surface p-2 rounded-lg border border-border"><span class="w-2 h-2 rounded-full bg-primary"></span><span class="text-xs font-medium">Entregado: Dispositivo abonado.</span></div>
                                    </div>
                                </div>
                            </li>
                        </ul>


                    </div>
                </div>
            </section>
            
            <!-- PASO 4 -->
            <section class="bg-card border border-border rounded-3xl p-6 md:p-8 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group">
                <div class="absolute -top-20 -right-20 w-48 h-48 bg-primary/5 rounded-full blur-3xl transition-all group-hover:bg-primary/10"></div>
                <div class="relative z-10">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center font-display font-bold text-xl border border-primary/20 shadow-inner">4</div>
                        <h2 class="text-2xl md:text-3xl font-display font-bold text-text-main">Ventas y Cobros</h2>
                    </div>
                    <div class="md:ml-16 text-text-main leading-relaxed space-y-5">
                        <p class="text-text-muted text-base">Utiliza el módulo de ventas para gestionar los ingresos monetarios del negocio y entregar comprobantes válidos a los clientes.</p>
                        <ul class="space-y-3">
                            <li class="flex items-start gap-3">
                                <span class="material-symbols-outlined text-primary text-[20px] mt-0.5">point_of_sale</span>
                                <div><strong class="text-text-main">Cobro de Reparaciones:</strong> Desde la caja, puedes asociar la venta a una orden de reparación pendiente. El monto total se calculará de forma automática basado en el costo del servicio técnico y los repuestos empleados.</div>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="material-symbols-outlined text-primary text-[20px] mt-0.5">shopping_cart_checkout</span>
                                <div><strong class="text-text-main">Venta Directa:</strong> Si vendes un accesorio suelto (como una funda o cargador), búscalo para agregarlo al "carrito" de la venta y finaliza la operación ingresando el método de pago (Efectivo, Tarjeta, Transferencia).</div>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="material-symbols-outlined text-primary text-[20px] mt-0.5">receipt_long</span>
                                <div><strong class="text-text-main">Comprobantes:</strong> Una vez finalizada cualquier venta, el sistema generará una factura o ticket que puedes imprimir al instante o enviarlo de forma digital.</div>
                            </li>                        </ul>

                        <!-- Tutorial: Taller (Movie Card Style Carousel) -->
                        <div class="mt-10 mb-8" id="tutorial-carousel-3">
                            <h3 class="text-xl font-display font-bold text-text-main mb-6 flex items-center gap-2">
                                <span class="material-symbols-outlined text-[#0EA5A0]">build_circle</span>
                                ¿Cómo gestionar una reparación?
                            </h3>
                            
                            <div class="overflow-hidden relative rounded-xl shadow-[0_25px_50px_rgba(0,0,0,0.6)] border border-border">
                                <div class="flex transition-transform duration-500 ease-in-out" id="carousel-track-3">
                                    
                                    <!-- Step 1: Estado -->
                                    <div class="w-full shrink-0">
                                        <div class="flex flex-col md:flex-row bg-[#1e1b26] relative group">
                                            <div class="relative w-full md:w-[50%] min-h-[250px] md:min-h-[350px] overflow-hidden bg-[#15131b]">
                                                <div class="absolute inset-0 bg-cover bg-center bg-no-repeat transition-transform duration-700 hover:scale-105" style="background-image: url('<?php echo BASE_URL; ?>/assets/images/help/workshop1.png');"></div>
                                            </div>
                                            <div class="relative w-full md:w-[50%] p-6 md:p-10 z-10 flex flex-col justify-center">
                                                <div class="flex justify-between items-start mb-2">
                                                    <h4 class="text-2xl font-bold text-white tracking-tight flex items-center gap-3 font-display">
                                                        <span class="text-[#0EA5A0] opacity-80 text-lg">01</span> Actualizar Estado
                                                    </h4>
                                                    <span class="bg-[#0EA5A0] text-white text-xs px-3 py-1 rounded-full font-bold uppercase tracking-wider">Paso 1</span>
                                                </div>
                                                <div class="flex gap-2 mb-6 mt-3">
                                                    <span class="px-3 py-1 rounded-full border border-white/10 text-xs font-medium text-white/60">Seguimiento</span>
                                                </div>
                                                <p class="text-white/80 leading-relaxed text-lg mb-6">Mantén al cliente informado cambiando el estado del equipo: desde <strong class="text-white">Recibido</strong> hasta <strong class="text-white">Listo</strong>.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Step 2: Presupuesto -->
                                    <div class="w-full shrink-0">
                                        <div class="flex flex-col md:flex-row bg-[#1e1b26] relative group">
                                            <div class="relative w-full md:w-[50%] min-h-[250px] md:min-h-[350px] overflow-hidden bg-[#15131b]">
                                                <div class="absolute inset-0 bg-cover bg-center bg-no-repeat transition-transform duration-700 hover:scale-105" style="background-image: url('<?php echo BASE_URL; ?>/assets/images/help/workshop2.png');"></div>
                                            </div>
                                            <div class="relative w-full md:w-[50%] p-6 md:p-10 z-10 flex flex-col justify-center">
                                                <div class="flex justify-between items-start mb-2">
                                                    <h4 class="text-2xl font-bold text-white tracking-tight flex items-center gap-3 font-display">
                                                        <span class="text-[#0EA5A0] opacity-80 text-lg">02</span> Fijar Presupuesto
                                                    </h4>
                                                    <span class="bg-[#0EA5A0] text-white text-xs px-3 py-1 rounded-full font-bold uppercase tracking-wider">Paso 2</span>
                                                </div>
                                                <div class="flex gap-2 mb-6 mt-3">
                                                    <span class="px-3 py-1 rounded-full border border-white/10 text-xs font-medium text-white/60">Costos</span>
                                                </div>
                                                <p class="text-white/80 leading-relaxed text-lg mb-6">Una vez diagnosticado el problema, ingresa el valor total que se le cobrará al cliente por la reparación.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Step 3: Fotos -->
                                    <div class="w-full shrink-0">
                                        <div class="flex flex-col md:flex-row bg-[#1e1b26] relative group">
                                            <div class="relative w-full md:w-[50%] min-h-[250px] md:min-h-[350px] overflow-hidden bg-[#15131b]">
                                                <div class="absolute inset-0 bg-cover bg-center bg-no-repeat transition-transform duration-700 hover:scale-105" style="background-image: url('<?php echo BASE_URL; ?>/assets/images/help/workshop3.png');"></div>
                                            </div>
                                            <div class="relative w-full md:w-[50%] p-6 md:p-10 z-10 flex flex-col justify-center">
                                                <div class="flex justify-between items-start mb-2">
                                                    <h4 class="text-2xl font-bold text-white tracking-tight flex items-center gap-3 font-display">
                                                        <span class="text-[#0EA5A0] opacity-80 text-lg">03</span> Evidencia Visual
                                                    </h4>
                                                    <span class="bg-[#0EA5A0] text-white text-xs px-3 py-1 rounded-full font-bold uppercase tracking-wider">Paso 3</span>
                                                </div>
                                                <div class="flex gap-2 mb-6 mt-3">
                                                    <span class="px-3 py-1 rounded-full border border-white/10 text-xs font-medium text-white/60">Imágenes</span>
                                                </div>
                                                <p class="text-white/80 leading-relaxed text-lg mb-6">Sube fotos del dispositivo para registrar su estado al ingresar (roturas previas, pantalla, etc).</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Step 4: Repuestos -->
                                    <div class="w-full shrink-0">
                                        <div class="flex flex-col md:flex-row bg-[#1e1b26] relative group">
                                            <div class="relative w-full md:w-[50%] min-h-[250px] md:min-h-[350px] overflow-hidden bg-[#15131b]">
                                                <div class="absolute inset-0 bg-cover bg-center bg-no-repeat transition-transform duration-700 hover:scale-105" style="background-image: url('<?php echo BASE_URL; ?>/assets/images/help/workshop4.png');"></div>
                                            </div>
                                            <div class="relative w-full md:w-[50%] p-6 md:p-10 z-10 flex flex-col justify-center">
                                                <div class="flex justify-between items-start mb-2">
                                                    <h4 class="text-2xl font-bold text-white tracking-tight flex items-center gap-3 font-display">
                                                        <span class="text-[#0EA5A0] opacity-80 text-lg">04</span> Asignar Piezas
                                                    </h4>
                                                    <span class="bg-[#0EA5A0] text-white text-xs px-3 py-1 rounded-full font-bold uppercase tracking-wider">Paso 4</span>
                                                </div>
                                                <div class="flex gap-2 mb-6 mt-3">
                                                    <span class="px-3 py-1 rounded-full border border-white/10 text-xs font-medium text-white/60">Inventario</span>
                                                </div>
                                                <p class="text-white/80 leading-relaxed text-lg mb-6">Selecciona los repuestos utilizados en la reparación. El sistema descontará estas unidades del inventario automáticamente.</p>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                                
                                <!-- Carousel Controls -->
                                <div class="absolute bottom-0 left-0 right-0 p-4 md:p-6 bg-gradient-to-t from-black/80 to-transparent flex justify-between items-center z-20">
                                    <button type="button" onclick="prevSlide3()" id="btn-prev-3" class="bg-white/10 text-white backdrop-blur-md px-4 py-2.5 rounded-xl flex items-center gap-2 hover:bg-white/20 disabled:opacity-30 disabled:cursor-not-allowed transition-colors text-sm font-bold border border-white/10">
                                        <span class="material-symbols-outlined text-[18px]">arrow_back</span> Anterior
                                    </button>
                                    <div class="flex gap-2">
                                        <div class="w-4 h-2 rounded-full bg-[#0EA5A0] indicator-3 transition-colors"></div>
                                        <div class="w-2 h-2 rounded-full bg-border indicator-3 transition-colors"></div>
                                        <div class="w-2 h-2 rounded-full bg-border indicator-3 transition-colors"></div>
                                        <div class="w-2 h-2 rounded-full bg-border indicator-3 transition-colors"></div>
                                    </div>
                                    <button type="button" onclick="nextSlide3()" id="btn-next-3" class="bg-[#0EA5A0] text-white px-4 py-2.5 rounded-xl flex items-center gap-2 hover:bg-[#0EA5A0]/80 disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-sm font-bold shadow-md shadow-[#0EA5A0]/20">
                                        Siguiente <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div></div></section></div>