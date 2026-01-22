<?php
require_once "../config/db.php";
session_start();
$redirect = "login.php";
if(isset($_SESSION["ruolo"])) {
	if($_SESSION["ruolo"] === "consumatore") {
		$redirect = "login_consumatore.php";
	} elseif($_SESSION["ruolo"] === "ristoratore") {
		$redirect = "login_ristoratore.php";
	}
}
$_SESSION = array();
session_destroy();
header("location: $redirect");
exit;
?>
