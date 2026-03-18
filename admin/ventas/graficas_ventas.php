<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin_auth();

require_once '../../config/bd.php';

// Fetch sales data grouped by date
$sql = "SELECT DATE(fecha) as sale_date, SUM(total) as daily_total 
        FROM venta 
        GROUP BY DATE(fecha) 
        ORDER BY sale_date ASC 
        LIMIT 30"; // Last 30 days

$result = $conn->query($sql);

$dates = [];
$totals = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $dates[] = $row['sale_date'];
        $totals[] = $row['daily_total'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas de Ventas - Nokia 1100</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-container {
            position: relative; 
            height: 60vh; 
            width: 100%;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <div class="dashboard-container" style="padding-top: 3rem; align-items: stretch; justify-content: flex-start;">
        
        <div style="margin-bottom: 3rem;">
            <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                <div>
                    <p style="text-transform: uppercase; letter-spacing: 0.2em; font-size: 0.75rem; color: var(--primary); font-weight: 800; margin-bottom: 0.5rem;">Análisis & Rendimiento</p>
                    <h1 style="margin-bottom: 0;">Estadísticas de Ventas</h1>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <a href="../panel_admin.php" class="btn btn-outline">Volver al Panel</a>
                </div>
            </div>
            <div style="height: 2px; width: 40px; background: var(--primary); margin: 1rem 0;"></div>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Visualización de ingresos generados en los últimos 30 días.</p>
        </div>

        <div class="glass-card">
            <div class="chart-container">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [{
                    label: 'Ventas Totales ($)',
                    data: <?php echo json_encode($totals); ?>,
                    backgroundColor: 'rgba(16, 185, 129, 0.2)',
                    borderColor: '#10b981',
                    borderWidth: 2,
                    pointBackgroundColor: '#38bdf8',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#38bdf8',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: '#f1f5f9',
                            font: {
                                family: 'Inter'
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: '#94a3b8',
                            font: {
                                family: 'Inter'
                            }
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: '#94a3b8',
                            font: {
                                family: 'Inter'
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
