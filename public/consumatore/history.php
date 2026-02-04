<?php
session_start();
require_once "../../config/db.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['id'];
$orders_result = false;

try {
    $sql = "SELECT o.id, o.data_ordine, o.totale, o.stato, r.nome as nome_ristorante 
            FROM ordini o 
            JOIN ristoranti r ON o.ristorante_id = r.id 
            WHERE o.user_id = ? 
            ORDER BY o.data_ordine DESC";

    $stmt = mysqli_prepare($link, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $orders_result = mysqli_stmt_get_result($stmt);
    }
} catch (Exception $e) { $orders_result = false; }
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>I miei Ordini - ClickNeat</title>
    <link rel="stylesheet" href="../css/style_consumatori.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            <a href="history.php" class="nav-item active">
                <i class="fa-solid fa-clock-rotate-left"></i> <span>Ordini</span>
            </a>
            <a href="profile_consumatore.php" class="nav-item">
                <i class="fa-solid fa-user"></i> <span>Profilo</span>
            </a>
            <a href="mailto:help@clickneat.com" class="nav-item">
    <i class="fa-solid fa-circle-question"></i> <span>Aiuto</span>
</a>
            <a href="logout.php" class="btn-logout-nav">
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
        <div class="grid-container">
            <?php if ($orders_result && mysqli_num_rows($orders_result) > 0): ?>
                <?php while($ordine = mysqli_fetch_assoc($orders_result)): 
                    $stato = $ordine['stato'];
                    $badgeClass = 'pending';
                    if (stripos($stato, 'consegnato') !== false) $badgeClass = 'completed';
                    if (stripos($stato, 'annullato') !== false) $badgeClass = 'cancelled';
                ?>
                <div class="card-style order-card">
                    <div>
                        <div class="order-header">
                            <span class="order-id">#<?php echo $ordine['id']; ?></span>
                            <span class="status-badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($stato); ?></span>
                        </div>
                        <div class="order-rest-name"><?php echo htmlspecialchars($ordine['nome_ristorante']); ?></div>
                        <div class="order-date">
                            <i class="fa-regular fa-calendar"></i> <?php echo date("d/m/Y H:i", strtotime($ordine['data_ordine'])); ?>
                        </div>
                    </div>

                    <div class="order-footer">
                        <div style="font-weight: 700; color: #1A4D4E; font-size: 18px;">
                            € <?php echo number_format($ordine['totale'], 2); ?>
                        </div>
                        <a href="#" style="color: #A3AED0;"><i class="fa-solid fa-chevron-right"></i></a>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 50px; background: white; border-radius: 20px;">
                    <i class="fa-solid fa-box-open" style="font-size: 40px; color: #ddd; margin-bottom: 15px;"></i>
                    <h3 style="color: #2B3674;">Nessun ordine</h3>
                    <p style="color: #A3AED0;">Non hai ancora effettuato ordini.</p>
                    <a href="dashboard_consumatore.php" style="margin-top:20px; display:inline-block; color: #FF9F43; font-weight:600;">Inizia ora &rarr;</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
<?php 
if (isset($stmt)) mysqli_stmt_close($stmt);
mysqli_close($link); 
?>