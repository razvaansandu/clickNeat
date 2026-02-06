<?php
if(session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once "../../config/db.php";          
require_once "../../config/google_config.php";
require_once "../../models/User.php";      

if (isset($_GET['code'])) {
    
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
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
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

    $google_id = $google_account_info['sub'];
    $email = $google_account_info['email'];
    $name = $google_account_info['name'];

    $userModel = new User($db);

    $user = $userModel->findByEmail($email);

    if ($user) {
        if (empty($user['google_id'])) {
            $userModel->linkGoogleId($user['id'], $google_id);
        }

        $_SESSION["loggedin"] = true;
        $_SESSION["id"] = $user['id'];
        $_SESSION["username"] = $user['username'];
        $_SESSION["ruolo"] = $user['ruolo'];
        
    } else {
        $ruolo = 'consumatore';
        $password_fake = password_hash(bin2hex(random_bytes(10)), PASSWORD_DEFAULT);

        $new_user_id = $userModel->create($name, $email, $password_fake, $ruolo, $google_id);

        if ($new_user_id) {
            $_SESSION["loggedin"] = true;
            $_SESSION["id"] = $new_user_id;
            $_SESSION["username"] = $name;
            $_SESSION["ruolo"] = $ruolo;
        } else {
            die("Errore nella creazione dell'account.");
        }
    }
    
    if (isset($_SESSION['ruolo']) && $_SESSION['ruolo'] == 'ristoratore') {
        header("Location: ../ristoratore/dashboard_ristoratore.php");
    } else {
        header("Location: ../consumatore/dashboard_consumatore.php");
    }
    exit();

} else {
    header("Location: login.php");
    exit();
}
?>