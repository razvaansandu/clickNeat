<?php
if(session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once "../../config/db.php";
require_once "../../config/google_config.php";
require_once "../../models/GoogleAuthModel.php";

if (!isset($_GET['code'])) {
    header("Location: login.php");
    exit();
}

$token_url = 'https://oauth2.googleapis.com/token';
$post_data = [
    'code'          => $_GET['code'],
    'client_id'     => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri'  => GOOGLE_REDIRECT_URL,
    'grant_type'    => 'authorization_code'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $token_url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$token_data = json_decode(curl_exec($ch), true);
curl_close($ch);

if (!isset($token_data['access_token'])) {
    die("Errore durante il login con Google (Token mancante).");
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/oauth2/v3/userinfo');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token_data['access_token']]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$google_account_info = json_decode(curl_exec($ch), true);
curl_close($ch);

$authModel = new GoogleAuthModel($db);

$user = $authModel->handleGoogleLogin(
    $google_account_info['sub'],
    $google_account_info['email'],
    $google_account_info['name']
);

if (!$user) {
    die("Errore nella creazione dell'account Google.");
}

session_regenerate_id(true);
$_SESSION["loggedin"]      = true;
$_SESSION["id"]            = $user['id'];
$_SESSION["username"]      = $user['username'];
$_SESSION["ruolo"]         = $user['ruolo'];
$_SESSION['USER_IP']       = $_SERVER['REMOTE_ADDR'];
$_SESSION['USER_AGENT']    = $_SERVER['HTTP_USER_AGENT'];
$_SESSION['LAST_ACTIVITY'] = time();
$_SESSION['CREATED']       = time();

if ($user['ruolo'] === 'ristoratore') {
    header("Location: ../ristoratore/dashboard_ristoratore.php");
} else {
    header("Location: ../consumatore/dashboard_consumatore.php");
}
exit();
?>
