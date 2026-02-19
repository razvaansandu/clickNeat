<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once "../../config/db.php";
require_once "../../models/RistoranteRistoratoreModel.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["ruolo"] !== 'ristoratore') {
    header("location: ../auth/login.php");
    exit;
}

$restaurant_id = $_GET['id'] ?? null;
$user_id = $_SESSION['id'];
$ristoranteModel = new RistoranteRistoratoreModel($db);
$restaurant = $ristoranteModel->getByIdAndOwner($restaurant_id, $user_id);

if (!$restaurant) {
    header("location: dashboard_ristoratore.php");
    exit;
}

$msg = "";
$msg_type = "";

if (isset($_POST['delete_restaurant'])) {
    if ($ristoranteModel->delete($restaurant_id)) {
        header("Location: dashboard_ristoratore.php");
        exit;
    } else {
        $msg = "Errore durante l'eliminazione del ristorante.";
        $msg_type = "error";
    }

    if (isset($_POST['update_info'])) {
        $data = [
            'nome' => trim($_POST['nome']),
            'indirizzo' => trim($_POST['indirizzo']),
            'categoria' => trim($_POST['categoria'])
        ];

        if ($ristoranteModel->update($restaurant_id, $data)) {
            $msg = "Informazioni aggiornate con successo!";
            $msg_type = "success";
            $restaurant = $ristoranteModel->getByIdAndOwner($restaurant_id, $user_id);
        } else {
            $msg = "Errore durante l'aggiornamento.";
            $msg_type = "error";
        }
    }

    if (isset($_POST['update_img'])) {
        if (isset($_FILES['restaurant_image']) && $_FILES['restaurant_image']['error'] === 0) {
            $upload_dir = '../assets/';
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_name = $_FILES['restaurant_image']['name'];
            $file_tmp = $_FILES['restaurant_image']['tmp_name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($file_ext, $allowed)) {
                $new_file_name = uniqid('rest_') . '.' . $file_ext;
                $dest_path = $upload_dir . $new_file_name;

                if (move_uploaded_file($file_tmp, $dest_path)) {
                    $db_path = "/assets/" . $new_file_name;
                    
                    if ($ristoranteModel->update($restaurant_id, ['image_url' => $db_path])) {
                        $msg = "Immagine di vetrina aggiornata!";
                        $msg_type = "success";
                        $restaurant = $ristoranteModel->getByIdAndOwner($restaurant_id, $user_id);
                    } else {
                        $msg = "Errore nel salvataggio DB.";
                        $msg_type = "error";
                    }
                } else {
                    $msg = "Errore upload file (controlla permessi cartella assets).";
                    $msg_type = "error";
                }
            } else {
                $msg = "Formato non valido. Usa JPG, PNG o WEBP.";
                $msg_type = "error";
            }
        } else {
            $msg = "Seleziona un file valido.";
            $msg_type = "error";
        }
    }
}

$nome = $restaurant['nome'];
$indirizzo = $restaurant['indirizzo'];
$categoria = $restaurant['categoria'];
$vetrina_url = $restaurant['image_url'] ?? null;
$created_at = $restaurant['created_at'] ?? date("Y-m-d");
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <title>Modifica Locale - <?php echo htmlspecialchars($nome); ?></title>
    <link rel="stylesheet" href="../../css/style_ristoratori.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=dashboard_2" />
</head>

