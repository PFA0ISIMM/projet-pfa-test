<?php
session_start();
require('config.php');

// ── Filtres GET ──────────────────────────────────────────
$domaine  = isset($_GET['domaine'])  ? esc($conn, $_GET['domaine'])  : '';
$type     = isset($_GET['type'])     ? esc($conn, $_GET['type'])     : '';
$region   = isset($_GET['region'])   ? esc($conn, $_GET['region'])   : '';
$score    = isset($_GET['score'])    ? (float)$_GET['score']         : 0;
$q        = isset($_GET['q'])        ? esc($conn, $_GET['q'])        : '';
$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;
$offset   = ($page - 1) * $per_page;

// ── Construction WHERE ───────────────────────────────────
$where = ["f.statut = 'active'"];
if ($domaine) $where[] = "f.domaine = '$domaine'";
if ($type)    $where[] = "f.type_formation = '$type'";
if ($score)   $where[] = "f.score_min <= $score";
if ($q)       $where[] = "(f.titre LIKE '%$q%' OR f.description LIKE '%$q%')";
if ($region) {
    $where[] = "g.nom = '$region'";
}

$whereSQL = implode(' AND ', $where);
$join = "LEFT JOIN universites u ON f.universite_id = u.id
         LEFT JOIN gouvernorats g ON u.gouvernorat_id = g.id";

// ── Total ────────────────────────────────────────────────
$total_res  = $conn->query("SELECT COUNT(*) as c FROM filieres f $join WHERE $whereSQL");
$total_rows = $total_res->fetch_assoc()['c'];
$total_pages = ceil($total_rows / $per_page);

