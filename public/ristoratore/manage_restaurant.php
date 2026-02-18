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

// --- GESTIONE AGGIORNAMENTO ORDINE ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_order'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    if ($orderModel->updateStatus($order_id, $new_status)) {
        $msg = "Ordine aggiornato con successo!";
        $msg_type = "success";
    } else {
        $msg = "Errore durante l'aggiornamento dell'ordine.";
        $msg_type = "error";
    }
}

// --- GESTIONE AGGIUNTA PIATTO ---
if (isset($_POST['add_dish'])) {
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    $price = $_POST['price'];
    $cat_select = $_POST['categoria_select'] ?? 'altro';
    $cat_custom = trim($_POST['categoria_custom'] ?? '');
    $categoria = !empty($cat_custom) ? $cat_custom : $cat_select;
    
    // Inizializziamo image_url a null
    $image_url = null;

    // Controllo parole vietate
    $badWords = getBadWords();
    $isForbidden = false;
    foreach ($badWords as $word) {
        if (preg_match("/\b" . preg_quote($word, '/') . "\b/i", $categoria)) {
            $isForbidden = true;
            break;
        }
    }

    if ($isForbidden) {
        $msg = "Linguaggio inappropriato rilevato nella categoria.";
        $msg_type = "error";
    } elseif (!empty($name) && !empty($price)) {
        
        if (isset($_FILES['dish_image']) && $_FILES['dish_image']['error'] === 0) {
            $upload_dir = '../assets/'; 
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_name = $_FILES['dish_image']['name'];
            $file_tmp = $_FILES['dish_image']['tmp_name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($file_ext, $allowed)) {
                $new_file_name = uniqid('dish_') . '.' . $file_ext;
                $dest_path = $upload_dir . $new_file_name;

                if (move_uploaded_file($file_tmp, $dest_path)) {
                    $image_url = "assets/" . $new_file_name;
                } else {
                    $msg = "Errore caricamento immagine.";
                    $msg_type = "error";
                }
            } else {
                $msg = "Formato immagine non valido (usa JPG, PNG, WEBP).";
                $msg_type = "error";
            }
        }

        if (empty($msg_type) || $msg_type !== 'error') {
            if ($menuModel->create($restaurant_id, $name, $desc, $price, $categoria, $image_url)) {
                $msg = "Piatto aggiunto con successo!";
                $msg_type = "success";
            } else {
                $msg = "Errore nell'inserimento nel database.";
                $msg_type = "error";
            }
        }
    }
}

$menu_items = $menuModel->getByRestaurant($restaurant_id);
$orders = $orderModel->getByRestaurantId($restaurant_id);

