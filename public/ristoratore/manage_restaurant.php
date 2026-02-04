<?php
session_start();
require_once "../../config/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["ruolo"] !== 'ristoratore') {
    header("location: login_ristoratore.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("location: dashboard_ristoratore.php");
    exit;
}

$restaurant_id = $_GET['id'];
$user_id = $_SESSION['id'];

$sql = "SELECT * FROM ristoranti WHERE id = ? AND proprietario_id = ?";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "ii", $restaurant_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $restaurant = $result->fetch_assoc();
    
    if (!$restaurant) {
        header("location: dashboard_ristoratore.php");
        exit;
    }
    mysqli_stmt_close($stmt);
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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_order'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];  
    
    $sql = "UPDATE orders SET status = ? WHERE id = ? AND restaurant_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "sii", $new_status, $order_id, $restaurant_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

$menu_items = [];
$sql_menu = "SELECT * FROM menu_items WHERE restaurant_id = ? ORDER BY created_at DESC";
if ($stmt = mysqli_prepare($link, $sql_menu)) {
    mysqli_stmt_bind_param($stmt, "i", $restaurant_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = $res->fetch_assoc()) $menu_items[] = $row;
    mysqli_stmt_close($stmt);
}

$orders = [];
$sql_orders = "SELECT o.*, u.username 
               FROM orders o 
               JOIN users u ON o.user_id = u.id 
               WHERE o.restaurant_id = ? 
               ORDER BY CASE WHEN o.status = 'pending' THEN 1 WHEN o.status = 'accepted' THEN 2 ELSE 3 END, o.created_at DESC";
