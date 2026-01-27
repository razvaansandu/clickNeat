<?php
require '../config/db.php'; // importa $link

$sql = "SELECT 
            orders.id,
            orders.user_id,
            orders.restaurant_id,
            orders.total_amount,
            orders.status,
            orders.created_at,
            ristoranti.nome AS nome_ristorante
        FROM orders
        INNER JOIN ristoranti 
            ON orders.restaurant_id = ristoranti.id
        ORDER BY orders.id DESC";

$result = mysqli_query($link, $sql);

$orders = [];

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $orders[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Lista Ordini - ClickNeat</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
        th { background: #f4f4f4; }
    </style>
</head>
<body>

<h1>Lista Completa degli Ordini</h1>

<?php if (!empty($orders)): ?>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>ID Utente</th>
            <th>Ristorante</th>
            <th>Totale (€)</th>
            <th>Status</th>
            <th>Creato il</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($orders as $order): ?>
        <tr>
            <td><?php echo htmlspecialchars($order['id']); ?></td>
            <td><?php echo htmlspecialchars($order['user_id']); ?></td>
            <td><?php echo htmlspecialchars($order['nome_ristorante']); ?></td>
            <td><?php echo htmlspecialchars($order['total_amount']); ?></td>
            <td><?php echo htmlspecialchars($order['status']); ?></td>
            <td><?php echo htmlspecialchars($order['created_at']); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
    <p>Nessun ordine trovato.</p>
<?php endif; ?>

<button><a href="dashboard_ristoratore.php">Torna al Dashboard</a></button>

</body>
</html>
