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
        $row['image_url'] = htmlspecialchars($row['image_url']);
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .restaurant-header-custom {
            background: linear-gradient(105deg, var(--accent-orange) 0%, var(--accent-red) 100%);
            padding: 40px 60px;
            color: var(--white);
            position: relative;
            overflow: hidden;
            margin-bottom: 40px;
        }

        .restaurant-header-custom::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 60%;
            height: 200%; 
            background: rgba(255,255,255,0.1);
            transform: rotate(-15deg);
            pointer-events: none;
        }

        .restaurant-header-content {
            max-width: 1300px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        .restaurant-header-custom h1 {
            font-size: 44px;
            font-weight: 800;
            margin-bottom: 15px;
            line-height: 1.2;
            letter-spacing: -0.5px;
            color: white;
        }

        .restaurant-header-custom p {
            font-size: 18px;
            opacity: 0.95;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            color: white;
        }

        .restaurant-header-custom i {
            margin-right: 8px;
        }

        .dish-image-container { 
            border-radius: 30px; 
            position: relative;
            width: 100%;
            height: 220px;
            overflow: hidden;
            background: #f5f5f5;  
        }

        .dish-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s;
        }

        .card-style:hover .dish-image {
            transform: scale(1.05);
        }

        .image-placeholder-neutral {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f0f0f0;
            color: #999;
            font-size: 3.5em;
        }

        .image-placeholder-neutral i {
            opacity: 0.5;
        }

        .section-title-with-icon {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 30px;
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .section-title-with-icon i {
            color: var(--accent-orange);
            font-size: 28px;
        }

        .section-title-with-icon::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 70px;
            height: 4px;
            background: var(--accent-orange);
            border-radius: 4px;
        }

        .dish-desc {
            font-size: 14px;
            color: #666;
            line-height: 1.6;
            margin: 12px 0 20px 0;
            flex: 1;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            font-weight: 400;
            letter-spacing: 0.2px;
        }

        .price-tag {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-brand);
            display: flex;
            align-items: center;
            gap: 2px;
        }

        .price-tag::before {
            content: '€';
            font-size: 18px;
            font-weight: 500;
            color: var(--text-grey);
            margin-right: 2px;
        }

        .dish-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 8px;
            line-height: 1.3;
            letter-spacing: -0.3px;
        }

        .empty-menu-custom {
            grid-column: 1/-1;
            text-align: center;
            padding: 80px 40px;
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(0,0,0,0.03);
        }

        .empty-menu-custom i {
            font-size: 5em;
            background: linear-gradient(135deg, var(--primary-brand) 0%, var(--primary-dark) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 25px;
        }

        .empty-menu-custom h3 {
            font-size: 2em;
            color: var(--primary-dark);
            margin-bottom: 15px;
            font-weight: 700;
        }

        .empty-menu-custom p {
            color: var(--text-grey);
            font-size: 1.1em;
        }

        .dish-footer .btn-add {
            background: var(--bg-light);
            color: var(--primary-brand);
            padding: 12px 24px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.95em;
            border: 1px solid rgba(26,77,78,0.15);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        }

        .dish-footer .btn-add:hover {
            background: var(--primary-brand);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(26,77,78,0.2);
            border-color: var(--primary-brand);
        }

        .dish-footer .btn-add i {
            font-size: 14px;
            transition: transform 0.3s ease;
        }

        .dish-footer .btn-add:hover i {
            transform: rotate(90deg);
        }

        .dish-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: auto;
            padding-top: 20px;
            border-top: 1px solid rgba(0,0,0,0.05);
        }

        .dish-body {
            padding: 24px;
            display: flex;
            flex-direction: column;
            flex: 1;
            background: var(--white);
        }

        @media (max-width: 991px) {
            .restaurant-header-custom {
                padding: 30px 20px;
            }

            .restaurant-header-custom h1 {
                font-size: 32px;
            }

            .restaurant-header-custom p {
                font-size: 16px;
            }

            .dish-image-container {
                height: 200px;
            }

            .section-title-with-icon {
                font-size: 24px;
            }

            .empty-menu-custom {
                padding: 50px 20px;
            }

            .empty-menu-custom h3 {
                font-size: 1.5em;
            }

            .empty-menu-custom p {
                font-size: 1em;
            }
        }

        @media (max-width: 768px) {
            .dish-footer {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }

            .dish-footer .btn-add {
                justify-content: center;
                padding: 14px 24px;
            }

            .price-tag {
                text-align: center;
                justify-content: center;
            }
            
            .dish-title {
                font-size: 18px;
                text-align: center;
            }
            
            .dish-desc {
                text-align: center;
                font-size: 13px;
            }
        }

        @media (max-width: 480px) {
            .restaurant-header-custom h1 {
                font-size: 26px;
            }

            .restaurant-header-custom p {
                font-size: 14px;
            }

            .btn-back-hero {
                padding: 8px 16px;
                font-size: 13px;
            }

            .dish-image-container {
                height: 180px;
            }

            .dish-body {
                padding: 20px;
            }

            .section-title-with-icon {
                font-size: 22px;
            }
            
            .price-tag {
                font-size: 22px;
            }
            
            .price-tag::before {
                font-size: 16px;
            }
        }
    </style>
