<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar" id="sidebar">
    <button class="close-sidebar" id="closeSidebarBtn" aria-label="Chiudi menu">
        <i class="fa-solid fa-times"></i>
    </button>

    <div class="brand-logo">
        <i class="fa-solid fa-utensils"></i> ClickNeat
    </div>

    <div class="nav-links">
        <a href="dashboard_ristoratore.php" class="<?php echo $current_page == 'dashboard_ristoratore.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-chart-pie"></i> Dashboard
        </a>
        <a href="profile_ristoratore.php" class="<?php echo $current_page == 'profile_ristoratore.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-user"></i> Profilo
        </a>
        <a href="stats.php" class="<?php echo $current_page == 'stats.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-chart-simple"></i> Statistiche
        </a>
    </div>

    <a href="../auth/logout.php" class="btn-logout-sidebar">
        <i class="fa-solid fa-sign-out-alt"></i> Logout
    </a>
</div>