<?php
require_once "../config/db.php";
// session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["ruolo"]) || $_SESSION["ruolo"] !== "consumatore"){
    header("Location: login_consumatore.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Consumatore - ClickNeat</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/consumatore.css?v=1.2">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="main-container">
        <div class="container">
            <!-- Header con benvenuto -->
            <div class="dashboard-header">
                <div class="header-content">
                    <div class="welcome-section">
                        <h1><i class="fas fa-user-circle"></i> Benvenuto, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h1>
                        <p class="subtitle">Scegli tra i migliori ristoranti della tua zona</p>
                    </div>
                    <div class="header-actions">
                        <a href="ordini.php" class="header-btn">
                            <i class="fas fa-history"></i>
                            <span>I tuoi ordini</span>
                        </a>
                        <a href="profile_consumatore.php" class="header-btn">
                            <i class="fas fa-user-cog"></i>
                            <span>Profilo</span>
                        </a>
                        <a href="logout.php" class="btn-logout">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Esci</span>
                        </a>
                    </div>
                </div>
                
                <!-- Statistiche rapide -->
                <div class="stats-cards">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Ristoranti disponibili</h3>
                            <?php
                            $count_sql = "SELECT COUNT(*) as total FROM ristoranti";
                            $count_result = mysqli_query($link, $count_sql);
                            $count_row = mysqli_fetch_assoc($count_result);
                            ?>
                            <p class="stat-number"><?php echo $count_row['total']; ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-shipping-fast"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Consegna media</h3>
                            <p class="stat-number">30-40 min</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Ristoranti top</h3>
                            <p class="stat-number">15+</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sezione ristoranti -->
            <div class="ristoranti-section">
                <div class="section-title-bar">
                    <h2><i class="fas fa-store"></i> Ristoranti Disponibili</h2>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Cerca ristorante...">
                    </div>
                </div>

                <?php
                $sql = "SELECT id, nome, indirizzo, descrizione FROM ristoranti ORDER BY nome ASC";
                $result = mysqli_query($link, $sql);
                
                if($result && mysqli_num_rows($result) > 0):
                ?>
                <div class="ristoranti-grid" id="ristorantiGrid">
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <div class="cardRistorante" data-name="<?php echo strtolower(htmlspecialchars($row['nome'])); ?>" 
                                                data-address="<?php echo strtolower(htmlspecialchars($row['indirizzo'])); ?>"
                                                data-desc="<?php echo strtolower(htmlspecialchars($row['descrizione'])); ?>">
                        <div class="card-header">
                            <div class="restaurant-icon">
                                <i class="fas fa-utensils"></i>
                            </div>
                            <h3><?php echo htmlspecialchars($row['nome']); ?></h3>
                        </div>
                        
                        <div class="card-body">
                            <div class="info-row">
                                <i class="fas fa-map-marker-alt"></i>
                                <div class="info-content">
                                    <span class="info-label">Indirizzo:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($row['indirizzo']); ?></span>
                                </div>
                            </div>
                            
                            <div class="info-row">
                                <i class="fas fa-info-circle"></i>
                                <div class="info-content">
                                    <span class="info-label">Descrizione:</span>
                                    <p class="info-value description"><?php echo htmlspecialchars($row['descrizione']); ?></p>
                                </div>
                            </div>
                            
                            <div class="restaurant-tags">
                                <span class="tag">Ristorante</span>
                                <span class="tag">Consegna</span>
                                <?php if(strlen($row['descrizione']) < 50): ?>
                                <span class="tag tag-popular">Popolare</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="card-footer">
                            <a href="menu.php?ristorante_id=<?php echo $row['id']; ?>" class="btn-menu">
                                <i class="fas fa-book-open"></i>
                                Visualizza Menu 
                            </a>
                            <button class="btn-favorite" title="Aggiungi ai preferiti">
                                <i class="far fa-heart"></i>
                            </button>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                
                <div class="no-results" id="noResults" style="display: none;">
                    <div class="empty-state">
                        <i class="fas fa-search fa-3x"></i>
                        <h3>Nessun ristorante trovato</h3>
                        <p>Prova a modificare i termini di ricerca</p>
                    </div>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-utensils fa-3x"></i>
                    <h3>Nessun ristorante disponibile</h3>
                    <p>Al momento non ci sono ristoranti attivi nella tua zona.</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Footer della dashboard -->
            <div class="dashboard-footer">
                <div class="footer-content">
                    <div class="footer-info">
                        <h3><i class="fas fa-info-circle"></i> Come funziona</h3>
                        <div class="steps">
                            <div class="step">
                                <span class="step-number">1</span>
                                <p>Scegli il ristorante</p>
                            </div>
                            <div class="step">
                                <span class="step-number">2</span>
                                <p>Seleziona i piatti</p>
                            </div>
                            <div class="step">
                                <span class="step-number">3</span>
                                <p>Ricevi a domicilio</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="footer-actions">
                        <a href="supporto.php" class="footer-link">
                            <i class="fas fa-headset"></i> Supporto
                        </a>
                        <a href="faq.php" class="footer-link">
                            <i class="fas fa-question-circle"></i> FAQ
                        </a>
                        <a href="termini.php" class="footer-link">
                            <i class="fas fa-file-contract"></i> Termini
                        </a>
                    </div>
                </div>
                
                <div class="copyright">
                    <p>&copy; 2024 ClickNeat. Tutti i diritti riservati.</p>
                    <p class="version">v1.0</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Script semplice per la ricerca -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const ristorantiGrid = document.getElementById('ristorantiGrid');
        const cards = document.querySelectorAll('.cardRistorante');
        const noResults = document.getElementById('noResults');
        
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                let visibleCount = 0;
                
                cards.forEach(card => {
                    const name = card.getAttribute('data-name');
                    const address = card.getAttribute('data-address');
                    const desc = card.getAttribute('data-desc');
                    
                    const matches = name.includes(searchTerm) || 
                                   address.includes(searchTerm) || 
                                   desc.includes(searchTerm);
                    
                    if (matches) {
                        card.style.display = 'block';
                        visibleCount++;
                        setTimeout(() => {
                            card.style.opacity = '1';
                            card.style.transform = 'translateY(0)';
                        }, 10);
                    } else {
                        card.style.opacity = '0';
                        card.style.transform = 'translateY(10px)';
                        setTimeout(() => {
                            card.style.display = 'none';
                        }, 300);
                    }
                });
                
                // Mostra/Nascondi messaggio "nessun risultato"
                if (visibleCount === 0 && searchTerm !== '') {
                    noResults.style.display = 'block';
                    ristorantiGrid.style.display = 'none';
                } else {
                    noResults.style.display = 'none';
                    ristorantiGrid.style.display = 'grid';
                }
            });
        }
        
        // Aggiungi ai preferiti
        const favoriteButtons = document.querySelectorAll('.btn-favorite');
        favoriteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const icon = this.querySelector('i');
                if (icon.classList.contains('far')) {
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                    icon.style.color = '#FF6B6B';
                    this.style.transform = 'scale(1.2)';
                    setTimeout(() => {
                        this.style.transform = 'scale(1)';
                    }, 300);
                } else {
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                    icon.style.color = '';
                }
            });
        });
        
        // Click su card (esclusi bottoni)
        cards.forEach(card => {
            card.addEventListener('click', function(e) {
                if (!e.target.closest('.btn-menu') && !e.target.closest('.btn-favorite')) {
                    const menuLink = this.querySelector('.btn-menu');
                    if (menuLink) {
                        window.location.href = menuLink.href;
                    }
                }
            });
        });
    });
    </script>
</body>
</html> 