<?php
require_once "../../config/db.php";
require_once "../../models/RistoranteModel.php";
require_once "../../models/MenuModel.php";

if (session_status() !== PHP_SESSION_ACTIVE)
    session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: dashboard_consumatore.php");
    exit;
}

$ristorante_id = intval($_GET['id']);

$ristoranteModel = new RistoranteModel($db);
$menuModel = new MenuModel($db);

$ristorante = $ristoranteModel->getById($ristorante_id);

if (!$ristorante) {
    die("Ristorante non trovato.");
}

$raw_piatti = $menuModel->getByRestaurant($ristorante_id);
$lista_piatti = [];

foreach ($raw_piatti as $row) {
    if (!empty($row['image_url'])) {
        $row['image_url'] = "/" . htmlspecialchars($row['image_url']);
    }
    
    $lista_piatti[] = $row;
}

$total_qty = 0;
if (isset($_SESSION['cart']) && !empty($_SESSION['cart']['items'])) {
    foreach ($_SESSION['cart']['items'] as $item) {
        $total_qty += $item['qty'];
    }
}
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Menu - <?php echo htmlspecialchars($ristorante['nome']); ?></title>
    <link rel="stylesheet" href="../../css/style_consumatori.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
</head>

<body>

    <nav class="top-navbar">
        <a href="dashboard_consumatore.php" class="brand-logo">
            <i class="fa-solid fa-leaf" style="color: #05CD99;"></i> ClickNeat
        </a>
        <div class="nav-links">
            <a href="dashboard_consumatore.php" class="nav-item">
                <i class="fa-solid fa-house"></i> <span>Home</span>
            </a>
            <a href="storico.php" class="nav-item">
                <i class="fa-solid fa-clock-rotate-left"></i> <span>Ordini</span>
            </a>
            <a href="profile_consumatore.php" class="nav-item">
                <i class="fa-solid fa-user"></i> <span>Profilo</span>
            </a>
            <a href="help.php" class="nav-item">
                <i class="fa-solid fa-circle-question"></i> <span>Aiuto</span>
            </a>
            <a href="../auth/logout.php" class="btn-logout-nav">
                <i class="fa-solid fa-right-from-bracket"></i> Esci
            </a>
        </div>
    </nav>

    <header class="hero-section">
        <div class="hero-content">
            <a href="dashboard_consumatore.php" class="btn-back-hero">
                <i class="fa-solid fa-arrow-left"></i> Torna ai Ristoranti
            </a>
            <div class="hero-title">
                <h1><?php echo htmlspecialchars($ristorante['nome']); ?></h1>
                <p><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($ristorante['indirizzo']); ?>
                </p>
            </div>
        </div>
    </header>

    <div class="main-container">
        <h3 class="section-title">Menu del Giorno</h3>

        <div class="grid-container">
            <?php if (!empty($lista_piatti)): ?>
                <?php foreach ($lista_piatti as $piatto): ?>
                    <div class="card-style">
                        <img src="<?php echo $piatto['image_url']; ?>" class="dish-image" alt="Piatto" style="object-fit: cover; width: 100%; height: 200px;">

                        <div class="dish-body">
                            <div class="dish-title"><?php echo htmlspecialchars($piatto['name']); ?></div>
                            <div class="dish-desc">
                                <?php echo !empty($piatto['description']) ? htmlspecialchars($piatto['description']) : "Nessuna descrizione."; ?>
                            </div>

                            <div class="dish-footer">
                                <div class="price-tag">€ <?php echo number_format($piatto['price'], 2); ?></div>
                                <form action="add_to_cart.php" method="POST">
                                    <input type="hidden" name="piatto_id" value="<?php echo $piatto['id']; ?>">
                                    <input type="hidden" name="ristorante_id" value="<?php echo $ristorante_id; ?>">
                                    <button type="submit" class="btn-add">
                                        Aggiungi <i class="fa-solid fa-plus"></i> 
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 50px; background: white; border-radius: 20px;">
                    <i class="fa-solid fa-utensils" style="font-size: 40px; color: #ddd; margin-bottom: 15px;"></i>
                    <h3 style="color: #2B3674;">Menu in arrivo</h3>
                    <p style="color: #A3AED0;">Non ci sono ancora piatti disponibili per questo locale.</p>
                </div>
            <?php endif; ?>
        </div>
    </div> 

    <?php if ($total_qty > 0): ?>
        <a href="checkout.php" class="floating-cart-btn" title="Vai al carrello">
            <i class="fa-solid fa-cart-shopping"></i>
            <span class="cart-count-badge"><?php echo $total_qty; ?></span>
        </a> 
    <?php endif; ?>

</body>
</html> 