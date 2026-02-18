<?php
if(session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once "../../config/db.php";
require_once "../../models/OrderRistoratoreModel.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["ruolo"] !== 'ristoratore') {
    header("location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION["id"];
$orderModel = new OrderRistoratoreModel($db);

$kpi = $orderModel->getOwnerKPI($user_id);
$total_revenue = $kpi['revenue'] ?? 0.0;
$total_orders = $kpi['num_orders'] ?? 0;
$avg_order = ($total_orders > 0) ? ($total_revenue / $total_orders) : 0;

$top_restaurants = $orderModel->getTopRestaurantsByOwner($user_id);

$chart_data_raw = $orderModel->getWeeklyChartData($user_id);

$chart_data = [];
$labels = [];
$data_values = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_data[$date] = 0; 
}

foreach ($chart_data_raw as $row) {
    if (isset($chart_data[$row['data_ordine']])) {
        $chart_data[$row['data_ordine']] = $row['quanti'];
    }
}

foreach ($chart_data as $date => $count) {
    $labels[] = date('d/m', strtotime($date));
    $data_values[] = $count;
}

$json_labels = json_encode($labels);
$json_data = json_encode($data_values);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiche Globali - ClickNeat</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=dashboard_2" />
    <link rel="stylesheet" href="../../css/style_ristoratori.css">
    <style>
        @media (max-width: 768px) {
            .kpi-row {
                grid-template-columns: 1fr !important;
                gap: 15px !important;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr !important;
                gap: 20px !important;
            }
            
            .chart-container {
                height: 250px !important;
            }
            
            .top-item {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 8px !important;
                padding: 15px 0 !important;
            }
            
            .top-item > div:first-child {
                width: 100% !important;
            }
            
            .top-item > div:last-child {
                margin-left: 0 !important;
                text-align: left !important;
                width: 100% !important;
                padding-left: 45px !important;
            }
            
            .page-header {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 10px !important;
            }
            
            .page-header h1 {
                font-size: 24px !important;
            }
            
            .header-date {
                align-self: flex-start !important;
            }
        }
    </style>
</head>
<body>

    <div class="mobile-header">  
        <button class="hamburger-btn">  
            <i class="fa-solid fa-bars"></i> 
        </button>  
    </div>

    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <div>
                <p>Panoramica Finanziaria</p>
                <h1>Statistiche Globali</h1>
            </div>
            <div class="header-date">
                <i class="fa-regular fa-calendar"></i> <?php echo date("d M Y"); ?>
            </div>
        </div>

        <div class="kpi-row">
            <div class="kpi-card">
                <div class="kpi-icon icon-money"><i class="fa-solid fa-sack-dollar"></i></div>
                <div class="kpi-info">
                    <h4>Fatturato Totale</h4>
                    <h2>€ <?php echo number_format($total_revenue, 2); ?></h2>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon icon-cart"><i class="fa-solid fa-basket-shopping"></i></div>
                <div class="kpi-info">
                    <h4>Totale Ordini</h4>
                    <h2><?php echo $total_orders; ?></h2>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon icon-avg"><i class="fa-solid fa-chart-line"></i></div>
                <div class="kpi-info">
                    <h4>Media Scontrino</h4>
                    <h2>€ <?php echo number_format($avg_order, 2); ?></h2>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            
            <div class="chart-card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap: wrap; gap: 10px;">
                    <h3 style="color:#2B3674;">Andamento Ordini</h3>
                    <span style="font-size:12px; color:#A3AED0;">Ultimi 7 giorni</span>
                </div>
                
                <div class="chart-container">
                    <canvas id="orderChart"></canvas>
                </div>
            </div>

            <div class="top-list-card">
                <h3 style="color:#2B3674; margin-bottom: 25px;">🏆 Top Ristoranti</h3>
                
                <?php if(empty($top_restaurants)): ?>
                    <p style="color:#A3AED0;">Nessun dato disponibile.</p>
                <?php else: ?>
                    <?php $rank = 1; foreach($top_restaurants as $top): ?>
                        <div class="top-item">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <div class="rank-circle rank-<?php echo $rank; ?>">
                                    <?php echo $rank; ?>
                                </div>
                                <div>
                                    <strong style="color:#2B3674; display:block;"><?php echo htmlspecialchars($top['nome']); ?></strong>
                                    <span style="font-size:12px; color:#A3AED0;">Fatturato</span>
                                </div>
                            </div>
                            <div style="font-weight: 700; color: #1A4D4E; font-size: 1.1em;">
                                € <?php echo number_format($top['fatturato'], 2); ?>
                            </div>
                        </div>
                    <?php $rank++; endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector('.sidebar');
            const hamburger = document.querySelector('.hamburger-btn');
            const closeBtn = document.getElementById('closeSidebarBtn');
            
            let overlay = document.querySelector('.sidebar-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.classList.add('sidebar-overlay');
                document.body.appendChild(overlay);
            }

            function openSidebar() {
                sidebar.classList.add('active');
                overlay.classList.add('active');
            }

            function closeSidebar() {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            }

            if (hamburger) {
                hamburger.addEventListener('click', openSidebar);
            }

            if (closeBtn) {
                closeBtn.addEventListener('click', closeSidebar);
            }

            overlay.addEventListener('click', closeSidebar);
        });

        const ctx = document.getElementById('orderChart').getContext('2d');
        
        const labels = <?php echo $json_labels; ?>;
        const dataValues = <?php echo $json_data; ?>;

        new Chart(ctx, {
            type: 'bar', 
            data: {
                labels: labels,
                datasets: [{
                    label: 'Numero Ordini',
                    data: dataValues,
                    backgroundColor: '#1A4D4E',
                    borderRadius: 5,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#2B3674',
                        padding: 10,
                        titleFont: { family: 'Inter', size: 13 },
                        bodyFont: { family: 'Inter', size: 13 }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#F4F7FE',
                            borderDash: [5, 5]
                        },
                        ticks: {
                            stepSize: 1,
                            font: { family: 'Inter', color: '#A3AED0' }
                        },
                        border: { display: false }
                    },
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { family: 'Inter', color: '#A3AED0' },
                            maxRotation: 45,
                            minRotation: 45
                        },
                        border: { display: false }
                    }
                }
            }
        });
    </script>

</body>
</html>