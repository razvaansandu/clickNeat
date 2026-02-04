<?php
session_start();
require_once "../../config/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["ruolo"] !== 'ristoratore') {
    header("location: login.php");
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
        $sql = "INSERT INTO ristoranti (proprietario_id, nome, indirizzo, descrizione) VALUES (?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "isss", $proprietario_id, $nome, $indirizzo, $descrizione);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = "Ristorante creato con successo! Verrai reindirizzato...";
                header("refresh:2;url=dashboard_ristoratore.php");
            } else {
                $error = "Qualcosa è andato storto. Riprova più tardi.";
            }
            mysqli_stmt_close($stmt);
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
    <link rel="stylesheet" href="../css/style_ristoratori.css">
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="form-card">
            <div class="header-title">
                <div class="icon-top"><i class="fa-solid fa-shop"></i></div>
                <h1>Nuovo Ristorante</h1>
                <p>Inserisci i dettagli per iniziare a ricevere ordini.</p>
            </div>

            <?php if($error): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if($success): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form action="create_restaurant.php" method="POST">
                <div class="form-group">
                    <label for="nome">Nome del Locale *</label>
                    <input type="text" id="nome" name="nome" placeholder="Es. Pizzeria Bella Napoli" required>
                </div>

                <div class="form-group">
                    <label for="indirizzo">Indirizzo Completo *</label>
                    <input type="text" id="indirizzo" name="indirizzo" placeholder="Via Roma 123, Milano" required>
                </div>

                <div class="form-group">
                    <label for="descrizione">Descrizione (Opzionale)</label>
                    <textarea id="descrizione" name="descrizione" placeholder="Raccontaci brevemente la tua cucina... (es. Specialità pesce fresco)"></textarea>
                </div>

                <button type="submit" class="btn-submit">Crea Ristorante</button>
            </form>

            <a href="dashboard_ristoratore.php" class="btn-back">
                <i class="fa-solid fa-arrow-left"></i> Annulla e torna alla Dashboard
            </a>
        </div>

    </div>

</body>
</html>