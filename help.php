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

<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 pb-32 min-h-screen flex flex-col bg-background relative">
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
                                                <div class="absolute inset-4 bg-contain bg-center bg-no-repeat transition-transform duration-700 hover:scale-105" style="background-image: url('<?php echo BASE_URL; ?>/assets/images/help/step1.png');"></div>
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
                                                <div class="absolute inset-4 bg-contain bg-center bg-no-repeat transition-transform duration-700 hover:scale-105" style="background-image: url('<?php echo BASE_URL; ?>/assets/images/help/step2.png');"></div>
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
                                                <div class="absolute inset-4 bg-contain bg-center bg-no-repeat transition-transform duration-700 hover:scale-105" style="background-image: url('<?php echo BASE_URL; ?>/assets/images/help/step3.png');"></div>
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
                                                <div class="absolute inset-4 bg-contain bg-no-repeat transition-transform duration-700 hover:scale-105" style="background-image: url('<?php echo BASE_URL; ?>/assets/images/help/client1.png'); background-position: center;"></div>
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
                                                <div class="absolute inset-4 bg-contain bg-no-repeat transition-transform duration-700 hover:scale-105" style="background-image: url('<?php echo BASE_URL; ?>/assets/images/help/client2.png'); background-position: center;"></div>
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
                        </ul>

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
                                                <div class="absolute inset-4 bg-contain bg-center bg-no-repeat transition-transform duration-700 hover:scale-105" style="background-image: url('<?php echo BASE_URL; ?>/assets/images/help/workshop1.png');"></div>
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
                                                <div class="absolute inset-4 bg-contain bg-center bg-no-repeat transition-transform duration-700 hover:scale-105" style="background-image: url('<?php echo BASE_URL; ?>/assets/images/help/workshop2.png');"></div>
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
                                                <div class="absolute inset-4 bg-contain bg-center bg-no-repeat transition-transform duration-700 hover:scale-105" style="background-image: url('<?php echo BASE_URL; ?>/assets/images/help/workshop3.png');"></div>
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
                                                <div class="absolute inset-4 bg-contain bg-center bg-no-repeat transition-transform duration-700 hover:scale-105" style="background-image: url('<?php echo BASE_URL; ?>/assets/images/help/workshop4.png');"></div>
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

                                                                        <!-- Tutorial: Ventas (Movie Card Style Carousel) -->
                        <div class="mt-10 mb-8" id="tutorial-carousel-4">
                            <h3 class="text-xl font-display font-bold text-text-main mb-6 flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary">point_of_sale</span>
                                ¿Cómo realizar ventas y cobros?
                            </h3>
                            
                            <div class="overflow-hidden relative rounded-xl shadow-[0_25px_50px_rgba(0,0,0,0.6)] border border-border">
                                <div class="flex transition-transform duration-500 ease-in-out" id="carousel-track-4">
                                    
                                    <!-- Step 1: Nueva Venta -->
                                    <div class="w-full shrink-0">
                                        <div class="flex flex-col md:flex-row bg-[#1e1b26] relative group">
                                            <div class="relative w-full md:w-[50%] min-h-[250px] md:min-h-[350px] overflow-hidden bg-[#15131b]">
                                                <div class="absolute inset-4 bg-contain bg-center bg-no-repeat transition-transform duration-700 hover:scale-105" style="background-image: url('<?php echo BASE_URL; ?>/assets/images/help/sale1.png');"></div>
                                            </div>
                                            <div class="relative w-full md:w-[50%] p-6 md:p-10 z-10 flex flex-col justify-center">
                                                <div class="flex justify-between items-start mb-2">
                                                    <h4 class="text-2xl font-bold text-white tracking-tight flex items-center gap-3 font-display">
                                                        <span class="text-primary opacity-80 text-lg">01</span> Iniciar Venta
                                                    </h4>
                                                    <span class="bg-primary text-white text-xs px-3 py-1 rounded-full font-bold uppercase tracking-wider">Paso 1</span>
                                                </div>
                                                <div class="flex gap-2 mb-6 mt-3">
                                                    <span class="px-3 py-1 rounded-full border border-white/10 text-xs font-medium text-white/60">Caja</span>
                                                </div>
                                                <p class="text-white/80 leading-relaxed text-lg mb-6">Para vender productos sueltos, haz clic en <strong class="text-white">Nueva Venta</strong> desde el módulo de Caja.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Step 2: Agregar Productos -->
                                    <div class="w-full shrink-0">
                                        <div class="flex flex-col md:flex-row bg-[#1e1b26] relative group">
                                            <div class="relative w-full md:w-[50%] min-h-[250px] md:min-h-[350px] overflow-hidden bg-[#15131b]">
                                                <div class="absolute inset-4 bg-contain bg-center bg-no-repeat transition-transform duration-700 hover:scale-105" style="background-image: url('<?php echo BASE_URL; ?>/assets/images/help/sale2.png');"></div>
                                            </div>
                                            <div class="relative w-full md:w-[50%] p-6 md:p-10 z-10 flex flex-col justify-center">
                                                <div class="flex justify-between items-start mb-2">
                                                    <h4 class="text-2xl font-bold text-white tracking-tight flex items-center gap-3 font-display">
                                                        <span class="text-primary opacity-80 text-lg">02</span> Agregar Productos
                                                    </h4>
                                                    <span class="bg-primary text-white text-xs px-3 py-1 rounded-full font-bold uppercase tracking-wider">Paso 2</span>
                                                </div>
                                                <div class="flex gap-2 mb-6 mt-3">
                                                    <span class="px-3 py-1 rounded-full border border-white/10 text-xs font-medium text-white/60">Carrito</span>
                                                </div>
                                                <p class="text-white/80 leading-relaxed text-lg mb-6">Busca el producto, indica la cantidad y súmalo al ticket.</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Step 3: Finalizar Venta (antes era el 5) -->
                                    <div class="w-full shrink-0">
                                        <div class="flex flex-col md:flex-row bg-[#1e1b26] relative group">
                                            <div class="relative w-full md:w-[50%] min-h-[250px] md:min-h-[350px] overflow-hidden bg-[#15131b]">
                                                <div class="absolute inset-4 bg-contain bg-center bg-no-repeat transition-transform duration-700 hover:scale-105" style="background-image: url('<?php echo BASE_URL; ?>/assets/images/help/sale3.png');"></div>
                                            </div>
                                            <div class="relative w-full md:w-[50%] p-6 md:p-10 z-10 flex flex-col justify-center">
                                                <div class="flex justify-between items-start mb-2">
                                                    <h4 class="text-2xl font-bold text-white tracking-tight flex items-center gap-3 font-display">
                                                        <span class="text-primary opacity-80 text-lg">03</span> Finalizar Venta
                                                    </h4>
                                                    <span class="bg-primary text-white text-xs px-3 py-1 rounded-full font-bold uppercase tracking-wider">Paso 3</span>
                                                </div>
                                                <div class="flex gap-2 mb-6 mt-3">
                                                    <span class="px-3 py-1 rounded-full border border-white/10 text-xs font-medium text-white/60">Liquidación</span>
                                                </div>
                                                <p class="text-white/80 leading-relaxed text-lg mb-6">Selecciona el <strong class="text-white">Método de Pago</strong> y confirma la operación para generar el ticket final.</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Step 4: TRANSICIÓN a Reparaciones -->
                                    <div class="w-full shrink-0">
                                        <div class="flex flex-col bg-gradient-to-br from-[#1e1b26] to-[#0a192f] relative group min-h-[250px] md:min-h-[350px] items-center justify-center p-10 text-center">
                                            <div class="w-20 h-20 bg-primary/20 rounded-full flex items-center justify-center mb-6 shadow-[0_0_30px_rgba(0,229,255,0.2)]">
                                                <span class="material-symbols-outlined text-[40px] text-primary">build_circle</span>
                                            </div>
                                            <h4 class="text-3xl font-display font-bold text-white mb-4">Cobro de Reparaciones</h4>
                                            <p class="text-white/70 max-w-lg text-lg">El proceso cambia levemente si lo que necesitas cobrar es una orden proveniente del Taller de Reparaciones. Veamos cómo se hace en los siguientes pasos.</p>
                                        </div>
                                    </div>

                                    <!-- Step 5: Cobrar Reparacion -->
                                    <div class="w-full shrink-0">
                                        <div class="flex flex-col md:flex-row bg-[#1e1b26] relative group">
                                            <div class="relative w-full md:w-[50%] min-h-[250px] md:min-h-[350px] overflow-hidden bg-[#242b3b] flex flex-col items-center justify-center">
                                                <div class="w-full h-24 bg-contain bg-top bg-no-repeat mt-12" style="background-image: url('<?php echo BASE_URL; ?>/assets/images/help/repair_btn.png');"></div>
                                                
                                                <div class="relative flex flex-col items-center mt-2">
                                                    <svg class="w-16 h-16 drop-shadow-[0_0_8px_rgba(0,229,255,0.6)] animate-bounce" viewBox="0 0 100 100">
                                                        <path d="M50,80 L50,20 M30,40 L50,15 L70,40" fill="none" stroke="#00e5ff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                    <div class="border border-[#00e5ff] bg-black/80 backdrop-blur-md text-white font-bold px-4 py-2 rounded-xl text-sm shadow-[0_0_15px_rgba(0,229,255,0.3)]">
                                                        Click en <span class="text-[#00e5ff]">"Cobrar Reparaciones"</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="relative w-full md:w-[50%] p-6 md:p-10 z-10 flex flex-col justify-center">
                                                <div class="flex justify-between items-start mb-2">
                                                    <h4 class="text-2xl font-bold text-white tracking-tight flex items-center gap-3 font-display">
                                                        <span class="text-primary opacity-80 text-lg">04</span> Cobrar Reparaciones
                                                    </h4>
                                                    <span class="bg-primary text-white text-xs px-3 py-1 rounded-full font-bold uppercase tracking-wider">Taller</span>
                                                </div>
                                                <p class="text-white/80 leading-relaxed text-lg mb-6 mt-6">En la pantalla de Caja, en lugar de iniciar una nueva venta, haz clic en el botón <strong class="text-[#00e5ff]">Cobrar Reparaciones</strong> para buscar la orden terminada.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Step 6: Detalles Reparacion -->
                                    <div class="w-full shrink-0">
                                        <div class="flex flex-col md:flex-row bg-[#1e1b26] relative group">
                                            <div class="relative w-full md:w-[50%] min-h-[250px] md:min-h-[350px] overflow-hidden bg-[#15131b]">
                                                <div class="absolute inset-4 bg-contain bg-center bg-no-repeat transition-transform duration-700 hover:scale-105" style="background-image: url('<?php echo BASE_URL; ?>/assets/images/help/repair_checkout.png');"></div>
                                            </div>
                                            <div class="relative w-full md:w-[50%] p-6 md:p-10 z-10 flex flex-col justify-center">
                                                <div class="flex justify-between items-start mb-2">
                                                    <h4 class="text-2xl font-bold text-white tracking-tight flex items-center gap-3 font-display">
                                                        <span class="text-primary opacity-80 text-lg">05</span> Confirmar Orden
                                                    </h4>
                                                    <span class="bg-primary text-white text-xs px-3 py-1 rounded-full font-bold uppercase tracking-wider">Finalizar</span>
                                                </div>
                                                <p class="text-white/80 leading-relaxed text-lg mb-6 mt-6">Revisa los detalles de la orden técnica, selecciona el método de pago y haz clic en "Cobrar y Entregar" para finalizar.</p>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                                
                                <!-- Carousel Controls -->
                                <div class="absolute bottom-0 left-0 right-0 p-4 md:p-6 bg-gradient-to-t from-black/80 to-transparent flex justify-between items-center z-20">
                                    <button type="button" onclick="prevSlide4()" id="btn-prev-4" class="bg-white/10 text-white backdrop-blur-md px-4 py-2.5 rounded-xl flex items-center gap-2 hover:bg-white/20 disabled:opacity-30 disabled:cursor-not-allowed transition-colors text-sm font-bold border border-white/10">
                                        <span class="material-symbols-outlined text-[18px]">arrow_back</span> Anterior
                                    </button>
                                    <div class="flex gap-2">
                                        <div class="w-4 h-2 rounded-full bg-primary indicator-4 transition-colors"></div>
                                        <div class="w-2 h-2 rounded-full bg-border indicator-4 transition-colors"></div>
                                        <div class="w-2 h-2 rounded-full bg-border indicator-4 transition-colors"></div>
                                        <div class="w-2 h-2 rounded-full bg-border indicator-4 transition-colors"></div>
                                        <div class="w-2 h-2 rounded-full bg-border indicator-4 transition-colors"></div>
                                        <div class="w-2 h-2 rounded-full bg-border indicator-4 transition-colors"></div>
                                    </div>
                                    <button type="button" onclick="nextSlide4()" id="btn-next-4" class="bg-primary text-white px-4 py-2.5 rounded-xl flex items-center gap-2 hover:bg-primary/80 disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-sm font-bold shadow-md shadow-primary/20">
                                        Siguiente <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                                    </button>
                                </div>
                            </div>
                        </div>

        </div>

                <!-- CONSEJOS Y AVISOS SECTION -->
        <div class="mt-16 bg-surface border border-border rounded-3xl p-8 relative overflow-hidden mb-12">
            <div class="absolute top-0 right-0 w-64 h-64 bg-primary/5 rounded-full blur-3xl -z-10 translate-x-1/2 -translate-y-1/2"></div>
            
            <h3 class="text-2xl font-display font-bold text-text-main flex items-center gap-3 mb-8">
                <div class="w-10 h-10 rounded-full bg-primary/20 text-primary flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">lightbulb</span>
                </div>
                Recomendaciones y Avisos Importantes
            </h3>
            
            <div class="space-y-6">
                <!-- Callout Tip -->
                <div class="relative overflow-hidden bg-background border border-border/80 rounded-2xl p-6 group transition-colors hover:border-blue-500/30">
                    <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-blue-500"></div>
                    <div class="flex flex-col sm:flex-row items-start gap-5 relative z-10">
                        <div class="w-12 h-12 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0 shadow-inner">
                            <span class="material-symbols-outlined text-[24px]">edit_note</span>
                        </div>
                        <div class="flex-1">
                            <h5 class="text-text-main font-bold text-base mb-2 flex flex-wrap items-center gap-2">
                                Cargar Productos Específicos
                                <span class="px-2 py-0.5 rounded text-[10px] uppercase font-bold tracking-wider bg-blue-500/10 text-blue-400 border border-blue-500/20">Sugerencia</span>
                            </h5>
                            <p class="text-sm text-text-muted leading-relaxed mb-5">
                                Al crear un producto, el sistema sugiere un nombre automático (ej. "Periférico" o "Funda"). <strong>Recuerda que puedes borrarlo o completarlo a tu gusto.</strong>
                            </p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-surface border border-border/50 rounded-xl p-4">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="material-symbols-outlined text-blue-400 text-[18px]">smartphone</span>
                                        <span class="font-semibold text-xs text-text-main uppercase tracking-wider">Funda con diseño</span>
                                    </div>
                                    <p class="text-xs text-text-muted">Escribe "Funda de Silicona Spider-Man", Marca "Motorola", Modelo "Moto G20". Usa la descripción para el color.</p>
                                </div>
                                <div class="bg-surface border border-border/50 rounded-xl p-4">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="material-symbols-outlined text-blue-400 text-[18px]">mouse</span>
                                        <span class="font-semibold text-xs text-text-main uppercase tracking-wider">Accesorios sueltos</span>
                                    </div>
                                    <p class="text-xs text-text-muted">Si cargas un mouse, borra "Periférico" y escribe "Mouse Inalámbrico Logitech M185".</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Callout Importante -->
                    <div class="relative overflow-hidden bg-background border border-border/80 rounded-2xl p-6 group transition-colors hover:border-red-500/30">
                        <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-red-500"></div>
                        <div class="flex flex-col sm:flex-row items-start gap-4 relative z-10">
                            <div class="w-10 h-10 rounded-xl bg-red-500/10 border border-red-500/20 flex items-center justify-center text-red-400 shrink-0 shadow-inner">
                                <span class="material-symbols-outlined text-[20px]">inventory_2</span>
                            </div>
                            <div>
                                <h5 class="text-text-main font-bold text-sm mb-1 flex flex-wrap items-center gap-2">
                                    Atención con el Stock
                                    <span class="px-2 py-0.5 rounded text-[10px] uppercase font-bold tracking-wider bg-red-500/10 text-red-400 border border-red-500/20">Crítico</span>
                                </h5>
                                <p class="text-sm text-text-muted leading-relaxed">
                                    Mantén el inventario actualizado. El sistema descontará stock automáticamente al cobrar reparaciones. Un stock desactualizado generará errores en caja.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Callout Warning -->
                    <div class="relative overflow-hidden bg-background border border-border/80 rounded-2xl p-6 group transition-colors hover:border-orange-500/30">
                        <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-orange-500"></div>
                        <div class="flex flex-col sm:flex-row items-start gap-4 relative z-10">
                            <div class="w-10 h-10 rounded-xl bg-orange-500/10 border border-orange-500/20 flex items-center justify-center text-orange-400 shrink-0 shadow-inner">
                                <span class="material-symbols-outlined text-[20px]">build</span>
                            </div>
                            <div>
                                <h5 class="text-text-main font-bold text-sm mb-1 flex flex-wrap items-center gap-2">
                                    Cierre de Reparaciones
                                    <span class="px-2 py-0.5 rounded text-[10px] uppercase font-bold tracking-wider bg-orange-500/10 text-orange-400 border border-orange-500/20">Atención</span>
                                </h5>
                                <p class="text-sm text-text-muted leading-relaxed">
                                    Cuando la reparación pase a estado "Listo", asentar qué repuestos se usaron es <strong class="text-orange-400 font-medium">esencial</strong> para descontar correctamente al cobrar.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-12 bg-surface border border-border rounded-2xl p-8 text-center flex flex-col items-center justify-center gap-3">
            <div class="w-12 h-12 rounded-full bg-primary/10 text-primary flex items-center justify-center">
                <span class="material-symbols-outlined">support_agent</span>
            </div>
            <h3 class="text-text-main font-bold font-display text-lg">¿Necesitas más ayuda?</h3>
            <p class="text-sm text-text-muted max-w-md">Si tienes dudas técnicas sobre el sistema o encuentras algún inconveniente, por favor contacta con el equipo de administración de Nokia1100.</p>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- Carousel 1 (Inventario) ---
        let currentSlide1 = 0;
        const track1 = document.getElementById('carousel-track');
        const prevBtn1 = document.getElementById('btn-prev');
        const nextBtn1 = document.getElementById('btn-next');
        const indicators1 = document.querySelectorAll('.indicator');
        
        function updateCarousel1() {
            if(!track1) return;
            track1.style.transform = `translateX(-${currentSlide1 * 100}%)`;
            
            if(prevBtn1) prevBtn1.disabled = currentSlide1 === 0;
            if(nextBtn1) nextBtn1.disabled = currentSlide1 === 2;
            
            indicators1.forEach((ind, i) => {
                ind.classList.toggle('bg-primary', i === currentSlide1);
                ind.classList.toggle('w-4', i === currentSlide1);
                ind.classList.toggle('bg-border', i !== currentSlide1);
                ind.classList.toggle('w-2', i !== currentSlide1);
            });
        }
        
        window.nextSlide = function() {
            if(currentSlide1 < 2) { currentSlide1++; updateCarousel1(); }
        }
        window.prevSlide = function() {
            if(currentSlide1 > 0) { currentSlide1--; updateCarousel1(); }
        }
        updateCarousel1();

        // --- Carousel 2 (Clientes) ---
        let currentSlide2 = 0;
        const track2 = document.getElementById('carousel-track-2');
        const prevBtn2 = document.getElementById('btn-prev-2');
        const nextBtn2 = document.getElementById('btn-next-2');
        const indicators2 = document.querySelectorAll('.indicator-2');
        
        function updateCarousel2() {
            if(!track2) return;
            track2.style.transform = `translateX(-${currentSlide2 * 100}%)`;
            
            if(prevBtn2) prevBtn2.disabled = currentSlide2 === 0;
            if(nextBtn2) nextBtn2.disabled = currentSlide2 === 1; // Only 2 slides
            
            indicators2.forEach((ind, i) => {
                ind.classList.toggle('bg-secondary', i === currentSlide2);
                ind.classList.toggle('w-4', i === currentSlide2);
                ind.classList.toggle('bg-border', i !== currentSlide2);
                ind.classList.toggle('w-2', i !== currentSlide2);
            });
        }
        
        window.nextSlide2 = function() {
            if(currentSlide2 < 1) { currentSlide2++; updateCarousel2(); }
        }
        window.prevSlide2 = function() {
            if(currentSlide2 > 0) { currentSlide2--; updateCarousel2(); }
        }
        updateCarousel2();

            // --- Carousel 4: Ventas y Cobros ---
    const track4 = document.getElementById('carousel-track-4');
    const prevBtn4 = document.getElementById('btn-prev-4');
    const nextBtn4 = document.getElementById('btn-next-4');
    const indicators4 = document.querySelectorAll('.indicator-4');
    
    let currentIndex4 = 0;
    const maxIndex4 = 5; // 4 slides (0, 1, 2, 3)

    function updateCarousel4() {
        if (!track4) return;
        track4.style.transform = `translateX(-${currentIndex4 * 100}%)`;
        
        // Update buttons
        if(prevBtn4) prevBtn4.disabled = currentIndex4 === 0;
        if(nextBtn4) nextBtn4.disabled = currentIndex4 === maxIndex4;
        
        // Update indicators
        indicators4.forEach((ind, i) => {
            if (i === currentIndex4) {
                ind.classList.remove('w-2', 'bg-border');
                ind.classList.add('w-4', 'bg-primary');
            } else {
                ind.classList.remove('w-4', 'bg-primary');
                ind.classList.add('w-2', 'bg-border');
            }
        });
    }

    window.nextSlide4 = function() {
        if (currentIndex4 < maxIndex4) {
            currentIndex4++;
            updateCarousel4();
        }
    }

    window.prevSlide4 = function() {
        if (currentIndex4 > 0) {
            currentIndex4--;
            updateCarousel4();
        }
    }
    
    // Initialize
    updateCarousel4();

    // --- Carousel 3 (Taller de Reparaciones) ---
        let currentSlide3 = 0;
        const track3 = document.getElementById('carousel-track-3');
        const prevBtn3 = document.getElementById('btn-prev-3');
        const nextBtn3 = document.getElementById('btn-next-3');
        const indicators3 = document.querySelectorAll('.indicator-3');
        
        function updateCarousel3() {
            if(!track3) return;
            track3.style.transform = `translateX(-${currentSlide3 * 100}%)`;
            
            if(prevBtn3) prevBtn3.disabled = currentSlide3 === 0;
            if(nextBtn3) nextBtn3.disabled = currentSlide3 === 3; // 4 slides
            
            indicators3.forEach((ind, i) => {
                ind.classList.toggle('bg-[#0EA5A0]', i === currentSlide3);
                ind.classList.toggle('w-4', i === currentSlide3);
                ind.classList.toggle('bg-border', i !== currentSlide3);
                ind.classList.toggle('w-2', i !== currentSlide3);
            });
        }
        
        window.nextSlide3 = function() {
            if(currentSlide3 < 3) { currentSlide3++; updateCarousel3(); }
        }
        window.prevSlide3 = function() {
            if(currentSlide3 > 0) { currentSlide3--; updateCarousel3(); }
        }
        updateCarousel3();
    });
</script>

<?php Layout::renderFooter(); ?>
