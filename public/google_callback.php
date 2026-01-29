<?php
session_start();
require_once "../config/db.php";
require_once "../config/google_config.php";

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

    $google_id = $google_account_info['sub']; // ID univoco Google
    $email = $google_account_info['email'];
    $name = $google_account_info['name'];

    $sql = "SELECT id, username, ruolo, google_id FROM users WHERE email = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = $result->fetch_assoc();
        mysqli_stmt_close($stmt);

        if ($user) {
            if (empty($user['google_id'])) {
                $upd = "UPDATE users SET google_id = ?, email_verified = 1 WHERE id = ?";
                $stmt_upd = mysqli_prepare($link, $upd);
                mysqli_stmt_bind_param($stmt_upd, "si", $google_id, $user['id']);
                mysqli_stmt_execute($stmt_upd);
            }
            $_SESSION["loggedin"] = true;
            $_SESSION["id"] = $user['id'];
            $_SESSION["username"] = $user['username'];
            $_SESSION["ruolo"] = $user['ruolo'];
            
        } else {
            $ruolo = 'consumatore';
            $password_fake = password_hash(bin2hex(random_bytes(10)), PASSWORD_DEFAULT);
            $verified = 1;

            $ins = "INSERT INTO users (username, email, password, ruolo, email_verified, google_id) VALUES (?, ?, ?, ?, ?, ?)";
            if ($stmt_ins = mysqli_prepare($link, $ins)) {
                mysqli_stmt_bind_param($stmt_ins, "ssssis", $name, $email, $password_fake, $ruolo, $verified, $google_id);
                if (mysqli_stmt_execute($stmt_ins)) {
                    $_SESSION["loggedin"] = true;
                    $_SESSION["id"] = mysqli_insert_id($link);
                    $_SESSION["username"] = $name;
                    $_SESSION["ruolo"] = $ruolo;
                }
            }
        }
        
        if (isset($_SESSION['ruolo']) && $_SESSION['ruolo'] == 'ristoratore') {
            header("Location: dashboard_ristoratore.php");
        } else {
            header("Location: dashboard_consumatore.php");
        }
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?>