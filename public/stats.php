<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["ruolo"] !== 'ristoratore') {
    header("location: login.php");
    exit;
}

$user_id = $_SESSION["id"];

$total_revenue = 0;
$total_orders = 0;
$avg_order = 0;

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

$chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_data[$date] = 0; 
}

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

$max_val = max($chart_data);
if ($max_val == 0) $max_val = 1;
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Statistiche Globali - ClickNeat</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: #F4F7FE; min-height: 100vh; }
        .main-content { margin-left: 260px; padding: 40px; }

        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-title h1 { color: #2B3674; font-size: 28px; font-weight: 700; }
        .page-title p { color: #A3AED0; margin-top: 5px; }

        .kpi-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .kpi-card {
            background: white;
            padding: 20px;
            border-radius: 20px;
            box-shadow: 0 18px 40px rgba(112, 144, 176, 0.12);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.3s;
        }
        .kpi-card:hover { transform: translateY(-5px); }
        
        .kpi-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .icon-money { background: #E6FFFA; color: #1A4D4E; }
        .icon-cart { background: #FFF7E6; color: #E89020; }
        .icon-avg { background: #F4F7FE; color: #4318FF; }

        .kpi-info h4 { color: #A3AED0; font-size: 14px; font-weight: 500; }
        .kpi-info h2 { color: #2B3674; font-size: 28px; font-weight: 700; margin-top: 5px; }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr; 
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 18px 40px rgba(112, 144, 176, 0.12);
        }
        .chart-container {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            height: 300px;
            padding-bottom: 10px;
            border-bottom: 1px solid #E0E5F2;
        }
        .bar-group { display: flex; flex-direction: column; align-items: center; width: 10%; }
        .bar {
            width: 100%;
            background: linear-gradient(180deg, #1A4D4E 0%, #113031 100%);
            border-radius: 8px 8px 0 0;
            transition: height 0.5s ease;
            position: relative;
        }
        .bar:hover::after {
            content: attr(data-value);
            position: absolute;
            top: -35px;
            left: 50%;
            transform: translateX(-50%);
            background: #2B3674;
            color: white;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: bold;
            white-space: nowrap;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        .day-label { margin-top: 15px; color: #A3AED0; font-size: 13px; font-weight: 500; }

        .top-list-card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 18px 40px rgba(112, 144, 176, 0.12);
            display: flex;
            flex-direction: column;
        }
        .top-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #F4F7FE;
        }
        .top-item:last-child { border-bottom: none; }
        
        .rank-circle {
            width: 30px;
            height: 30px;
            background: #F4F7FE;
            color: #2B3674;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            margin-right: 15px;
        }
        .rank-1 { background: #FFD700; color: #fff; text-shadow: 0 1px 2px rgba(0,0,0,0.2); }
        .rank-2 { background: #C0C0C0; color: #fff; text-shadow: 0 1px 2px rgba(0,0,0,0.2); }
        .rank-3 { background: #CD7F32; color: #fff; text-shadow: 0 1px 2px rgba(0,0,0,0.2); }

        @media (max-width: 1100px) {
            .dashboard-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .kpi-row { grid-template-columns: 1fr; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

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
                    <?php foreach ($chart_data as $date => $count): 
                        $height_percent = ($count / $max_val) * 100;
                        
                        $timestamp = strtotime($date);
                        $day_num = date('d', $timestamp);
                        $day_idx = date('w', $timestamp);
                        $days_it = ['Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab'];
                        $day_name = $days_it[$day_idx] . " " . $day_num;
                    ?>
                    <div class="bar-group">
                        <div class="bar" style="height: <?php echo $height_percent; ?>%;" data-value="<?php echo $count; ?> Ordini"></div>
                        <span class="day-label"><?php echo $day_name; ?></span>
                    </div>
                    <?php endforeach; ?>
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

</body>
</html>