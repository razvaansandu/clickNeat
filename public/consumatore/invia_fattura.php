<?php
session_start();
require_once "../../config/db.php";
require_once "../../models/FatturaModel.php";

header('Content-Type: application/json');

$fattura_id = $_GET['fattura_id'] ?? null;
$fatturaModel = new FatturaModel($db);

if ($fatturaModel->sendFatturaEmail($fattura_id)) {
    echo json_encode(['success' => true, 'message' => 'Fattura inviata via email!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Errore nell\'invio.']);
}
