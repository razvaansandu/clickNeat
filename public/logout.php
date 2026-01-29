<?php
require_once "../config/db.php";
$redirect = "login.php";
if(isset($_SESSION["ruolo"])) {
	if($_SESSION["ruolo"] === "consumatore") {
		$redirect = "login.php";
	} elseif($_SESSION["ruolo"] === "ristoratore") {
		$redirect = "login.php";
	}
}
$_SESSION = array();
session_destroy();
header("location: $redirect");
exit;
?>