<body>

    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">

        <div style="margin-bottom: 20px;">
             <a href="manage_restaurant.php?id=<?php echo $restaurant_id; ?>" class="btn-cancel" style="text-decoration:none; color: #A3AED0; font-weight:500;">
                <i class="fa-solid fa-arrow-left"></i> Torna alla Gestione
            </a>
        </div>

        <?php if ($msg): ?>
            <div class="msg-box <?php echo $msg_type; ?>" style="padding: 15px; margin-bottom: 20px; border-radius: 10px; color: white; background-color: <?php echo $msg_type == 'success' ? '#05CD99' : '#E31A1A'; ?>;">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <div class="profile-wrapper">

            <div class="card-style avatar-box">
                <div class="avatar-circle" style="background: #F4F7FE; color: #4318FF;">
                    <i class="fa-solid fa-shop"></i>
                </div>

                <h2 style="color: #2B3674; font-size: 20px; margin-top: 15px;"><?php echo htmlspecialchars($nome); ?></h2>
                <span class="status-badge active" style="margin-top:5px;"><?php echo htmlspecialchars($categoria); ?></span>

                <div class="info-list">
                    <div class="info-row">
                        <span>Aperto dal</span>
                        <b><?php echo date("d M Y", strtotime($created_at)); ?></b>
                    </div>
                    <div class="info-row">
                        <span>Posizione</span>
                        <b style="font-size: 11px;"><?php echo htmlspecialchars($indirizzo); ?></b>
                    </div>
                </div>

                <div style="margin-top: 30px; text-align: center;">
                    <form method="POST" action="" onsubmit="return confirm('Sei sicuro di voler eliminare questo ristorante? Questa azione non può essere annullata.');">
                        <button type="submit" name="delete_restaurant" style="background: red; color: white; padding: 12px 24px; border: none; border-radius: 30px; font-weight: 600; display: inline-flex; justify-content: center; align-items: center; width: 100%; box-sizing: border-box; gap: 8px; box-shadow: 0 4px 10px rgba(255, 0, 0, 0.25); cursor: pointer; transition: all 0.2s ease;">
                            <i class="fa-solid fa-trash"></i> Elimina Ristorante
                        </button>
                    </form>
                </div>
            </div>

            <div class="card-style form-box">
                
                <div class="form-title" style="margin-bottom: 20px; font-weight:700; color:#2B3674; border-bottom:1px solid #eee; padding-bottom:10px;">Modifica Informazioni</div>
                
                <form method="POST" action="">
                    <div style="display: grid; grid-template-columns: 1fr; gap: 20px;">
                        <div class="input-group">
                            <label>Nome Ristorante</label>
                            <input type="text" name="nome" value="<?php echo htmlspecialchars($nome); ?>" required>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="input-group">
                                <label>Indirizzo</label>
                                <input type="text" name="indirizzo" value="<?php echo htmlspecialchars($indirizzo); ?>" required>
                            </div>
                            <div class="input-group">
                                <label>Categoria</label>
                                <input type="text" name="categoria" value="<?php echo htmlspecialchars($categoria); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div style="text-align: right; margin-top: 20px;">
                        <button type="submit" name="update_info" class="btn-save">Salva Info</button>
                    </div>
                </form>

                <div style="margin: 40px 0; border-top: 1px solid #eee;"></div>

                <div class="form-title" style="margin-bottom: 20px; font-weight:700; color:#2B3674; border-bottom:1px solid #eee; padding-bottom:10px;">
                    Immagine di Vetrina
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="font-size: 14px; color: #2B3674; font-weight: 500; margin-bottom: 8px; display: block;">Anteprima Attuale:</label>
                    <?php if($vetrina_url): ?>
                        <div style="width: 100%; height: 200px; border-radius: 10px; overflow: hidden; border: 1px solid #eee;">
                            <img src="<?php echo htmlspecialchars($vetrina_url); ?>" alt="Vetrina Ristorante" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    <?php else: ?>
                        <div style="width: 100%; height: 150px; background-color: #F4F7FE; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #A3AED0; border: 2px dashed #E0E5F2;">
                            <i class="fa-regular fa-image" style="font-size: 30px; margin-right: 10px;"></i> Nessuna immagine caricata
                        </div>
                    <?php endif; ?>
                </div>
                
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="input-group">
                        <label>Carica Nuova Foto</label>
                        <input type="file" name="restaurant_image" accept="image/*" required>
                        <small style="color: #A3AED0;">Questa immagine verrà mostrata nella lista ristoranti. (Consigliato: orizzontale)</small>
                    </div>
                    
                    <div style="text-align: right; margin-top: 20px;">
                        <button type="submit" name="update_img" class="btn-save" style="background-color: #2B3674;">Aggiorna Vetrina</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>
</html>