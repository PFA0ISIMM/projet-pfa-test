<?php
session_start();
require('config.php');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$filiere = $conn->query("
    SELECT f.*, u.nom as univ_nom, u.sigle as univ_sigle, u.site_web,
           u.type as univ_type, g.nom as gouvernorat
    FROM filieres f
    LEFT JOIN universites u ON f.universite_id = u.id
    LEFT JOIN gouvernorats g ON u.gouvernorat_id = g.id
    WHERE f.id = $id AND f.statut = 'active'
")->fetch_assoc();

if (!$filiere) {
    redirect('filieres.php');
}

// Gestion favori
$isFavori = false;
if (isLoggedIn()) {
    $uid = intval($_SESSION['user_id']);
    $check = $conn->query("SELECT id FROM candidatures WHERE utilisateur_id=$uid AND filiere_id=$id");
    $isFavori = $check->num_rows > 0;

    if (isset($_POST['toggle_favori'])) {
        if ($isFavori) {
            $conn->query("DELETE FROM candidatures WHERE utilisateur_id=$uid AND filiere_id=$id");
            setFlash('info', 'Filière retirée de vos favoris.');
        } else {
            $conn->query("INSERT INTO candidatures (utilisateur_id, filiere_id) VALUES ($uid, $id)");
            setFlash('success', 'Filière ajoutée à vos favoris ! ❤️');
        }
        redirect("detail.php?id=$id");
    }
}

// Filières similaires
$similaires = $conn->query("
    SELECT f.*, u.sigle as univ_sigle
    FROM filieres f LEFT JOIN universites u ON f.universite_id = u.id
    WHERE f.domaine = '{$conn->real_escape_string($filiere['domaine'])}'
    AND f.id != $id AND f.statut = 'active'
    LIMIT 3
")->fetch_all(MYSQLI_ASSOC);

// Décoder JSON
$debouches = json_decode($filiere['debouches'] ?? '[]', true) ?: [];
$matieres  = json_decode($filiere['matieres']  ?? '[]', true) ?: [];

$pageTitle = htmlspecialchars($filiere['titre']) . ' — OrientTN';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php require('nav.php'); ?>

<!-- BANNER -->
<div class="detail-banner">
    <div style="position:relative;z-index:1;max-width:1240px;margin:0 auto;">
        <div class="breadcrumb">
            <a href="index.php">Accueil</a>
            <span class="sep">/</span>
            <a href="filieres.php">Filières</a>
            <span class="sep">/</span>
            <a href="filieres.php?domaine=<?= urlencode($filiere['domaine']) ?>"><?= htmlspecialchars($filiere['domaine']) ?></a>
            <span class="sep">/</span>
            <span><?= htmlspecialchars($filiere['titre']) ?></span>
        </div>
        <h1 class="detail-banner h1" style="font-family:var(--font-display);font-size:clamp(1.9rem,4vw,2.9rem);font-weight:700;color:white;margin-bottom:12px;">
            <?= $filiere['icon'] ?> <?= htmlspecialchars($filiere['titre']) ?>
        </h1>
        <div class="tags-row">
            <span class="tag tag-red"><?= htmlspecialchars($filiere['domaine']) ?></span>
            <?php if($filiere['type_formation']): ?>
                <span class="tag tag-gold"><?= htmlspecialchars($filiere['type_formation']) ?></span>
            <?php endif; ?>
            <?php if($filiere['duree']): ?>
                <span class="tag tag-blue">⏱ <?= $filiere['duree'] ?> ans</span>
            <?php endif; ?>
            <?php if($filiere['langue']): ?>
                <span class="tag tag-blue">🌐 <?= htmlspecialchars($filiere['langue']) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div style="position:absolute;inset:0;background:radial-gradient(circle at 70% 50%,rgba(200,16,46,0.2) 0%,transparent 50%);pointer-events:none;"></div>
</div>

<!-- CONTENU PRINCIPAL -->
<div class="detail-body">

    <!-- ─── Colonne principale ─────────────────── -->
    <main>
        <?php getFlash(); ?>

        <!-- Description -->
        <div class="prose" style="background:white;border-radius:var(--radius-lg);padding:30px;border:1px solid var(--gray-mid);box-shadow:var(--shadow-sm);margin-bottom:28px;">
            <h2>À propos de cette formation</h2>
            <?php if($filiere['description_longue']): ?>
                <?= $filiere['description_longue'] ?>
            <?php else: ?>
                <p><?= htmlspecialchars($filiere['description']) ?></p>
            <?php endif; ?>
        </div>

        <!-- Matières enseignées -->
        <?php if(!empty($matieres)): ?>
        <div style="background:white;border-radius:var(--radius-lg);padding:28px;border:1px solid var(--gray-mid);box-shadow:var(--shadow-sm);margin-bottom:28px;">
            <h2 style="font-family:var(--font-display);font-size:1.45rem;font-weight:700;color:var(--text);margin-bottom:18px;">📚 Matières principales</h2>
            <div style="display:flex;flex-wrap:wrap;gap:10px;">
                <?php foreach($matieres as $mat): ?>
                    <span class="chip chip-blue" style="font-size:13px;padding:7px 16px;"><?= htmlspecialchars($mat) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Débouchés -->
        <?php if(!empty($debouches)): ?>
        <div style="background:white;border-radius:var(--radius-lg);padding:28px;border:1px solid var(--gray-mid);box-shadow:var(--shadow-sm);margin-bottom:28px;">
            <h2 style="font-family:var(--font-display);font-size:1.45rem;font-weight:700;color:var(--text);margin-bottom:18px;">💼 Débouchés professionnels</h2>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <?php foreach($debouches as $deb): ?>
                    <div style="display:flex;align-items:center;gap:10px;padding:12px 16px;background:var(--gray-soft);border-radius:var(--radius-md);border:1px solid var(--gray-mid);">
                        <span style="color:var(--red);font-size:16px;">✓</span>
                        <span style="font-size:14px;color:var(--text);font-weight:500;"><?= htmlspecialchars($deb) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Conditions d'admission -->
        <div style="background:white;border-radius:var(--radius-lg);padding:28px;border:1px solid var(--gray-mid);box-shadow:var(--shadow-sm);margin-bottom:28px;">
            <h2 style="font-family:var(--font-display);font-size:1.45rem;font-weight:700;color:var(--text);margin-bottom:18px;">📝 Conditions d'admission</h2>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div style="padding:16px;background:var(--red-light);border-radius:var(--radius-md);border:1px solid rgba(200,16,46,0.15);">
                    <div style="font-size:11px;font-weight:700;color:var(--red);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">Score minimum requis</div>
                    <div style="font-family:var(--font-display);font-size:1.8rem;font-weight:700;color:var(--red);"><?= number_format($filiere['score_min'],3) ?></div>
                    <div style="font-size:12px;color:var(--red);opacity:0.7;">/ 20.000</div>
                </div>
                <div style="padding:16px;background:var(--gold-light);border-radius:var(--radius-md);border:1px solid rgba(212,168,83,0.2);">
                    <div style="font-size:11px;font-weight:700;color:#7A5A18;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">Score moyen admis</div>
                    <div style="font-family:var(--font-display);font-size:1.8rem;font-weight:700;color:#7A5A18;"><?= number_format($filiere['score_moyen'] ?? $filiere['score_min'],3) ?></div>
                    <div style="font-size:12px;color:#7A5A18;opacity:0.7;">/ 20.000</div>
                </div>
            </div>
            <?php if($filiere['capacite']): ?>
            <div style="margin-top:14px;padding:14px 16px;background:var(--blue-light);border-radius:var(--radius-md);display:flex;align-items:center;gap:10px;">
                <span style="font-size:20px;">👥</span>
                <span style="font-size:14px;color:var(--blue);font-weight:600;">Capacité d'accueil : <strong><?= $filiere['capacite'] ?> places</strong> par an</span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Filières similaires -->
        <?php if(!empty($similaires)): ?>
        <div style="margin-bottom:28px;">
            <h2 style="font-family:var(--font-display);font-size:1.45rem;font-weight:700;color:var(--text);margin-bottom:18px;">🔗 Filières similaires</h2>
            <div class="grid-3">
                <?php foreach($similaires as $s): ?>
                <a href="detail.php?id=<?= $s['id'] ?>" class="filiere-card" style="text-decoration:none;">
                    <div class="card-top <?= htmlspecialchars($s['couleur_classe']) ?>" style="height:100px;">
                        <div class="card-icon"><?= $s['icon'] ?></div>
                        <span class="card-score">≥ <?= number_format($s['score_min'],3) ?></span>
                    </div>
                    <div class="card-body" style="padding:16px;">
                        <h4 style="font-family:var(--font-display);font-size:1rem;font-weight:700;color:var(--text);"><?= htmlspecialchars($s['titre']) ?></h4>
                        <p style="font-size:12px;color:var(--gray-text);">🏛️ <?= htmlspecialchars($s['univ_sigle']??'N/A') ?></p>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <!-- ─── Carte latérale ─────────────────────────────────── -->
    <aside class="detail-sticky">
        <div class="info-card">
            <!-- Score block -->
            <div class="score-block">
                <div style="font-size:12px;opacity:0.8;margin-bottom:4px;text-transform:uppercase;letter-spacing:0.5px;">Score minimum requis</div>
                <div class="score-big"><?= number_format($filiere['score_min'],3) ?></div>
                <div class="score-lbl">/ 20.000</div>
            </div>

            <!-- Infos liste -->
            <div class="info-list">
                <?php if($filiere['univ_nom']): ?>
                <div class="info-row">
                    <div class="info-ico">🏛️</div>
                    <div>
                        <div style="font-size:11px;color:var(--gray-text);text-transform:uppercase;font-weight:700;margin-bottom:2px;">Université</div>
                        <div style="font-size:13px;font-weight:600;"><?= htmlspecialchars($filiere['univ_nom']) ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if($filiere['gouvernorat']): ?>
                <div class="info-row">
                    <div class="info-ico">📍</div>
                    <div>
                        <div style="font-size:11px;color:var(--gray-text);text-transform:uppercase;font-weight:700;margin-bottom:2px;">Région</div>
                        <div style="font-size:13px;font-weight:600;"><?= htmlspecialchars($filiere['gouvernorat']) ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if($filiere['type_formation']): ?>
                <div class="info-row">
                    <div class="info-ico">🎓</div>
                    <div>
                        <div style="font-size:11px;color:var(--gray-text);text-transform:uppercase;font-weight:700;margin-bottom:2px;">Type</div>
                        <div style="font-size:13px;font-weight:600;"><?= htmlspecialchars($filiere['type_formation']) ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if($filiere['duree']): ?>
                <div class="info-row">
                    <div class="info-ico">⏱️</div>
                    <div>
                        <div style="font-size:11px;color:var(--gray-text);text-transform:uppercase;font-weight:700;margin-bottom:2px;">Durée</div>
                        <div style="font-size:13px;font-weight:600;"><?= $filiere['duree'] ?> ans</div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if($filiere['langue']): ?>
                <div class="info-row">
                    <div class="info-ico">🌐</div>
                    <div>
                        <div style="font-size:11px;color:var(--gray-text);text-transform:uppercase;font-weight:700;margin-bottom:2px;">Langue</div>
                        <div style="font-size:13px;font-weight:600;"><?= htmlspecialchars($filiere['langue']) ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Bouton favori -->
            <?php if(isLoggedIn()): ?>
                <form method="POST" style="margin-bottom:10px;">
                    <button type="submit" name="toggle_favori" class="btn btn-full <?= $isFavori ? 'btn-outline' : 'btn-primary' ?>">
                        <?= $isFavori ? '❤️ Retirer des favoris' : '🤍 Ajouter aux favoris' ?>
                    </button>
                </form>
            <?php else: ?>
                <a href="connexion.php" class="btn btn-primary btn-full" style="margin-bottom:10px;">
                    🔐 Se connecter pour sauvegarder
                </a>
            <?php endif; ?>

            <a href="chatbot.php?prefill=<?= urlencode('Parle moi de la filière '.$filiere['titre']) ?>"
               class="btn btn-dark btn-full">
                🤖 Demander au Chatbot IA
            </a>

            <?php if($filiere['site_web']): ?>
            <a href="<?= htmlspecialchars($filiere['site_web']) ?>" target="_blank" class="btn btn-full"
               style="background:var(--blue-light);color:var(--blue);margin-top:10px;font-weight:700;">
                🌍 Site officiel de l'université
            </a>
            <?php endif; ?>
        </div>

        <!-- Widget recherche -->
        <div class="info-card" style="margin-top:18px;background:linear-gradient(135deg,var(--navy),var(--navy-mid));border:none;">
            <h4 style="font-family:var(--font-display);font-size:1rem;color:white;margin-bottom:14px;">🔍 Chercher une filière similaire</h4>
            <a href="recherche.php?domaine=<?= urlencode($filiere['domaine']) ?>" class="btn btn-gold btn-full btn-sm">
                Voir toutes les filières en <?= htmlspecialchars($filiere['domaine']) ?>
            </a>
        </div>
    </aside>

</div>

<?php require('footer.php'); ?>
</body>
</html>