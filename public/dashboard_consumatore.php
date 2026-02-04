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
    <style>
        body {
            background-image: none !important;
            background-color: #ffffff !important;
            padding: 0;
        }

        .top-menu {
            background: white;
            padding: 16px 40px;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(43, 54, 116, 0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin: 20px 40px;
            flex-wrap: wrap;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .top-menu .lead {
            color: #1A4D4E;
            font-weight: 700;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .top-menu .lead:hover {
            color: #E89020;
            transform: translateY(-2px);
        }

        .menu-links {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .menu-links a {
            background: #F4F7FE;
            color: #1A4D4E;
            padding: 10px 16px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .menu-links a:hover {
            background: #1A4D4E;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(26, 77, 78, 0.15);
        }

        .menu-links a.logout {
            background: #E89020;
            color: white;
        }

        .menu-links a.logout:hover {
            background: #D67A0C;
        }

        @media (max-width: 768px) {
            .top-menu {
                margin: 20px;
                padding: 12px 20px;
            }

            .top-menu .lead {
                font-size: 18px;
            }

            .menu-links a {
                padding: 8px 12px;
                font-size: 12px;
            }
        }

        .search-wrapper {
            display: flex;
            gap: 12px;
            align-items: center;
            max-width: 620px;
            width: 100%;
        }

        .search-input-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search-wrapper img.search-icon {
            width: 220px;
            height: 220px;
            border-radius: 18px;
            object-fit: cover;
            box-shadow: 0 12px 40px rgba(26, 77, 78, 0.14);
            flex-shrink: 0;
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .search-wrapper img.search-icon:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 18px 50px rgba(26, 77, 78, 0.18);
        }

        @media (max-width: 1400px) {
            .search-wrapper img.search-icon {
                width: 200px;
                height: 200px;
            }
        }

        @media (max-width: 1024px) {
            .search-wrapper img.search-icon {
                width: 160px;
                height: 160px;
            }
        }

        @media (max-width: 768px) {
            .search-wrapper img.search-icon {
                width: 140px;
                height: 140px;
            }
        }

        @media (max-width: 480px) {
            .search-wrapper img.search-icon {
                width: 110px;
                height: 110px;
                margin-left: auto;
            }

            .search-wrapper {
                gap: 8px;
                align-items: center;
            }

            .search-submit-btn {
                padding: 8px 10px;
                font-size: 14px;
            }
        }

        :root { --accent-orange: #ff6a00; }
        .hero {
            display: flex;
            align-items: center;
            gap: 24px;
            margin: 20px 40px;
            min-height: 420px;
            justify-content: space-between;
        }

        .hero-left {
            flex: 1 1 54%;
            max-width: 54%;
            padding-right: 24px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .search-wrapper { margin-top: 18px; }

        .hero-right {
            --bg-shift: 35%;
            flex: 1 1 42%;
            min-width: 320px;
            background: transparent;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            position: relative;
            overflow: visible;
            padding: 25px 0 25px 2px;
            color: white;
        }

        .hero-right::before {
            content: "";
            position: absolute;
            right: -10%;
            top: -20%;
            width: 135%;
            height: 150%;
            background: linear-gradient(120deg, var(--accent-orange) 0%, #ff7a1a 100%);
            transform: translateX(var(--bg-shift, 5%)) skewX(-10deg);
            transform-origin: top right;
            z-index: 0;
            border-radius: 12px;
        }

        .promo-wrap {
            position: relative;
            z-index: 1;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 260px;
        }

        .promo-image {
            position: absolute;
            left: calc(50% + var(--bg-shift, 5%));
            top: 55%;
            transform: translate(-50%, -50%) rotate(-6deg);
            max-width: 400px;
            width: auto;
            height: auto;
            object-fit: contain;
            box-shadow: 0 22px 44px rgba(0,0,0,0.18);
            transition: transform 0.22s ease, box-shadow 0.22s ease, left 0.22s ease;
        }

        .promo-image:hover {
            transform: translate(-50%, -52%) rotate(-6deg) scale(1.02);
            box-shadow: 0 40px 80px rgba(0,0,0,0.28);
        }



        @media (max-width: 1200px) {
            .hero { min-height: 360px; }
            .hero-right { --bg-shift: 4%; }
            .promo-image { max-width: 370px; left: calc(50% + var(--bg-shift, 4%)); }
        }

        @media (max-width: 1024px) {
            .hero { min-height: 320px; }
            .hero-right { --bg-shift: 3%; }
            .promo-image { max-width: 350px; left: calc(50% + var(--bg-shift, 3%)); }
        }

        @media (max-width: 768px) {
            .hero { flex-direction: column-reverse; gap: 12px; min-height: auto; margin: 16px; }
            .hero-left { max-width: 100%; padding-right: 0; }
            .hero-right { width: 100%; padding: 14px; border-radius: 10px; --bg-shift: 0%; }
            .promo-image { max-width: 60%; position: relative; right: 0; top: 0; transform: rotate(-4deg); left: 50%; transform: translate(-50%, 0) rotate(-4deg); }
            .promo-logo { position: relative; bottom: 0; left: 0; transform: none; margin-top: 12px; }
        }

        @media (max-width: 480px) {
            .promo-image { max-width: 46%; }
            .hero { margin: 12px; }
            .promo-logo { font-size: 14px; }
        }
    </style>
</head>
<body>
        <div class="top-menu">
            <a href="dashboard_consumatore.php" class="lead" style="text-decoration: none;">
                <i class="fa-solid fa-leaf"></i> ClickNeat
            </a>
            <div class="menu-links">
                <a href="ordini.php" title="I tuoi ordini">
                    <i class="fas fa-history"></i> Ordini
                </a>
                <a href="profile_consumatore.php" title="Profilo">
                    <i class="fas fa-user-cog"></i> Profilo
                </a>
                <a href="logout.php" class="logout" title="Esci">
                    <i class="fas fa-sign-out-alt"></i> Esci
                </a>
            </div>
        </div>

        <div class="hero">
            <div class="hero-left">
                <div class="welcome-section">
                        <h1><i class="fas fa-user-circle"></i> Benvenuto, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h1>
                        <p class="subtitle">Scegli tra i migliori ristoranti della tua zona</p>
                </div>
                <div class="search-wrapper">
                    <div class="search-input-group">
                        <i class="fas fa-map-marker-alt"></i>
                        <input type="text" id="locationInput" placeholder="Cerca Ristorante" class="location-input">
                        <button class="clear-input-btn">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <button class="search-submit-btn">Cerca</button>
                </div>
            </div>

            <div class="hero-right" aria-hidden="true">
                <div class="promo-wrap">
                    <img src="https://cdn.discordapp.com/attachments/1463887221835960498/1468526820130291805/Picsart_26-02-04_09-41-08-370.jpg?ex=698457ad&is=6983062d&hm=3dfefc754de02d1761839fc29e1388eeca7b012f492129dccb42284571c85e56" alt="Promo" class="promo-image" />
                </div>
            </div>
        </div>

        <div class="container">
            <div class="dashboard-header">
                
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
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Ristoranti preferiti</h3>
                            <p class="stat-number">0</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ristoranti-section">
                <div class="section-title-bar">
                    <h2><i class="fas fa-store"></i> I Ristoranti Piu' Popolari</h2>
                </div>

                <?php
                $sql = "SELECT id, nome, indirizzo, descrizione FROM ristoranti ORDER BY nome ASC LIMIT 9";
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
                                <span class="tag">Pasticceria</span>
                                <?php if(strlen($row['descrizione']) < 50): ?>
                                <span class="tag tag-popular">Popolare</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="card-footer">
                            <a href="menu.php?ristorante_id=<?php echo $row['id']; ?>" class="btn-menu">
                                <i class="fas fa-book-open"></i>
                                Visualizza Menu' 
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
                                <p>Ritira</p>
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
                    <p>&copy; 2026 ClickNeat. Tutti i diritti riservati.</p>
                    <p class="version">v1.0</p>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const locationInput = document.getElementById('locationInput');
        const searchInput = locationInput; // alias per chiarezza
        const ristorantiGrid = document.getElementById('ristorantiGrid');
        const cards = document.querySelectorAll('.cardRistorante');
        const noResults = document.getElementById('noResults');
        
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                let visibleCount = 0;

                cards.forEach(card => {
                    const name = card.getAttribute('data-name') || '';
                    const address = card.getAttribute('data-address') || '';
                    const desc = card.getAttribute('data-desc') || '';

                    const matches = name.includes(searchTerm) ||
                                    address.includes(searchTerm) ||
                                    desc.includes(searchTerm);

                    if (matches || searchTerm === '') {
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

                if (visibleCount === 0 && searchTerm !== '') {
                    noResults.style.display = 'block';
                    if (ristorantiGrid) ristorantiGrid.style.display = 'none';
                } else {
                    if (noResults) noResults.style.display = 'none';
                    if (ristorantiGrid) ristorantiGrid.style.display = 'grid';
                }
            });
        }

        const searchBtn = document.querySelector('.search-submit-btn');
        if (searchBtn && locationInput) {
            searchBtn.addEventListener('click', function() {
                const term = locationInput.value.toLowerCase().trim();
                if (searchInput) {
                    searchInput.value = term;
                    searchInput.dispatchEvent(new Event('input'));
                    searchInput.focus();
                }
            });
        }
        
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