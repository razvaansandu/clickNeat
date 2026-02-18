<?php
if(session_status() !== PHP_SESSION_ACTIVE) session_start();
$current_page = basename($_SERVER['PHP_SELF']);

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ClickNeat</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style_ristoratori.css"> 
</head>
<body>
 
    <div class="mobile-header">  
        <button class="hamburger-btn">  
            <i class="fa-solid fa-bars" id="menuBtn"></i> 
        </button>  
    </div>

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
                    
                    <?php if (!empty($rest['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($rest['image_url']); ?>" 
                             alt="Foto Ristorante" 
                             style="width: 100%; height: 150px; object-fit: cover; border-radius: 12px; margin-bottom: 15px;">
                    <?php else: ?>
                        <div style="width: 100%; height: 150px; background-color: #F4F7FE; border-radius: 12px; margin-bottom: 15px; display: flex; align-items: center; justify-content: center;">
                            <i class="fa-solid fa-store" style="font-size: 40px; color: #A3AED0;"></i>
                        </div>
                    <?php endif; ?>

                    <h2 style="margin-top: 5px;"><?php echo htmlspecialchars($rest['nome']); ?></h2>
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
    </script>
</body>
</html>