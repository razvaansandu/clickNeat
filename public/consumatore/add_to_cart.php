<?php
session_start();
require_once "../../config/db.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

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

$sql = "SELECT * FROM menu_items WHERE id = ?";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, "i", $piatto_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$piatto = mysqli_fetch_assoc($result);

if (!$piatto) {
    die("Errore: Prodotto non trovato.");
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [
        'restaurant_id' => $ristorante_id,
        'items' => [],
        'total' => 0.00
    ];
}

if ($_SESSION['cart']['restaurant_id'] != $ristorante_id) {
    $_SESSION['cart'] = [
        'restaurant_id' => $ristorante_id,
        'items' => [],
        'total' => 0.00
    ];
}

$found = false;
foreach ($_SESSION['cart']['items'] as &$item) {
    if ($item['id'] == $piatto_id) {
        $item['qty']++;
        $found = true;
        break;
    }
}

$img_url = !empty($piatto['image_url']) ? $piatto['image_url'] : "https://via.placeholder.com/150?text=No+Image";

if (!$found) {
    $_SESSION['cart']['items'][] = [
        'id' => $piatto['id'],
        'name' => $piatto['name'],
        'price' => $piatto['price'],
        'qty' => 1,
        'image' => $img_url
    ];
}

$_SESSION['cart']['total'] += $piatto['price'];
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prodotto Aggiunto - ClickNeat</title>

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
            <a href="profile_ristoratore.php" class="nav-item">
                <i class="fa-solid fa-user"></i> <span>Profilo</span>
            </a>
            <a href="../auth/logout.php" class="btn-logout-nav">
                <i class="fa-solid fa-right-from-bracket"></i> Esci
            </a>
        </div>
    </nav>

    <header class="hero-section">
        <div class="hero-content" style="text-align: center;">
            <div class="hero-title">
                <h1>Ottima scelta!</h1>
                <p>Il prodotto è stato aggiunto al carrello con successo.</p>
            </div>
        </div>
    </header>

    <div class="main-container">
        <div class="confirmation-box">

            <div class="success-icon-circle">
                <i class="fa-solid fa-check"></i>
            </div>

            <div style="color: #A3AED0; font-size: 14px; margin-bottom: 5px;">Hai aggiunto:</div>

            <img src="<?php echo htmlspecialchars($img_url); ?>" alt="Piatto"
                style="width: 100px; height: 100px; object-fit: cover; border-radius: 15px; margin: 15px auto; display: block;">

            <div class="added-item-name"><?php echo htmlspecialchars($piatto['name']); ?></div>

            <div class="cart-summary">
                <div style="text-align: left;">
                    <div class="cart-total-label">Totale Carrello</div>
                    <div style="font-size: 13px; color: #A3AED0;">
                        <?php echo count($_SESSION['cart']['items']); ?> articoli
                    </div>
                </div>
                <div class="cart-total-value">
                    € <?php echo number_format($_SESSION['cart']['total'], 2); ?>
                </div>
            </div>

            <div class="action-buttons">
                <a href="menu.php?id=<?php echo $ristorante_id; ?>" class="btn-continue">
                    <i class="fa-solid fa-arrow-left"></i> Continua
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
if (isset($stmt))
    mysqli_stmt_close($stmt);
mysqli_close($link);
?>