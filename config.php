<?php
// ============================================================
// config.php — OrientTN
// Source données : دليل طاقة استيعاب 2025
// Score bac tunisien : 0 – 210 points
// ============================================================

define('DB_HOST',   'localhost');
define('DB_USER',   'root');
define('DB_PASS',   '');
define('DB_NAME',   'orientation');
define('SITE_NAME', 'OrientTN');
define('SITE_URL',  'http://localhost/orienttn');
define('SCORE_MAX', 210);   // Score bac tunisien sur 210

// ── Connexion MySQLi ──────────────────────────────────────
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;padding:40px;color:#991B1B;background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;max-width:500px;margin:40px auto;">
        <h2>⚠️ Erreur de connexion</h2>
        <p>' . htmlspecialchars($conn->connect_error) . '</p>
    </div>');
}

// ── Fonctions utilitaires ─────────────────────────────────

function esc($conn, $val) {
    return $conn->real_escape_string(trim($val));
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin($redirect = 'connexion.php') {
    if (!isLoggedIn()) {
        redirect($redirect . '?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    }
}

/**
 * Formate un score sur 210
 */
function formatScore($score) {
    return number_format((float)$score, 2, '.', '') . ' / 210';
}

/**
 * Convertit un score /20 (bac) en score /210 orienttatif
 * La formule réelle dépend de la filière (FG+M, FG+AR, etc.)
 * Ici on fournit une approximation : moyenne × 10.5
 */
function score20to210($score20) {
    return round((float)$score20 * 10.5, 2);
}

/**
 * Détermine le niveau d'admission d'un score
 */
function scoreNiveau($score) {
    if ($score >= 180) return ['label' => 'Excellent', 'class' => 'chip-green',  'emoji' => '🌟'];
    if ($score >= 150) return ['label' => 'Très bien',  'class' => 'chip-blue',   'emoji' => '✅'];
    if ($score >= 120) return ['label' => 'Bien',        'class' => 'chip-gold',   'emoji' => '👍'];
    if ($score >= 90)  return ['label' => 'Passable',    'class' => 'chip-gray',   'emoji' => '📝'];
    return               ['label' => 'Difficile',  'class' => 'chip-red',    'emoji' => '⚠️'];
}

function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        $icon = match($f['type']) {
            'success' => '✅', 'error' => '❌', 'warning' => '⚠️', default => 'ℹ️',
        };
        echo "<div class=\"alert alert-{$f['type']}\">$icon " . htmlspecialchars($f['message']) . "</div>";
    }
}

function hashPassword($password)        { return password_hash($password, PASSWORD_DEFAULT); }
function verifyPassword($password, $hash) { return password_verify($password, $hash); }

/**
 * Retourne les filières compatibles avec un score (sur 210)
 */
function getFilieresByScore($conn, $score, $serie = '') {
    $score = (float)$score;
    $sql   = "SELECT f.*, u.nom as univ_nom, u.sigle as univ_sigle
              FROM filieres f
              LEFT JOIN universites u ON f.universite_id = u.id
              WHERE f.statut = 'active' AND f.score_min <= $score
              ORDER BY f.score_min DESC LIMIT 8";
    return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

/**
 * Chatbot IA — réponse basée sur règles
 * Score attendu sur 210
 */
function chatbotReponse($message, $conn, $user = null) {
    $msg = mb_strtolower(trim($message), 'UTF-8');

    // Détecter un score dans le message (format: 120, 150.5, 175...)
    preg_match('/\b(1[0-9]{2}(?:[.,]\d{1,2})?|[5-9]\d(?:[.,]\d{1,2})?|2[0-1]\d(?:[.,]\d{1,2})?)\b/', $msg, $m);
    $scoreDetecte = $m ? (float)str_replace(',', '.', $m[1]) : null;

    // Détecter aussi la notation /20 et la convertir
    if (!$scoreDetecte) {
        preg_match('/\b(\d{1,2}[.,]\d{1,3})\s*(?:\/\s*20)?\b/', $msg, $m2);
        if ($m2 && (float)str_replace(',','.',$m2[1]) <= 20) {
            $scoreDetecte = score20to210((float)str_replace(',','.',$m2[1]));
        }
    }

    $keywords = [
        'bonjour|salut|hello|bonsoir|salam'    => 'bonjour',
        'médecine|médecin|pharmacie|dentiste'   => 'medecine',
        'informatique|info|programmation|code|ia|intelligence artificielle|réseau' => 'informatique',
        'ingénieur|génie|btp|civil|électrique|mécanique' => 'ingenierie',
        'droit|avocat|magistrat|juridique|loi' => 'droit',
        'commerce|gestion|économie|finance|comptabilité' => 'commerce',
        'score|moyenne|bac|résultat|total|points' => 'score',
        'université|études|formation|filière|orientation' => 'general',
        'aide|help|comment|quoi|que faire|comment' => 'aide',
        'merci|super|bravo|parfait|génial'      => 'merci',
        'lettre|langue|arabe|français|anglais'  => 'lettres',
        'architecture|urbanisme|bâtiment'       => 'architecture',
        'médecin|médecine|pharmacien|santé'     => 'medecine',
        'agro|agriculture|biotechnologie'       => 'agronomie',
    ];

    $intent = null;
    foreach ($keywords as $pattern => $name) {
        if (preg_match("/($pattern)/u", $msg)) { $intent = $name; break; }
    }

    $responses = [
        'bonjour'      => "Bonjour ! 👋 Je suis <strong>TN Guide</strong>, votre assistant d'orientation universitaire 2025.<br><br>
                           Dites-moi votre <strong>score du bac (sur 210)</strong> et je vous proposerai les filières compatibles.<br>
                           Ou parlez-moi d'un domaine : médecine, informatique, droit...",
        'medecine'     => "🩺 <strong>Médecine & Santé</strong><br><br>
                           Les filières médicales sont très sélectives en Tunisie :<br>
                           • <strong>Médecine</strong> (7 ans) : score ≥ 185/210<br>
                           • <strong>Médecine Dentaire</strong> (6 ans) : score ≥ 175/210<br>
                           • <strong>Pharmacie</strong> (5 ans) : score ≥ 170/210<br>
                           • <strong>Sciences Infirmières</strong> : score ≥ 120/210<br><br>
                           Quel est votre score total au bac ?",
        'informatique' => "💻 <strong>Informatique & Numérique</strong><br><br>
                           • <strong>Licence en Informatique</strong> : score ≥ 100/210<br>
                           • <strong>Réseaux & Télécoms</strong> : score ≥ 105/210<br>
                           • <strong>Cycle Ingénieur Info</strong> : score ≥ 160/210<br>
                           • <strong>TIC</strong> : score ≥ 95/210<br><br>
                           Formule de calcul typique : <code>FG+(M+SP+Info)/3</code>",
        'ingenierie'   => "🏗️ <strong>Ingénierie & Technologie</strong><br><br>
                           • <strong>Cycle Prépa Ingénieur</strong> : score ≥ 155/210<br>
                           • <strong>Génie Électrique</strong> : score ≥ 160/210<br>
                           • <strong>Génie Mécanique</strong> : score ≥ 155/210<br>
                           • <strong>Génie Civil</strong> : score ≥ 150/210<br><br>
                           Les cycles ingénieurs durent 5 ans avec 2 ans de classe préparatoire.",
        'droit'        => "⚖️ <strong>Droit & Sciences Politiques</strong><br><br>
                           • <strong>Licence en Droit Privé</strong> : score ≥ 90/210<br>
                           • <strong>Licence en Droit Public</strong> : score ≥ 88/210<br>
                           • <strong>Sciences Politiques</strong> : score ≥ 95/210<br><br>
                           La licence dure 3 ans et ouvre vers : avocat, magistrat, notaire, diplomate.",
        'commerce'     => "📊 <strong>Sciences Économiques & Gestion</strong><br><br>
                           • <strong>Licence en Économie</strong> : score ≥ 90/210<br>
                           • <strong>Finance & Comptabilité</strong> : score ≥ 95/210<br>
                           • <strong>Management des Entreprises</strong> : score ≥ 88/210<br>
                           • <strong>Marketing</strong> : score ≥ 85/210<br><br>
                           Formule : <code>FG+M</code> ou <code>FG+(M+GEST)/2</code>",
        'lettres'      => "📚 <strong>Lettres, Langues & Sciences Humaines</strong><br><br>
                           • <strong>Licence en Langue Arabe</strong> : score ≥ 75/210<br>
                           • <strong>Licence en Langue Anglaise</strong> : score ≥ 95/210<br>
                           • <strong>Licence en Langue Française</strong> : score ≥ 85/210<br>
                           • <strong>Sciences de l\'Éducation</strong> : score ≥ 80/210",
        'architecture' => "🏛️ <strong>Architecture & Génie Civil</strong><br><br>
                           • <strong>Cycle Ingénieur Architecture</strong> : score ≥ 165/210<br>
                           • <strong>Classe Préparatoire Sciences</strong> : score ≥ 155/210<br><br>
                           Formation de 5 ans très sélective. Compétences en dessin et mathématiques requises.",
        'agronomie'    => "🌱 <strong>Sciences Agronomiques & Biotechnologie</strong><br><br>
                           • <strong>Licence en Sciences Agronomiques</strong> : score ≥ 95/210<br>
                           • <strong>Biotechnologie</strong> : score ≥ 100/210<br>
                           • <strong>Environnement</strong> : score ≥ 90/210<br><br>
                           Formule : <code>FG+SVT</code>",
        'score'        => $scoreDetecte
                          ? chatbotParScore($conn, $scoreDetecte)
                          : "📝 <strong>Votre score du bac</strong><br><br>
                             Indiquez votre <strong>score total sur 210</strong> (ex: <em>145</em> ou <em>162.5</em>) et je vous proposerai les meilleures filières compatibles !<br><br>
                             <small style='color:#9CA3AF'>Note : le score est la somme pondérée de vos notes selon la formule de la filière choisie, sur un maximum de 210 points.</small>",
        'general'      => "🎓 <strong>Orientation Universitaire 2025</strong><br><br>
                           Notre plateforme OrientTN vous aide à :<br>
                           ✅ Trouver les filières selon votre score (sur <strong>210</strong>)<br>
                           ✅ Comparer les <strong>689 filières</strong> dans toutes les universités tunisiennes<br>
                           ✅ Découvrir les débouchés professionnels<br>
                           ✅ Gérer votre liste de vœux<br><br>
                           Par où voulez-vous commencer ?",
        'aide'         => "🆘 <strong>Comment je peux vous aider</strong><br><br>
                           Dites-moi :<br>
                           • Votre <strong>score total au bac (sur 210)</strong><br>
                           • Votre <strong>section du bac</strong> (Maths, Sciences, Éco...)<br>
                           • Votre <strong>domaine d'intérêt</strong><br>
                           • La <strong>région</strong> où vous souhaitez étudier<br><br>
                           Je vous proposerai les filières les plus adaptées !",
        'merci'        => "Avec plaisir ! 😊 Bonne chance pour votre orientation 2025 ! 🌟🇹🇳",
    ];

    if ($scoreDetecte && $intent !== 'score') {
        return chatbotParScore($conn, $scoreDetecte);
    }
    if ($intent && isset($responses[$intent])) {
        return $responses[$intent];
    }

    return "Je n'ai pas bien compris. Pouvez-vous reformuler ?<br><br>
            Exemples :<br>
            • « <em>Mon score est 145</em> »<br>
            • « <em>Je m'intéresse à l'informatique</em> »<br>
            • « <em>Médecine à Tunis</em> »";
}

function chatbotParScore($conn, $score) {
    $filieres = getFilieresByScore($conn, $score, '');
    $niveau   = scoreNiveau($score);

    if (empty($filieres)) {
        return "Avec un score de <strong>{$score}/210</strong>, je vous recommande de consulter les filières de formation professionnelle et les instituts techniques.
                <a href='recherche.php?score={$score}' style='color:var(--red)'>Voir toutes les options →</a>";
    }

    $result  = "Avec votre score de <strong>{$score}/210</strong> {$niveau['emoji']} — Niveau <em>{$niveau['label']}</em><br><br>";
    $result .= "Filières compatibles :<br><br>";
    foreach ($filieres as $f) {
        $result .= "• <strong>{$f['titre']}</strong> — {$f['univ_sigle']}
                    <span style='font-size:12px;color:#6B7A99'>(min: {$f['score_min']}/210)</span><br>";
    }
    $result .= "<br><a href='recherche.php?score={$score}' style='color:var(--red);font-weight:700'>
                Voir toutes les filières compatibles →</a>";
    return $result;
}
