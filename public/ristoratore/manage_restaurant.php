<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once "../../config/db.php";
require_once "../../models/RistoranteRistoratoreModel.php";
require_once "../../models/MenuRistoratoreModel.php";
require_once "../../models/OrderRistoratoreModel.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["ruolo"] !== 'ristoratore') {
    header("location: ../auth/login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("location: dashboard_ristoratore.php");
    exit;
}

$restaurant_id = $_GET['id'];
$user_id = $_SESSION['id'];

$ristoranteModel = new RistoranteRistoratoreModel($db);
$menuModel = new MenuRistoratoreModel($db);
$orderModel = new OrderRistoratoreModel($db);

$restaurant = $ristoranteModel->getByIdAndOwner($restaurant_id, $user_id);

if (!$restaurant) {
    header("location: dashboard_ristoratore.php");
    exit;
}

$msg = "";
$msg_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_dish'])) {
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    $price = $_POST['price'];
    $categoria = $_POST['categoria'];

    if (!empty($name) && !empty($price)) {
        $sql = "INSERT INTO menu_items (restaurant_id, name, description, price, categoria) VALUES (?, ?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "issss", $restaurant_id, $name, $desc, $price, $categoria);
            mysqli_stmt_execute($stmt);
            $msg = "Piatto aggiunto al menu!";
            $msg_type = "success";
            mysqli_stmt_close($stmt);
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_dish'])) {
    $dish_id = $_POST['dish_id'];
    $sql = "DELETE FROM menu_items WHERE id = ? AND restaurant_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $dish_id, $restaurant_id);
        mysqli_stmt_execute($stmt);
        $msg = "Piatto eliminato.";
        $msg_type = "success";
        mysqli_stmt_close($stmt);
    }
}

    if (isset($_POST['update_order'])) {
        $order_id = $_POST['order_id'];
        $new_status = $_POST['status'];
        if ($orderModel->updateStatus($order_id, $new_status)) {
            header("Refresh:0");
        }
    }
}

