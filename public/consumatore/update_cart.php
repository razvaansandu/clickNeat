<?php

if(session_status() !== PHP_SESSION_ACTIVE) session_start();

if (!isset($_SESSION['cart']) || !isset($_GET['action']) || !isset($_GET['id'])) {
    header("Location: checkout.php");
    exit;
}

$id = intval($_GET['id']);
$action = $_GET['action'];

foreach ($_SESSION['cart']['items'] as $key => &$item) {
    if ($item['id'] == $id) {

        if ($action == 'increase') {
            $item['qty']++;
        } elseif ($action == 'decrease') {
            $item['qty']--;
        } elseif ($action == 'remove') {
            $item['qty'] = 0;
        }

        if ($item['qty'] <= 0) {
            unset($_SESSION['cart']['items'][$key]);
        }

        break;
    }
}

if (isset($item)) {
    unset($item);
}

$_SESSION['cart']['items'] = array_values($_SESSION['cart']['items']);

$new_total = 0;

foreach ($_SESSION['cart']['items'] as $prod) {
    $new_total += ($prod['price'] * $prod['qty']);
}
$_SESSION['cart']['total'] = $new_total;

if (empty($_SESSION['cart']['items'])) {
    unset($_SESSION['cart']);
    header("Location: dashboard_consumatore.php");
    exit;
}

header("Location: checkout.php");
exit;
?>