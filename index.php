<?php
session_start();
require('config.php');

$filieres_vedette = $conn->query("
    SELECT f.*, u.sigle as univ_sigle, u.nom as univ_nom, g.nom as gouvernorat
    FROM filieres f
    LEFT JOIN universites u ON f.universite_id = u.id
    LEFT JOIN gouvernorats g ON u.gouvernorat_id = g.id
    WHERE f.statut = 'active'
    ORDER BY f.score_min DESC LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

$nb_filieres = $conn->query("SELECT COUNT(*) as c FROM filieres WHERE statut='active'")->fetch_assoc()['c'];
$nb_univs    = $conn->query("SELECT COUNT(*) as c FROM universites")->fetch_assoc()['c'];
$nb_users    = $conn->query("SELECT COUNT(*) as c FROM utilisateurs WHERE role='etudiant'")->fetch_assoc()['c'];

$domaines_db = $conn->query("
    SELECT domaine, icon, couleur_classe, COUNT(*) as nb
    FROM filieres WHERE statut='active'
    GROUP BY domaine, icon, couleur_classe
    ORDER BY nb DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OrientTN — Orientation Universitaire en Tunisie 2025</title>
    <meta name="description" content="689 filières universitaires tunisiennes. Guide officiel 2025. Score sur 210 points.">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php require('nav.php'); ?>

<section class="hero">
    <div class="hero-bg"></div>
    <div class="hero-grid"></div>
    <div class="hero-content">
        <span class="hero-badge">🇹🇳 Guide officiel orientation 2025 — دليل طاقة استيعاب</span>
        <h1>Trouvez votre<br><em>voie académique</em><br>en Tunisie</h1>
        <p class="hero-desc">
            <strong><?= $nb_filieres ?> filières</strong> dans <?= $nb_univs ?> universités tunisiennes.
            Recommandations basées sur votre <strong>score du bac (sur 210 points)</strong> — données officielles 2025.
        </p>
        <div class="hero-actions">
            <a href="recherche.php" class="btn btn-primary btn-lg">🔍 Rechercher une filière</a>
            <a href="chatbot.php"   class="btn btn-secondary btn-lg">🤖 Chatbot IA</a>
        </div>
        <p style="font-size:11px;color:rgba(255,255,255,0.4);margin-top:14px;">
            📊 Source : Ministère de l'Enseignement Supérieur · Scores sur 210 points
        </p>
    </div>

    <div class="hero-widget">
        <h3>⚡ Orientation rapide</h3>
        <form action="recherche.php" method="GET">
            <div class="widget-field">
                <label>Score du Bac <span style="color:var(--gold);font-size:10px;opacity:0.8">(sur 210 points)</span></label>
                <input type="number" name="score" step="0.1" min="0" max="210"
                       placeholder="Ex: 145.5  (sur 210)"
                       value="<?= htmlspecialchars($_GET['score'] ?? '') ?>">
            </div>
            <div class="widget-field">
                <label>Section du Bac</label>
                <select name="serie">
                    <option value="">-- Toutes sections --</option>
                    <option value="Mathématiques">🔢 Mathématiques</option>
                    <option value="Sciences Expérimentales">🔬 Sciences Expérimentales</option>
                    <option value="Sciences Techniques">⚙️ Sciences Techniques</option>
                    <option value="Informatique">💻 Informatique</option>
                    <option value="Économie & Gestion">📊 Économie & Gestion</option>
                    <option value="Lettres">📚 Lettres</option>
                    <option value="Sport">⛷️ Sport</option>
                </select>
            </div>
            <div class="widget-field">
                <label>Domaine d'intérêt</label>
                <select name="domaine">
                    <option value="">-- Tous domaines --</option>
                    <option value="Sciences Exactes & Technologie">⚛️ Sciences & Technologie</option>
                    <option value="Médecine, Pharmacie & Odontologie">🩺 Médecine & Santé</option>
                    <option value="Architecture & Génie Civil">🏗️ Architecture & Génie</option>
                    <option value="Sciences Économiques & Gestion">📊 Économie & Gestion</option>
                    <option value="Droit & Sciences Politiques">⚖️ Droit</option>
                    <option value="Lettres & Langues">📚 Lettres & Langues</option>
                    <option value="Sciences Humaines & Sociales">🏛️ Sciences Humaines</option>
                    <option value="Culture & Beaux-Arts">🎨 Culture & Arts</option>
                    <option value="Sciences Agronomiques & Biotech">🌱 Agronomie & Biotech</option>
                    <option value="Tourisme & Sport">⛷️ Tourisme & Sport</option>
                </select>
            </div>
            <button type="submit" class="btn btn-gold btn-full" style="margin-top:8px">Voir mes filières →</button>
        </form>
    </div>
</section>

<section class="section section-dark">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-box reveal"><div class="stat-num" data-count="<?= $nb_filieres ?>">0</div><div class="stat-lbl">Filières disponibles</div></div>
            <div class="stat-box reveal"><div class="stat-num" data-count="<?= $nb_univs ?>">0</div><div class="stat-lbl">Universités & Instituts</div></div>
            <div class="stat-box reveal"><div class="stat-num" data-count="210">0</div><div class="stat-lbl">Score max bac (points)</div></div>
            <div class="stat-box reveal"><div class="stat-num" data-count="24">0</div><div class="stat-lbl">Gouvernorats couverts</div></div>
        </div>
        <p style="text-align:center;color:rgba(255,255,255,0.3);font-size:12px;margin-top:16px;">
            Source : دليل طاقة استيعاب — دورة التوجيه الجامعي 2025
        </p>
    </div>
</section>

<section class="section section-alt">
    <div class="container">
        <div class="section-header reveal">
            <span class="section-tag">10 domaines d'études</span>
            <h2 class="section-title">Explorez par domaine</h2>
            <p class="section-sub">Toutes les formations universitaires publiques tunisiennes, classées par domaine.</p>
        </div>
        <div class="grid-4" style="gap:18px;">
            <?php foreach($domaines_db as $d): ?>
            <a href="recherche.php?domaine=<?= urlencode($d['domaine']) ?>"
               style="display:block;border-radius:var(--radius-lg);overflow:hidden;box-shadow:var(--shadow-sm);
                      transition:transform 0.28s,box-shadow 0.28s;text-decoration:none;"
               onmouseover="this.style.transform='translateY(-6px)';this.style.boxShadow='var(--shadow-lg)'"
               onmouseout="this.style.transform='';this.style.boxShadow='var(--shadow-sm)'">
                <div class="card-top <?= htmlspecialchars($d['couleur_classe']) ?>" style="height:110px;align-items:center;justify-content:center;">
                    <div style="text-align:center;position:relative;z-index:1;">
                        <div style="font-size:2.2rem;margin-bottom:4px;"><?= $d['icon'] ?></div>
                        <div style="font-size:11px;color:rgba(255,255,255,0.7);font-weight:600;"><?= $d['nb'] ?> filière<?= $d['nb']>1?'s':'' ?></div>
                    </div>
                </div>
                <div style="padding:14px 16px;background:white;border:1px solid var(--gray-mid);border-top:none;border-radius:0 0 var(--radius-lg) var(--radius-lg);">
                    <div style="font-family:var(--font-display);font-size:0.95rem;font-weight:700;color:var(--text);line-height:1.3;"><?= htmlspecialchars($d['domaine']) ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Score explainer -->
<section class="section">
    <div class="container">
        <div class="section-header reveal">
            <span class="section-tag">Comment ça marche ?</span>
            <h2 class="section-title">Le score sur 210 — comprendre le système</h2>
            <p class="section-sub">Le score d'orientation tunisien est calculé selon une formule spécifique à chaque filière, à partir de vos notes de bac.</p>
        </div>
        <div class="grid-3" style="margin-bottom:40px;">
            <div class="feature-card reveal" style="background:var(--navy);border:none;">
                <div class="feature-ico" style="background:rgba(200,16,46,0.2);">📝</div>
                <h3 class="feature-ttl" style="color:white;">Vos notes du bac (sur 20)</h3>
                <p class="feature-txt" style="color:rgba(255,255,255,0.6);">Chaque matière est notée sur 20. La note générale <strong style="color:var(--gold)">FG</strong> est votre moyenne pondérée. Les spécialisées (Maths M, Sciences SP, etc.) comptent en plus.</p>
            </div>
            <div class="feature-card reveal" style="background:var(--navy);border:none;">
                <div class="feature-ico" style="background:rgba(212,168,83,0.2);">⚙️</div>
                <h3 class="feature-ttl" style="color:white;">La formule de la filière</h3>
                <p class="feature-txt" style="color:rgba(255,255,255,0.6);">
                    Ex: <code style="color:var(--gold)">FG+M</code> = Moy. Générale + Note Maths<br>
                    Ex: <code style="color:var(--gold)">FG+SVT</code> = Moy. Générale + Note SVT<br>
                    Ex: <code style="color:var(--gold)">FG+AR</code> = Moy. Générale + Note Arabe
                </p>
            </div>
            <div class="feature-card reveal" style="background:var(--navy);border:none;">
                <div class="feature-ico" style="background:rgba(27,79,138,0.3);">🎯</div>
                <h3 class="feature-ttl" style="color:white;">Score sur 210 max</h3>
                <p class="feature-txt" style="color:rgba(255,255,255,0.6);">La somme (2 notes sur 20 = max 40, multiplié par facteur) donne un total entre 0 et <strong style="color:var(--gold)">210 points</strong>. Comparez votre score au minimum de la filière.</p>
            </div>
        </div>

        <div class="reveal" style="background:white;border-radius:var(--radius-lg);border:1px solid var(--gray-mid);box-shadow:var(--shadow-sm);overflow:hidden;">
            <div style="padding:18px 24px;background:var(--navy);"><h3 style="font-family:var(--font-display);font-size:1rem;color:white;margin:0;">📊 Sections du bac & fourchettes de score</h3></div>
            <div style="overflow-x:auto;">
                <table class="tbl">
                    <thead><tr><th>Section</th><th>Formule type</th><th>Filières cibles</th><th>Score admission</th></tr></thead>
                    <tbody>
                        <tr><td><strong>Mathématiques</strong></td><td><code>FG+M</code> / <code>FG+(M+SP)/2</code></td><td>Ingénierie, Informatique, Médecine</td><td><strong>155 – 185 / 210</strong></td></tr>
                        <tr><td><strong>Sciences Expérimentales</strong></td><td><code>FG+SVT</code></td><td>Médecine, Agronomie, Sciences</td><td><strong>130 – 175 / 210</strong></td></tr>
                        <tr><td><strong>Sciences Techniques</strong></td><td><code>FG+(SP+TE)/2</code></td><td>Génie, Électronique</td><td><strong>120 – 165 / 210</strong></td></tr>
                        <tr><td><strong>Informatique</strong></td><td><code>FG+(M+SP+Info)/3</code></td><td>Informatique, Réseaux, TIC</td><td><strong>125 – 175 / 210</strong></td></tr>
                        <tr><td><strong>Économie & Gestion</strong></td><td><code>FG+(M+GEST)/2</code></td><td>Gestion, Finance, Droit</td><td><strong>90 – 140 / 210</strong></td></tr>
                        <tr><td><strong>Lettres</strong></td><td><code>FG+AR</code> / <code>FG+ANG</code></td><td>Lettres, Langues, Sc. Humaines</td><td><strong>75 – 120 / 210</strong></td></tr>
                        <tr><td><strong>Sport</strong></td><td><code>FG+PH</code></td><td>STAPS, Tourisme, Arts</td><td><strong>80 – 130 / 210</strong></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="container">
        <div class="section-header reveal">
            <span class="section-tag">Filières sélectives</span>
            <h2 class="section-title">Les formations à fort score d'admission</h2>
            <p class="section-sub">Ces filières nécessitent les scores les plus élevés. Score affiché sur 210 points.</p>
        </div>
        <div class="grid-auto">
            <?php foreach($filieres_vedette as $i => $f): ?>
            <article class="filiere-card reveal" style="animation-delay:<?= $i * 0.07 ?>s">
                <a href="detail.php?id=<?= $f['id'] ?>" style="text-decoration:none;display:contents;">
                    <div class="card-top <?= htmlspecialchars($f['couleur_classe']) ?>">
                        <div class="card-icon"><?= $f['icon'] ?></div>
                        <span class="card-score">≥ <?= number_format($f['score_min'],1) ?>/210</span>
                    </div>
                    <div class="card-body">
                        <span class="card-chip"><?= htmlspecialchars($f['domaine']) ?></span>
                        <h3 class="card-title"><?= htmlspecialchars($f['titre']) ?></h3>
                        <p class="card-desc"><?= htmlspecialchars(mb_strimwidth($f['description'],0,100,'…')) ?></p>
                        <div class="card-footer">
                            <span class="card-region">📍 <?= htmlspecialchars($f['gouvernorat'] ?? $f['univ_sigle'] ?? 'N/A') ?></span>
                            <span class="card-link">Voir →</span>
                        </div>
                    </div>
                </a>
            </article>
            <?php endforeach; ?>
        </div>
        <div style="text-align:center;margin-top:40px;">
            <a href="recherche.php" class="btn btn-outline btn-lg">Voir les <?= $nb_filieres ?> filières →</a>
        </div>
    </div>
</section>

<section class="section section-dark">
    <div class="container" style="display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center;">
        <div class="reveal">
            <span class="section-tag">Chatbot IA</span>
            <h2 class="section-title" style="color:white;margin-top:10px;">
                Votre conseiller<br>d'orientation <em style="color:var(--gold)">intelligent</em>
            </h2>
            <p class="section-sub" style="margin-bottom:28px;">
                Entrez votre score <strong style="color:var(--gold)">sur 210</strong> et votre section —
                TN Guide vous propose instantanément les filières compatibles avec votre profil.
            </p>
            <a href="chatbot.php" class="btn btn-gold btn-lg">🤖 Démarrer une conversation</a>
        </div>
        <div class="reveal" style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1);border-radius:var(--radius-lg);padding:28px;">
            <div style="display:flex;flex-direction:column;gap:12px;">
                <div style="display:flex;gap:10px;">
                    <div style="width:30px;height:30px;border-radius:9px;background:linear-gradient(135deg,var(--red),var(--red-dark));display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0;">🤖</div>
                    <div style="background:white;color:var(--text);padding:11px 15px;border-radius:16px;border-bottom-left-radius:4px;font-size:13px;line-height:1.6;box-shadow:var(--shadow-sm);">Bonjour ! Quel est votre score bac <strong>sur 210</strong> ? 🎓</div>
                </div>
                <div style="display:flex;flex-direction:row-reverse;gap:10px;">
                    <div style="width:30px;height:30px;border-radius:9px;background:linear-gradient(135deg,var(--navy),var(--navy-mid));display:flex;align-items:center;justify-content:center;font-size:13px;color:white;flex-shrink:0;">👤</div>
                    <div style="background:var(--navy);color:white;padding:11px 15px;border-radius:16px;border-bottom-right-radius:4px;font-size:13px;">Mon score est <strong>162</strong>, section Maths</div>
                </div>
                <div style="display:flex;gap:10px;">
                    <div style="width:30px;height:30px;border-radius:9px;background:linear-gradient(135deg,var(--red),var(--red-dark));display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0;">🤖</div>
                    <div style="background:white;color:var(--text);padding:11px 15px;border-radius:16px;border-bottom-left-radius:4px;font-size:13px;line-height:1.6;box-shadow:var(--shadow-sm);">✅ Avec <strong>162/210</strong> : Cycle Ingénieur, Informatique, Médecine Dentaire sont accessibles ! 🌟</div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if (!isLoggedIn()): ?>
<section class="section" style="background:linear-gradient(135deg,var(--red-light),#fff,var(--gold-light));">
    <div class="container" style="text-align:center;max-width:680px;">
        <div class="reveal">
            <span class="section-tag">Rejoignez OrientTN</span>
            <h2 class="section-title" style="margin-top:10px;">Créez votre compte gratuitement</h2>
            <p class="section-sub" style="margin-bottom:32px;">Liste de vœux, recommandations IA, calcul de score personnalisé et suivi de candidatures.</p>
            <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;">
                <a href="inscription.php" class="btn btn-primary btn-lg">Créer un compte gratuit</a>
                <a href="connexion.php"   class="btn btn-outline btn-lg">Se connecter</a>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require('footer.php'); ?>
<script>
document.querySelectorAll('.stat-num[data-count]').forEach(el => {
    const target = parseInt(el.dataset.count), step = target/(1800/16);
    let cur = 0;
    const t = setInterval(()=>{ cur+=step; if(cur>=target){cur=target;clearInterval(t);} el.textContent=Math.floor(cur).toLocaleString('fr-FR'); },16);
});
const obs = new IntersectionObserver(es=>es.forEach(e=>{if(e.isIntersecting){e.target.classList.add('visible');obs.unobserve(e.target);}}),{threshold:0.1});
document.querySelectorAll('.reveal').forEach(el=>obs.observe(el));
</script>
</body>
</html>
