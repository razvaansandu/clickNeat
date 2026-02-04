<?php
session_start();
require_once "../../config/db.php";

// Report errori per debug
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
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

    try {
        mysqli_begin_transaction($link);

        // 1. Inserisci Ordine (SENZA INDIRIZZO E NOTE)
        // Usiamo solo i campi che hai confermato esistere nella tua tabella orders
        $sql = "INSERT INTO orders (user_id, restaurant_id, total_amount, status, created_at) VALUES (?, ?, ?, 'pending', NOW())";
        $stmt = mysqli_prepare($link, $sql);
        mysqli_stmt_bind_param($stmt, "iid", $user_id, $restaurant_id, $total);
        mysqli_stmt_execute($stmt);

        $order_id = mysqli_insert_id($link);

        // 2. Inserisci i dettagli piatti
        $sql_item = "INSERT INTO order_items (order_id, dish_id, quantity, price_at_time) VALUES (?, ?, ?, ?)";
        $stmt_item = mysqli_prepare($link, $sql_item);

        foreach ($cart['items'] as $item) {
            mysqli_stmt_bind_param($stmt_item, "iiid", $order_id, $item['id'], $item['qty'], $item['price']);
            mysqli_stmt_execute($stmt_item);
        }

        mysqli_commit($link);
        unset($_SESSION['cart']);
        header("Location: history.php?msg=success");
        exit;

    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($link);
        $msg = "Errore Database: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <title>Checkout - ClickNeat</title>
    <link rel="stylesheet" href="../css/style_consumatori.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .checkout-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .checkout-form {
            padding: 30px;
        }

        .checkout-summary {
            background: #F9FAFB;
            padding: 30px;
            border-left: 1px solid #eee;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px dashed #E0E5F2;
            font-size: 14px;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            font-size: 20px;
            font-weight: 700;
            color: #1A4D4E;
        }

        .btn-confirm {
            width: 100%;
            background: #1A4D4E;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-confirm:hover {
            background: #FF9F43;
            transform: translateY(-2px);
        }

        .pickup-info {
            background: #E6FAF5;
            color: #05CD99;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .checkout-layout {
                grid-template-columns: 1fr;
            }
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
            <a href="profile_ristoratore.php" class="nav-item">
                <i class="fa-solid fa-user"></i> <span>Profilo</span>
            </a>
            <a href="mailto:help@clickneat.com" class="nav-item">
                <i class="fa-solid fa-circle-question"></i> <span>Aiuto</span>
            </a>
            <a href="../auth/logout.php" class="btn-logout-nav">
                <i class="fa-solid fa-right-from-bracket"></i> Esci
            </a>
        </div>
    </nav>

    <header class="hero-section">
        <div class="hero-content">
            <a href="menu.php?id=<?php echo $cart['restaurant_id']; ?>" class="btn-back-hero"><i
                    class="fa-solid fa-arrow-left"></i> Torna al Menu</a>
            <div class="hero-title">
                <h1>Checkout</h1>
                <p>Conferma il tuo ordine per il ritiro.</p>
            </div>
        </div>
    </header>

    <div class="main-container">
        <?php if ($msg): ?>
            <div class="msg-box error"><?php echo $msg; ?></div><?php endif; ?>

        <div class="card-style">
            <div class="checkout-layout">

                <div class="checkout-form">
                    <h3 class="section-title" style="margin-top:0;">Dettagli Ritiro</h3>

                    <div class="pickup-info">
                        <i class="fa-solid fa-bag-shopping"></i> Modalità: Ritiro al Ristorante (Takeout)
                    </div>

                    <form method="POST" action="checkout.php">
                        <div class="input-group"><label>Metodo di Pagamento</label>
                            <select
                                style="width: 100%; padding: 12px; border: 1px solid #E0E5F2; border-radius: 12px; background: #F9FAFB;">
                                <option>Paga al ritiro (Contanti/Carta)</option>
                                <option>Carta di Credito (Online)</option>
                            </select>
                        </div>
                </div>

                <div class="checkout-summary">
                    <h3 class="section-title" style="margin-top:0; font-size:18px;">Riepilogo</h3>
                    <?php foreach ($cart['items'] as $item): ?>
                        <div class="summary-row">
                            <span><b style="color:#1A4D4E;"><?php echo $item['qty']; ?>x</b>
                                <?php echo htmlspecialchars($item['name']); ?></span>
                            <span>€ <?php echo number_format($item['price'] * $item['qty'], 2); ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="summary-total"><span>Totale da Pagare</span><span>€
                            <?php echo number_format($cart['total'], 2); ?></span></div>
                    <button type="submit" name="place_order" class="btn-confirm">Conferma e Ritira <i
                            class="fa-solid fa-check"></i></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
<?php mysqli_close($link); ?>