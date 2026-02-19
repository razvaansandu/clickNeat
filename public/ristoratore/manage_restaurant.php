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

if (isset($_POST['delete_dish'])) {
    $dish_id = $_POST['dish_id'];
    
    if ($menuModel->delete_piatto($dish_id)) {
        $msg = "Piatto eliminato con successo!";
        $msg_type = "success";
    } else {
        $msg = "Impossibile eliminare: il piatto fa parte di un ordine esistente.";
        $msg_type = "error";
    }
}

if (isset($_POST['edit_dish'])) {
    $dish_id = $_POST['dish_id'];
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    $price = $_POST['price'];
    $cat_select = $_POST['categoria_select'] ?? 'altro';
    $cat_custom = trim($_POST['categoria_custom'] ?? '');
    $categoria = !empty($cat_custom) ? $cat_custom : $cat_select;
    
    $image_url = $_POST['existing_image'] ?? null;

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
                $msg = "Formato immagine non valido.";
                $msg_type = "error";
            }
        }

        if (empty($msg_type) || $msg_type !== 'error') {
            $data = [
                'name' => $name,
                'description' => $desc,
                'price' => $price,
                'categoria' => $categoria,
                'image_url' => $image_url
            ];
            
            if ($menuModel->update_piatto($dish_id, $data)) {
                $msg = "Piatto modificato con successo!";
                $msg_type = "success";
            } else {
                $msg = "Errore durante la modifica del piatto.";
                $msg_type = "error";
            }
        }
    }
}

