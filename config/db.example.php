<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); 
ini_set('session.cookie_samesite', 'Strict');

session_start();

<<<<<<< HEAD
// Configurazione database (Default per Docker)
define('DB_SERVER', 'db');
define('DB_USERNAME', 'username');
define('DB_PASSWORD', 'password');
define('DB_NAME', 'name');
=======
define('DB_SERVER', 'db');           
define('DB_USERNAME', 'razvan_root');      
define('DB_PASSWORD', 'razvan123');   
define('DB_NAME', 'clickneat');      
>>>>>>> 2028e48c07c4d4c62e37e25fd59f24bfb479fd75

define('PASSWORD_MIN_LENGTH', 8);

$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if($link === false){
    die("ERRORE: Impossibile connettersi al database. " . mysqli_connect_error());
}

mysqli_set_charset($link, "utf8mb4");

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
    session_unset();
    session_destroy();
    
    $redirect = 'login_consumatore.php';
    if(isset($_SESSION['ruolo']) && $_SESSION['ruolo'] == 'ristoratore'){
        $redirect = 'login_ristoratore.php';
    }
    
    header("Location: ../public/$redirect?timeout=1");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

if (!isset($_SESSION['CREATED'])) {
    $_SESSION['CREATED'] = time();
} else if (time() - $_SESSION['CREATED'] > 600) {
    session_regenerate_id(true);
    $_SESSION['CREATED'] = time();
}

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