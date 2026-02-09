<?php
if(session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once "../../config/db.php";
require_once "../../config/google_config.php";
require_once "../../models/auth/GoogleAuthModel.php"; 

if (!isset($_GET['code'])) {
    header("Location: login.php");
    exit();
}

$token_url = 'https://oauth2.googleapis.com/token';
$post_data = [
    'code' => $_GET['code'],
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => GOOGLE_REDIRECT_URL,
    'grant_type' => 'authorization_code'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $token_url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);

$token_data = json_decode($response, true);

if (!isset($token_data['access_token'])) {
    die("Errore durante il login con Google (Token mancante).");
}

$access_token = $token_data['access_token'];

$info_url = 'https://www.googleapis.com/oauth2/v3/userinfo';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $info_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$info_response = curl_exec($ch);
curl_close($ch);

$google_account_info = json_decode($info_response, true);

$email = $google_account_info['email'];
$google_id = $google_account_info['sub'];
$name = $google_account_info['name'];

$authModel = new GoogleAuthModel($db);

$user = $authModel->findByGoogleId($google_id);

if (!$user) {
    $user = $authModel->findByUsernameOrEmail($email);
    
    if ($user) {
        $authModel->linkGoogleId($user['id'], $google_id);
        $user = $authModel->findByGoogleId($google_id);
    }
}

if (!$user) {
    $new_user_id = $authModel->createFromGoogle($email, $name, $google_id, 'consumatore');
    
    if ($new_user_id) {
        $user = $authModel->findByGoogleId($google_id);
    } else {
        die("Errore nella creazione dell'account Google.");
    }
}

session_regenerate_id(true);
$_SESSION["loggedin"] = true;
$_SESSION["id"] = $user['id'];
$_SESSION["username"] = $user['username'];
$_SESSION["ruolo"] = $user['ruolo'];

$_SESSION['USER_IP'] = $_SERVER['REMOTE_ADDR'];
$_SESSION['USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];
$_SESSION['LAST_ACTIVITY'] = time();
$_SESSION['CREATED'] = time();

if ($user['ruolo'] === 'ristoratore') {
    header("Location: ../ristoratore/dashboard_ristoratore.php");
} else {
    header("Location: ../consumatore/dashboard_consumatore.php");
}
exit();
?>