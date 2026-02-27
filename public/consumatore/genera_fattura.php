<?php
session_start();
require_once "../../config/db.php";
require_once "../../models/FatturaModel.php";

if (!isset($_SESSION["loggedin"])) {
    header("location: ../auth/login.php");
    exit;
}

$order_id = $_GET['order_id'] ?? null;
if (!$order_id) die("Ordine non valido");

$fatturaModel = new FatturaModel($db);
$fattura_id = $fatturaModel->createFattura($order_id, $_SESSION['id']);

if ($fattura_id) {
    header("Location: storico.php?fattura_creata=1");
} else {
    header("Location: storico.php?errore=1");
}
