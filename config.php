<?php
// Configurazione sessione sicura (DEVE essere PRIMA di session_start!)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Metti 1 se usi HTTPS
ini_set('session.cookie_samesite', 'Strict');

// ADESSO avvia la sessione
session_start();

// Configurazione database
define('DB_SERVER', '192.168.5.132');
define('DB_USERNAME', 'razvan_root');
define('DB_PASSWORD', 'razvan123');
define('DB_NAME', 'clickneat');

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
    
    header("Location: $redirect?timeout=1");
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
            header("Location: $redirect?security=1");
            exit();
        }
    }
}
?>
