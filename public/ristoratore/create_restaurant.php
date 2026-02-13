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
        $proprietario_id = $_SESSION["id"];
        $ristoranteModel = new RistoranteRistoratoreModel($db);
        if ($ristoranteModel->create($proprietario_id, $nome, $indirizzo, $descrizione, $categoria_string)) {
            $success = "Ristorante creato con successo!";
            header("refresh:2;url=dashboard_ristoratore.php");
        } else {
            $error = "Errore durante il salvataggio. Controlla la connessione.";
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

            <form action="" method="POST" id="main-form">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Nome Locale</label>
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
        const tagInput = document.getElementById('tag-input');
        const tagContainer = document.getElementById('tag-container');
        const hiddenContainer = document.getElementById('hidden-inputs');
        const tagCountDisplay = document.getElementById('tag-count');
        const statusHelp = document.getElementById('status-help');
        const tags = new Set();
        const maxTags = 15;
        const badWords = <?php echo json_encode(getBadWords()); ?>;

        function renderTags() {
            document.querySelectorAll('.tag').forEach(t => t.remove());
            hiddenContainer.innerHTML = '';
            tags.forEach(tag => {
                const span = document.createElement('span');
                span.className = 'tag';
                span.innerHTML = `${tag} <i class="fa-solid fa-xmark" onclick="removeTag('${tag}')"></i>`;
                tagContainer.insertBefore(span, tagInput);

                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'categories[]';
                hidden.value = tag;
                hiddenContainer.appendChild(hidden);
            });
            tagCountDisplay.innerText = `${tags.size}/${maxTags}`;
            tagInput.disabled = tags.size >= maxTags;
        }

        function removeTag(tag) { tags.delete(tag); renderTags(); }

        tagInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const val = this.value.trim();
                if (!val || tags.size >= maxTags) return;

                const isBad = badWords.some(word => new RegExp("\\b" + word + "\\b", "i").test(val.toLowerCase()));
                if (isBad) {
                    statusHelp.innerText = "Parola non ammessa!";
                    statusHelp.style.color = "#ea4335";
                    return;
                }

                tags.add(val);
                this.value = '';
                statusHelp.innerText = "";
                renderTags();
            }
        });
    </script>
</body>
</html>