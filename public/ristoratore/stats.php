<?php
session_start();
require_once "../../config/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["ruolo"] !== 'ristoratore') {
    header("location: login.php");
    exit;
}

$user_id = $_SESSION["id"];

$total_revenue = 0;
$total_orders = 0;
$avg_order = 0;

// Query KPI
$sql_kpi = "SELECT 
                COUNT(o.id) as num_orders, 
                SUM(o.total_amount) as revenue 
            FROM orders o
            JOIN ristoranti r ON o.restaurant_id = r.id
            WHERE r.proprietario_id = ? AND o.status = 'completed'";

if ($stmt = mysqli_prepare($link, $sql_kpi)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $total_orders, $total_revenue);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
}

$total_revenue = $total_revenue ?? 0.0;
$total_orders = $total_orders ?? 0;

if ($total_orders > 0) {
    $avg_order = $total_revenue / $total_orders;
}

// Query Top Ristoranti
$top_restaurants = [];
$sql_top = "SELECT r.nome, COALESCE(SUM(o.total_amount), 0) as fatturato 
            FROM ristoranti r 
            LEFT JOIN orders o ON r.id = o.restaurant_id AND o.status = 'completed'
            WHERE r.proprietario_id = ? 
            GROUP BY r.id 
            ORDER BY fatturato DESC 
            LIMIT 5"; 

if ($stmt = mysqli_prepare($link, $sql_top)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = $result->fetch_assoc()) {
        $top_restaurants[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// Logica dati Grafico (Ultimi 7 giorni)
$chart_data = [];
$labels = [];
$data_values = [];

// Inizializza gli ultimi 7 giorni a 0
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_data[$date] = 0; 
}

// Popola con i dati dal DB
$sql_chart = "SELECT DATE(o.created_at) as data_ordine, COUNT(o.id) as quanti 
              FROM orders o
              JOIN ristoranti r ON o.restaurant_id = r.id
              WHERE r.proprietario_id = ? 
              AND o.created_at >= DATE(NOW()) - INTERVAL 7 DAY 
              GROUP BY DATE(o.created_at)";

if ($stmt = mysqli_prepare($link, $sql_chart)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = $result->fetch_assoc()) {
        if (isset($chart_data[$row['data_ordine']])) {
            $chart_data[$row['data_ordine']] = $row['quanti'];
        }
    }
    mysqli_stmt_close($stmt);
}

// Prepara array per JavaScript
foreach ($chart_data as $date => $count) {
    $labels[] = date('d/m', strtotime($date)); // Formato giorno/mese
    $data_values[] = $count;
}

// Converti in JSON per JS
$json_labels = json_encode($labels);
$json_data = json_encode($data_values);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Statistiche Globali - ClickNeat</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style_ristoratori.css">
</head>
<body>

    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="top-bar">
            <div class="page-title">
                <p>Panoramica Finanziaria</p>
                <h1>Statistiche Globali</h1>
            </div>
            <div style="background: white; padding: 10px 20px; border-radius: 30px; color:#1A4D4E; font-weight:600; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
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
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
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
                            <div style="display:flex; align-items:center;">
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
        const ctx = document.getElementById('orderChart').getContext('2d');
        
        // Dati passati da PHP
        const labels = <?php echo $json_labels; ?>;
        const dataValues = <?php echo $json_data; ?>;

        new Chart(ctx, {
            type: 'bar', // Puoi cambiarlo in 'line' per un grafico a linee
            data: {
                labels: labels,
                datasets: [{
                    label: 'Numero Ordini',
                    data: dataValues,
                    backgroundColor: '#1A4D4E',
                    borderRadius: 5, // Bordi arrotondati delle barre
                    borderSkipped: false,
                    barThickness: 30, // Larghezza delle barre
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false // Nascondi la legenda
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
                            stepSize: 1, // Mostra solo numeri interi
                            font: { family: 'Inter', color: '#A3AED0' }
                        },
                        border: { display: false }
                    },
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { family: 'Inter', color: '#A3AED0' }
                        },
                        border: { display: false }
                    }
                }
            }
        });
    </script>

</body>
</html>