</head>

<body>

    <div class="mobile-header-fixed">
        <div class="mobile-top-row">
            <a href="dashboard_consumatore.php" class="brand-logo">
                <i class="fa-solid fa-leaf"></i> ClickNeat
            </a>
            <a href="../auth/logout.php" class="mobile-logout">
                <i class="fa-solid fa-right-from-bracket"></i>
            </a>
        </div>
        <div class="mobile-search-bar">
            <input type="text" placeholder="Cerca piatti...">
            <i class="fa-solid fa-search search-icon"></i>
        </div>
    </div>

    <nav class="top-navbar">
        <a href="dashboard_consumatore.php" class="brand-logo">
            <i class="fa-solid fa-leaf" style="color: #05CD99;"></i> ClickNeat
        </a>
        <div class="searchBar">
            <input type="text" placeholder="Cerca piatti...">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
        </div>
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

    <header class="restaurant-header-custom">
        <div class="restaurant-header-content">
            <a href="dashboard_consumatore.php" class="btn-back-hero">
                <i class="fa-solid fa-arrow-left"></i> Torna ai Ristoranti
            </a>
            <h1><?php echo htmlspecialchars($ristorante['nome']); ?></h1>
            <p><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($ristorante['indirizzo']); ?></p>
            <p><i class="fa-solid fa-clock"></i> Orari: 12:00 - 23:00</p>
        </div>
    </header>

    <div class="main-container"> 
        <h3 class="section-title-with-icon">
            <i class="fa-solid fa-utensils"></i> Menu del Giorno
        </h3> 
 
        <div class="grid-container">
            <?php if (!empty($lista_piatti)): ?>
                <?php foreach ($lista_piatti as $piatto): ?>
                    <div class="card-style"> 
                        <div class="dish-image-container">
                            <?php if (!empty($piatto['image_url'])): ?>
                                <img src="/<?php echo $piatto['image_url']; ?>" class="dish-image" alt="<?php echo htmlspecialchars($piatto['name']); ?>">
                            <?php else: ?>
                                <div class="image-placeholder-neutral">
                                    <i class="fa-solid fa-image"></i> 
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="dish-body">
                            <div class="dish-title"><?php echo htmlspecialchars($piatto['name']); ?></div>
                            <div class="dish-desc">
                                <?php echo !empty($piatto['description']) ? htmlspecialchars($piatto['description']) : "Delizioso piatto preparato con ingredienti freschi e selezionati."; ?>
                            </div>

                            <div class="dish-footer">
                                <div class="price-tag"><?php echo number_format($piatto['price'], 2); ?></div>
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
                <div class="empty-menu-custom">
                    <i class="fa-solid fa-utensils"></i>
                    <h3>Menu in preparazione</h3>
                    <p>Il ristorante sta aggiornando il suo menu. Torna presto per scoprire le nostre specialità!</p>
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

     <div class="bottom-nav">
        <a href="dashboard_consumatore.php" class="nav-item-bottom active">
            <i class="fa-solid fa-house"></i>
            <span>Home</span>
        </a>
        <a href="storico.php" class="nav-item-bottom">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>Ordini</span>
        </a>
        <a href="profile_consumatore.php" class="nav-item-bottom">
            <i class="fa-solid fa-user"></i>
            <span>Profilo</span>
        </a>
        <a href="help.php" class="nav-item-bottom">
            <i class="fa-solid fa-circle-question"></i>
            <span>Aiuto</span>
        </a>
    </div> 

</body>
</html>  