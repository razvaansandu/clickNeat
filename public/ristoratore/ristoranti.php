<?php
if(session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once "../../config/db.php";
require_once "../../models/OrderRistoratoreModel.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["ruolo"] !== 'ristoratore') {
    header("location: ../auth/login.php");
    exit;
}

$owner_id = $_SESSION['id'];
$orderModel = new OrderRistoratoreModel($db);

$orders = $orderModel->getByOwnerId($owner_id);

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Ristoranti disponibili</title>
    <link rel="stylesheet" href="../css/style_ristoratori.css?v=1.0">
</head>
<body>
    <div class="container">
        <h2>Ristoranti disponibili</h2>
        <?php if(count($ristoranti) === 0): ?>
            <p>Nessun ristorante disponibile.</p>
        <?php else: ?>
            <ul style="list-style:none; padding:0;">
            <?php foreach($ristoranti as $r): ?>
                <li style="margin-bottom:30px; background:#f7f7fa; border-radius:8px; box-shadow:0 2px 8px rgba(102,126,234,0.08); padding:20px;">
                    <h3 style="color:#667eea; margin-bottom:8px;"><?php echo htmlspecialchars($r['nome']); ?></h3>
                    <p style="color:#555; margin-bottom:4px;"><strong>Indirizzo:</strong> <?php echo htmlspecialchars($r['indirizzo']); ?></p>
                    <p style="color:#666;"><strong>Descrizione:</strong> <?php echo htmlspecialchars($r['descrizione']); ?></p>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <a href="dashboard_consumatore.php" class="btn-logout">Torna alla dashboard</a>
    </div>
</body>
</html>
