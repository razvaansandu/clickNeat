<?php
require_once "../../config/db.php";
require_once "../../models/RistoranteRistoratoreModel.php";

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["ruolo"] !== 'ristoratore') {
    header("location: ../auth/login.php");
    exit;
}
function getBadWords() {
    $jsonPath = __DIR__ . "/../../config/cursed_words.json";
    if (file_exists($jsonPath)) {
        $jsonData = file_get_contents($jsonPath);
        return json_decode($jsonData, true) ?? [];
    }
    return [];
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST["nome"]);
    $indirizzo = trim($_POST["indirizzo"]);
    $descrizione = trim($_POST["descrizione"]);
    $categories_posted = $_POST['categories'] ?? [];
    $badWords = getBadWords();
    $valid_categories = [];

    foreach (array_slice($categories_posted, 0, 15) as $cat) {
        $cat = trim(htmlspecialchars($cat));
        if (empty($cat)) continue;
        $isForbidden = false;
        foreach ($badWords as $word) {
            if (preg_match("/\b" . preg_quote($word, '/') . "\b/i", $cat)) {
                $isForbidden = true;
                break;
            }
        }
        if (!$isForbidden) $valid_categories[] = $cat;
    }

    $categoria_string = implode(", ", $valid_categories);
    if (empty($nome) || empty($indirizzo)) {
        $error = "Nome e Indirizzo sono obbligatori.";
    } elseif (empty($valid_categories)) {
        $error = "Inserisci almeno una categoria valida.";
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
    <link rel="stylesheet" href="../../css/style_ristoratori.css">
    <style>
        .tag-input-container {
            display: flex; flex-wrap: wrap; gap: 8px; padding: 12px;
            border: 1px solid #d1d9e2; border-radius: 12px; background: #fff;
            min-height: 50px; align-items: center;
        }
        .tag {
            background: #4318FF; color: white; padding: 6px 12px;
            border-radius: 20px; display: flex; align-items: center; gap: 8px;
            font-size: 13px; font-weight: 500;
        }
        #tag-input { border: none; outline: none; flex: 1; min-width: 120px; }
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-weight: 500; }
        .alert-error { background: #FFF5F5; color: #E53E3E; }
        .alert-success { background: #F0FFF4; color: #38A169; }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content" style="display: flex; align-items: center; justify-content: center; padding: 40px;">
        <div class="form-container" style="max-width: 550px; width: 100%;">
            <div class="form-header" style="text-align: center; margin-bottom: 25px;">
                <h1>Nuovo Ristorante</h1>
                <p>Inserisci i dettagli e aggiungi fino a 15 categorie</p>
            </div>

            <?php if($error): ?> <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?></div> <?php endif; ?>
            <?php if($success): ?> <div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> <?php echo $success; ?></div> <?php endif; ?>

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
                        <input type="text" name="nome" placeholder="Nome del ristorante" required>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Indirizzo</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-location-dot"></i>
                        <input type="text" name="indirizzo" placeholder="Via, Civico, Città" required>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Categorie (Scrivi e premi Invio)</label>
                    <div id="tag-container" class="tag-input-container">
                        <input type="text" id="tag-input" placeholder="Es. Pizza, Sushi..." maxlength="20">
                    </div>
                    <div id="hidden-inputs"></div>
                    <div style="display:flex; justify-content:space-between; font-size: 11px; margin-top:5px;">
                        <span id="status-help"></span>
                        <span id="tag-count" style="color:#A3AED0;">0/15</span>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Descrizione</label>
                    <div class="input-wrapper textarea-wrapper">
                        <i class="fa-solid fa-pen" style="top:15px;"></i>
                        <textarea name="descrizione" placeholder="Descrivi brevemente il locale..."></textarea>
                    </div>
                </div>

                <div class="form-actions" style="display: flex; gap: 10px;">
                    <a href="dashboard_ristoratore.php" class="btn-cancel" style="flex:1; text-align:center;">Annulla</a>
                    <button type="submit" class="btn-submit" style="flex:2;">Salva Ristorante</button>
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