<?php
session_start();
require_once "../config/db.php";

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

    if (!empty($name) && !empty($price)) {
        $sql = "INSERT INTO menu_items (restaurant_id, name, description, price) VALUES (?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "isss", $restaurant_id, $name, $desc, $price);
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

$menu_items = [];
$sql_menu = "SELECT * FROM menu_items WHERE restaurant_id = ? ORDER BY created_at DESC";
if ($stmt = mysqli_prepare($link, $sql_menu)) {
    mysqli_stmt_bind_param($stmt, "i", $restaurant_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = $res->fetch_assoc())
        $menu_items[] = $row;
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
        /* STILI BASE */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: #F4F7FE;
            min-height: 100vh;
        }

        .main-content {
            margin-left: 260px;
            padding: 40px;
        }

        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .top-header h1 {
            color: #2B3674;
            font-size: 26px;
            font-weight: 700;
        }

        .top-header span {
            color: #A3AED0;
            font-size: 14px;
        }

        .btn-back {
            color: #1A4D4E;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
        }

        .btn-back:hover {
            transform: translateX(-5px);
            color: #E89020;
        }

        .management-grid {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 30px;
        }

        .card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 18px 40px rgba(112, 144, 176, 0.12);
            height: fit-content;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #F4F7FE;
            padding-bottom: 15px;
        }

        .card-title {
            font-size: 18px;
            font-weight: 700;
            color: #2B3674;
        }

        .add-dish-form {
            background: #F4F7FE;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
        }

        .form-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        input,
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #E0E5F2;
            border-radius: 10px;
            outline: none;
            transition: 0.3s;
        }

        input:focus,
        textarea:focus {
            border-color: #1A4D4E;
            box-shadow: 0 0 0 3px rgba(26, 77, 78, 0.1);
        }

        .btn-add {
            background: #1A4D4E;
            color: white;
            border: none;
            padding: 12px 15px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 4px 6px rgba(26, 77, 78, 0.2);
        }

        .btn-add:hover {
            background: #E89020;
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 10px 20px rgba(232, 144, 32, 0.3);
        }

        .btn-add:active {
            transform: scale(0.95);
            box-shadow: 0 2px 4px rgba(232, 144, 32, 0.2);
        }

        .menu-list {
            max-height: 600px;
            overflow-y: auto;
        }

        .menu-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
            transition: 0.3s;
        }

        .menu-item:hover {
            background-color: #FAFCFE;
            padding-left: 10px;
        }

        .menu-item:last-child {
            border-bottom: none;
        }

        .dish-info h4 {
            color: #1B2559;
            font-weight: 600;
        }

        .dish-info p {
            color: #A3AED0;
            font-size: 13px;
        }

        .dish-price {
            font-weight: bold;
            color: #1A4D4E;
            margin-right: 15px;
        }

        .btn-delete {
            color: #E53E3E;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            transition: 0.3s;
        }

        .btn-delete:hover {
            transform: scale(1.2) rotate(10deg);
            color: #C53030;
        }

        .btn-action {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s cubic-bezier(0.25, 0.8, 0.25, 1);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 5px;
        }

        .btn-action:hover {
            transform: translateY(-3px);
        }

        .btn-action:active {
            transform: scale(0.95);
        }

        .btn-accept {
            background: #E6FFFA;
            color: #1A4D4E;
            border: 1px solid #1A4D4E;
        }

        .btn-accept:hover {
            background: #1A4D4E;
            color: white;
            box-shadow: 0 5px 15px rgba(26, 77, 78, 0.3);
        }

        .btn-complete {
            background: #2F855A;
            color: white;
            box-shadow: 0 4px 6px rgba(47, 133, 90, 0.2);
        }

        .btn-complete:hover {
            background: #276749;
            box-shadow: 0 8px 15px rgba(47, 133, 90, 0.4);
        }

        .btn-cancel {
            background: white;
            color: #C53030;
            border: 1px solid #C53030;
        }

        .btn-cancel:hover {
            background: #C53030;
            color: white;
            box-shadow: 0 5px 15px rgba(197, 48, 48, 0.3);
        }

        @media (max-width: 1000px) {
            .management-grid {
                grid-template-columns: 1fr;
            }

            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">

        <div class="top-header">
            <div>
                <a href="dashboard_ristoratore.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Torna alla
                    Dashboard</a>
                <h1 style="margin-top:10px;"><?php echo htmlspecialchars($restaurant['nome']); ?></h1>
                <span><i class="fa-solid fa-location-dot"></i>
                    <?php echo htmlspecialchars($restaurant['indirizzo']); ?></span>
            </div>
            <?php if ($msg): ?>
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
                        <textarea name="description" placeholder="Ingredienti..." rows="2"
                            style="margin-bottom:10px;"></textarea>
                        <button type="submit" class="btn-add">Salva Piatto</button>
                    </form>
                </div>

                <div class="menu-list">
                    <?php if (empty($menu_items)): ?>
                        <p style="text-align:center; color:#ccc; margin-top:20px;">Il menu è vuoto.</p>
                    <?php else: ?>
                        <?php foreach ($menu_items as $item): ?>
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
        </div>
    </div>

</body>
</html>