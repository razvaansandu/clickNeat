<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once "../../config/db.php";
require_once "../../models/RistoranteRistoratoreModel.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["ruolo"] !== 'ristoratore') {
    header("location: ../auth/login.php");
    exit;
}

$restaurant_id = $_GET['id'] ?? null;

if (!$restaurant_id) {
    header("location: dashboard_ristoratore.php");
    exit;
}

$user_id = $_SESSION['id'];
$ristoranteModel = new RistoranteRistoratoreModel($db);
$restaurant = $ristoranteModel->getByIdAndOwner($restaurant_id, $user_id);

if (!$restaurant) {
    header("location: dashboard_ristoratore.php");
    exit;
}

// Carica categorie consentite
$categoriePath = "../../config/categorie.json";
$categorieSuggerite = [];
if (file_exists($categoriePath)) {
    $jsonContent = file_get_contents($categoriePath);
    $categorieSuggerite = json_decode($jsonContent, true) ?? [];
}

// Carica parole vietate
$badWords = [];
$badWordsPath = "../../config/cursed_words.json";
if (file_exists($badWordsPath)) {
    $jsonContent = file_get_contents($badWordsPath);
    $badWords = json_decode($jsonContent, true) ?? [];
}

$msg = "";
$msg_type = "";

if (isset($_POST['delete_restaurant'])) {
    if ($ristoranteModel->delete($restaurant_id)) {
        header("Location: dashboard_ristoratore.php");
        exit;
    } else {
        $msg = "Errore durante l'eliminazione del ristorante.";
        $msg_type = "error";
    }
}

if (isset($_POST['update_info'])) {
    $nome = trim($_POST['nome']);
    $indirizzo = trim($_POST['indirizzo']);
    $categoria = trim($_POST['categoria']); // Verrà dal campo hidden
    $descrizione = trim($_POST['descrizione']);

    // Protezione lunghezza
    if (strlen($categoria) > 100) { $categoria = substr($categoria, 0, 100); }

    $isForbidden = false;
    $testoCompleto = strtolower($nome . " " . $indirizzo . " " . $categoria . " " . $descrizione);

    foreach ($badWords as $word) {
        $wordPulita = strtolower(trim($word));
        if (!empty($wordPulita) && strpos($testoCompleto, $wordPulita) !== false) {
            $isForbidden = true;
            break;
        }
    }

    if ($isForbidden) {
        $msg = "Linguaggio inappropriato rilevato nei campi compilati.";
        $msg_type = "error";
    } elseif (!empty($nome) && !empty($indirizzo) && !empty($categoria)) {
        $data = [
            'nome' => $nome,
            'indirizzo' => $indirizzo,
            'categoria' => $categoria,
            'descrizione' => $descrizione
        ];

        if ($ristoranteModel->update($restaurant_id, $data)) {
            $msg = "Informazioni aggiornate con successo!";
            $msg_type = "success";
            $restaurant = $ristoranteModel->getByIdAndOwner($restaurant_id, $user_id);
        } else {
            $msg = "Errore durante l'aggiornamento.";
            $msg_type = "error";
        }
    }
}

if (isset($_POST['update_img'])) {
    if (isset($_FILES['restaurant_image']) && $_FILES['restaurant_image']['error'] === 0) {
        $upload_dir = '../assets/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $file_name = $_FILES['restaurant_image']['name'];
        $file_tmp = $_FILES['restaurant_image']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($file_ext, $allowed)) {
            $new_file_name = uniqid('rest_') . '.' . $file_ext;
            $dest_path = $upload_dir . $new_file_name;
            if (move_uploaded_file($file_tmp, $dest_path)) {
                $db_path = "/assets/" . $new_file_name;
                if ($ristoranteModel->update($restaurant_id, ['image_url' => $db_path])) {
                    $msg = "Immagine di vetrina aggiornata!";
                    $msg_type = "success";
                    $restaurant = $ristoranteModel->getByIdAndOwner($restaurant_id, $user_id);
                }
            }
        }
    } else {
        $msg = "Seleziona un file valido. Ricorda che il file non deve superare gli 8MB.";
        $msg_type = "error";
    }
}

