<?php
require_once "../../config/db.php";
require_once "../../models/RistoranteModel.php";
require_once "../../models/MenuModel.php";
require_once "../../models/WalletModel.php";
require_once "../../models/MenuGiornalieroModel.php";

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
$giorno_richiesto = isset($_GET['giorno']) ? intval($_GET['giorno']) : date('w');

$ristoranteModel = new RistoranteModel($db);
$menuModel = new MenuModel($db);
$walletModel = new WalletModel($db);
$menuGiornalieroModel = new MenuGiornalieroModel($db);

$ristorante = $ristoranteModel->getById($ristorante_id);
$creditoEuro = $walletModel->getBalanceEuro($_SESSION['id']);

if (!$ristorante) {
    die("Ristorante non trovato.");
}

$giorni = ['Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato'];

// Query diretta per bypassare eventuali problemi del modello
$sql_menu = "SELECT * FROM menus 
             WHERE ristorante_id = ? AND type = 'daily' AND weekday = ? AND is_active = 1";
$menu_giornaliero = $db->selectOne($sql_menu, [$ristorante_id, $giorno_richiesto]);

$lista_piatti = [];
$tipo_menu = 'completo';
$titolo_menu = 'Menu del Giorno';

if ($menu_giornaliero) {
    // Prendi i piatti di questo menu
    $sql_piatti = "SELECT mi.* FROM menu_entries me
                   JOIN menu_items mi ON me.menu_item_id = mi.id
                   WHERE me.menu_id = ? AND mi.deleted_at IS NULL
                   ORDER BY me.sort_order ASC";
    $lista_piatti = $db->select($sql_piatti, [$menu_giornaliero['id']]);
    
    if (!empty($lista_piatti)) {
        $tipo_menu = 'giornaliero';
        $titolo_menu = $menu_giornaliero['title'];
    }
}

// Se non ci sono piatti nel menu giornaliero, prova il fallback
if (empty($lista_piatti)) {
    $sql_fallback = "SELECT * FROM menus 
                     WHERE ristorante_id = ? AND type = 'fallback' AND is_active = 1";
    $menu_fallback = $db->selectOne($sql_fallback, [$ristorante_id]);
    
    if ($menu_fallback) {
        $sql_piatti = "SELECT mi.* FROM menu_entries me
                       JOIN menu_items mi ON me.menu_item_id = mi.id
                       WHERE me.menu_id = ? AND mi.deleted_at IS NULL
                       ORDER BY me.sort_order ASC";
        $lista_piatti = $db->select($sql_piatti, [$menu_fallback['id']]);
        
        if (!empty($lista_piatti)) {
            $tipo_menu = 'fallback';
            $titolo_menu = $menu_fallback['title'];
        }
    }
}

// Se ancora non ci sono piatti, mostra tutti i piatti del ristorante
if (empty($lista_piatti)) {
    $raw_piatti = $menuModel->getByRestaurant($ristorante_id);
    foreach ($raw_piatti as $row) {
        if (!empty($row['image_url'])) {
            $row['image_url'] = htmlspecialchars($row['image_url']);
        }
        $lista_piatti[] = $row;
    }
    $titolo_menu = 'Il nostro Menu';
}

