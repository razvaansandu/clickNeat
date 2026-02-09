<?php
function check_login_attempts($db, $identifier) {
    $ip = $_SERVER['REMOTE_ADDR'];
    
    $db->delete('login_attempts', 'attempt_time < DATE_SUB(NOW(), INTERVAL 15 MINUTE)');
    
    $sql = "SELECT COUNT(*) as attempts 
            FROM login_attempts 
            WHERE (email = ? OR ip_address = ?) 
            AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
    
    $row = $db->selectOne($sql, [$identifier, $ip]);
    
    return $row ? (int)$row['attempts'] : 0;
}

function record_failed_attempt($db, $identifier) {
    $ip = $_SERVER['REMOTE_ADDR'];
    
    $db->insert('login_attempts', [
        'email' => $identifier,
        'ip_address' => $ip
    ]);
}

function clear_login_attempts($db, $identifier) {
    $db->delete('login_attempts', 'email = ?', [$identifier]);
}
?>