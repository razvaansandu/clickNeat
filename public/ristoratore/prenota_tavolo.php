<?php
session_start();
require_once "../../config/db.php";
require_once "../../models/RistoranteTavoloModel.php";
require_once "../../models/PrenotazioneTavoloModel.php";

if (!isset($_SESSION['loggedin'])) {
    header("Location: ../auth/login.php");
    exit;
}

$ristorante_id = $_GET['ristorante_id'] ?? null;
if (!$ristorante_id) { header("Location: dashboard_consumatore.php"); exit; }

$tavoloModel = new RistoranteTavoloModel($db);
$prenotModel = new PrenotazioneTavoloModel($db);
$msg = ""; $msg_type = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data    = $_POST['data'];
    $ora     = $_POST['ora'] . ':00';
    $ora_fine = date('H:i:s', strtotime($ora) + 90 * 60);
    $persone = (int) $_POST['numero_persone'];

    if (!$prenotModel->isOrarioValido($data, $ora)) {
        $msg = "Orario non valido. Prenotazioni accettate dalle 12:00 alle 22:00.";
        $msg_type = "error";
    } else {
        $tavoli = $tavoloModel->getTavoliConDisponibilita($ristorante_id, $data, $ora, $ora_fine);
        $tavolo_scelto = null;

        foreach ($tavoli as $t) {
            if ($t['disponibilita'] === 'libero' && $t['capacita'] >= $persone) {
                $tavolo_scelto = $t;
                break;
            }
        }

        if (!$tavolo_scelto) {
            $msg = "Nessun tavolo disponibile per $persone persone in questo orario.";
            $msg_type = "error";
        } else {
            $prenotModel->create([
                'tavolo_id'         => $tavolo_scelto['id'],
                'ristorante_id'     => $ristorante_id,
                'cliente_id'        => $_SESSION['id'],
                'nome_cliente'      => $_SESSION['username'],
                'data_prenotazione' => $data,
                'ora_prenotazione'  => $ora,
                'numero_persone'    => $persone,
                'note'              => trim($_POST['note'] ?? ''),
                'stato'             => 'in_attesa'
            ]);
            $msg = "Prenotazione inviata! Il ristorante la confermerà a breve.";
            $msg_type = "success";
        }
    }
}
?>

<form method="POST">
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px;">
        <div>
            <label>Data *</label>
            <input type="date" name="data" min="<?php echo date('Y-m-d'); ?>" required
                   style="width:100%; padding:10px; border:1px solid #E0E5F2; border-radius:8px; box-sizing:border-box;">
        </div>
        <div>
            <label>Ora *</label>
            <input type="time" name="ora" min="12:00" max="22:00" required
                   style="width:100%; padding:10px; border:1px solid #E0E5F2; border-radius:8px; box-sizing:border-box;">
        </div>
    </div>
    <div style="margin-bottom:20px;">
        <label>Numero persone *</label>
        <input type="number" name="numero_persone" min="1" max="20" required
               style="width:100%; padding:10px; border:1px solid #E0E5F2; border-radius:8px; box-sizing:border-box;">
    </div>
    <div style="margin-bottom:20px;">
        <label>Note (allergie, occasioni...)</label>
        <textarea name="note" rows="2"
                  style="width:100%; padding:10px; border:1px solid #E0E5F2; border-radius:8px; resize:none; box-sizing:border-box;"></textarea>
    </div>
    <button type="submit" style="width:100%; background:linear-gradient(135deg,#4318FF,#6B46C1); color:white; border:none; padding:12px; border-radius:10px; cursor:pointer; font-weight:600; font-size:15px;">
        <i class="fa-solid fa-calendar-check"></i> Prenota Tavolo
    </button>
</form>
