<?php
session_start();
require('config.php');

// ── Paramètres GET ─────────────────────────────
$score   = isset($_GET['score'])   ? (float)$_GET['score']   : null;
$domaine = isset($_GET['domaine']) ? trim($_GET['domaine'])   : '';
$serie   = isset($_GET['serie'])   ? trim($_GET['serie'])     : '';
$type    = isset($_GET['type'])    ? trim($_GET['type'])      : '';
$region  = isset($_GET['region'])  ? trim($_GET['region'])    : '';
$q       = isset($_GET['q'])       ? trim($_GET['q'])         : '';
$sort    = isset($_GET['sort'])    ? trim($_GET['sort'])      : 'score_desc';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;

// ── Construire la requête ──────────────────────
$where = ["f.statut = 'active'"];

if ($q !== '')       $where[] = "(f.titre LIKE '%".esc($conn,$q)."%' OR f.description LIKE '%".esc($conn,$q)."%')";
if ($domaine !== '') $where[] = "f.domaine = '".esc($conn,$domaine)."'";
if ($type !== '')    $where[] = "f.type_formation = '".esc($conn,$type)."'";
if ($region !== '')  $where[] = "g.nom = '".esc($conn,$region)."'";
if ($score !== null) $where[] = "f.score_min <= ".number_format($score, 3, '.', '');

$orderMap = [
    'score_desc' => 'f.score_min DESC',
    'score_asc'  => 'f.score_min ASC',
    'titre_asc'  => 'f.titre ASC',
    'recent'     => 'f.id DESC',
];
$orderBy = $orderMap[$sort] ?? 'f.score_min DESC';

$whereSql = implode(' AND ', $where);

