<?php
/**
 * nav.php — Header commun OrientTN
 * Nécessite session_start() avant inclusion
 */
$current = basename($_SERVER['PHP_SELF']);
function navActive($file, $current) {
    return basename($file) === $current ? ' class="active"' : '';
}
?>
<header class="site-header" id="siteHeader">
    <div class="logo">
        <div class="logo-mark">O</div>
        <span class="logo-text">Orient<span>TN</span></span>
    </div>

    <nav class="site-nav" id="siteNav">
        <a href="index.php"<?= navActive('index.php',$current) ?>>Accueil</a>
        <a href="filieres.php"<?= navActive('filieres.php',$current) ?>>Filières</a>
        <a href="recherche.php"<?= navActive('recherche.php',$current) ?>>Recherche</a>
        <a href="chatbot.php"<?= navActive('chatbot.php',$current) ?>>🤖 Chatbot IA</a>

        <?php if (isLoggedIn()): ?>
            <a href="mon-compte.php"<?= navActive('mon-compte.php',$current) ?>>Mon Compte</a>
            <?php if (isAdmin()): ?>
                <a href="admin.php"<?= navActive('admin.php',$current) ?>>⚙️ Admin</a>
            <?php endif; ?>
            <span class="nav-user-name">👤 <?= htmlspecialchars($_SESSION['prenom'] ?? '') ?></span>
            <a href="deconnexion.php" class="nav-btn-outline">Déconnexion</a>
        <?php else: ?>
            <a href="connexion.php"<?= navActive('connexion.php',$current) ?> class="nav-btn-outline">Connexion</a>
            <a href="inscription.php" class="nav-btn-primary">S'inscrire</a>
        <?php endif; ?>
    </nav>

    <button class="nav-toggle" id="navToggle" aria-label="Menu">
        <span></span><span></span><span></span>
    </button>
</header>

<script>
(function(){
    var toggle = document.getElementById('navToggle');
    var nav    = document.getElementById('siteNav');
    var header = document.getElementById('siteHeader');
    if(toggle) toggle.addEventListener('click', function(){
        nav.classList.toggle('open');
    });
    window.addEventListener('scroll', function(){
        header.classList.toggle('scrolled', window.scrollY > 50);
    });
})();
</script>