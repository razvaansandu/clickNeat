<?php
if(session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once "../../config/db.php";
require_once "../../models/RistoranteRistoratoreModel.php";
require_once "../../models/OrderRistoratoreModel.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["ruolo"] !== 'ristoratore') {
    header("location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION["id"];
$ristoranteModel = new RistoranteRistoratoreModel($db);
$orderModel = new OrderRistoratoreModel($db);

$my_restaurants = $ristoranteModel->getAllByUserId($user_id);

if (!empty($my_restaurants)) {
    foreach ($my_restaurants as &$row) {
        $rest_id = $row['id'];
        
        $stats = $orderModel->getRestaurantStats($rest_id);
        
        $row['total_orders'] = $stats['total_orders'];
        $row['revenue'] = $stats['revenue'];
        
        $row['trend_data'] = [rand(20, 100), rand(20, 100), rand(20, 100), rand(20, 100), rand(50, 100)];
    }
    unset($row); 
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - ClickNeat</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style_ristoratori.css">
</head>
<body>

    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <div>
                <p>Panoramica</p>
                <h1>Bentornato, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
            </div>
            <div style="background: white; padding: 10px 20px; border-radius: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); font-weight:600; color:#1A4D4E;">
                <i class="fa-regular fa-calendar"></i> <?php echo date("d M Y"); ?>
            </div>
        </div>

        <h3 style="color: #2B3674; margin-bottom: 20px;">I tuoi Ristoranti</h3>

        <div class="grid-container">
            
            <?php foreach($my_restaurants as $rest): ?>
                <div class="card">
                    <div style="display:flex; justify-content:space-between;">
                        <div class="card-icon"><i class="fa-solid fa-store"></i></div>
                        <div style="color:#A3AED0; cursor:pointer;"></div>
                    </div>

                    <h2><?php echo htmlspecialchars($rest['nome']); ?></h2>
                    <p class="subtitle">Ordini totali: <b><?php echo $rest['total_orders']; ?></b></p>

                    <div class="stat-row">
                        <div class="revenue">
                            € <?php echo number_format($rest['revenue'], 2); ?>
                            <br><span>Guadagni totali</span>
                        </div>
                        
                        <div class="mini-chart">
                            <?php foreach($rest['trend_data'] as $key => $val): ?>
                                <div class="bar <?php echo $key > 2 ? 'active' : ''; ?>" style="height: <?php echo $val; ?>%;"></div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <a href="manage_restaurant.php?id=<?php echo $rest['id']; ?>" class="btn-manage">
                        Gestisci Menu <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            <?php endforeach; ?>

            <a href="create_restaurant.php" style="text-decoration:none;">
                <div class="card card-add">
                    <div class="icon-plus">+</div>
                    <div class="text-add">Aggiungi Ristorante</div>
                    <p style="color:#A3AED0; font-size:14px; margin-top:5px;">Espandi il tuo business</p>
                </div>
            </a>

        </div>
    </div>

</body>
</html>