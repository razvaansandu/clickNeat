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

    $image_path = null;

    if (empty($nome) || empty($indirizzo)) {
        $error = "Per favore, inserisci almeno il nome e l'indirizzo.";
    } else {

        if (isset($_FILES['immagine_ristorante']) && $_FILES['immagine_ristorante']['error'] === 0) {

            $upload_dir = '../assets/';

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_name = $_FILES['immagine_ristorante']['name'];
            $file_tmp = $_FILES['immagine_ristorante']['tmp_name'];

            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($file_ext, $allowed)) {

                $new_file_name = uniqid('rest_') . '.' . $file_ext;

                $dest_path = $upload_dir . $new_file_name;

                if (move_uploaded_file($file_tmp, $dest_path)) {
                    $image_path = "assets/" . $new_file_name;
                } else {
                    $error = "Errore nel caricamento dell'immagine sul server.";
                }
            } else {
                $error = "Formato non valido. Usa JPG, PNG o WEBP.";
            }
        }

        if (empty($error)) {
            $ristoranteModel = new RistoranteRistoratoreModel($db);

            if ($ristoranteModel->create($proprietario_id, $nome, $indirizzo, $descrizione, $image_path)) {
                $success = "Ristorante creato! Reindirizzamento...";
                header("refresh:2;url=dashboard_ristoratore.php");
            } else {
                $error = "Errore database.";
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

    <div class="main-content" style="display: flex; align-items: center; justify-content: center;">

        <div class="form-container">
            <div class="form-header">
                <div class="icon-header">
                    <i class="fa-solid fa-store"></i>
                </div>
                <h1>Nuovo Ristorante</h1>
                <p>Inserisci i dettagli e aggiungi fino a 15 categorie</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success); ?>
                </div>

            <form action="" method="POST" enctype="multipart/form-data">

                <label for="upload-img" class="card card-add">
                    <div class="icon-plus">+</div>
                    <div class="text-add" id="file-name">Aggiungi immagine in primo piano</div>
                </label>

                <input type="file" name="immagine_ristorante" id="upload-img" style="display: none;" accept="image/*">
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
                        <i class="fa-solid fa-pen" style="margin-top: 15px;"></i>
                        <textarea id="descrizione" name="descrizione"
                            placeholder="Raccontaci brevemente la tua cucina..."></textarea>
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
        document.getElementById('upload-img').addEventListener('change', function (e) {
            var fileName = e.target.files[0].name;
            document.getElementById('file-name').textContent = "File selezionato: " + fileName;
            document.querySelector('.icon-plus').style.backgroundColor = '#4CAF50';
            document.querySelector('.icon-plus').textContent = '✓';
        });
    </script>

</body>

</html>