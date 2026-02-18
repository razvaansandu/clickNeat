<?php
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

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

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
                        <textarea id="descrizione" name="descrizione"
                            placeholder="Raccontaci brevemente la tua cucina..."></textarea>
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
        document.getElementById('upload-img').addEventListener('change', function (e) {
            var fileName = e.target.files[0].name;
            document.getElementById('file-name').textContent = "File selezionato: " + fileName;
            document.querySelector('.icon-plus').style.backgroundColor = '#4CAF50';
            document.querySelector('.icon-plus').textContent = '✓';
        });
    </script>

</body>

</html>