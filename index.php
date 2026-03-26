<?php
session_start();
require('config.php');

// Récupérer quelques filières pour l'affichage
$filieres_vedette = $conn->query("
    SELECT f.*, u.sigle as univ_sigle, u.nom as univ_nom
    FROM filieres f
    LEFT JOIN universites u ON f.universite_id = u.id
    WHERE f.statut = 'active'
    ORDER BY f.score_min DESC
    LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

// Stats
$nb_filieres = $conn->query("SELECT COUNT(*) as c FROM filieres WHERE statut='active'")->fetch_assoc()['c'];
$nb_univs    = $conn->query("SELECT COUNT(*) as c FROM universites")->fetch_assoc()['c'];
$nb_users    = $conn->query("SELECT COUNT(*) as c FROM utilisateurs WHERE role='etudiant'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OrientTN — Orientation Universitaire en Tunisie</title>
    <meta name="description" content="Trouvez la filière universitaire idéale en Tunisie selon votre score du bac et vos intérêts.">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php require('nav.php'); ?>

<!-- ══════════════════════════════════════════════════════
     HERO
══════════════════════════════════════════════════════ -->
<section class="hero">
    <div class="hero-bg"></div>
    <div class="hero-grid"></div>

    <div class="hero-content">
        <span class="hero-badge">🇹🇳 Plateforme officielle d'orientation universitaire</span>
        <h1>
            Trouvez votre<br>
            <em>voie académique</em><br>
            en Tunisie
        </h1>
        <p class="hero-desc">
            Explorez plus de <?= $nb_filieres ?>+ filières dans <?= $nb_univs ?> universités tunisiennes.
            Obtenez des recommandations personnalisées basées sur votre score du baccalauréat et vos aspirations.
        </p>
        <div class="hero-actions">
            <a href="recherche.php" class="btn btn-primary btn-lg">
                🔍 Rechercher une filière
            </a>
            <a href="chatbot.php" class="btn btn-secondary btn-lg">
                🤖 Essayer le Chatbot IA
            </a>
        </div>
    </div>

    <!-- Widget score rapide -->
    <div class="hero-widget">
        <h3>⚡ Orientation rapide</h3>
        <form action="recherche.php" method="GET">
            <div class="widget-field">
                <label>Votre score du Bac</label>
                <input type="number" name="score" step="0.001" min="0" max="20"
                       placeholder="Ex: 15.250"
                       value="<?= htmlspecialchars($_GET['score'] ?? '') ?>">
            </div>
            <div class="widget-field">
                <label>Série du Bac</label>
                <select name="serie">
                    <option value="">-- Toutes les séries --</option>
                    <option value="Mathématiques">Mathématiques</option>
                    <option value="Sciences">Sciences Expérimentales</option>
                    <option value="Technique">Technique</option>
                    <option value="Informatique">Informatique</option>
                    <option value="Economie">Économie et Gestion</option>
                    <option value="Lettres">Lettres</option>
                    <option value="Sport">Sport</option>
                </select>
            </div>
            <div class="widget-field">
                <label>Domaine d'intérêt</label>
                <select name="domaine">
                    <option value="">-- Tous les domaines --</option>
                    <option value="Informatique">💻 Informatique</option>
                    <option value="Médecine">🩺 Médecine & Santé</option>
                    <option value="Ingénierie">🏗️ Ingénierie</option>
                    <option value="Commerce">📊 Commerce & Gestion</option>
                    <option value="Droit">⚖️ Droit</option>
                    <option value="Lettres">📚 Lettres & Langues</option>
                    <option value="Sciences">⚛️ Sciences</option>
                </select>
            </div>
            <button type="submit" class="btn btn-gold btn-full" style="margin-top:8px">
                Voir mes filières →
            </button>
        </form>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════
     STATS
══════════════════════════════════════════════════════ -->
<section class="section section-dark">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-box reveal">
                <div class="stat-num" data-count="<?= $nb_filieres ?>">0</div>
                <div class="stat-lbl">Filières disponibles</div>
            </div>
            <div class="stat-box reveal">
                <div class="stat-num" data-count="<?= $nb_univs ?>">0</div>
                <div class="stat-lbl">Universités partenaires</div>
            </div>
            <div class="stat-box reveal">
                <div class="stat-num" data-count="<?= max($nb_users, 1240) ?>">0</div>
                <div class="stat-lbl">Étudiants orientés</div>
            </div>
            <div class="stat-box reveal">
                <div class="stat-num" data-count="24">0</div>
                <div class="stat-lbl">Gouvernorats couverts</div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════
     DOMAINES
══════════════════════════════════════════════════════ -->
<section class="section section-alt">
    <div class="container">
        <div class="section-header reveal">
            <span class="section-tag">Domaines d'études</span>
            <h2 class="section-title">Explorez par domaine</h2>
            <p class="section-sub">Choisissez le secteur qui vous correspond et découvrez toutes les formations disponibles.</p>
        </div>

        <div class="grid-4" style="gap:18px;">
            <?php
            $domaines = [
                ['icon'=>'💻','label'=>'Informatique',  'color'=>'ct-info','count'=>2,'slug'=>'Informatique'],
                ['icon'=>'🩺','label'=>'Médecine',       'color'=>'ct-med', 'count'=>2,'slug'=>'Médecine'],
                ['icon'=>'🏗️','label'=>'Ingénierie',    'color'=>'ct-ing', 'count'=>2,'slug'=>'Ingénierie'],
                ['icon'=>'📊','label'=>'Commerce',       'color'=>'ct-com', 'count'=>1,'slug'=>'Commerce'],
                ['icon'=>'⚖️','label'=>'Droit',          'color'=>'ct-droit','count'=>1,'slug'=>'Droit'],
                ['icon'=>'📚','label'=>'Lettres',        'color'=>'ct-lett','count'=>1,'slug'=>'Lettres'],
                ['icon'=>'⚛️','label'=>'Sciences',       'color'=>'ct-sci', 'count'=>1,'slug'=>'Sciences'],
                ['icon'=>'🎨','label'=>'Arts',           'color'=>'ct-art', 'count'=>0,'slug'=>'Arts'],
            ];
            foreach($domaines as $d):
            ?>
            <a href="filieres.php?domaine=<?= urlencode($d['slug']) ?>"
               style="display:block;border-radius:var(--radius-lg);overflow:hidden;box-shadow:var(--shadow-sm);transition:transform 0.28s,box-shadow 0.28s;text-decoration:none;"
               onmouseover="this.style.transform='translateY(-6px)';this.style.boxShadow='var(--shadow-lg)'"
               onmouseout="this.style.transform='';this.style.boxShadow='var(--shadow-sm)'">
                <div class="card-top <?= $d['color'] ?>" style="height:110px;align-items:center;justify-content:center;">
                    <div style="text-align:center;position:relative;z-index:1;">
                        <div style="font-size:2.2rem;margin-bottom:4px;"><?= $d['icon'] ?></div>
                        <div style="font-size:11px;color:rgba(255,255,255,0.7);font-weight:600;"><?= $d['count'] ?> filière<?= $d['count']>1?'s':'' ?></div>
                    </div>
                </div>
                <div style="padding:14px 16px;background:white;border:1px solid var(--gray-mid);border-top:none;border-radius:0 0 var(--radius-lg) var(--radius-lg);">
                    <div style="font-family:var(--font-display);font-size:1.05rem;font-weight:700;color:var(--text);"><?= $d['label'] ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════
     FILIÈRES VEDETTE
══════════════════════════════════════════════════════ -->
<section class="section">
    <div class="container">
        <div class="section-header reveal">
            <span class="section-tag">Filières populaires</span>
            <h2 class="section-title">Les formations les plus consultées</h2>
            <p class="section-sub">Découvrez les filières les plus demandées par les étudiants tunisiens cette année.</p>
        </div>

        <div class="grid-auto">
            <?php foreach($filieres_vedette as $i => $f): ?>
            <article class="filiere-card reveal" style="animation-delay:<?= $i * 0.08 ?>s">
                <div class="card-top <?= htmlspecialchars($f['couleur_classe']) ?>">
                    <div class="card-icon"><?= $f['icon'] ?></div>
                    <span class="card-score">≥ <?= number_format($f['score_min'],3) ?></span>
                </div>
                <div class="card-body">
                    <span class="card-chip"><?= htmlspecialchars($f['domaine']) ?></span>
                    <h3 class="card-title"><?= htmlspecialchars($f['titre']) ?></h3>
                    <p class="card-desc"><?= htmlspecialchars(mb_strimwidth($f['description'],0,110,'…')) ?></p>
                    <div class="card-footer">
                        <span class="card-region">🏛️ <?= htmlspecialchars($f['univ_sigle'] ?? 'N/A') ?></span>
                        <a href="detail.php?id=<?= $f['id'] ?>" class="card-link">
                            Voir →
                        </a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <div style="text-align:center;margin-top:40px;">
            <a href="filieres.php" class="btn btn-outline btn-lg">
                Voir toutes les filières →
            </a>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════
     FEATURES / SERVICES
══════════════════════════════════════════════════════ -->
<section class="section section-alt">
    <div class="container">
        <div class="section-header reveal">
            <span class="section-tag">Nos services</span>
            <h2 class="section-title">Comment OrientTN vous aide</h2>
            <p class="section-sub">Des outils pensés pour simplifier votre parcours d'orientation.</p>
        </div>

        <div class="features-grid">
            <div class="feature-card reveal">
                <div class="feature-ico ico-red">🔍</div>
                <h3 class="feature-ttl">Recherche intelligente</h3>
                <p class="feature-txt">Filtrez plus de <?= $nb_filieres ?> filières par score, région, domaine et type de formation. Trouvez exactement ce que vous cherchez en quelques clics.</p>
            </div>
            <div class="feature-card reveal">
                <div class="feature-ico ico-gold">🤖</div>
                <h3 class="feature-ttl">Chatbot IA personnalisé</h3>
                <p class="feature-txt">Notre assistant intelligent analyse votre profil — score du bac, série, intérêts — et vous suggère les formations les plus adaptées en temps réel.</p>
            </div>
            <div class="feature-card reveal">
                <div class="feature-ico ico-blue">📋</div>
                <h3 class="feature-ttl">Fiches détaillées</h3>
                <p class="feature-txt">Accédez aux informations complètes de chaque filière : score minimum, matières, débouchés professionnels, durée et conditions d'admission.</p>
            </div>
            <div class="feature-card reveal">
                <div class="feature-ico ico-green">❤️</div>
                <h3 class="feature-ttl">Liste de vœux</h3>
                <p class="feature-txt">Sauvegardez vos filières préférées et organisez vos candidatures. Comparez facilement plusieurs formations avant de faire votre choix.</p>
            </div>
            <div class="feature-card reveal">
                <div class="feature-ico ico-red">🏛️</div>
                <h3 class="feature-ttl">Annuaire des universités</h3>
                <p class="feature-txt">Découvrez les <?= $nb_univs ?> universités tunisiennes, leurs spécialités, leur localisation et les formations qu'elles proposent.</p>
            </div>
            <div class="feature-card reveal">
                <div class="feature-ico ico-gold">📊</div>
                <h3 class="feature-ttl">Statistiques et tendances</h3>
                <p class="feature-txt">Analysez les données d'orientation, les scores moyens par filière et les tendances du marché de l'emploi tunisien.</p>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════
     CTA CHATBOT
══════════════════════════════════════════════════════ -->
<section class="section section-dark">
    <div class="container" style="display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center;">
        <div class="reveal">
            <span class="section-tag">Chatbot IA</span>
            <h2 class="section-title" style="color:white;margin-top:10px;">
                Parlez à votre conseiller<br>
                d'orientation <em style="color:var(--gold)">intelligent</em>
            </h2>
            <p class="section-sub" style="margin-bottom:28px;">
                TN Guide analyse votre profil en temps réel et vous propose des recommandations sur mesure. Disponible 24h/24, 7j/7.
            </p>
            <a href="chatbot.php" class="btn btn-gold btn-lg">
                🤖 Démarrer une conversation
            </a>
        </div>
        <div class="reveal" style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1);border-radius:var(--radius-lg);padding:28px;backdrop-filter:blur(10px);">
            <!-- Mini aperçu chatbot -->
            <div style="display:flex;flex-direction:column;gap:12px;">
                <div class="msg bot" style="display:flex;gap:10px;max-width:100%;">
                    <div class="msg-ava bot-ava" style="width:30px;height:30px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0;background:linear-gradient(135deg,var(--red),var(--red-dark));">🤖</div>
                    <div class="msg-bubble" style="background:white;color:var(--text);padding:11px 15px;border-radius:16px;border-bottom-left-radius:4px;font-size:13px;line-height:1.6;">
                        Bonjour ! Je suis <strong>TN Guide</strong>. Quel est votre score du bac ? 🎓
                    </div>
                </div>
                <div style="display:flex;flex-direction:row-reverse;gap:10px;max-width:100%;">
                    <div style="width:30px;height:30px;border-radius:9px;background:linear-gradient(135deg,var(--navy),var(--navy-mid));display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0;color:white;">👤</div>
                    <div style="background:var(--navy);color:white;padding:11px 15px;border-radius:16px;border-bottom-right-radius:4px;font-size:13px;line-height:1.6;">
                        Mon score est 15.750, série mathématiques
                    </div>
                </div>
                <div class="msg bot" style="display:flex;gap:10px;max-width:100%;">
                    <div style="width:30px;height:30px;border-radius:9px;background:linear-gradient(135deg,var(--red),var(--red-dark));display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0;">🤖</div>
                    <div style="background:white;color:var(--text);padding:11px 15px;border-radius:16px;border-bottom-left-radius:4px;font-size:13px;line-height:1.6;box-shadow:var(--shadow-sm);">
                        Excellent ! Avec 15.750/20 en Math, je vous recommande : <strong>Ingénierie, Informatique, Sciences</strong>. Voulez-vous plus de détails ? ✨
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════
     INSCRIPTION CTA
══════════════════════════════════════════════════════ -->
<?php if (!isLoggedIn()): ?>
<section class="section" style="background:linear-gradient(135deg,var(--red-light),#fff,var(--gold-light));">
    <div class="container" style="text-align:center;max-width:680px;">
        <div class="reveal">
            <span class="section-tag">Rejoignez-nous</span>
            <h2 class="section-title" style="margin-top:10px;">
                Créez votre compte gratuitement
            </h2>
            <p class="section-sub" style="margin-bottom:32px;">
                Accédez à toutes les fonctionnalités d'OrientTN : liste de vœux personnalisée, recommandations IA, suivi de candidatures et bien plus.
            </p>
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
// ─── Compteur animé ──────────────────────────────────
document.querySelectorAll('.stat-num[data-count]').forEach(el => {
    const target = parseInt(el.dataset.count);
    const duration = 1800;
    const step = target / (duration / 16);
    let current = 0;
    const timer = setInterval(() => {
        current += step;
        if (current >= target) { current = target; clearInterval(timer); }
        el.textContent = Math.floor(current).toLocaleString('fr-FR');
    }, 16);
});

// ─── Reveal on scroll ────────────────────────────────
const observer = new IntersectionObserver(entries => {
    entries.forEach(e => {
        if (e.isIntersecting) {
            e.target.classList.add('visible');
            observer.unobserve(e.target);
        }
    });
}, { threshold: 0.1 });
document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
</script>

</body>
</html>