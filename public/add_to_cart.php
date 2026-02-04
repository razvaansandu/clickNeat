<?php
session_start();
require_once "../config/db.php";

// Controllo accesso
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Verifica metodo richiesta
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard_consumatore.php");
    exit;
}

$piatto_id = isset($_POST['piatto_id']) ? intval($_POST['piatto_id']) : 0;
$ristorante_id = isset($_POST['ristorante_id']) ? intval($_POST['ristorante_id']) : 0;

if ($piatto_id === 0 || $ristorante_id === 0) {
    header("Location: dashboard_consumatore.php");
    exit;
}

// Recupera dettagli piatto dal DB
$sql = "SELECT * FROM menu_items WHERE id = ?";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, "i", $piatto_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$piatto = mysqli_fetch_assoc($result);

if (!$piatto) {
    die("Errore: Prodotto non trovato.");
}

// --- LOGICA CARRELLO (SESSIONE) ---
// Inizializza carrello se non esiste
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [
        'restaurant_id' => $ristorante_id,
        'items' => [],
        'total' => 0.00
    ];
}

// Controllo Ristorante: Se aggiungi da un altro ristorante, svuota il vecchio carrello
// (Logica semplificata: un ordine = un ristorante)
if ($_SESSION['cart']['restaurant_id'] != $ristorante_id) {
    $_SESSION['cart'] = [
        'restaurant_id' => $ristorante_id,
        'items' => [],
        'total' => 0.00
    ];
}

// Aggiungi o Incrementa quantità
$found = false;
foreach ($_SESSION['cart']['items'] as &$item) {
    if ($item['id'] == $piatto_id) {
        $item['qty']++;
        $found = true;
        break;
    }
}

if (!$found) {
    $_SESSION['cart']['items'][] = [
        'id' => $piatto['id'],
        'name' => $piatto['name'],
        'price' => $piatto['price'],
        'qty' => 1,
        // Fallback immagine se manca
        'image' => !empty($piatto['image_url']) ? $piatto['image_url'] : "https://loremflickr.com/300/300/food?lock=".$piatto['id']
    ];
}

// Ricalcola totale
$_SESSION['cart']['total'] += $piatto['price'];

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Prodotto Aggiunto - ClickNeat</title>
    <link rel="stylesheet" href="css/style_consumatori.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* CSS Extra specifico per questa pagina di conferma */
        .confirmation-box {
            background: white;
            border-radius: 20px;
            padding: 50px 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            max-width: 600px;
            margin: -50px auto 0 auto; /* Sovrappone all'hero */
            position: relative;
            z-index: 10;
        }

        .success-icon-circle {
            width: 80px; height: 80px;
            background: #E6FAF5;
            color: #05CD99;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 40px;
            margin: 0 auto 20px auto;
        }

        .added-item-name {
            font-size: 22px; font-weight: 700; color: #2B3674; margin-bottom: 5px;
        }

        .cart-summary {
            background: #F9FAFB;
            border-radius: 15px;
            padding: 20px;
            margin: 30px 0;
            display: flex; justify-content: space-between; align-items: center;
        }

        .cart-total-label { font-size: 14px; color: #A3AED0; }
        .cart-total-value { font-size: 24px; font-weight: 700; color: #2B3674; }

        .action-buttons {
            display: grid; grid-template-columns: 1fr 1fr; gap: 20px;
        }

        .btn-continue {
            background: #F4F7FE; color: #1A4D4E; padding: 15px; border-radius: 15px;
            font-weight: 600; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 10px;
        }
        .btn-continue:hover { background: #e0e7ff; }

        .btn-checkout {
            background: #1A4D4E; color: white; padding: 15px; border-radius: 15px;
            font-weight: 600; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 10px;
            box-shadow: 0 10px 20px rgba(26, 77, 78, 0.2);
        }
        .btn-checkout:hover { background: #FF9F43; transform: translateY(-3px); }

        @media (max-width: 600px) {
            .action-buttons { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <nav class="top-navbar">
        <a href="dashboard_consumatore.php" class="brand-logo">
            <i class="fa-solid fa-leaf" style="color: #FF9F43;"></i> ClickNeat
        </a>
        <div class="nav-links">
            <a href="dashboard_consumatore.php" class="nav-item">
                <i class="fa-solid fa-house"></i> <span>Home</span>
            </a>
            <a href="history.php" class="nav-item">
                <i class="fa-solid fa-clock-rotate-left"></i> <span>Ordini</span>
            </a>
            <a href="profile.php" class="nav-item">
                <i class="fa-solid fa-user"></i> <span>Profilo</span>
            </a>
            <a href="logout.php" class="btn-logout-nav">
                <i class="fa-solid fa-right-from-bracket"></i> Esci
            </a>
        </div>
    </nav>

    <header class="hero-section" style="background: linear-gradient(100deg, #FF9F43 0%, #FF6B6B 100%); padding-bottom: 80px;">
        <div class="hero-content" style="text-align: center;">
            <div class="hero-title">
                <h1>Ottima scelta! 😋</h1>
                <p style="justify-content: center;">Il prodotto è stato aggiunto al tuo carrello.</p>
            </div>
        </div>
    </header>

    <div class="main-container">
        
        <div class="confirmation-box">
            <div class="success-icon-circle">
                <i class="fa-solid fa-check"></i>
            </div>

            <div style="color: #A3AED0; font-size: 14px; margin-bottom: 5px;">Hai aggiunto:</div>
            <div class="added-item-name"><?php echo htmlspecialchars($piatto['name']); ?></div>
            
            <div class="cart-summary">
                <div>
                    <div class="cart-total-label">Totale Carrello</div>
                    <div style="font-size: 13px; color: #A3AED0;"><?php echo count($_SESSION['cart']['items']); ?> articoli</div>
                </div>
                <div class="cart-total-value">
                    € <?php echo number_format($_SESSION['cart']['total'], 2); ?>
                </div>
            </div>

            <div class="action-buttons">
                <a href="menu.php?id=<?php echo $ristorante_id; ?>" class="btn-continue">
                    <i class="fa-solid fa-arrow-left"></i> Continua a ordinare
                </a>
                
                <a href="checkout.php" class="btn-checkout">
                    Vai alla cassa <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>

    </div>

</body>
</html>
<?php 
if (isset($stmt)) mysqli_stmt_close($stmt);
mysqli_close($link); 
?>