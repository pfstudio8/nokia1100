<?php
require_once __DIR__ . '/config/db.php';

$codigo = isset($_GET['code']) ? $conn->real_escape_string($_GET['code']) : '';
$order_found = false;
$repair = null;

if ($codigo) {
    $sql = "SELECT codigo_orden, equipo_marca, equipo_modelo, estado FROM reparacion WHERE codigo_orden = '$codigo'";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $repair = $result->fetch_assoc();
        $order_found = true;
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Seguimiento de Reparación - NOKIA1100</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script>
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            colors: {
              background: "#0A0A0B",
              surface: "#111113",
              "surface-hover": "#18181B",
              primary: "#21b8bd",
              secondary: "#E04FEE",
              border: "#27272A",
              "text-main": "#FAFAFA",
              "text-muted": "#A1A1AA",
            },
            fontFamily: {
              sans: ["Inter", "sans-serif"],
              display: ["Outfit", "sans-serif"],
            },
          },
        },
      }
    </script>
    <style>
      .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 300, 'GRAD' 0, 'opsz' 24; }
      body { background-color: #0A0A0B; color: #FAFAFA; }
      .glass-card {
        background: rgba(17, 17, 19, 0.7);
        backdrop-filter: blur(16px);
        border: 1px solid #27272A;
      }
    </style>
</head>
<body class="font-sans antialiased text-text-main selection:bg-primary/20 selection:text-primary min-h-screen flex flex-col items-center justify-center p-6 bg-[url('assets/images/grid-pattern.svg')] bg-center">
    
    <div class="w-full max-w-2xl">
        <div class="text-center mb-10">
            <h1 class="text-3xl font-bold font-display tracking-tight text-text-main mb-2">NOKIA<span class="text-primary">1100</span></h1>
            <p class="text-text-muted text-sm uppercase tracking-widest font-medium">Portal de Seguimiento</p>
        </div>

        <?php if(!$order_found && $codigo): ?>
            <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-xl mb-6 text-sm flex gap-3 items-center justify-center">
                <span class="material-symbols-outlined text-[20px]">error</span>
                No encontramos ninguna orden con el código <strong><?php echo htmlspecialchars($codigo); ?></strong>
            </div>
        <?php endif; ?>

        <?php if(!$codigo || (!$order_found && $codigo)): ?>
            <div class="glass-card rounded-2xl p-8 text-center sm:mx-10 relative overflow-hidden">
                <div class="absolute -right-20 -top-20 w-40 h-40 bg-primary/10 rounded-full blur-3xl"></div>
                <h2 class="text-xl font-display font-medium text-text-main mb-6 relative z-10">Consultar Estado de mi Equipo</h2>
                
                <form action="track.php" method="GET" class="relative z-10 max-w-sm mx-auto">
                    <div class="relative mb-4">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-text-muted">search</span>
                        <input type="text" name="code" placeholder="Ej: 24041234" required class="w-full bg-surface border border-border pl-12 pr-4 py-3.5 rounded-xl text-center text-text-main focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary/50 transition-all text-lg font-display uppercase tracking-widest">
                    </div>
                    <button type="submit" class="w-full bg-primary hover:bg-[#1da1a6] text-background font-medium py-3.5 rounded-xl transition-all shadow-lg hover:shadow-primary/25">Rastrear Orden</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if($order_found): 
            $estados = ['Recibido', 'En diagnóstico', 'En reparación', 'Listo', 'Entregado'];
            $current_idx = array_search($repair['estado'], $estados);
            if ($current_idx === false) $current_idx = 0; // fallback para 'Cancelado' u otros
            
            $is_cancelado = ($repair['estado'] === 'Cancelado');
        ?>
            <div class="glass-card rounded-3xl p-8 sm:p-12 relative overflow-hidden border-border/80 shadow-2xl">
                <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-transparent via-primary/50 to-transparent"></div>
                
                <div class="text-center mb-10">
                    <p class="text-sm text-text-muted uppercase tracking-widest font-semibold mb-2">Orden</p>
                    <h2 class="text-4xl sm:text-5xl font-display font-bold text-text-main mb-3"><?php echo htmlspecialchars($repair['codigo_orden']); ?></h2>
                    <p class="text-text-muted text-lg"><?php echo htmlspecialchars($repair['equipo_marca'] . ' ' . $repair['equipo_modelo']); ?></p>
                </div>

                <?php if($is_cancelado): ?>
                    <div class="py-10 text-center">
                        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-red-500/10 border-2 border-red-500 mb-6 shadow-[0_0_30px_rgba(239,68,68,0.2)]">
                            <span class="material-symbols-outlined text-[40px] text-red-500">cancel</span>
                        </div>
                        <h3 class="text-2xl font-display font-semibold text-red-500 mb-2">Orden Cancelada</h3>
                        <p class="text-text-muted">Por favor, comuníquese con el taller para más información.</p>
                    </div>
                <?php else: ?>
                    <!-- STEPPER TIMELINE -->
                    <div class="relative max-w-lg mx-auto py-8">
                        <div class="hidden sm:block absolute top-[52px] left-[10%] right-[10%] h-[3px] bg-surface border-y border-border/50">
                            <!-- Progress highlight -->
                            <?php 
                            $progress = ($current_idx / (count($estados) - 1)) * 100;
                            ?>
                            <div class="absolute top-0 left-0 h-full bg-gradient-to-r from-primary to-blue-500 transition-all duration-1000 ease-out shadow-[0_0_10px_rgba(33,184,189,0.5)]" style="width: <?php echo $progress; ?>%;"></div>
                        </div>

                        <div class="flex flex-col sm:flex-row justify-between gap-6 sm:gap-0 relative z-10 w-full px-2">
                            <?php foreach($estados as $idx => $est): 
                                $status = 'pending';
                                if ($idx < $current_idx) $status = 'completed';
                                if ($idx === $current_idx) $status = 'current';

                                // Define colors based on status
                                $dotClass = "w-5 h-5 rounded-full border-2 bg-surface border-border transition-all duration-500";
                                $textClass = "text-text-muted text-xs font-medium sm:mt-4 transition-all duration-500 group-hover:text-text-main";
                                
                                if ($status === 'completed') {
                                    $dotClass = "w-5 h-5 rounded-full bg-primary border-transparent shadow-[0_0_10px_rgba(33,184,189,0.4)]";
                                } else if ($status === 'current') {
                                    $dotClass = "w-6 h-6 rounded-full border-[3px] border-primary bg-background shadow-[0_0_15px_rgba(33,184,189,0.6)] animate-pulse";
                                    $textClass = "text-primary text-xs font-bold sm:mt-4";
                                }

                                // Mobile specific layout adjustments
                                $flexDir = "flex-row sm:flex-col";
                                $alignItems = "items-center sm:items-center";
                                $textSpacing = "ml-4 sm:ml-0 text-left sm:text-center";
                            ?>
                            <div class="flex <?php echo $flexDir; ?> <?php echo $alignItems; ?> group w-full sm:w-1/5 relative">
                                <?php if($idx < count($estados)-1): ?>
                                    <!-- Mobile Vertical Line -->
                                    <div class="sm:hidden absolute top-6 left-[9px] bottom-[-24px] w-[2px] <?php echo ($idx < $current_idx) ? 'bg-primary' : 'bg-border'; ?>"></div>
                                <?php endif; ?>
                                
                                <div class="shrink-0 flex items-center justify-center w-6 h-6 sm:pt-4">
                                    <div class="<?php echo $dotClass; ?>"></div>
                                </div>
                                <span class="<?php echo $textSpacing; ?> <?php echo $textClass; ?> w-full">
                                    <?php 
                                    // Breaks lines nicely on desktop
                                    $words = explode(" ", $est);
                                    if(count($words)>1) {
                                        echo "<span class='hidden sm:inline'>" . $words[0] . "<br>" . $words[1] . "</span>";
                                        echo "<span class='sm:hidden'>" . $est . "</span>";
                                    } else {
                                        echo $est;
                                    }
                                    ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="text-center mt-10">
                        <span class="inline-block px-6 py-2 rounded-full border border-primary/30 bg-primary/10 text-primary font-medium text-sm shadow-[0_0_20px_rgba(33,184,189,0.15)]">
                            Estado: <?php echo htmlspecialchars($repair['estado']); ?>
                        </span>
                    </div>
                <?php endif; ?>

                <div class="mt-14 pt-6 border-t border-border text-center">
                    <p class="text-text-muted text-sm flex items-center justify-center gap-2">
                        Para consultas directas: 
                        <a href="#" class="inline-flex items-center gap-1 text-text-main hover:text-green-400 font-medium transition-colors">
                            WhatsApp al taller
                        </a>
                    </p>
                    <a href="track.php" class="inline-block mt-4 text-xs text-text-muted hover:text-text-main underline decoration-border underline-offset-4">Consultar otro equipo</a>
                </div>
            </div>
        <?php endif; ?>
        
    </div>

</body>
</html>