$nome = $restaurant['nome'];
$indirizzo = $restaurant['indirizzo'];
$categoria = $restaurant['categoria'];
$descrizione = $restaurant['descrizione'] ?? '';
$vetrina_url = $restaurant['image_url'] ?? null;
$created_at = $restaurant['created_at'] ?? date("Y-m-d");
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifica Locale - <?php echo htmlspecialchars($nome); ?></title>
    <link rel="stylesheet" href="../../css/style_ristoratori.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Stili aggiuntivi solo per questa pagina (non modificano il CSS principale) */
        .category-search-container {
            position: relative;
            margin-bottom: 15px;
        }
        .search-results-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #E0E5F2;
            border-radius: 8px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .category-result-item {
            padding: 12px 15px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.2s;
        }
        .category-result-item:hover {
            background-color: #F4F7FE;
        }
        .selected-category-badge {
            background: #E6FAF5;
            border-radius: 30px;
            padding: 12px 18px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .btn-change-category {
            background: none;
            border: 1px solid #4318FF;
            color: #4318FF;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-change-category:hover {
            background: #4318FF;
            color: white;
        }
        .category-help {
            color: #A3AED0;
            font-size: 11px;
            margin-top: 5px;
            display: block;
            height: 15px;
        }
        .btn-save:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        /* Mantieni stili esistenti */
        .btn-delete-rest { background-color: #FFF0F0; color: #E31A1A; border: 1px solid #FFE0E0; padding: 12px 24px; border-radius: 12px; font-weight: 600; width: 100%; cursor: pointer; transition: all 0.3s ease; }
        .btn-delete-rest:hover { background-color: #E31A1A; color: #FFFFFF; }
        .textarea-custom { width: 100%; padding: 12px; border: 1px solid #E0E5F2; border-radius: 8px; font-family: inherit; resize: vertical; box-sizing: border-box; }
        .textarea-custom:focus { outline: none; border-color: #4318FF; }
    </style>
</head>
<body>

    <div class="mobile-header">  
        <button class="hamburger-btn"><i class="fa-solid fa-bars"></i></button>  
    </div>

    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <div style="margin-bottom: 20px;">
             <a href="manage_restaurant.php?id=<?php echo $restaurant_id; ?>" class="btn-cancel" style="text-decoration:none; color: #A3AED0; font-weight:500;">
                <i class="fa-solid fa-arrow-left"></i> Torna alla Gestione
            </a>
        </div>

        <?php if ($msg): ?>
            <div class="msg-box" style="padding: 15px; margin-bottom: 20px; border-radius: 10px; color: white; background-color: <?php echo $msg_type == 'success' ? '#05CD99' : '#E31A1A'; ?>;">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <div class="profile-wrapper">
            <div class="card-style avatar-box" style="text-align: center;">
                <div class="avatar-circle" style="background: #F4F7FE; color: #4318FF; width: 80px; height: 80px; line-height: 80px; border-radius: 50%; margin: 0 auto; font-size: 30px;">
                    <i class="fa-solid fa-shop"></i>
                </div>
                <h2 style="color: #2B3674; font-size: 20px; margin-top: 15px;"><?php echo htmlspecialchars($nome); ?></h2>
                <span class="status-badge active"><?php echo htmlspecialchars($categoria); ?></span>

                <div class="info-list" style="margin-top: 20px; text-align: left;">
                    <div class="info-row" style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>Aperto dal</span>
                        <b><?php echo date("d M Y", strtotime($created_at)); ?></b>
                    </div>
                    <div class="info-row" style="display: flex; justify-content: space-between;">
                        <span>Posizione</span>
                        <b style="font-size: 11px;"><?php echo htmlspecialchars($indirizzo); ?></b>
                    </div>
                </div>

                <form method="POST" onsubmit="return confirm('Sei sicuro di voler eliminare il ristorante?');" style="margin-top: 30px;">
                    <button type="submit" name="delete_restaurant" class="btn-delete-rest">
                        <i class="fa-solid fa-trash"></i> Elimina Ristorante
                    </button>
                </form>
            </div>

            <div class="card-style form-box">
                <div class="form-title" style="margin-bottom: 20px; font-weight:700; color:#2B3674; border-bottom:1px solid #eee; padding-bottom:10px;">Modifica Informazioni</div>
                
                <form method="POST" id="form-modifica-ristorante">
                    <div style="display: grid; grid-template-columns: 1fr; gap: 20px;">
                        <div class="input-group">
                            <label>Nome Ristorante</label>
                            <input type="text" name="nome" value="<?php echo htmlspecialchars($nome); ?>" required>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="input-group">
                                <label>Indirizzo</label>
                                <input type="text" name="indirizzo" value="<?php echo htmlspecialchars($indirizzo); ?>" required>
                            </div>
                            
                            <!-- NUOVO SELEZIONE CATEGORIA -->
                            <div class="input-group">
                                <label>Categoria <span style="color: #E31A1A;">*</span></label>
                                
                                <!-- Container per la ricerca (inizialmente visibile solo se nessuna categoria è selezionata) -->
                                <div class="category-search-container" id="category_search_container" style="<?php echo empty($categoria) ? 'display:block;' : 'display:none;'; ?>">
                                    <div class="input-wrapper" style="position: relative;">
                                        <i class="fa-solid fa-search"></i>
                                        <input type="text" 
                                               id="category_search" 
                                               placeholder="Cerca una categoria (es. Pizza, Pasta, Sushi...)" 
                                               autocomplete="off"
                                               style="padding-right: 30px;">
                                        <i class="fa-solid fa-times-circle" id="clear_search" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #A3AED0; cursor: pointer; display: none;"></i>
                                    </div>
                                    
                                    <!-- Dropdown risultati -->
                                    <div id="search_results" class="search-results-dropdown" style="display: none;"></div>
                                </div>

                                <!-- Categoria selezionata (visibile se già presente o dopo selezione) -->
                                <div id="selected_category_container" class="selected-category-badge" style="<?php echo empty($categoria) ? 'display:none;' : 'display:flex;'; ?>">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <i class="fa-solid fa-check-circle" style="color: #05CD99; font-size: 18px;"></i>
                                        <div>
                                            <div style="font-size: 12px; color: #2B3674; opacity: 0.7;">Categoria selezionata</div>
                                            <span id="selected_category_name" style="font-weight: 700; color: #2B3674; font-size: 16px;"><?php echo htmlspecialchars($categoria); ?></span>
                                        </div>
                                    </div>
                                    <button type="button" id="change_category_btn" class="btn-change-category">
                                        <i class="fa-solid fa-pen"></i> Cambia
                                    </button>
                                </div>

                                <!-- Campo hidden per inviare la categoria -->
                                <input type="hidden" name="categoria" id="selected_category_hidden" value="<?php echo htmlspecialchars($categoria); ?>" required>

                                <small id="category_help" class="category-help">
                                    <?php if (empty($categoria)): ?>
                                        <i class="fa-solid fa-info-circle"></i> Cerca e seleziona una categoria dall'elenco
                                    <?php else: ?>
                                        <i class="fa-solid fa-check-circle" style="color: #05CD99;"></i> Categoria già selezionata
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>

                        <div class="input-group">
                            <label>Descrizione</label>
                            <textarea name="descrizione" rows="4" class="textarea-custom" required><?php echo htmlspecialchars($descrizione); ?></textarea>
                        </div>
                    </div>
                    
                    <div style="text-align: right; margin-top: 20px;">
                        <button type="submit" name="update_info" class="btn-save" id="save-info-btn">Salva Info</button>
                    </div>
                </form>

                <div style="margin: 40px 0; border-top: 1px solid #eee;"></div>

                <div class="form-title" style="margin-bottom: 20px; font-weight:700; color:#2B3674; border-bottom:1px solid #eee; padding-bottom:10px;">Immagine di Vetrina</div>
                
                <?php if($vetrina_url): ?>
                    <div style="width: 100%; height: 200px; border-radius: 10px; overflow: hidden; margin-bottom: 20px; border: 1px solid #eee;">
                        <img src="<?php echo htmlspecialchars($vetrina_url); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="input-group">
                        <label>Carica Nuova Foto</label>
                        <input type="file" name="restaurant_image" accept="image/*" required>
                    </div>
                    <div style="text-align: right; margin-top: 20px;">
                        <button type="submit" name="update_img" class="btn-save" style="background-color: #2B3674;">Aggiorna Vetrina</button>
                    </div>
                </form> 
            </div>
        </div>
    </div> 

    <script>
        // Passa i dati dal PHP al JavaScript
        const foodCategories = <?php echo json_encode($categorieSuggerite); ?>;
        const badWords = <?php echo json_encode($badWords); ?>;
        const currentCategory = <?php echo json_encode($categoria); ?>;

        // Variabili globali
        let searchContainer, selectedContainer, searchInput, resultsDiv, clearBtn, selectedName, selectedHidden, changeBtn, categoryHelp, saveBtn;

        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar functionality
            const sidebar = document.querySelector('.sidebar');
            const hamburger = document.querySelector('.hamburger-btn');
            const closeBtn = document.getElementById('closeSidebarBtn');
            let overlay = document.querySelector('.sidebar-overlay') || document.createElement('div');
            
            if (!overlay.parentElement) {
                overlay.classList.add('sidebar-overlay');
                document.body.appendChild(overlay);
            }

            hamburger.addEventListener('click', () => { sidebar.classList.add('active'); overlay.classList.add('active'); });
            [closeBtn, overlay].forEach(el => el && el.addEventListener('click', () => { sidebar.classList.remove('active'); overlay.classList.remove('active'); }));

            // Inizializza il selettore categorie
            initCategorySelector();
        });

        function initCategorySelector() {
            searchContainer = document.getElementById('category_search_container');
            selectedContainer = document.getElementById('selected_category_container');
            searchInput = document.getElementById('category_search');
            resultsDiv = document.getElementById('search_results');
            clearBtn = document.getElementById('clear_search');
            selectedName = document.getElementById('selected_category_name');
            selectedHidden = document.getElementById('selected_category_hidden');
            changeBtn = document.getElementById('change_category_btn');
            categoryHelp = document.getElementById('category_help');
            saveBtn = document.getElementById('save-info-btn');

            // Se esiste già una categoria, disabilita il pulsante salva se contiene parole vietate
            if (currentCategory) {
                const found = checkBadWordsInString(currentCategory);
                if (found) {
                    saveBtn.disabled = true;
                    categoryHelp.innerHTML = "<i class='fa-solid fa-ban' style='color: #ea4335;'></i> <span style='color: #ea4335;'>Termine non consentito nella categoria attuale</span>";
                } else {
                    saveBtn.disabled = false;
                }
            } else {
                // Nessuna categoria iniziale: pulsante disabilitato
                saveBtn.disabled = true;
                saveBtn.style.opacity = '0.5';
            }

            // Gestione input ricerca
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.trim().toLowerCase();
                    
                    if (searchTerm.length > 0) {
                        clearBtn.style.display = 'block';
                    } else {
                        clearBtn.style.display = 'none';
                        resultsDiv.style.display = 'none';
                        return;
                    }
                    
                    if (searchTerm.length >= 1) {
                        const matches = foodCategories.filter(cat => 
                            cat.toLowerCase().includes(searchTerm)
                        ).slice(0, 10);
                        
                        if (matches.length > 0) {
                            showSearchResults(matches, searchTerm);
                        } else {
                            resultsDiv.innerHTML = `
                                <div style="padding: 20px; text-align: center; color: #A3AED0;">
                                    <i class="fa-solid fa-search" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>
                                    Nessuna categoria trovata per "${searchTerm}"
                                </div>
                            `;
                            resultsDiv.style.display = 'block';
                        }
                    } else {
                        resultsDiv.style.display = 'none';
                    }
                });

                // Tasto Enter
                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const firstResult = document.querySelector('.category-result-item');
                        if (firstResult) {
                            firstResult.click();
                        }
                    }
                });
            }

            // Pulsante clear
            if (clearBtn) {
                clearBtn.addEventListener('click', function() {
                    searchInput.value = '';
                    resultsDiv.style.display = 'none';
                    clearBtn.style.display = 'none';
                    searchInput.focus();
                });
            }

            // Pulsante Cambia categoria
            if (changeBtn) {
                changeBtn.addEventListener('click', function() {
                    // Nascondi il badge selezionato e mostra la ricerca
                    selectedContainer.style.display = 'none';
                    searchContainer.style.display = 'block';
                    selectedHidden.value = '';
                    searchInput.value = '';
                    searchInput.focus();
                    categoryHelp.innerHTML = '<i class="fa-solid fa-info-circle"></i> Cerca e seleziona una categoria dall\'elenco';
                    categoryHelp.style.color = '#A3AED0';
                    saveBtn.disabled = true;
                    saveBtn.style.opacity = '0.5';
                });
            }

            // Click fuori dal dropdown
            document.addEventListener('click', function(e) {
                if (searchInput && !searchInput.contains(e.target) && resultsDiv && !resultsDiv.contains(e.target)) {
                    resultsDiv.style.display = 'none';
                }
            });

            function showSearchResults(matches, searchTerm) {
                let html = '<div style="padding: 8px 0;">';
                
                matches.forEach(cat => {
                    const highlighted = cat.replace(new RegExp(searchTerm, 'gi'), match => `<strong style="color: #4318FF;">${match}</strong>`);
                    html += `
                        <div class="category-result-item" data-category="${cat}">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <i class="fa-solid fa-utensils" style="color: #4318FF; width: 20px;"></i>
                                <span style="flex: 1;">${highlighted}</span>
                                <i class="fa-solid fa-chevron-right" style="color: #A3AED0; font-size: 12px;"></i>
                            </div>
                        </div>
                    `;
                });
                
                html += '</div>';
                resultsDiv.innerHTML = html;
                resultsDiv.style.display = 'block';
                
                document.querySelectorAll('.category-result-item').forEach(item => {
                    item.addEventListener('click', function() {
                        const category = this.getAttribute('data-category');
                        selectCategory(category);
                    });
                });
            }

            function selectCategory(category) {
                selectedName.textContent = category;
                selectedHidden.value = category;
                
                // Mostra il badge e nasconde la ricerca
                selectedContainer.style.display = 'flex';
                searchContainer.style.display = 'none';
                
                // Resetta ricerca
                if (searchInput) {
                    searchInput.value = '';
                    resultsDiv.style.display = 'none';
                    if (clearBtn) clearBtn.style.display = 'none';
                }
                
                // Aggiorna messaggio aiuto
                categoryHelp.innerHTML = '<i class="fa-solid fa-check-circle" style="color: #05CD99;"></i> Categoria selezionata correttamente';
                categoryHelp.style.color = '#05CD99';
                
                // Controllo parole vietate
                const found = checkBadWordsInString(category);
                if (found) {
                    categoryHelp.innerHTML = "<i class='fa-solid fa-ban' style='color: #ea4335;'></i> <span style='color: #ea4335;'>Termine non consentito</span>";
                    saveBtn.disabled = true;
                    saveBtn.style.opacity = '0.5';
                } else {
                    saveBtn.disabled = false;
                    saveBtn.style.opacity = '1';
                }
            }

            function checkBadWordsInString(text) {
                return badWords.some(word => {
                    const regex = new RegExp("\\b" + word + "\\b", "i");
                    return regex.test(text);
                });
            }
        }

        // Validazione form prima dell'invio
        document.getElementById('form-modifica-ristorante').addEventListener('submit', function(e) {
            const categoriaHidden = document.getElementById('selected_category_hidden');
            if (!categoriaHidden.value) {
                e.preventDefault();
                alert('Per favore, seleziona una categoria per il ristorante.');
                
                // Evidenzia il campo di ricerca se visibile
                const searchInput = document.getElementById('category_search');
                if (searchInput && searchInput.offsetParent !== null) {
                    searchInput.style.borderColor = '#E31A1A';
                    setTimeout(() => {
                        searchInput.style.borderColor = '#d1d9e2';
                    }, 3000);
                }
            }
        });
    </script>
</body>
</html>