<?php
define('GOOGLE_CLIENT_ID', '720927387722-ec3a3n7bmiqrp1svvi7ktsdnbluko418.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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'); 
define('GOOGLE_REDIRECT_URL', 'http://localhost:8000/google_callback.php');

function getGoogleLoginUrl() {
    $params = [
        'response_type' => 'code',
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => GOOGLE_REDIRECT_URL,
        'scope' => 'email profile',
        'access_type' => 'online',
        'prompt' => 'select_account'
    ];
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}
?>