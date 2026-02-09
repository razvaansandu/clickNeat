<?php

if(session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once "../../config/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["ruolo"] !== 'ristoratore') {
    header("location: login.php");
    exit;
}

$owner_id = $_SESSION['id'];
$orders = [];

$sql = "SELECT o.id, o.total_amount, o.status, o.created_at, 
               u.username as cliente, r.nome as ristorante_nome
        FROM orders o
        JOIN ristoranti r ON o.restaurant_id = r.id
        JOIN users u ON o.user_id = u.id
        WHERE r.proprietario_id = ?
        ORDER BY o.created_at DESC";

if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $owner_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $order_id = $row['id'];
        
        $sql_items = "SELECT oi.quantity, oi.price_at_time, m.name 
                      FROM order_items oi 
                      JOIN menu_items m ON oi.dish_id = m.id 
                      WHERE oi.order_id = $order_id";
                      
        $res_items = mysqli_query($link, $sql_items);
        $items = [];
        if($res_items) {
            while($item = mysqli_fetch_assoc($res_items)){
                $items[] = $item;
            }
        }
        $row['items'] = $items;
        $orders[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Ordini Takeout - Ristoratore</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style_consumatori.css">
</head>
<body>

    <nav class="admin-nav">
        <a href="dashboard_ristoratore.php" class="logo"><i class="fa-solid fa-leaf"></i> ClickNeat Admin</a>
        <a href="dashboard_ristoratore.php" class="btn-back">Torna alla Dashboard</a>
    </nav>

    <div class="container">
        <h1 class="page-title">Ordini da Preparare (Takeout)</h1>

        <?php if (empty($orders)): ?>
            <div style="text-align: center; padding: 60px; color: #A3AED0;">
                <i class="fa-solid fa-clipboard-check" style="font-size: 50px; margin-bottom: 20px;"></i>
                <h3>Nessun ordine attivo</h3>
            </div>
        <?php else: ?>
            
            <?php foreach($orders as $order): ?>
            <div class="order-card">
                <div class="order-header">
                    <div>
                        <div class="order-id">Ordine #<?php echo $order['id']; ?></div>
                        <div class="order-meta"><i class="fa-solid fa-store"></i> <?php echo htmlspecialchars($order['ristorante_nome']); ?></div>
                    </div>
                    <div style="text-align: right;">
                        <div class="order-meta"><i class="fa-regular fa-clock"></i> <?php echo date("d/m/Y H:i", strtotime($order['created_at'])); ?></div>
                        <div style="margin-top:5px; color:#05CD99; font-weight:600; font-size:12px;">Ritiro in sede</div>
                    </div>
                </div>

                <div class="info-grid">
                    <div class="info-box">
                        <label>Cliente</label>
                        <p><?php echo htmlspecialchars($order['cliente']); ?></p>
                    </div>
                </div>

                <table class="items-table">
                    <?php if (!empty($order['items'])): ?>
                        <?php foreach($order['items'] as $item): ?>
                        <tr>
                            <td width="10%"><b><?php echo $item['quantity']; ?>x</b></td>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td width="20%" style="text-align: right;">€ <?php echo number_format($item['price_at_time'] * $item['quantity'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3" style="text-align:center; color:#999;">Dettagli non disponibili</td></tr>
                    <?php endif; ?>
                </table>

                <div class="footer">
                    <div class="total">Totale: € <?php echo number_format($order['total_amount'], 2); ?></div>
                    <span class="status"><?php echo htmlspecialchars($order['status']); ?></span>
                </div>
            </div>
            <?php endforeach; ?>

        <?php endif; ?>
    </div>

</body>
</html>
<?php mysqli_close($link); ?>