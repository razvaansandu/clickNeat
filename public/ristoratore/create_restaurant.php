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

    // LOGICA DOPPIA SCELTA
    $cat_predefinita = $_POST["categoria_predefinita"] ?? '';
    $cat_custom = trim($_POST["categoria_custom"] ?? '');

    if (!empty($cat_custom)) {
        $categoria = $cat_custom;
    } elseif ($cat_predefinita !== 'custom') {
        $categoria = $cat_predefinita;
    } else {
        $categoria = 'altro'; 
    }

    $proprietario_id = $_SESSION["id"];

    if (empty($nome) || empty($indirizzo)) {
        $error = "Per favore, inserisci almeno il nome e l'indirizzo del locale.";
    } else {
        $sql = "INSERT INTO ristoranti (proprietario_id, nome, indirizzo, descrizione, categoria) VALUES (?, ?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "issss", $proprietario_id, $nome, $indirizzo, $descrizione, $categoria);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = "Ristorante creato con successo! Verrai reindirizzato...";
                header("refresh:2;url=dashboard_ristoratore.php");
            } else {
                $error = "Errore durante il salvataggio: " . mysqli_error($link);
            }
            mysqli_stmt_close($stmt);
        } else {
            $error = "Errore nella preparazione della query: " . mysqli_error($link);
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

            <form action="" method="POST">
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
                <div class="form-group">
    <label>Categoria Ristorante:</label>
    <select name="categoria" class="form-control" required>
        <option value="pizza">Pizzeria</option>
        <option value="pasta">Pasta</option>
        <option value="panineria">Panineria & Burger</option>
        <option value="orientale">Cucina Orientale</option>
        <option value="altro" selected>Altro</option>
    </select>
</div>

                <div class="form-actions">
                    <a href="dashboard_ristoratore.php" class="btn-cancel">Annulla</a>
                    <button type="submit" class="btn-submit">Crea Ristorante</button>
                </div>
            </form>
        </div>

    </div>

</body>
</html>