if (isset($_POST['add_dish'])) {
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    $price = $_POST['price'];
    $cat_select = $_POST['categoria_select'] ?? 'altro';
    $cat_custom = trim($_POST['categoria_custom'] ?? '');
    $categoria = !empty($cat_custom) ? $cat_custom : $cat_select;
    
    $image_url = null;

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
                $msg = "Formato immagine non valido.";
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione - <?php echo htmlspecialchars($restaurant['nome']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/style_ristoratori.css">
    <style>
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        @media screen and (max-width: 768px) {
            .management-grid {
                grid-template-columns: 1fr !important;
                gap: 20px !important;
            }
            [style*="grid-template-columns: 2fr 1fr"] {
                grid-template-columns: 1fr !important;
            }
            .menu-item {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 10px !important;
            }
            .dish-img {
                width: 100% !important;
                height: 150px !important;
            }
            .order-header {
                flex-direction: column !important;
                gap: 10px !important;
            }
            .order-actions {
                flex-direction: column !important;
            }
            .page-header {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 15px !important;
            }
            .msg-box {
                width: 100% !important;
            }
        }
    </style>
</head>
<body>

    <div class="mobile-header">  
        <button class="hamburger-btn">  
            <i class="fa-solid fa-bars"></i> 
        </button>  
    </div>

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
                <i class="fa-solid fa-pen-to-square"></i> Modifica Informazioni Ristorante
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
                                <div class="menu-item" style="display: flex; gap: 15px; align-items: center; position: relative;">
                                    
                                    <div class="dish-img" style="width: 60px; height: 60px; border-radius: 10px; overflow: hidden; background: #f4f7fe; flex-shrink: 0;">
                                        <?php if (!empty($item['image_url'])): ?>
                                            <img src="/<?php echo htmlspecialchars(ltrim($item['image_url'], '/')); ?>" alt="Foto piatto" style="width: 100%; height: 100%; object-fit: cover;">
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
                                        <span style="display: inline-block; margin-top: 5px; font-size: 11px; background: #f0f0f0; padding: 2px 8px; border-radius: 10px; color: #666;">
                                            <?php echo htmlspecialchars($item['categoria'] ?? ''); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="dish-actions" style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
                                        <span class="dish-price" style="font-weight: bold; color: #2B3674;">€ <?php echo number_format($item['price'], 2); ?></span>
                                        
                                        <div style="display: flex; gap: 8px;">
                                            <button type="button" class="btn-open-edit" 
                                                data-id="<?php echo $item['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($item['name']); ?>"
                                                data-desc="<?php echo htmlspecialchars($item['description']); ?>"
                                                data-price="<?php echo $item['price']; ?>"
                                                data-cat="<?php echo htmlspecialchars($item['categoria'] ?? 'altro'); ?>"
                                                data-img="<?php echo htmlspecialchars($item['image_url'] ?? ''); ?>"
                                                style="background: none; border: none; color: #4318FF; cursor: pointer; font-size: 14px;">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            
                                            <form method="POST" action="" onsubmit="return confirm('Sei sicuro di voler eliminare questo piatto?');" style="margin:0;">
                                                <input type="hidden" name="delete_dish" value="1">
                                                <input type="hidden" name="dish_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" style="background: none; border: none; color: #E31A1A; cursor: pointer; font-size: 14px;">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
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

    <div id="editModal" class="modal-overlay">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="color: #2B3674; margin: 0;">Modifica Piatto</h3>
                <button type="button" id="closeEditModal" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #A3AED0;">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="edit_dish" value="1">
                <input type="hidden" name="dish_id" id="edit_dish_id">
                <input type="hidden" name="existing_image" id="edit_existing_image">

                <label for="edit-upload-img" class="card card-add" style="cursor: pointer; padding: 10px; min-height: auto;">
                    <div class="icon-plus" style="width: 30px; height: 30px; font-size: 16px;">+</div>
                    <div class="text-add" id="edit-file-name" style="font-size: 13px;">Sostituisci Immagine</div>
                </label>
                <input type="file" name="dish_image" id="edit-upload-img" style="display: none;" accept="image/*">

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px; margin-bottom: 15px; margin-top: 15px;">
                    <div class="input-wrapper">
                        <i class="fa-solid fa-utensils"></i>
                        <input type="text" name="name" id="edit_name" required>
                    </div>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-euro-sign"></i>
                        <input type="number" step="0.50" name="price" id="edit_price" required>
                    </div>
                </div>

                <div class="input-wrapper textarea-wrapper" style="margin-bottom: 15px;">
                    <i class="fa-solid fa-align-left" style="top: 15px;"></i>
                    <textarea name="description" id="edit_desc" rows="2" style="min-height: 80px;"></textarea>
                </div>

                <label style="display:block; margin-bottom:8px; font-weight:600; color: #2B3674; font-size: 14px;">Categoria</label>
                <select name="categoria_select" id="edit_piatto_select" style="width:100%; padding:10px; border-radius:8px; border:1px solid #d1d9e2; margin-bottom:12px;">
                    <option value="pizza">Pizza</option>
                    <option value="pasta">Pasta</option>
                    <option value="panino">Panino</option>
                    <option value="orientale">Orientale</option>
                    <option value="altro">Altro</option>
                </select>

                <div class="input-wrapper">
                    <i class="fa-solid fa-plus-circle"></i>
                    <input type="text" name="categoria_custom" id="edit_piatto_custom" placeholder="Oppure scrivi categoria personalizzata...">
                </div>

                <button type="submit" class="btn-save" style="width: 100%; margin-top: 20px; border: none; cursor: pointer;">
                    Salva Modifiche
                </button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector('.sidebar');
            const hamburger = document.querySelector('.hamburger-btn');
            const closeBtn = document.getElementById('closeSidebarBtn');
            
            let overlay = document.querySelector('.sidebar-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.classList.add('sidebar-overlay');
                document.body.appendChild(overlay);
            }

            function openSidebar() {
                sidebar.classList.add('active');
                overlay.classList.add('active');
            }

            function closeSidebar() {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            }

            if (hamburger) hamburger.addEventListener('click', openSidebar);
            if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
            overlay.addEventListener('click', closeSidebar);
        });

        const inputCustom = document.getElementById('piatto_custom');
        const selectDefault = document.getElementById('piatto_select');
        const statusHelp = document.getElementById('status-help');
        const btnSave = document.querySelector('.btn-add');
        const badWords = <?php echo json_encode(getBadWords()); ?>;

        document.getElementById('upload-img').addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                document.getElementById('file-name').textContent = e.target.files[0].name;
            }
        });

        document.getElementById('edit-upload-img').addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                document.getElementById('edit-file-name').textContent = e.target.files[0].name;
            }
        });

        function checkBadWords(inputElement, selectElement, helpElement, buttonElement) {
            const val = inputElement.value.trim().toLowerCase();
            const found = badWords.some(word => {
                const regex = new RegExp("\\b" + word + "\\b", "i");
                return regex.test(val);
            });

            if (found) {
                inputElement.style.borderColor = "#ea4335";
                if(helpElement) {
                    helpElement.innerHTML = "<i class='fa-solid fa-ban'></i> Termine non consentito";
                    helpElement.style.color = "#ea4335";
                }
                buttonElement.disabled = true;
                buttonElement.style.opacity = "0.5";
                return; 
            }

            if (val.length > 0) {
                selectElement.disabled = true;
                selectElement.style.opacity = "0.5";
                if(helpElement) {
                    helpElement.innerHTML = "<i class='fa-solid fa-keyboard'></i> Categoria personalizzata attiva";
                    helpElement.style.color = "#4318FF";
                }
            } else {
                selectElement.disabled = false;
                selectElement.style.opacity = "1";
                if(helpElement) helpElement.innerHTML = "";
            }
            buttonElement.disabled = false;
            buttonElement.style.opacity = "1";
            inputElement.style.borderColor = "#d1d9e2";
        }

        if(inputCustom) {
            inputCustom.addEventListener('input', function() {
                checkBadWords(this, selectDefault, statusHelp, btnSave);
            });
        }

        document.getElementById('form-piatto').addEventListener('submit', function() {
            selectDefault.disabled = false;
        });

        const editModal = document.getElementById('editModal');
        const closeEditModal = document.getElementById('closeEditModal');
        const editBtns = document.querySelectorAll('.btn-open-edit');

        editBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('edit_dish_id').value = this.dataset.id;
                document.getElementById('edit_name').value = this.dataset.name;
                document.getElementById('edit_price').value = this.dataset.price;
                document.getElementById('edit_desc').value = this.dataset.desc;
                document.getElementById('edit_existing_image').value = this.dataset.img;
                document.getElementById('edit-file-name').textContent = "Sostituisci Immagine";

                const cat = this.dataset.cat.toLowerCase();
                const select = document.getElementById('edit_piatto_select');
                const custom = document.getElementById('edit_piatto_custom');
                
                let found = false;
                Array.from(select.options).forEach(opt => {
                    if (opt.value === cat) {
                        opt.selected = true;
                        found = true;
                    }
                });

                if (!found && cat !== '') {
                    select.value = 'altro';
                    custom.value = this.dataset.cat;
                    select.disabled = true;
                } else {
                    custom.value = '';
                    select.disabled = false;
                }

                editModal.style.display = 'flex';
            });
        });

        closeEditModal.addEventListener('click', function() {
            editModal.style.display = 'none';
        });

        window.addEventListener('click', function(e) {
            if (e.target === editModal) {
                editModal.style.display = 'none';
            }
        });
        
        const editCustomInput = document.getElementById('edit_piatto_custom');
        const editSelect = document.getElementById('edit_piatto_select');
        const editSubmitBtn = editModal.querySelector('.btn-save');
        
        editCustomInput.addEventListener('input', function() {
            checkBadWords(this, editSelect, null, editSubmitBtn);
        });
        
        editModal.querySelector('form').addEventListener('submit', function() {
            editSelect.disabled = false;
        });
    </script>
</body>
</html>