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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_restaurant'])) {
    $data = [
        'nome' => trim($_POST['nome']),
        'indirizzo' => trim($_POST['indirizzo']),
        'categoria' => trim($_POST['categoria']),
        //implementare img da ricaricare sul ristorante
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
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Modifica Locale - <?php echo htmlspecialchars($restaurant['nome']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/style_ristoratori.css">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <div>
                <a href="manage_restaurant.php?id=<?php echo $restaurant_id; ?>" class="btn-cancel" style="padding-left:0; margin-bottom:10px; display:inline-block;">
                    <i class="fa-solid fa-arrow-left"></i> Torna alla Gestione
                </a>
                <h1>Impostazioni Ristorante</h1>
                <p>Modifica le informazioni pubbliche del tuo locale</p>
            </div>
        </div>

        <?php if ($msg): ?>
            <div class="msg-box <?php echo $msg_type; ?>">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <div class="card" style="max-width: 600px;">
            <form method="POST">
                <input type="hidden" name="update_restaurant" value="1">
                
                <div class="input-wrapper" style="margin-bottom: 20px;">
                    <label style="display:block; margin-bottom:8px; font-weight:600;">Nome del Ristorante</label>
                    <input type="text" name="nome" value="<?php echo htmlspecialchars($restaurant['nome']); ?>" required>
                </div>

                <div class="input-wrapper" style="margin-bottom: 20px;">
                    <label style="display:block; margin-bottom:8px; font-weight:600;">Indirizzo</label>
                    <input type="text" name="indirizzo" value="<?php echo htmlspecialchars($restaurant['indirizzo']); ?>" required>
                </div>

                <div class="input-wrapper" style="margin-bottom: 20px;">
                    <label style="display:block; margin-bottom:8px; font-weight:600;">Categoria</label>
                    <input type="text" name="categoria" value="<?php echo htmlspecialchars($restaurant['categoria']); ?>" required>
                </div>

                <button type="submit" class="btn-add" style="width: 100%; margin-top: 10px;">Salva Modifiche</button>
            </form>
        </div>
    </div>
</body>
</html>