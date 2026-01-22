<?php
function check_login_attempts($link, $identifier) {
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // Pulisci tentativi vecchi (oltre 15 minuti)
    $sql = "DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
    mysqli_query($link, $sql);
    
    // Conta tentativi ultimi 15 minuti
    $sql = "SELECT COUNT(*) as attempts FROM login_attempts 
            WHERE (email = ? OR ip_address = ?) 
            AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
    
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "ss", $identifier, $ip);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        return $row['attempts'];
    }
    return 0;
}

function record_failed_attempt($link, $identifier) {
    $ip = $_SERVER['REMOTE_ADDR'];
    
    $sql = "INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "ss", $identifier, $ip);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

function clear_login_attempts($link, $identifier) {
    $sql = "DELETE FROM login_attempts WHERE email = ?";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "s", $identifier);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}
?>