if ($stmt = mysqli_prepare($link, $sql_orders)) {
    mysqli_stmt_bind_param($stmt, "i", $restaurant_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = $res->fetch_assoc()) $orders[] = $row;
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestisci <?php echo htmlspecialchars($restaurant['nome']); ?> - ClickNeat</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: #F4F7FE; min-height: 100vh; }
        .main-content { margin-left: 260px; padding: 40px; }

        .top-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .top-header h1 { color: #2B3674; font-size: 26px; font-weight: 700; }
        .top-header span { color: #A3AED0; font-size: 14px; }
        .btn-back { color: #1A4D4E; text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 8px; }

        .management-grid {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 30px;
        }

        .card { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 18px 40px rgba(112, 144, 176, 0.12); height: fit-content; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #F4F7FE; padding-bottom: 15px; }
        .card-title { font-size: 18px; font-weight: 700; color: #2B3674; }

        .add-dish-form { background: #F4F7FE; padding: 20px; border-radius: 15px; margin-bottom: 20px; }
        .form-row { display: flex; gap: 10px; margin-bottom: 10px; }
        input, textarea { width: 100%; padding: 10px; border: 1px solid #E0E5F2; border-radius: 10px; outline: none; }
        .btn-add { background: #1A4D4E; color: white; border: none; padding: 10px 15px; border-radius: 10px; cursor: pointer; font-weight: 600; width: 100%; }
        .btn-add:hover { background: #E89020; }

        .menu-list { max-height: 600px; overflow-y: auto; }
        .menu-item { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid #eee; }
        .menu-item:last-child { border-bottom: none; }
        .dish-info h4 { color: #1B2559; font-weight: 600; }
        .dish-info p { color: #A3AED0; font-size: 13px; }
        .dish-price { font-weight: bold; color: #1A4D4E; margin-right: 15px; }
        .btn-delete { color: #E53E3E; background: none; border: none; cursor: pointer; font-size: 16px; }

        .order-card { background: #fff; border: 1px solid #E0E5F2; border-radius: 15px; padding: 20px; margin-bottom: 15px; }
        .order-header { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .order-user { font-weight: 700; color: #1B2559; }
        .order-time { font-size: 12px; color: #A3AED0; }
        
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-pending { background: #FFF7E6; color: #D97706; } /* Giallo */
        .status-accepted { background: #E6FFFA; color: #1A4D4E; } /* Verde acqua */
        .status-completed { background: #F0FFF4; color: #2F855A; } /* Verde scuro */
        .status-cancelled { background: #FFF5F5; color: #C53030; } /* Rosso */

        .order-total { font-size: 18px; font-weight: 700; color: #1A4D4E; margin: 10px 0; }

        .order-actions { display: flex; gap: 10px; margin-top: 15px; }
        .btn-action { flex: 1; padding: 8px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: 0.2s; }
        
        .btn-accept { background: #E6FFFA; color: #1A4D4E; border: 1px solid #1A4D4E; }
        .btn-accept:hover { background: #1A4D4E; color: white; }
        
        .btn-complete { background: #2F855A; color: white; }
        .btn-complete:hover { background: #276749; }

        .btn-cancel { background: white; color: #C53030; border: 1px solid #C53030; }
        .btn-cancel:hover { background: #C53030; color: white; }

        @media (max-width: 1000px) {
            .management-grid { grid-template-columns: 1fr; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>

    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="top-header">
            <div>
                <a href="dashboard_ristoratore.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Torna alla Dashboard</a>
                <h1 style="margin-top:10px;"><?php echo htmlspecialchars($restaurant['nome']); ?></h1>
                <span><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($restaurant['indirizzo']); ?></span>
            </div>
            <?php if($msg): ?>
                <div style="background:white; padding:10px 20px; border-radius:10px; color:green; font-weight:bold;">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="management-grid">

            <div class="card">
                <div class="card-header">
                    <span class="card-title">Menu</span>
                    <span style="color:#A3AED0;"><?php echo count($menu_items); ?> Piatti</span>
                </div>

                <div class="add-dish-form">
                    <h5 style="margin-bottom:10px; color:#1A4D4E;">+ Aggiungi Piatto</h5>
                    <form method="POST">
                        <input type="hidden" name="add_dish" value="1">
                        <div class="form-row">
                            <input type="text" name="name" placeholder="Nome Piatto" required>
                            <input type="number" step="0.50" name="price" placeholder="€" style="width: 80px;" required>
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

                <div class="menu-list">
                    <?php if(empty($menu_items)): ?>
                        <p style="text-align:center; color:#ccc; margin-top:20px;">Il menu è vuoto.</p>
                    <?php else: ?>
                        <?php foreach($menu_items as $item): ?>
                            <div class="menu-item">
                                <div class="dish-info">
                                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <p><?php echo htmlspecialchars($item['description']); ?></p>
                                </div>
                                <div style="display:flex; align-items:center;">
                                    <span class="dish-price">€ <?php echo number_format($item['price'], 2); ?></span>
                                    <form method="POST" onsubmit="return confirm('Eliminare questo piatto?');">
                                        <input type="hidden" name="delete_dish" value="1">
                                        <input type="hidden" name="dish_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="btn-delete"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card" style="background: transparent; box-shadow: none; padding: 0;">
                <h3 style="margin-bottom:20px; color:#2B3674;">Ordini in Arrivo</h3>

                <?php if(empty($orders)): ?>
                    <div style="text-align:center; padding:50px; background:white; border-radius:20px;">
                        <i class="fa-solid fa-bell-slash" style="font-size:40px; color:#E0E5F2; margin-bottom:20px;"></i>
                        <p style="color:#A3AED0;">Nessun ordine ricevuto.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($orders as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div>
                                    <div class="order-user"><?php echo htmlspecialchars($order['username']); ?></div>
                                    <div class="order-time"><?php echo date("d/m H:i", strtotime($order['created_at'])); ?></div>
                                </div>
                                <div>
                                    <?php 
                                    $status = $order['status'];
                                    $label = match($status) {
                                        'pending' => 'In Attesa',
                                        'accepted' => 'In Preparazione',
                                        'completed' => 'Consegnato',
                                        'cancelled' => 'Annullato',
                                        default => $status
                                    };
                                    ?>
                                    <span class="status-badge status-<?php echo $status; ?>"><?php echo $label; ?></span>
                                </div>
                            </div>

                            <div class="order-total">Totale: € <?php echo number_format($order['total_amount'], 2); ?></div>

                            <div class="order-actions">
                                
                                <?php if($status == 'pending'): ?>
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
                                        <button type="submit" class="btn-action btn-cancel">Rifiuta</button>
                                    </form>
                                <?php endif; ?>

                                <?php if($status == 'accepted'): ?>
                                    <form method="POST" style="width:100%;">
                                        <input type="hidden" name="update_order" value="1">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <input type="hidden" name="status" value="completed">
                                        <button type="submit" class="btn-action btn-complete">Segna come Completato</button>
                                    </form>
                                <?php endif; ?>

                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>