$menu_items = $menuModel->getByRestaurant($restaurant_id);
$orders = $orderModel->getByRestaurantId($restaurant_id);
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <title>Gestione - <?php echo htmlspecialchars($restaurant['nome']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=dashboard_2" />
    <link rel="stylesheet" href="../../css/style_ristoratori.css">
</head>

<body>

    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <div>
                <a href="dashboard_ristoratore.php" class="btn-cancel" style="padding-left:0; margin-bottom:10px; display:inline-block;">
                    <i class="fa-solid fa-arrow-left"></i> Torna alla Dashboard
                </a>
                <h1><?php echo htmlspecialchars($restaurant['nome']); ?></h1>
                <p><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($restaurant['indirizzo']); ?></p>
            </div>
        </div>

        <?php if ($msg): ?>
            <div class="msg-box <?php echo $msg_type; ?>">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <div class="management-grid">

            <div class="col-menu">

                <div class="card" style="margin-bottom: 30px;">
                    <h3 style="color: #2B3674; margin-bottom: 20px;">Aggiungi Piatto</h3>
                    <form method="POST">
                        <input type="hidden" name="add_dish" value="1">

                        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px; margin-bottom: 15px;">
                            <div class="input-wrapper">
                                <i class="fa-solid fa-utensils"></i>
                                <input type="text" name="name" placeholder="Nome Piatto" required>
                            </div>
                            <div class="input-wrapper">
                                <i class="fa-solid fa-euro-sign"></i>
                                <input type="number" step="0.50" name="price" placeholder="Prezzo" required>
                            </div>
                        </div>

                        <div class="input-wrapper textarea-wrapper" style="margin-bottom: 15px;">
                            <i class="fa-solid fa-align-left" style="top: 15px;"></i>
                            <textarea name="description" placeholder="Descrizione e ingredienti..." rows="2" style="min-height: 80px;"></textarea>
                        </div>
                        <textarea name="description" placeholder="Ingredienti..." rows="2" style="margin-bottom:10px;"></textarea>
                        <div>
                            <label>Categoria del piatto:</label>
                            <select name="categoria" required>
                                <option value="pizza">Pizza</option>
                                <option value="pasta">Pasta</option>
                                <option value="panino">panino</option>
                                <option value="orientale">Orientale</option>
                                <option value="altro">Altro</option>
                            </select>
                            <div class="form-group">
    <label for="categoria"></label>
    <input type="text" id="categoria" name="categoria" placeholder="Scrivi la tua categoria..." required>
</div>
                        </div>
                        <button type="submit" class="btn-add">Salva Piatto</button>
                    </form>
                </div>

                <div class="card">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
                        <h3 style="color: #2B3674; margin:0;">Il tuo Menu</h3>
                        <span style="background:#E6FAF5; color:#05CD99; padding:5px 12px; border-radius:15px; font-weight:600; font-size:12px;">
                            <?php echo count($menu_items); ?> Piatti
                        </span>
                    </div>

                    <?php if (empty($menu_items)): ?>
                        <p style="text-align:center; color:#A3AED0; padding: 20px;">Ancora nessun piatto.</p>
                    <?php else: ?>
                        <div class="menu-list">
                            <?php foreach ($menu_items as $item): ?>
                                <div class="menu-item">
                                    <div class="dish-info">
                                        <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                        <p><?php echo htmlspecialchars($item['description']); ?></p>
                                    </div>
                                    <div class="dish-actions">
                                        <span class="dish-price">€ <?php echo number_format($item['price'], 2); ?></span>
                                        <form method="POST" onsubmit="return confirm('Eliminare questo piatto?');">
                                            <input type="hidden" name="delete_dish" value="1">
                                            <input type="hidden" name="dish_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" class="btn-icon-delete"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-orders">
                <div class="card" style="min-height: 600px;">
                    <h3 style="color: #2B3674; margin-bottom: 20px;">Ordini Recenti</h3>

                    <?php if (empty($orders)): ?>
                        <div style="text-align:center; padding:50px 20px;">
                            <div style="width:60px; height:60px; background:#F4F7FE; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px auto;">
                                <i class="fa-solid fa-bell-slash" style="font-size:24px; color:#A3AED0;"></i>
                            </div>
                            <p style="color:#A3AED0;">Nessun ordine ricevuto per questo locale.</p>
                        </div>
                    <?php else: ?>
                        <div class="orders-list">
                            <?php foreach ($orders as $order): ?>
                                <div class="order-card">
                                    <div class="order-header">
                                        <div>
                                            <span class="order-user"><?php echo htmlspecialchars($order['cliente_nome'] ?? 'Cliente'); ?></span>
                                            <div class="order-time"><?php echo date("d M, H:i", strtotime($order['created_at'])); ?></div>
                                        </div>
                                        <?php
                                        $status = $order['status'];
                                        $label = match ($status) {
                                            'pending' => 'In Attesa',
                                            'accepted' => 'In Preparazione',
                                            'completed' => 'Completato',
                                            'cancelled' => 'Rifiutato',
                                            default => $status
                                        };
                                        ?>
                                        <span class="status-badge status-<?php echo $status; ?>"><?php echo $label; ?></span>
                                    </div>

                                    <div class="order-total">
                                        Totale: <b>€ <?php echo number_format($order['total_amount'], 2); ?></b>
                                    </div>

                                    <div class="order-actions">
                                        <?php if ($status == 'pending'): ?>
                                            <form method="POST" style="flex:1;">
                                                <input type="hidden" name="update_order" value="1">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <input type="hidden" name="status" value="accepted">
                                                <button type="submit" class="btn-action btn-accept">Accetta</button>
                                            </form>
                                            <form method="POST" style="flex:1;">
                                                <input type="hidden" name="update_order" value="1">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <input type="hidden" name="status" value="cancelled">
                                                <button type="submit" class="btn-action btn-reject">Rifiuta</button>
                                            </form>
                                        <?php elseif ($status == 'accepted'): ?>
                                            <form method="POST" style="width:100%;">
                                                <input type="hidden" name="update_order" value="1">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <input type="hidden" name="status" value="completed">
                                                <button type="submit" class="btn-action btn-complete">Concludi Ordine</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

</body>

</html>