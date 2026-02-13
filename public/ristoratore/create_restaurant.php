<?php
if(session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once "../../config/db.php";
require_once "../../models/RistoranteRistoratoreModel.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["ruolo"] !== 'ristoratore') {
    header("location: ../auth/login.php");
    exit;
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $nome = trim($_POST["nome"]);
    $indirizzo = trim($_POST["indirizzo"]);
    $descrizione = trim($_POST["descrizione"]);
    $proprietario_id = $_SESSION["id"];

    if (empty($nome) || empty($indirizzo)) {
        $error = "Per favore, inserisci almeno il nome e l'indirizzo del locale.";
    } else {
        $image_path = null;

        if (isset($_FILES['immagine_ristorante']) && $_FILES['immagine_ristorante']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = "../../image/";
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_info = pathinfo($_FILES['immagine_ristorante']['name']);
            $file_ext = strtolower($file_info['extension']);
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($file_ext, $allowed_exts)) {
                $new_filename = uniqid('rest_') . '.' . $file_ext;
                $target_file = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['immagine_ristorante']['tmp_name'], $target_file)) {
                    $image_path = $new_filename;
                } else {
                    $error = "Errore durante il salvataggio dell'immagine sul server.";
                }
            } else {
                $error = "Formato immagine non valido. Usa JPG, PNG, GIF o WEBP.";
            }
        }

        if (empty($error)) {
            $ristoranteModel = new RistoranteRistoratoreModel($db);
            
            if ($ristoranteModel->create($proprietario_id, $nome, $indirizzo, $descrizione, $image_path)) {
                $success = "Ristorante creato con successo! Verrai reindirizzato...";
                header("refresh:2;url=dashboard_ristoratore.php");
            } else {
                $error = "Qualcosa è andato storto nel database. Riprova più tardi.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Crea Ristorante - ClickNeat</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=dashboard_2" />
    <link rel="stylesheet" href="../../css/style_ristoratori.css">
</head>
<body>

    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content" style="display: flex; align-items: center; justify-content: center;">
        
        <div class="form-container">
            <div class="form-header">
                <div class="icon-header">
                    <i class="fa-solid fa-store"></i>
                </div>
                <h1>Nuovo Ristorante</h1>
                <p>Inserisci i dettagli del tuo locale per iniziare.</p>
            </div>

            <?php if($error): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if($success): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form action="create_restaurant.php" method="POST" enctype="multipart/form-data">
                
                <div class="card card-add" style="max-width: 250px; min-height: 150px; height: auto; padding: 20px; margin: 0 auto 20px auto; display: flex; flex-direction: column; justify-content: center; align-items: center; box-sizing: border-box;">
                    <label for="foto-ristorante" style="cursor: pointer; display: flex; flex-direction: column; align-items: center; margin: 0;">
                        <div class="icon-plus">+</div>
                        <div class="text-add">Aggiungi immagine</div>
                    </label>
                    <input type="file" id="foto-ristorante" name="immagine_ristorante" accept="image/*" required style="display: none;">
                    <div id="nome-file-scelto" style="margin-top: 15px; font-size: 14px; color: #1A4D4E; font-weight: 600;"></div>
                </div>

                <div class="form-group">
                    <label for="nome">Nome del Locale</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-utensils"></i>
                        <input type="text" id="nome" name="nome" placeholder="Es. Pizzeria Bella Napoli" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="indirizzo">Indirizzo</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-location-dot"></i>
                        <input type="text" id="indirizzo" name="indirizzo" placeholder="Via Roma 123, Milano" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="descrizione">Descrizione (Opzionale)</label>
                    <div class="input-wrapper textarea-wrapper">
                        <i class="fa-solid fa-pen" style="margin-top: 15px;"></i>
                        <textarea id="descrizione" name="descrizione" placeholder="Raccontaci brevemente la tua cucina..."></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="dashboard_ristoratore.php" class="btn-cancel">Annulla</a>
                    <button type="submit" class="btn-submit">Crea Ristorante</button>
                </div>
                
            </form>
        </div>
    </div>

    <script>
        document.getElementById('foto-ristorante').addEventListener('change', function() {
            const display = document.getElementById('nome-file-scelto');
            if (this.files && this.files.length > 0) {
                display.textContent = "File caricato: " + this.files[0].name;
            } else {
                display.textContent = "";
            }
        });
    </script>

</body>
</html>