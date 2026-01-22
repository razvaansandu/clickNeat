<?php
// config/db.example.php
// QUESTO È UN FILE DI ESEMPIO.
// Istruzioni: Rinomina questo file in "db.php" e inserisci le tue credenziali reali.

// Configurazione sessione sicura (DEVE essere PRIMA di session_start!)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Metti 1 se usi HTTPS
ini_set('session.cookie_samesite', 'Strict');

// ADESSO avvia la sessione
session_start();

// Configurazione database (Default per Docker)
define('DB_SERVER', 'db');           // Usa 'db' per Docker, 'localhost' per XAMPP
define('DB_USERNAME', 'root');       // Inserisci qui il tuo utente DB
define('DB_PASSWORD', 'password');   // Inserisci qui la tua password DB
define('DB_NAME', 'clickneat');      // Nome del database

// Password requirements
define('PASSWORD_MIN_LENGTH', 8);

// Crea connessione
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Controlla connessione
if($link === false){
    die("ERRORE: Impossibile connettersi al database. " . mysqli_connect_error());
}

// Imposta charset
mysqli_set_charset($link, "utf8mb4");

// Timeout sessione (30 minuti)
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
    session_unset();
    session_destroy();
    
    // Reindirizza al login appropriato
    $redirect = 'login_consumatore.php';
    if(isset($_SESSION['ruolo']) && $_SESSION['ruolo'] == 'ristoratore'){
        $redirect = 'login_ristoratore.php';
    }
    
    // Risaliamo di un livello perché config è in una sottocartella, ma i login sono in public
    // Nota: A seconda di come includi questo file, potresti dover aggiustare il path
    header("Location: ../public/$redirect?timeout=1");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

// Rigenera session ID ogni 10 minuti
if (!isset($_SESSION['CREATED'])) {
    $_SESSION['CREATED'] = time();
} else if (time() - $_SESSION['CREATED'] > 600) {
    session_regenerate_id(true);
    $_SESSION['CREATED'] = time();
}

// Verifica IP e User Agent per prevenire session hijacking
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true){
    if(!isset($_SESSION['USER_IP'])){
        $_SESSION['USER_IP'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];
    } else {
        if($_SESSION['USER_IP'] != $_SERVER['REMOTE_ADDR'] || 
           $_SESSION['USER_AGENT'] != $_SERVER['HTTP_USER_AGENT']){
            session_unset();
            session_destroy();
            
            $redirect = 'login_consumatore.php';
            header("Location: ../public/$redirect?security=1");
            exit();
        }
    }
}
?>