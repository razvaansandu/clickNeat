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

if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_POST['place_order']) || isset($_POST['paypal_transaction_id']))) {
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

        if (!empty($_POST['paypal_transaction_id'])) {
            $orderModel->updateStatus($order_id, 'completed');
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <script src="https://www.paypal.com/sdk/js?client-id=ATHTb2gXY3GKqi99hQwcNXNVsHisCXgf7iYt5stbRypxjqjEe-qBPaffW9hC9-LEq9ZqgitpD0UYKGkY&currency=EUR"></script>
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
            <div style="background-color: #ffebee; color: #c62828; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ffcdd2;">
                <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <div class="card-style">

            <form id="checkout-form" method="POST" action="checkout.php">

                <input type="hidden" name="paypal_transaction_id" id="paypal_transaction_id" value="">

                <div class="checkout-layout">

                    <div class="checkout-form">
                        <h3 class="section-title checkout-section-title">Dettagli Ritiro</h3>

                        <div class="pickup-info">
                            <i class="fa-solid fa-bag-shopping"></i> Modalità: Ritiro al Ristorante (Takeout)
                        </div>

                        <div class="input-group">
                            <label>Metodo di Pagamento</label>
                            <select id="payment_method" name="payment_method" class="checkout-select" onchange="togglePayment()">
                                <option value="cash">Paga al ritiro (Contanti/Carta)</option>
                                <option value="paypal">Paga ora online con PayPal</option>
                            </select>
                        </div>
                    </div>

                    <div class="checkout-summary">
                        <h3 class="section-title checkout-section-title">Riepilogo</h3>

                        <?php foreach ($cart['items'] as $item): ?>
                            <div class="summary-row" style="align-items: center;">
                                <div class="qty-control-box">
                                    <a href="update_cart.php?action=decrease&id=<?php echo $item['id']; ?>" class="btn-qty minus">
                                        <i class="fa-solid fa-minus"></i>
                                    </a>
                                    <span style="font-weight: 700; width: 20px; text-align: center;">
                                        <?php echo $item['qty']; ?>
                                    </span>
                                    <a href="update_cart.php?action=increase&id=<?php echo $item['id']; ?>" class="btn-qty plus">
                                        <i class="fa-solid fa-plus"></i>
                                    </a>
                                </div>
                                <div class="item-name-checkout">
                                    <?php echo htmlspecialchars($item['name']); ?>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-weight: 600;">€ <?php echo number_format($item['price'] * $item['qty'], 2); ?></div>
                                    <a href="update_cart.php?action=remove&id=<?php echo $item['id']; ?>" class="btn-remove-item" title="Rimuovi">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="summary-total">
                            <span>Totale da Pagare</span>
                            <span>€ <?php echo number_format($cart['total'], 2); ?></span>
                        </div>

                        <div id="standard-submit-container">
                            <button type="submit" name="place_order" class="btn-confirm">
                                Conferma e Ritira <i class="fa-solid fa-check"></i>
                            </button>
                        </div>

                        <div id="paypal-button-container" style="display: none; margin-top: 20px;"></div>

                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function togglePayment() {
            const method = document.getElementById('payment_method').value;
            if (method === 'paypal') {
                document.getElementById('standard-submit-container').style.display = 'none';
                document.getElementById('paypal-button-container').style.display = 'block';
            } else {
                document.getElementById('standard-submit-container').style.display = 'block';
                document.getElementById('paypal-button-container').style.display = 'none';
            }
        }

        function showToast(message, type) {
            const toast = document.getElementById('custom-toast');
            const msgEl = document.getElementById('toast-message');

            msgEl.textContent = message;
            toast.className = 'toast show ' + type;

            setTimeout(() => {
                toast.classList.remove('show');
            }, 3500);
        }

        const orderTotal = '<?php echo number_format($cart['total'], 2, '.', ''); ?>';

        paypal.Buttons({
            createOrder: function(data, actions) {
                return actions.order.create({
                    purchase_units: [{
                        amount: {
                            value: orderTotal
                        }
                    }]
                });
            },
            onApprove: function(data, actions) {
                return actions.order.capture().then(function(details) {
                    document.getElementById('paypal_transaction_id').value = details.id;
                    document.getElementById('checkout-form').submit();
                });
            },
            onCancel: function(data) {
                showToast("Pagamento annullato. Nessun addebito effettuato.", "warning");
            },
            onError: function(err) {
                console.error(err);
                showToast("Errore di connessione con PayPal. Riprova.", "error");
            }
        }).render('#paypal-button-container');
    </script>
    <div id="custom-toast" class="toast">
        <div class="toast-message" id="toast-message"></div>
    </div>
</body>

</html>