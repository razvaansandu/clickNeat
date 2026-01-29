<?php

require_once "../config/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["ruolo"] !== 'ristoratore') {
    header("location: login_ristoratore.php");
    exit;
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $nome = trim($_POST["nome"]);
    $indirizzo = trim($_POST["indirizzo"]);
    $descrizione = trim($_POST["descrizione"]);
    $proprietario_id = $_SESSION["id"];
    $logo_path = NULL;

    if (empty($nome) || empty($indirizzo)) {
        $error = "Per favore, inserisci almeno il nome e l'indirizzo del locale.";
    } else {
        // Gestisci l'upload dell'immagine
        if (isset($_FILES["logo_ristorante"]) && $_FILES["logo_ristorante"]["size"] > 0) {
            $nome_file = basename($_FILES["logo_ristorante"]["name"]);
            $percorso_temporaneo = $_FILES["logo_ristorante"]["tmp_name"];
            
            // Validazione del file
            $tipi_ammessi = array("image/jpeg", "image/png", "image/gif", "image/webp");
            $tipo_file = mime_content_type($percorso_temporaneo);
            $dimensione_max = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($tipo_file, $tipi_ammessi)) {
                $error = "Solo file immagine sono ammessi (JPEG, PNG, GIF, WebP).";
            } elseif ($_FILES["logo_ristorante"]["size"] > $dimensione_max) {
                $error = "L'immagine è troppo grande. Massimo 5MB.";
            } else {
                // Crea cartella se non esiste
                if (!is_dir("image/restaurants")) {
                    mkdir("image/restaurants", 0755, true);
                }
                
                // Salva il file con nome univoco
                $estensione = pathinfo($nome_file, PATHINFO_EXTENSION);
                $nome_file_univoco = time() . "_" . uniqid() . "." . $estensione;
                $cartella_destinazione = "image/restaurants/" . $nome_file_univoco;
                
                if (move_uploaded_file($percorso_temporaneo, $cartella_destinazione)) {
                    $logo_path = $cartella_destinazione;
                } else {
                    $error = "Errore nel caricamento dell'immagine. Riprova.";
                }
            }
        }
        
        // Se non c'è errore, procedi con l'inserimento nel database
        if (empty($error)) {
            $sql = "INSERT INTO ristoranti (proprietario_id, nome, indirizzo, descrizione, img) VALUES (?, ?, ?, ?, ?)";
            
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "issss", $proprietario_id, $nome, $indirizzo, $descrizione, $logo_path);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success = "Ristorante creato con successo! Verrai reindirizzato...";
                    header("refresh:2;url=dashboard_ristoratore.php");
                } else {
                    $error = "Qualcosa è andato storto. Riprova più tardi.";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Crea Ristorante - ClickNeat</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: #F4F7FE; min-height: 100vh; }
        
        .main-content { margin-left: 260px; padding: 40px; display: flex; justify-content: center; }
        
        .form-card {
            background: white;
            width: 100%;
            max-width: 600px; 
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 18px 40px rgba(112, 144, 176, 0.12);
            margin-top: 20px;
        }

        .header-title {
            text-align: center;
            margin-bottom: 30px;
        }
        .header-title h1 { color: #1B2559; font-size: 24px; font-weight: 700; }
        .header-title p { color: #A3AED0; margin-top: 5px; font-size: 14px; }

        .form-group { margin-bottom: 25px; }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2B3674;
            font-weight: 600;
            font-size: 14px;
        }
        
        input[type="text"], textarea {
            width: 100%;
            padding: 14px 20px;
            border: 1px solid #E0E5F2;
            border-radius: 15px;
            font-size: 15px;
            color: #1B2559;
            background-color: #fff;
            transition: all 0.3s ease;
        }

        input[type="text"]:focus, textarea:focus {
            border-color: #1A4D4E;
            outline: none;
            box-shadow: 0 0 0 4px rgba(26, 77, 78, 0.1);
        }

        textarea {
            resize: vertical; 
            min-height: 100px;
        }

        .btn-submit {
            width: 100%;
            padding: 15px;
            background-color: #1A4D4E; 
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            box-shadow: 0 4px 10px rgba(26, 77, 78, 0.2);
        }
        .btn-submit:hover {
            background-color: #E89020; 
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(232, 144, 32, 0.3);
        }

        .btn-back {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #A3AED0;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: 0.3s;
        }
        .btn-back:hover { color: #1B2559; }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 14px;
            font-weight: 500;
            text-align: center;
        }
        .alert-error { background-color: #FFF5F5; color: #C53030; border: 1px solid #FEB2B2; }
        .alert-success { background-color: #E6FFFA; color: #1A4D4E; border: 1px solid #B2F5EA; }

        .icon-top {
            width: 60px;
            height: 60px;
            background: #F4F7FE;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: #E89020;
            font-size: 24px;
        }

        .image-preview-container {
            margin-top: 15px;
            text-align: center;
            display: none;
        }

        .image-preview-container.show {
            display: block;
        }

        .image-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(26, 77, 78, 0.2);
            margin: 0 auto;
        }

    </style>
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="form-card">
            <div class="header-title">
                <div class="icon-top"><i class="fa-solid fa-shop"></i></div>
                <h1>Nuovo Ristorante</h1>
                <p>Inserisci i dettagli per iniziare a ricevere ordini.</p>
            </div>

            <?php if($error): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if($success): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form action="create_restaurant.php" method="POST">
                <div class="form-group">
                    <label for="nome">Nome del Locale *</label>
                    <input type="text" id="nome" name="nome" placeholder="Es. Pizzeria Bella Napoli" required>
                </div>

                <div class="form-group">
                    <label for="indirizzo">Indirizzo Completo *</label>
                    <input type="text" id="indirizzo" name="indirizzo" placeholder="Via Roma 123, Milano" required>
                </div>

                <div class="form-group">
                    <label for="descrizione">Descrizione (Opzionale)</label>
                    <textarea id="descrizione" name="descrizione" placeholder="Raccontaci brevemente la tua cucina... (es. Specialità pesce fresco)"></textarea>
                </div>

                <div class="form-group">
                    <label for="logo_ristorante">Logo/Immagine Ristorante (Opzionale)</label>
                    <input type="file" id="logo_ristorante" name="logo_ristorante" accept="image/jpeg,image/png,image/gif,image/webp">
                    <small style="color: #A3AED0; margin-top: 5px; display: block;">Max 5MB. Formati: JPEG, PNG, GIF, WebP</small>
                    <div class="image-preview-container" id="previewContainer">
                        <img id="imagePreview" class="image-preview" alt="Anteprima immagine">
                    </div>
                </div>

                <button type="submit" class="btn-submit">Crea Ristorante</button>
                
            </form>

            <a href="dashboard_ristoratore.php" class="btn-back">
                <i class="fa-solid fa-arrow-left"></i> Annulla e torna alla Dashboard
            </a>
        </div>

    </div>

    <script>
        // Preview immagine quando viene selezionata
        document.getElementById('logo_ristorante').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const previewContainer = document.getElementById('previewContainer');
            const imagePreview = document.getElementById('imagePreview');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    imagePreview.src = event.target.result;
                    previewContainer.classList.add('show');
                };
                reader.readAsDataURL(file);
            } else {
                previewContainer.classList.remove('show');
            }
        });
    </script>

</body>
</html>