// Gestione immagini
foreach ($lista_piatti as &$piatto) {
    if (!empty($piatto['image_url'])) {
        $piatto['image_url'] = htmlspecialchars($piatto['image_url']);
    }
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
            <?php 
            $bg_url = "";
            if (!empty($ristorante['image_url'])) {
                $clean_path = ltrim($ristorante['image_url'], '/');
                $bg_url = "../../assets/" . $clean_path;
            }
            
            if ($bg_url): ?>
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.7)), url('<?php echo $bg_url; ?>') no-repeat center center;
            <?php else: ?>
            background: linear-gradient(105deg, var(--accent-orange) 0%, var(--accent-red) 100%);
            <?php endif; ?>
            background-size: cover;
            padding: 80px 60px;
            color: var(--white);
            position: relative;
            overflow: hidden;
            margin-bottom: 40px;
            border-radius: 0 0 40px 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
 
        .restaurant-header-custom::before {
            content: ''; 
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.2);
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

        .giorno-selector {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .giorno-selector select {
            padding: 12px 20px;
            border-radius: 50px;
            border: 1px solid rgba(255,255,255,0.3);
            background: rgba(255,255,255,0.15);
            color: white;
            font-weight: 500;
            font-size: 15px;
            backdrop-filter: blur(5px);
            cursor: pointer;
            min-width: 200px;
        }
        
        .giorno-selector select option {
            background: #FF9F43;
            color: white;
            border-radius: 12px;
            
        }
        
        .menu-badge {
            background: <?php echo $tipo_menu == 'giornaliero' ? '#05CD99' : ($tipo_menu == 'fallback' ? '#4318FF' : '#FF9F43'); ?>;
            color: white;
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .menu-badge i {
            font-size: 14px;
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

        .menu-subtitle {
            font-size: 18px;
            color: var(--text-grey);
            margin-top: -15px;
            margin-bottom: 30px;
            font-weight: 400;
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
            
            .giorno-selector {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .giorno-selector select {
                width: 100%;
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
            
            <!-- Selettore giorno e badge menu -->
            <div class="giorno-selector">
                <select onchange="cambiaGiorno(this.value)">
                    <?php for ($i = 0; $i < 7; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo $i == $giorno_richiesto ? 'selected' : ''; ?>>
                            <?php echo $giorni[$i]; ?>
                        </option>
                    <?php endfor; ?>
                </select>
                
                <div class="menu-badge">
                    <i class="fa-solid fa-<?php echo $tipo_menu == 'giornaliero' ? 'sun' : ($tipo_menu == 'fallback' ? 'umbrella' : 'utensils'); ?>"></i>
                    <?php 
                    if ($tipo_menu == 'giornaliero') echo 'Menu del Giorno';
                    elseif ($tipo_menu == 'fallback') echo 'Menu Standard';
                    else echo 'Menu Completo';
                    ?>
                </div>
            </div>
            
            <div class="wallet-badge-header" style="margin-top: 15px; display: inline-flex; align-items: center; background: rgba(255,255,255,0.2); padding: 8px 15px; border-radius: 50px; border: 1px solid rgba(255,255,255,0.4); backdrop-filter: blur(5px);">
                <i class="fa-solid fa-wallet" style="margin-right: 10px; color: #fff;"></i>
                <span style="color: #fff; font-weight: 600;">Credito: &euro; <?php echo $creditoEuro; ?></span> 
            </div> 
            <div style="margin-top: 18px;">
                <a href="prenota_tavolo_g.php?id=<?php echo $ristorante_id; ?>"
                   style="display: inline-flex; align-items: center; gap: 8px;
                          background: #FF9F43; color: white; font-weight: 700;
                          font-size: 15px; padding: 12px 28px; border-radius: 50px;
                          text-decoration: none; letter-spacing: 0.3px;
                          box-shadow: 0 6px 18px rgba(255,159,67,0.45);
                          transition: transform 0.2s, box-shadow 0.2s;"
                   onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 10px 24px rgba(255,159,67,0.55)'"
                   onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 6px 18px rgba(255,159,67,0.45)'">
                    <i class="fa-solid fa-calendar-plus"></i> Prenota un Tavolo
                </a>
            </div>
        </div> 
    </header>

    <div class="main-container"> 
        <h3 class="section-title-with-icon">
            <i class="fa-solid fa-utensils"></i> <?php echo htmlspecialchars($titolo_menu); ?>
        </h3>
        
        <?php if ($tipo_menu == 'giornaliero'): ?>
            <p class="menu-subtitle">
                <i class="fa-solid fa-calendar-day"></i> 
                Menu speciale per <?php echo $giorni[$giorno_richiesto]; ?>
            </p>
        <?php elseif ($tipo_menu == 'fallback'): ?>
            <p class="menu-subtitle">
                <i class="fa-solid fa-umbrella"></i> 
                Menu standard (usato quando non c'è menu specifico per il giorno)
            </p>
        <?php endif; ?>
 
        <div class="grid-container">
            <?php if (!empty($lista_piatti)): ?>
                <?php foreach ($lista_piatti as $piatto): ?>
                    <div class="card-style"> 
                        <div class="dish-image-container">
                            <?php if (!empty($piatto['image_url'])): ?>
                                <img src="/assets/<?php echo $piatto['image_url']; ?>" class="dish-image" alt="<?php echo htmlspecialchars($piatto['name']); ?>">
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
                    <h3>Nessun piatto disponibile</h3>
                    <p>Il ristorante non ha ancora configurato il menu per questo giorno.</p>
                    <?php if ($giorno_richiesto != date('w')): ?>
                        <a href="?id=<?php echo $ristorante_id; ?>" class="btn-add" style="margin-top: 20px; display: inline-block;">
                            <i class="fa-solid fa-calendar-day"></i> Vedi menu di oggi
                        </a>
                    <?php endif; ?>
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

    <script>
        function cambiaGiorno(giorno) {
            window.location.href = window.location.pathname + '?id=<?php echo $ristorante_id; ?>&giorno=' + giorno;
        }
    </script>
</body>
</html>