$totalRow = $conn->query("
    SELECT COUNT(*) as c FROM filieres f
    LEFT JOIN universites u ON f.universite_id = u.id
    LEFT JOIN gouvernorats g ON u.gouvernorat_id = g.id
    WHERE $whereSql
")->fetch_assoc();
$total  = (int)$totalRow['c'];
$pages  = max(1, ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

$filieres = $conn->query("
    SELECT f.*, u.nom as univ_nom, u.sigle as univ_sigle, g.nom as gouvernorat
    FROM filieres f
    LEFT JOIN universites u ON f.universite_id = u.id
    LEFT JOIN gouvernorats g ON u.gouvernorat_id = g.id
    WHERE $whereSql
    ORDER BY $orderBy
    LIMIT $perPage OFFSET $offset
")->fetch_all(MYSQLI_ASSOC);

// ── Listes pour filtres ────────────────────────
$domaines  = $conn->query("SELECT DISTINCT domaine FROM filieres WHERE statut='active' ORDER BY domaine")->fetch_all(MYSQLI_ASSOC);
$types     = $conn->query("SELECT DISTINCT type_formation FROM filieres WHERE statut='active' AND type_formation IS NOT NULL ORDER BY type_formation")->fetch_all(MYSQLI_ASSOC);
$regions   = $conn->query("SELECT DISTINCT g.nom FROM gouvernorats g JOIN universites u ON u.gouvernorat_id=g.id ORDER BY g.nom")->fetch_all(MYSQLI_ASSOC);

// URL helper
function buildUrl($params) {
    $base = array_filter(array_merge($_GET, $params), fn($v) => $v !== '' && $v !== null);
    unset($base['page']);
    return 'recherche.php?' . http_build_query($base);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recherche avancée — OrientTN</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .search-hero {
            background: linear-gradient(135deg, var(--navy), var(--navy-mid));
            padding: 70px 60px 50px;
            position: relative; overflow: hidden;
        }
        .search-hero::before {
            content: ""; position: absolute; inset: 0; pointer-events: none;
            background:
                radial-gradient(circle at 20% 60%, rgba(200,16,46,0.18) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(212,168,83,0.12) 0%, transparent 40%);
        }
        .search-hero-inner { position: relative; z-index: 1; max-width: 1240px; margin: 0 auto; }
        .search-hero h1 { font-family: var(--font-display); font-size: clamp(1.8rem,3.5vw,2.6rem); font-weight: 700; color: white; margin-bottom: 10px; }
        .search-hero p  { color: rgba(255,255,255,0.6); font-size: 1rem; margin-bottom: 30px; }

        .big-search {
            background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15);
            backdrop-filter: blur(16px);
            border-radius: var(--radius-xl); padding: 8px 8px 8px 24px;
            display: flex; align-items: center; gap: 10px; max-width: 780px;
        }
        .big-search input {
            flex: 1; background: transparent; border: none; outline: none;
            font-family: var(--font-body); font-size: 16px; color: white;
        }
        .big-search input::placeholder { color: rgba(255,255,255,0.4); }
        .big-search .sep { width: 1px; height: 28px; background: rgba(255,255,255,0.2); }
        .big-search select {
            background: transparent; border: none; outline: none;
            font-family: var(--font-body); font-size: 14px; color: rgba(255,255,255,0.75);
            cursor: pointer; padding: 0 10px; min-width: 140px;
        }
        .big-search select option { background: var(--navy); color: white; }

        .page-body { display: grid; grid-template-columns: 280px 1fr; gap: 30px; padding: 46px 60px; max-width: 1240px; margin: 0 auto; }

        .sidebar {
            position: sticky; top: 88px; align-self: start;
        }
        .filter-card {
            background: white; border-radius: var(--radius-lg); padding: 24px;
            border: 1px solid var(--gray-mid); box-shadow: var(--shadow-sm); margin-bottom: 18px;
        }
        .filter-card h3 { font-family: var(--font-display); font-size: 1.1rem; font-weight: 700; color: var(--text); margin-bottom: 18px; display: flex; align-items: center; gap: 8px; }

        .filter-group { margin-bottom: 20px; }
        .filter-group:last-child { margin-bottom: 0; }
        .filter-group label { display: block; font-size: 11px; font-weight: 700; color: var(--gray-text); text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 8px; }
        .filter-group input, .filter-group select {
            width: 100%; padding: 10px 13px;
            border: 1.5px solid var(--gray-mid); border-radius: var(--radius-sm);
            font-family: var(--font-body); font-size: 14px; color: var(--text);
            background: var(--gray-soft); transition: border-color var(--transition);
        }
        .filter-group input:focus, .filter-group select:focus { outline: none; border-color: var(--red); background: white; }

        .score-input-wrap { position: relative; }
        .score-input-wrap input { padding-right: 50px; }
        .score-input-wrap .score-unit {
            position: absolute; right: 13px; top: 50%; transform: translateY(-50%);
            font-size: 12px; font-weight: 700; color: var(--gray-text);
        }

        .results-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 22px; flex-wrap: wrap; gap: 12px;
        }
        .results-count { font-size: 15px; color: var(--gray-text); }
        .results-count strong { color: var(--text); font-weight: 700; }

        .sort-select {
            padding: 9px 14px; border: 1.5px solid var(--gray-mid);
            border-radius: var(--radius-sm); font-family: var(--font-body);
            font-size: 14px; color: var(--text); background: white; cursor: pointer;
            outline: none;
        }
        .sort-select:focus { border-color: var(--red); }

        .active-filters { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 22px; }
        .af-chip {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 5px 12px; background: var(--red-light); color: var(--red);
            border-radius: 50px; font-size: 12px; font-weight: 600;
            border: 1px solid rgba(200,16,46,0.2);
        }
        .af-chip a { color: var(--red); font-weight: 700; font-size: 14px; line-height: 1; }
        .af-chip a:hover { color: var(--red-dark); }

        .empty-state {
            text-align: center; padding: 80px 40px; background: white;
            border-radius: var(--radius-lg); border: 1px solid var(--gray-mid);
        }
        .empty-state .ico { font-size: 56px; margin-bottom: 16px; }
        .empty-state h3 { font-family: var(--font-display); font-size: 1.5rem; color: var(--text); margin-bottom: 10px; }
        .empty-state p  { color: var(--gray-text); font-size: 0.95rem; margin-bottom: 24px; }

        @media (max-width: 1000px) {
            .page-body { grid-template-columns: 1fr; padding: 30px 20px; }
            .sidebar { position: static; }
            .search-hero { padding: 50px 20px 36px; }
        }
    </style>
</head>
<body>
<?php require('nav.php'); ?>

<!-- HERO SEARCH -->
<div class="search-hero">
    <div class="search-hero-inner">
        <div class="hero-badge" style="margin-bottom:18px;">🔍 Recherche avancée</div>
        <h1>Trouvez la filière <em style="color:var(--gold);font-style:italic;">idéale</em></h1>
        <p>Filtrez parmi toutes les formations universitaires tunisiennes selon votre profil</p>

        <form method="GET" action="recherche.php" id="searchForm">
            <div class="big-search">
                <span style="font-size:18px;">🔍</span>
                <input type="text" name="q" placeholder="Nom de filière, mot-clé..." value="<?= htmlspecialchars($q) ?>">
                <div class="sep"></div>
                <select name="domaine">
                    <option value="">Tous les domaines</option>
                    <?php foreach($domaines as $d): ?>
                        <option value="<?= htmlspecialchars($d['domaine']) ?>" <?= $domaine === $d['domaine'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['domaine']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <!-- preserve other filters -->
                <?php if($score !== null): ?><input type="hidden" name="score" value="<?= $score ?>"><?php endif; ?>
                <?php if($serie): ?><input type="hidden" name="serie" value="<?= htmlspecialchars($serie) ?>"><?php endif; ?>
                <?php if($type): ?><input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>"><?php endif; ?>
                <?php if($region): ?><input type="hidden" name="region" value="<?= htmlspecialchars($region) ?>"><?php endif; ?>
                <button type="submit" class="btn btn-primary" style="border-radius:var(--radius-xl);white-space:nowrap;">Rechercher</button>
            </div>
        </form>
    </div>
</div>

<!-- BODY -->
<div class="page-body">

    <!-- SIDEBAR FILTRES -->
    <aside class="sidebar">
        <form method="GET" action="recherche.php" id="filterForm">
            <?php if($q): ?><input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>"><?php endif; ?>
            <?php if($domaine): ?><input type="hidden" name="domaine" value="<?= htmlspecialchars($domaine) ?>"><?php endif; ?>
            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">

            <div class="filter-card">
                <h3>🎯 Filtres</h3>

                <div class="filter-group">
                    <label>Mon score du bac</label>
                    <div class="score-input-wrap">
                        <input type="number" name="score" step="0.001" min="0" max="20"
                               placeholder="Ex: 15.250"
                               value="<?= $score !== null ? $score : '' ?>">
                        <span class="score-unit">/ 20</span>
                    </div>
                    <p style="font-size:11px;color:var(--gray-text);margin-top:5px;">Affiche les filières accessibles avec ce score</p>
                </div>

                <div class="filter-group">
                    <label>Série du bac</label>
                    <select name="serie">
                        <option value="">Toutes les séries</option>
                        <option value="Mathématiques" <?= $serie==='Mathématiques'?'selected':'' ?>>Mathématiques</option>
                        <option value="Sciences" <?= $serie==='Sciences'?'selected':'' ?>>Sciences Expérimentales</option>
                        <option value="Technique" <?= $serie==='Technique'?'selected':'' ?>>Technique</option>
                        <option value="Informatique" <?= $serie==='Informatique'?'selected':'' ?>>Informatique</option>
                        <option value="Economie" <?= $serie==='Economie'?'selected':'' ?>>Économie et Gestion</option>
                        <option value="Lettres" <?= $serie==='Lettres'?'selected':'' ?>>Lettres</option>
                        <option value="Sport" <?= $serie==='Sport'?'selected':'' ?>>Sport</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Type de formation</label>
                    <select name="type">
                        <option value="">Tous les types</option>
                        <?php foreach($types as $t): if(!$t['type_formation']) continue; ?>
                            <option value="<?= htmlspecialchars($t['type_formation']) ?>" <?= $type===$t['type_formation']?'selected':'' ?>>
                                <?= htmlspecialchars($t['type_formation']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Région / Gouvernorat</label>
                    <select name="region">
                        <option value="">Toutes les régions</option>
                        <?php foreach($regions as $r): ?>
                            <option value="<?= htmlspecialchars($r['nom']) ?>" <?= $region===$r['nom']?'selected':'' ?>>
                                <?= htmlspecialchars($r['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary btn-full">Appliquer les filtres</button>
            </div>

            <?php if($q || $domaine || $score !== null || $type || $region || $serie): ?>
            <a href="recherche.php" class="btn btn-outline btn-full btn-sm" style="justify-content:center;">
                ✕ Effacer tous les filtres
            </a>
            <?php endif; ?>
        </form>

        <!-- Aide -->
        <div class="filter-card" style="margin-top:18px;background:linear-gradient(135deg,var(--navy),var(--navy-mid));border:none;">
            <h3 style="color:white;font-size:0.95rem;">💡 Astuce</h3>
            <p style="font-size:13px;color:rgba(255,255,255,0.6);line-height:1.7;margin-bottom:14px;">
                Entrez votre score et laissez OrientTN filtrer automatiquement les filières accessibles.
            </p>
            <a href="chatbot.php" class="btn btn-gold btn-full btn-sm">🤖 Utiliser le Chatbot IA</a>
        </div>
    </aside>

    <!-- RÉSULTATS -->
    <main>
        <!-- Filtres actifs -->
        <?php
        $activeFilters = [];
        if($q)           $activeFilters[] = ['label'=>"«$q»", 'remove'=>buildUrl(['q'=>''])];
        if($domaine)     $activeFilters[] = ['label'=>$domaine, 'remove'=>buildUrl(['domaine'=>''])];
        if($score!==null)$activeFilters[] = ['label'=>"Score ≤ $score", 'remove'=>buildUrl(['score'=>''])];
        if($type)        $activeFilters[] = ['label'=>$type, 'remove'=>buildUrl(['type'=>''])];
        if($region)      $activeFilters[] = ['label'=>$region, 'remove'=>buildUrl(['region'=>''])];
        if($serie)       $activeFilters[] = ['label'=>$serie, 'remove'=>buildUrl(['serie'=>''])];
        ?>
        <?php if(!empty($activeFilters)): ?>
        <div class="active-filters">
            <span style="font-size:12px;font-weight:700;color:var(--gray-text);padding:5px 0;margin-right:4px;">Filtres actifs :</span>
            <?php foreach($activeFilters as $af): ?>
                <span class="af-chip">
                    <?= htmlspecialchars($af['label']) ?>
                    <a href="<?= htmlspecialchars($af['remove']) ?>" title="Supprimer ce filtre">×</a>
                </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- En-tête résultats -->
        <div class="results-header">
            <p class="results-count">
                <strong><?= $total ?></strong> filière<?= $total > 1 ? 's' : '' ?> trouvée<?= $total > 1 ? 's' : '' ?>
                <?php if($score !== null): ?> accessibles avec un score de <strong><?= $score ?>/20</strong><?php endif; ?>
            </p>
            <form method="GET" style="display:inline;" id="sortForm">
                <?php foreach($_GET as $k => $v): if($k !== 'sort' && $k !== 'page'): ?>
                    <input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars($v) ?>">
                <?php endif; endforeach; ?>
                <select name="sort" class="sort-select" onchange="document.getElementById('sortForm').submit()">
                    <option value="score_desc" <?= $sort==='score_desc'?'selected':'' ?>>Score décroissant</option>
                    <option value="score_asc"  <?= $sort==='score_asc'?'selected':'' ?>>Score croissant</option>
                    <option value="titre_asc"  <?= $sort==='titre_asc'?'selected':'' ?>>Titre A→Z</option>
                    <option value="recent"     <?= $sort==='recent'?'selected':'' ?>>Les plus récentes</option>
                </select>
            </form>
        </div>

        <?php if(empty($filieres)): ?>
        <!-- État vide -->
        <div class="empty-state">
            <div class="ico">🔍</div>
            <h3>Aucune filière trouvée</h3>
            <p>Essayez de modifier vos critères de recherche ou d'élargir les filtres.</p>
            <a href="recherche.php" class="btn btn-primary">Réinitialiser la recherche</a>
        </div>

        <?php else: ?>
        <!-- Grille de filières -->
        <div class="grid-auto" style="margin-bottom:36px;">
            <?php foreach($filieres as $f): ?>
            <a href="detail.php?id=<?= $f['id'] ?>" class="filiere-card" style="text-decoration:none;">
                <div class="card-top <?= htmlspecialchars($f['couleur_classe'] ?? 'ct-def') ?>">
                    <div class="card-icon"><?= $f['icon'] ?? '🎓' ?></div>
                    <span class="card-score">≥ <?= number_format($f['score_min'],3) ?></span>
                </div>
                <div class="card-body">
                    <span class="card-chip"><?= htmlspecialchars($f['domaine']) ?></span>
                    <h3 class="card-title"><?= htmlspecialchars($f['titre']) ?></h3>
                    <p class="card-desc"><?= htmlspecialchars(mb_substr($f['description'] ?? '', 0, 90)) ?>...</p>
                    <div class="card-footer">
                        <span class="card-region">📍 <?= htmlspecialchars($f['gouvernorat'] ?? $f['univ_sigle'] ?? 'N/A') ?></span>
                        <span class="card-link">Voir <span>→</span></span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if($pages > 1): ?>
        <div class="pagination">
            <?php if($page > 1): ?>
                <a href="<?= htmlspecialchars(buildUrl(['page'=>$page-1])) ?>">‹</a>
            <?php endif; ?>
            <?php for($i = max(1,$page-2); $i <= min($pages,$page+2); $i++): ?>
                <?php if($i === $page): ?>
                    <span class="pg-active"><?= $i ?></span>
                <?php else: ?>
                    <a href="<?= htmlspecialchars(buildUrl(['page'=>$i])) ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if($page < $pages): ?>
                <a href="<?= htmlspecialchars(buildUrl(['page'=>$page+1])) ?>">›</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php endif; ?>
    </main>
</div>

<?php require('footer.php'); ?>
<script src="script.js"></script>
</body>
</html>
