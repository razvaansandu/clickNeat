<?php
require_once "../config/db.php";

$message = "";

function executeQuery($link, $sql, $description) {
    if (mysqli_query($link, $sql)) {
        return "<div class='success'>$description creato con successo (o già esistente).</div>";
    } else {
        return "<div class='error'>Errore in $description: " . mysqli_error($link) . "</div>";
    }
}

$sql_users = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    ruolo ENUM('consumatore', 'ristoratore') NOT NULL,
    email_verified TINYINT(1) DEFAULT 0,
    email_verify_token VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$sql_ristoranti = "CREATE TABLE IF NOT EXISTS ristoranti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proprietario_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    indirizzo VARCHAR(255) NOT NULL,
    descrizione TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (proprietario_id) REFERENCES users(id) ON DELETE CASCADE
)";

$sql_menu = "CREATE TABLE IF NOT EXISTS menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES ristoranti(id) ON DELETE CASCADE
)";

$sql_orders = "CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    restaurant_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'accepted', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (restaurant_id) REFERENCES ristoranti(id)
)";

$sql_order_items = "CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    dish_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price_at_time DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (dish_id) REFERENCES menu_items(id)
)";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $message .= executeQuery($link, $sql_users, "Tabella Utenti");
    $message .= executeQuery($link, $sql_ristoranti, "Tabella Ristoranti");
    $message .= executeQuery($link, $sql_menu, "Tabella Menu");
    $message .= executeQuery($link, $sql_orders, "Tabella Ordini");
    $message .= executeQuery($link, $sql_order_items, "Tabella Dettagli Ordini");
    $message .= "<br><strong>🎉 Database configurato correttamente!</strong>";
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Setup Database - ClickNeat</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #F4F7FE; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 100%; max-width: 500px; text-align: center; }
        h1 { color: #1A4D4E; margin-bottom: 20px; }
        p { color: #666; margin-bottom: 30px; }
        button { background: #E89020; color: white; border: none; padding: 15px 30px; font-size: 16px; border-radius: 10px; cursor: pointer; font-weight: bold; width: 100%; transition: 0.3s; }
        button:hover { background: #d67e10; transform: translateY(-2px); }
        .success { color: green; background: #e8f5e9; padding: 10px; border-radius: 5px; margin-bottom: 5px; text-align: left; }
        .error { color: red; background: #ffebee; padding: 10px; border-radius: 5px; margin-bottom: 5px; text-align: left; }
        .back-link { display: block; margin-top: 20px; color: #1A4D4E; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="card">
        <h1>⚙️ Database Setup</h1>
        
        <?php if ($message): ?>
            <div style="margin-bottom: 20px;">
                <?php echo $message; ?>
            </div>
            <a href="login_ristoratore.php" class="back-link">Vai al Login Ristoratore →</a>
        <?php else: ?>
            <p>Clicca il pulsante qui sotto per creare automaticamente tutte le tabelle necessarie per ClickNeat.</p>
            <form method="POST">
                <button type="submit">Installa Database</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>