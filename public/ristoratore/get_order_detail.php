<?php
session_start();

require_once "../../config/db.php";
require_once "../../models/OrderRistoratoreModel.php";

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['loggedin']) || $_SESSION['ruolo'] !== 'ristoratore') {
    echo json_encode([
        'success' => false,
        'message' => 'Non autorizzato'
    ]);
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID ordine non valido'
    ]);
    exit;
}

$order_id = (int) $_GET['id'];
$owner_id = (int) $_SESSION['id'];

try {
    $orderModel = new OrderRistoratoreModel($db);

    $order = $orderModel->getDetailByOrderIdAndOwner($order_id, $owner_id);

    if (!$order) {
        echo json_encode([
            'success' => false,
            'message' => 'Ordine non trovato'
        ]);
        exit;
    }

    $items = $orderModel->getOrderItems($order_id);

    echo json_encode([
        'success' => true,
        'order' => $order,
        'items' => $items
    ]);
    exit;

} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Errore server',
        'debug' => $e->getMessage()
    ]);
    exit;
}
