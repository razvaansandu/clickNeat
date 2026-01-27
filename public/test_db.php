<?php
require_once "../config/db.php";

if($link){
    echo "Connessione al database riuscita!";
} else {
    echo "Errore: " . mysqli_connect_error();
}

mysqli_close($link);
?>
