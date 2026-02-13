<?php
// sidebar.php
?>
<div class="sidebar" id="sidebar">
    <!-- Pulsante di chiusura (visibile solo su mobile) -->
    <button class="close-sidebar" id="closeSidebarBtn" aria-label="Chiudi menu">
        <i class="fa-solid fa-times"></i>
    </button>

    <div class="brand-logo">
        <i class="fa-solid fa-utensils"></i> ClickNeat
    </div>

    <div class="nav-links">
        <a href="dashboard_ristoratore.php" class="active">
            <i class="fa-solid fa-chart-pie"></i> Dashboard
        </a>
        <a href="profile_ristoratore.php">
            <i class="fa-solid fa-user"></i> Profilo
        </a>
        <a href="ordini_ristoratore.php">
            <i class="fa-solid fa-clock"></i> Ordini
        </a>
    </div>

    <a href="../auth/logout.php" class="btn-logout-sidebar">
        <i class="fa-solid fa-sign-out-alt"></i> Logout
    </a>
</div> 