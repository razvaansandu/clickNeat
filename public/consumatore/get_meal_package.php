<?php
header("Content-Type: application/json");
require "config.php";

$user_id = 1;

$sql = "SELECT 
            ump.id,
            ump.user_id,
            ump.total_meals,
            ump.used_meals,
            ump.remaining_meals,
            ump.status,
            ump.expires_at,
            mp.name,
            mp.description
        FROM user_meal_packages ump
        JOIN meal_packages mp ON mp.id = ump.meal_package_id
        WHERE ump.user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$packages = [];

while($row = $result->fetch_assoc()) {
    $packages[] = $row;
}

echo json_encode($packages);
?> 