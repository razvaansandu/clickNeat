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

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_POST['update_info'])) {
        $data = [
            'nome' => trim($_POST['nome']),
            'indirizzo' => trim($_POST['indirizzo']),
            'categoria' => trim($_POST['categoria'])
        ];

        if ($ristoranteModel->update($restaurant_id, $data)) {
            $msg = "Informazioni del locale aggiornate!";
            $msg_type = "success";
            // Ricarica dati aggiornati
            $restaurant = $ristoranteModel->getByIdAndOwner($restaurant_id, $user_id);
        } else {
            $msg = "Errore durante l'aggiornamento.";
            $msg_type = "error";
        }
    }

    // LOGICA PER IMMAGINE (COMMENTATA COME RICHIESTO)
    /*
    if (isset($_POST['update_img'])) {
        // Qui andrebbe la logica per l'upload dell'immagine
        // $msg = "Immagine aggiornata!";
        // $msg_type = "success";
    }
    */
}

$nome = $restaurant['nome'];
$indirizzo = $restaurant['indirizzo'];
$categoria = $restaurant['categoria'];
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
            <div class="msg-box <?php echo $msg_type; ?>"><?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>

        <div class="profile-wrapper">

            <div class="card-style avatar-box">
                <div class="avatar-circle" style="background: #F4F7FE; color: #4318FF;">
                    <i class="fa-solid fa-shop"></i>
                </div>
                <h2 style="color: #2B3674; font-size: 20px;"><?php echo htmlspecialchars($nome); ?></h2>
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
            </div>

            <div class="card-style form-box">
                <div class="form-title" style="margin-bottom: 20px; font-weight:700; color:#2B3674; border-bottom:1px solid #eee; padding-bottom:10px;">Modifica Informazioni Locale</div>
                
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
                        <button type="submit" name="update_info" class="btn-save">Salva Modifiche Locale</button>
                    </div>
                </form>

                <div style="margin: 40px 0; border-top: 1px solid #eee;"></div>

                <div class="form-title" style="margin-bottom: 20px; font-weight:700; color:#2B3674; border-bottom:1px solid #eee; padding-bottom:10px;">Immagine Vetrina</div>
                
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="input-group">
                        <label>Foto Locale</label>
                        <input type="file" name="restaurant_image" disabled style="opacity: 0.5; cursor: not-allowed;">
                        <small style="color: #A3AED0;">L'upload delle immagini sarà disponibile nei prossimi aggiornamenti.</small>
                    </div>
                    
                    <div style="text-align: right; margin-top: 20px;">
                        <button type="button" class="btn-save" style="background-color: #A3AED0; cursor: not-allowed;">Aggiorna Immagine</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>
</html>