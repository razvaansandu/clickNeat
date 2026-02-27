<?php
ob_start();
session_start();
require_once "../../config/db.php";
require_once "../../models/RistoranteTavoloModel.php";
require_once "../../models/PrenotazioneTavoloModel.php";

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['ruolo'] !== 'ristoratore') {
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

$required = ['tavolo_id', 'data_prenotazione', 'ora_prenotazione', 'nome_cliente', 'numero_persone'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => "Campo mancante: $field"]);
        exit;
    }
}

$ristorante_id = $_SESSION['ristorante_id'] ?? null;

if (!$ristorante_id) {
    $tavolo = $db->selectOne(
        "SELECT ristorante_id FROM ristorante_tavoli WHERE id = ?",
        [$_POST['tavolo_id']]
    );
    if (!$tavolo) {
        echo json_encode(['success' => false, 'message' => 'Tavolo non trovato']);
        exit;
    }
    $ristorante_id = $tavolo['ristorante_id'];
}

$prenotModel = new PrenotazioneTavoloModel($db);

$tavolo_id    = (int) $_POST['tavolo_id'];
$data         = $_POST['data_prenotazione'];
$ora          = date('H:i:s', strtotime($_POST['ora_prenotazione']));
$ora_fine     = date('H:i:s', strtotime($ora) + 90 * 60);
$nome_cliente = trim($_POST['nome_cliente']);
$persone      = (int) $_POST['numero_persone'];

if (!$prenotModel->isOrarioValido($data, $ora)) {
    echo json_encode([
        'success' => false,
        'message' => 'Orario non valido. Accettiamo prenotazioni dalle 12:00 alle 22:00.'
    ]);
    exit;
}

if (!$prenotModel->isTavoloDisponibile($tavolo_id, $data, $ora, $ora_fine)) {
    echo json_encode([
        'success' => false,
        'message' => 'Tavolo già occupato in questo orario (' . substr($ora, 0, 5) . ' – ' . substr($ora_fine, 0, 5) . ').'
    ]);
    exit;
}

$result = $prenotModel->create([
    'tavolo_id'          => $tavolo_id,
    'ristorante_id'      => $ristorante_id,
    'cliente_id'         => null,
    'nome_cliente'       => $nome_cliente,
    'telefono'           => trim($_POST['telefono'] ?? ''),
    'email'              => trim($_POST['email']    ?? ''),
    'data_prenotazione'  => $data,
    'ora_prenotazione'   => $ora,
    'numero_persone'     => $persone,
    'note'               => trim($_POST['note']     ?? ''),
    'stato'              => 'confermata'
]);

echo json_encode([
    'success' => (bool) $result,
    'message' => $result ? 'Prenotazione salvata!' : 'Errore nel salvataggio.'
]);