function getBadWords() {
    $jsonPath = __DIR__ . "/../../config/cursed_words.json";
    if (file_exists($jsonPath)) {
        $jsonData = file_get_contents($jsonPath);
        $data = json_decode($jsonData, true);
        return is_array($data) ? ($data['words'] ?? $data) : [];
    }
    return [];
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione - <?php echo htmlspecialchars($restaurant['nome']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        <div class="page-header" style="display: flex; justify-content: flex-start; align-items: center; margin-top: -20px; padding-top: 0;">
            <a href="modifica_ristorante.php?id=<?php echo $restaurant_id; ?>" 
               style="background: #2B3674; color: white; padding: 12px 24px; border-radius: 30px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 10px rgba(67, 24, 255, 0.25); transition: all 0.2s ease;">
                <i class="fa-solid fa-pen-to-square"></i> Modifica Ristorante
            </a>
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
                    
                    <form method="POST" id="form-piatto" enctype="multipart/form-data">
                        <input type="hidden" name="add_dish" value="1">
                        
                        <label for="upload-img" class="card card-add" style="cursor: pointer;">
                            <div class="icon-plus">+</div>
                            <div class="text-add" id="file-name">Aggiungi immagine piatto</div>
                        </label>
                        <input type="file" name="dish_image" id="upload-img" style="display: none;" accept="image/*">
                        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px; margin-bottom: 15px; margin-top: 15px;">
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

                        <label style="display:block; margin-bottom:8px; font-weight:600; color: #2B3674;">Categoria Piatto</label>
                        <select name="categoria_select" id="piatto_select" style="width:100%; padding:12px; border-radius:8px; border:1px solid #d1d9e2; margin-bottom:12px;">
                            <option value="pizza">Pizza</option>
                            <option value="pasta">Pasta</option>
                            <option value="panino">Panino</option>
                            <option value="orientale">Orientale</option>
                            <option value="altro" selected>Altro</option>
                        </select>

                        <div style="text-align: center; margin-bottom: 12px; color: #a3aed0; font-size: 12px; font-weight: bold;">— OPPURE CREANE UNA NUOVA —</div>

                        <div class="input-wrapper">
                            <i class="fa-solid fa-plus-circle"></i>
                            <input type="text" name="categoria_custom" id="piatto_custom" placeholder="Scrivi una nuova categoria...">
                        </div>
                        <small id="status-help" style="color: #4318FF; font-size: 11px; margin-top: 5px; display: block; height: 15px;"></small>

                        <button type="submit" class="btn-add" style="display: flex; justify-content: space-between; background: #F4F7FE; color: var(--primary-brand); padding: 6px 7px; border-radius: 12px; font-weight: 500; font-size: 13px; border: 1px solid var(--primary-brand);">
                            Salva Piatto
                        </button>
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
                                <div class="menu-item" style="display: flex; gap: 15px; align-items: center;">
                                    
                                    <div class="dish-img" style="width: 60px; height: 60px; border-radius: 10px; overflow: hidden; background: #f4f7fe; flex-shrink: 0;">
                                        <?php if (!empty($item['image_url'])): ?>
                                            <img src="/<?php echo htmlspecialchars($item['image_url']); ?>" alt="Foto piatto" style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php else: ?>
                                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #A3AED0;">
                                                <i class="fa-solid fa-utensils"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="dish-info" style="flex: 1;">
                                        <h4 style="margin: 0; font-size: 16px; color: #2B3674;"><?php echo htmlspecialchars($item['name']); ?></h4>
                                        <p style="margin: 5px 0 0; font-size: 13px; color: #A3AED0; line-height: 1.4;">
                                            <?php echo htmlspecialchars($item['description']); ?>
                                        </p>
                                    </div>
                                    <div class="dish-actions">
                                        <span class="dish-price">€ <?php echo number_format($item['price'], 2); ?></span>
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
                            <i class="fa-solid fa-bell-slash" style="font-size:24px; color:#A3AED0; display:block; margin-bottom:10px;"></i>
                            <p style="color:#A3AED0;">Nessun ordine ricevuto.</p>
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

                                    <div class="order-actions" style="display: flex; gap: 10px; margin-top: 15px;">
                                        <?php if ($status == 'pending'): ?>
                                            <form method="POST" style="width: 100%;">
                                                <input type="hidden" name="update_order" value="1">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <input type="hidden" name="status" value="accepted">
                                                <button type="submit" class="btn-action btn-accept" style="width: 100%;">Accetta</button>
                                            </form>
                                        <?php elseif ($status == 'accepted'): ?>
                                            <form method="POST" style="width: 100%;">
                                                <input type="hidden" name="update_order" value="1">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <input type="hidden" name="status" value="completed">
                                                <button type="submit" class="btn-action btn-complete" style="width: 100%;">Concludi Ordine</button>
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

<script>
    const inputCustom = document.getElementById('piatto_custom');
    const selectDefault = document.getElementById('piatto_select');
    const statusHelp = document.getElementById('status-help');
    const btnSave = document.querySelector('.btn-add');
    const badWords = <?php echo json_encode(getBadWords()); ?>;

    document.getElementById('upload-img').addEventListener('change', function(e) {
        if (e.target.files.length > 0) {
            document.getElementById('file-name').textContent = "Selezionato: " + e.target.files[0].name;
            document.querySelector('.icon-plus').textContent = '✓';
            document.querySelector('.icon-plus').style.backgroundColor = '#05CD99';
            document.querySelector('.icon-plus').style.color = 'white';
        }
    });

    inputCustom.addEventListener('input', function() {
        const val = this.value.trim().toLowerCase();
        const found = badWords.some(word => {
            const regex = new RegExp("\\b" + word + "\\b", "i");
            return regex.test(val);
        });

        if (found) {
            this.style.borderColor = "#ea4335";
            statusHelp.innerHTML = "<i class='fa-solid fa-ban'></i> Termine non consentito";
            statusHelp.style.color = "#ea4335";
            btnSave.disabled = true;
            btnSave.style.opacity = "0.5";
            return; 
        }

        if (val.length > 0) {
            selectDefault.disabled = true;
            selectDefault.style.opacity = "0.5";
            statusHelp.innerHTML = "<i class='fa-solid fa-keyboard'></i> Categoria personalizzata attiva";
            statusHelp.style.color = "#4318FF";
        } else {
            selectDefault.disabled = false;
            selectDefault.style.opacity = "1";
            statusHelp.innerHTML = "";
        }
        btnSave.disabled = false;
        btnSave.style.opacity = "1";
        this.style.borderColor = "#d1d9e2";
    });

    document.getElementById('form-piatto').addEventListener('submit', function() {
        selectDefault.disabled = false;
    });
</script>
</body>
</html>