// ── Données ──────────────────────────────────────────────
$filieres = $conn->query("
    SELECT f.*, u.sigle as univ_sigle, u.nom as univ_nom, g.nom as gouvernorat
    FROM filieres f $join
    WHERE $whereSQL
    ORDER BY f.score_min DESC
    LIMIT $per_page OFFSET $offset
")->fetch_all(MYSQLI_ASSOC);

// ── Domaines pour filtre ─────────────────────────────────
$domaines_list = $conn->query("SELECT DISTINCT domaine FROM filieres WHERE statut='active' ORDER BY domaine")->fetch_all(MYSQLI_ASSOC);
$types_list    = $conn->query("SELECT DISTINCT type_formation FROM filieres WHERE statut='active' AND type_formation IS NOT NULL ORDER BY type_formation")->fetch_all(MYSQLI_ASSOC);
$regions_list  = $conn->query("SELECT g.nom FROM gouvernorats g JOIN universites u ON g.id=u.gouvernorat_id JOIN filieres f ON f.universite_id=u.id WHERE f.statut='active' GROUP BY g.nom ORDER BY g.nom")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filières — OrientTN</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php require('nav.php'); ?>

<!-- BANNER PAGE -->
<div class="detail-banner" style="padding:60px;">
    <div class="container" style="position:relative;z-index:1;">
        <div class="breadcrumb">
            <a href="index.php">Accueil</a>
            <span class="sep">/</span>
            <span>Filières</span>
            <?php if($domaine): ?>
                <span class="sep">/</span>
                <span><?= htmlspecialchars($domaine) ?></span>
            <?php endif; ?>
        </div>
        <h1 style="font-family:var(--font-display);font-size:2.4rem;color:white;font-weight:700;margin-bottom:12px;">
            <?= $domaine ? "Filières en " . htmlspecialchars($domaine) : "Toutes les filières" ?>
        </h1>
        <p style="color:rgba(255,255,255,0.6);font-size:1rem;">
            <?= $total_rows ?> formation<?= $total_rows>1?'s':'' ?> trouvée<?= $total_rows>1?'s':'' ?>
        </p>
    </div>
    <div style="position:absolute;inset:0;background:radial-gradient(circle at 70% 50%,rgba(200,16,46,0.2) 0%,transparent 50%);pointer-events:none;"></div>
</div>

<section class="section">
    <div class="container">

        <!-- BARRE RECHERCHE -->
        <div class="search-wrap" style="max-width:100%;margin-bottom:24px;">
            <span style="font-size:18px;">🔍</span>
            <form method="GET" style="display:contents;">
                <input type="text" name="q" placeholder="Rechercher une filière (ex: Médecine, Informatique, Droit...)"
                       value="<?= htmlspecialchars($q) ?>" style="font-size:15px;">
                <?php if($domaine): ?><input type="hidden" name="domaine" value="<?= htmlspecialchars($domaine) ?>"><?php endif; ?>
                <?php if($score):   ?><input type="hidden" name="score"   value="<?= $score ?>"><?php endif; ?>
                <div class="search-sep"></div>
                <select name="type" style="border:none;outline:none;font-family:var(--font-body);font-size:14px;background:transparent;min-width:130px;cursor:pointer;">
                    <option value="">Type de formation</option>
                    <?php foreach($types_list as $t): ?>
                        <option value="<?= htmlspecialchars($t['type_formation']) ?>" <?= $type===$t['type_formation']?'selected':'' ?>>
                            <?= htmlspecialchars($t['type_formation']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary btn-sm" style="border-radius:var(--radius-xl);">Chercher</button>
            </form>
        </div>

        <!-- FILTRES AVANCÉS -->
        <div class="filter-panel">
            <form method="GET" id="filterForm">
                <div class="filter-row">
                    <div class="fld">
                        <label>Domaine</label>
                        <select name="domaine" onchange="document.getElementById('filterForm').submit()">
                            <option value="">Tous les domaines</option>
                            <?php foreach($domaines_list as $d): ?>
                                <option value="<?= htmlspecialchars($d['domaine']) ?>" <?= $domaine===$d['domaine']?'selected':'' ?>>
                                    <?= htmlspecialchars($d['domaine']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="fld">
                        <label>Score minimum du bac</label>
                        <input type="number" name="score" step="0.001" min="0" max="20"
                               placeholder="Ex: 15.000" value="<?= $score ?: '' ?>">
                    </div>
                    <div class="fld">
                        <label>Région</label>
                        <select name="region" onchange="document.getElementById('filterForm').submit()">
                            <option value="">Toutes les régions</option>
                            <?php foreach($regions_list as $r): ?>
                                <option value="<?= htmlspecialchars($r['nom']) ?>" <?= $region===$r['nom']?'selected':'' ?>>
                                    <?= htmlspecialchars($r['nom']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="fld">
                        <label>Type de formation</label>
                        <select name="type" onchange="document.getElementById('filterForm').submit()">
                            <option value="">Tous les types</option>
                            <?php foreach($types_list as $t): ?>
                                <option value="<?= htmlspecialchars($t['type_formation']) ?>" <?= $type===$t['type_formation']?'selected':'' ?>>
                                    <?= htmlspecialchars($t['type_formation']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div style="display:flex;gap:10px;align-items:center;justify-content:space-between;">
                    <button type="submit" class="btn btn-primary btn-sm">🔍 Appliquer les filtres</button>
                    <a href="filieres.php" class="btn btn-sm" style="background:var(--gray-mid);color:var(--text);">✕ Réinitialiser</a>
                    <?php if($q): ?><input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>"><?php endif; ?>
                </div>
            </form>
        </div>

        <!-- RÉSULTATS -->
        <?php if(empty($filieres)): ?>
        <div style="text-align:center;padding:80px 20px;color:var(--gray-text);">
            <div style="font-size:3rem;margin-bottom:16px;">🔍</div>
            <h3 style="font-family:var(--font-display);font-size:1.5rem;color:var(--text);margin-bottom:10px;">Aucune filière trouvée</h3>
            <p>Essayez de modifier vos filtres ou d'élargir votre recherche.</p>
            <a href="filieres.php" class="btn btn-outline" style="margin-top:20px;">Voir toutes les filières</a>
        </div>
        <?php else: ?>
        <div class="grid-auto" style="margin-bottom:20px;">
            <?php foreach($filieres as $i => $f): ?>
            <article class="filiere-card reveal" style="animation-delay:<?= ($i%4)*0.08 ?>s">
                <div class="card-top <?= htmlspecialchars($f['couleur_classe']) ?>">
                    <div class="card-icon"><?= $f['icon'] ?></div>
                    <span class="card-score">≥ <?= number_format($f['score_min'],3) ?></span>
                </div>
                <div class="card-body">
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        <span class="card-chip"><?= htmlspecialchars($f['domaine']) ?></span>
                        <?php if($f['type_formation']): ?>
                            <span class="chip chip-blue" style="font-size:10px;"><?= htmlspecialchars($f['type_formation']) ?></span>
                        <?php endif; ?>
                    </div>
                    <h3 class="card-title"><?= htmlspecialchars($f['titre']) ?></h3>
                    <p class="card-desc"><?= htmlspecialchars(mb_strimwidth($f['description'],0,100,'…')) ?></p>
                    <div class="card-footer">
                        <div>
                            <div class="card-region">🏛️ <?= htmlspecialchars($f['univ_sigle'] ?? 'N/A') ?></div>
                            <?php if($f['duree']): ?>
                                <div style="font-size:12px;color:var(--gray-text);margin-top:3px;">⏱ <?= $f['duree'] ?> ans</div>
                            <?php endif; ?>
                        </div>
                        <a href="detail.php?id=<?= $f['id'] ?>" class="card-link">Voir →</a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <!-- PAGINATION -->
        <?php if($total_pages > 1): ?>
        <div class="pagination">
            <?php if($page > 1): ?>
                <a href="?page=<?= $page-1 ?>&domaine=<?= urlencode($domaine) ?>&score=<?= $score ?>&q=<?= urlencode($q) ?>">‹</a>
            <?php endif; ?>
            <?php for($p=max(1,$page-2); $p<=min($total_pages,$page+2); $p++): ?>
                <?php if($p === $page): ?>
                    <span class="pg-active"><?= $p ?></span>
                <?php else: ?>
                    <a href="?page=<?= $p ?>&domaine=<?= urlencode($domaine) ?>&score=<?= $score ?>&q=<?= urlencode($q) ?>"><?= $p ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if($page < $total_pages): ?>
                <a href="?page=<?= $page+1 ?>&domaine=<?= urlencode($domaine) ?>&score=<?= $score ?>&q=<?= urlencode($q) ?>">›</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>

    </div>
</section>

<?php require('footer.php'); ?>

<script>
const observer = new IntersectionObserver(entries => {
    entries.forEach(e => { if(e.isIntersecting){ e.target.classList.add('visible'); observer.unobserve(e.target); } });
}, {threshold:0.08});
document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
</script>
</body>
</html>