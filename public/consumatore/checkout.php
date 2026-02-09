<?php
if (session_status() !== PHP_SESSION_ACTIVE)
    session_start();

require_once "../../config/db.php";
require_once "../../models/OrderModel.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_SESSION['cart']) || empty($_SESSION['cart']['items'])) {
    header("Location: dashboard_consumatore.php");
    exit;
}

$cart = $_SESSION['cart'];
$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['place_order'])) {
    $user_id = $_SESSION['id'];
    $restaurant_id = $cart['restaurant_id'];
    $total = $cart['total'];

    $orderModel = new OrderModel($db);

    try {
        $orderModel->beginTransaction();

        $order_id = $orderModel->create($user_id, $restaurant_id, $total);

        if (!$order_id) {
            throw new Exception("Impossibile creare l'ordine.");
        }

        foreach ($cart['items'] as $item) {
            $orderModel->addItem($order_id, $item['id'], $item['qty'], $item['price']);
        }

        $orderModel->commit();

        unset($_SESSION['cart']);
        header("Location: storico.php?msg=success");
        exit;

    } catch (Exception $e) {
        $orderModel->rollback();
        $msg = "Errore durante il salvataggio: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - ClickNeat</title>
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
            <a href="menu.php?id=<?php echo $cart['restaurant_id']; ?>" class="btn-back-hero">
                <i class="fa-solid fa-arrow-left"></i> Torna al Menu
            </a>
            <div class="hero-title">
                <h1>Checkout</h1>
                <p>Conferma il tuo ordine per il ritiro.</p>
            </div>
        </div>
    </header>

    <div class="main-container">
        <?php if ($msg): ?>
            <div
                style="background-color: #ffebee; color: #c62828; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ffcdd2;">
                <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <div class="card-style">
            <div class="checkout-layout">

                <div class="checkout-form">
                    <h3 class="section-title checkout-section-title">Dettagli Ritiro</h3>

                    <div class="pickup-info">
                        <i class="fa-solid fa-bag-shopping"></i> Modalità: Ritiro al Ristorante (Takeout)
                    </div>

                    <form method="POST" action="checkout.php">
                        <div class="input-group">
                            <label>Metodo di Pagamento</label>
                            <select class="checkout-select">
                                <option>Paga al ritiro (Contanti/Carta)</option>
                                <option>Carta di Credito (Online)</option>
                            </select>
                        </div>

                </div>

                <div class="checkout-summary">
                    <h3 class="section-title checkout-section-title">Riepilogo</h3>

                    <?php foreach ($cart['items'] as $item): ?>
                        <div class="summary-row" style="align-items: center;">

                            <div class="qty-control-box">
                                <a href="update_cart.php?action=decrease&id=<?php echo $item['id']; ?>"
                                    class="btn-qty minus">
                                    <i class="fa-solid fa-minus"></i>
                                </a>

                                <span style="font-weight: 700; width: 20px; text-align: center;">
                                    <?php echo $item['qty']; ?>
                                </span>

                                <a href="update_cart.php?action=increase&id=<?php echo $item['id']; ?>"
                                    class="btn-qty plus">
                                    <i class="fa-solid fa-plus"></i>
                                </a>
                            </div>

                            <div class="item-name-checkout">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </div>

                            <div style="text-align: right;">
                                <div style="font-weight: 600;">€
                                    <?php echo number_format($item['price'] * $item['qty'], 2); ?></div>
                                <a href="update_cart.php?action=remove&id=<?php echo $item['id']; ?>"
                                    class="btn-remove-item" title="Rimuovi">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </div>

                        </div>
                    <?php endforeach; ?>

                    <div class="summary-total">
                        <span>Totale da Pagare</span>
                        <span>€ <?php echo number_format($cart['total'], 2); ?></span>
                    </div>

                    <button type="submit" name="place_order" class="btn-confirm">
                        Conferma e Ritira <i class="fa-solid fa-check"></i>
                    </button>

                    </form>
                </div>

            </div>
        </div>
    </div>
</body>

</html>