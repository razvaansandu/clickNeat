<?php
if (session_status() !== PHP_SESSION_ACTIVE)
    session_start();

require_once "../../config/db.php";
require_once "../../models/Order.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['id'];
$orders = [];
$error_message = "";

try {
    $orderModel = new Order($db);

    $orders = $orderModel->getByUserId($user_id);

} catch (Exception $e) {
    $error_message = "Errore nel recupero ordini: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <title>I miei Ordini - ClickNeat</title>
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
            <a href="storico.php" class="nav-item active">
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
            <div class="hero-title">
                <h1>I miei Ordini</h1>
                <p>Controlla lo stato dei tuoi ordini recenti.</p>
            </div>
        </div>
    </header>

    <div class="main-container">

        <?php if (!empty($error_message)): ?>
            <div
                style="background: #ffebee; color: #c62828; padding: 20px; border-radius: 10px; margin-bottom: 20px; border: 1px solid #ffcdd2;">
                <strong><i class="fa-solid fa-triangle-exclamation"></i> Ops! C'è un problema:</strong><br>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="grid-container">
            <?php if (!empty($orders)): ?>
                <?php foreach ($orders as $ordine):

                    $stato_db = strtolower($ordine['status']);

                    $badgeClass = 'pending';
                    $icona = '<i class="fa-regular fa-clock"></i>';
                    $testo_stato = 'In Attesa';

                    if ($stato_db == 'accepted') {
                        $badgeClass = 'accepted';
                        $icona = '<i class="fa-solid fa-fire-burner"></i>';
                        $testo_stato = 'In Preparazione';
                    } elseif ($stato_db == 'completed') {
                        $badgeClass = 'completed';
                        $icona = '<i class="fa-solid fa-check-double"></i>';
                        $testo_stato = 'Consegnato';
                    } elseif ($stato_db == 'cancelled' || $stato_db == 'rejected') {
                        $badgeClass = 'cancelled';
                        $icona = '<i class="fa-solid fa-xmark"></i>';
                        $testo_stato = 'Annullato';
                    }
                    ?>
                    <div class="card-style order-card">
                        <div>
                            <div class="order-header">
                                <span class="order-id">Ordine #<?php echo $ordine['id']; ?></span>

                                <span class="status-badge <?php echo $badgeClass; ?>">
                                    <?php echo $icona; ?>         <?php echo htmlspecialchars($testo_stato); ?>
                                </span>
                            </div>

                            <div class="order-rest-name"><?php echo htmlspecialchars($ordine['nome_ristorante']); ?></div>

                            <div class="order-date">
                                <i class="fa-regular fa-calendar"></i>
                                <?php echo date("d/m/Y - H:i", strtotime($ordine['created_at'])); ?>
                            </div>
                        </div>

                        <div class="order-footer">
                            <div style="font-weight: 700; color: #1A4D4E; font-size: 18px;">
                                € <?php echo number_format($ordine['total_amount'], 2); ?>
                            </div>
                            <div style="font-size: 13px; color: #A3AED0; cursor: default;">
                                <?php if ($stato_db == 'accepted'): ?>
                                    <span style="color: #006064; font-weight:600;">In cucina...</span>
                                <?php elseif ($stato_db == 'completed'): ?>
                                    <span style="color: #05CD99; font-weight:600;">Buon appetito!</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>

                <?php if (empty($error_message)): ?>
                    <div
                        style="grid-column: 1/-1; text-align: center; padding: 50px; background: white; border-radius: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                        <i class="fa-solid fa-burger" style="font-size: 50px; color: #E0E5F2; margin-bottom: 20px;"></i>
                        <h3 style="color: #2B3674; margin-bottom: 10px;">Nessun ordine trovato</h3>
                        <p style="color: #A3AED0; margin-bottom: 25px;">Non hai ancora effettuato ordini su ClickNeat.</p>
                        <a href="dashboard_consumatore.php" class="btn-save"
                            style="text-decoration:none; display:inline-block;">
                            Ordina qualcosa ora
                        </a>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>

</body>

</html>