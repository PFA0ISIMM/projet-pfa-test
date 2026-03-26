<?php
// ============================================================
// config.php — OrientTN
// Configuration de la base de données et fonctions utilitaires
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'orienttn');
define('SITE_NAME', 'OrientTN');
define('SITE_URL',  'http://localhost/projet pfa');

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

/**
 * Échappe une valeur pour l'insertion SQL
 */
function esc($conn, $val) {
    return $conn->real_escape_string(trim($val));
}

/**
 * Redirige vers une URL
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Vérifie si l'utilisateur est connecté
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Vérifie si l'utilisateur est admin
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Redirige si non connecté
 */
function requireLogin($redirect = 'connexion.php') {
    if (!isLoggedIn()) {
        redirect($redirect . '?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    }
}

/**
 * Formate un score de bac
 */
function formatScore($score) {
    return number_format((float)$score, 3, '.', '') . ' / 20';
}

/**
 * Génère un message flash
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Affiche et vide le message flash
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        $icon = match($f['type']) {
            'success' => '✅',
            'error'   => '❌',
            'warning' => '⚠️',
            default   => 'ℹ️',
        };
        echo "<div class=\"alert alert-{$f['type']}\">$icon " . htmlspecialchars($f['message']) . "</div>";
    }
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Vérifie un password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Retourne les filières par domaine (pour chatbot IA)
 */
function getFilieresByScore($conn, $score, $serie) {
    $score = (float)$score;
    $serie = esc($conn, $serie);
    $sql = "SELECT f.*, u.nom as univ_nom, u.sigle as univ_sigle
            FROM filieres f
            LEFT JOIN universites u ON f.universite_id = u.id
            WHERE f.statut = 'active'
            AND f.score_min <= $score
            ORDER BY f.score_min DESC
            LIMIT 8";
    return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

/**
 * Génère une réponse simple du chatbot (règle-based)
 */
function chatbotReponse($message, $conn, $user = null) {
    $msg = mb_strtolower(trim($message), 'UTF-8');

    // Score détecté dans le message
    preg_match('/\b(\d{1,2}[.,]\d{1,3})\b/', $msg, $scoreMatch);
    $scoreDetecte = $scoreMatch ? (float)str_replace(',', '.', $scoreMatch[1]) : null;

    // Mots-clés
    $keywords = [
        'bonjour|salut|hello|bonsoir'       => "bonjour",
        'médecine|médecin|pharmacie|santé'   => "medecine",
        'informatique|info|programmation|code|ia|intelligence artificielle' => "informatique",
        'ingéni|génie|btp|civil|électrique'  => "ingenierie",
        'droit|avocat|magistrat|juridique'    => "droit",
        'commerce|gestion|économie|finance'   => "commerce",
        'score|moyenne|bac|résultat'          => "score",
        'université|études|formation|filière' => "general",
        'aide|help|comment|quoi|que faire'    => "aide",
        'merci|super|bravo|parfait'           => "merci",
    ];

    $intent = null;
    foreach ($keywords as $pattern => $name) {
        if (preg_match("/($pattern)/u", $msg)) {
            $intent = $name;
            break;
        }
    }

    // Réponses selon l'intent
    $responses = [
        "bonjour" => "Bonjour ! 👋 Je suis <strong>TN Guide</strong>, votre assistant d'orientation universitaire. Comment puis-je vous aider aujourd'hui ?<br><br>Vous pouvez me parler de votre <strong>score du bac</strong>, votre <strong>domaine d'intérêt</strong>, ou demander des informations sur une <strong>filière spécifique</strong>.",
        "medecine" => "🩺 <strong>Médecine & Santé</strong><br><br>Pour accéder aux études médicales en Tunisie, il vous faut généralement :<br>• <strong>Médecine Générale</strong> : score ≥ 17.5/20 (très sélectif)<br>• <strong>Pharmacie</strong> : score ≥ 16.5/20<br>• <strong>Médecine Dentaire</strong> : score ≥ 16.0/20<br><br>Quel est votre score de bac ?",
        "informatique" => "💻 <strong>Informatique & Numérique</strong><br><br>Filières disponibles :<br>• <strong>Licence Informatique</strong> : score ≥ 12.0/20<br>• <strong>Ingénieur en Informatique</strong> : score ≥ 15.0/20<br>• <strong>Master IA</strong> : après une licence (≥ 14.0)<br><br>Voulez-vous plus de détails sur l'une de ces formations ?",
        "ingenierie" => "🏗️ <strong>Ingénierie & Technologie</strong><br><br>Options d'ingénierie :<br>• <strong>Génie Civil</strong> : score ≥ 15.0/20<br>• <strong>Génie Électrique</strong> : score ≥ 15.5/20<br>• <strong>Génie Mécanique</strong> : score ≥ 14.5/20<br><br>Les cycles ingénieurs durent 5 ans avec 2 ans de prépa intégrée.",
        "droit" => "⚖️ <strong>Droit & Sciences Juridiques</strong><br><br>La Licence en Droit est accessible avec un score ≥ 10.0/20. Elle dure 3 ans et ouvre vers des carrières d'avocat, magistrat, notaire ou juriste d'entreprise.",
        "commerce" => "📊 <strong>Commerce & Gestion</strong><br><br>Formations disponibles :<br>• <strong>Licence SEG</strong> : score ≥ 11.0/20<br>• <strong>Licence Commerce</strong> : score ≥ 10.5/20<br>• <strong>École de Commerce</strong> (privée) : conditions spécifiques",
        "score" => $scoreDetecte
            ? chatbotParScore($conn, $scoreDetecte)
            : "📝 <strong>Votre score du bac</strong><br><br>Indiquez-moi votre score de baccalauréat (ex: <em>15.250</em>) et je vous suggèrerai les meilleures filières correspondant à votre profil !",
        "general" => "🎓 <strong>Orientation Universitaire</strong><br><br>Notre plateforme OrientTN vous aide à :<br>✅ Rechercher des filières selon votre score<br>✅ Comparer les universités tunisiennes<br>✅ Découvrir les débouchés professionnels<br>✅ Créer votre liste de vœux<br><br>Par où voulez-vous commencer ?",
        "aide" => "🆘 <strong>Comment je peux vous aider</strong><br><br>Dites-moi :<br>• Votre <strong>score du bac</strong> et votre <strong>série</strong><br>• Votre <strong>domaine d'intérêt</strong><br>• La <strong>région</strong> où vous souhaitez étudier<br><br>Je vous proposerai les filières les plus adaptées à votre profil !",
        "merci" => "Avec plaisir ! 😊 N'hésitez pas si vous avez d'autres questions sur votre orientation. Bonne chance dans vos études ! 🌟",
    ];

    if ($scoreDetecte && $intent !== "score") {
        return chatbotParScore($conn, $scoreDetecte);
    }

    if ($intent && isset($responses[$intent])) {
        return $responses[$intent];
    }

    return "Je n'ai pas bien compris votre demande. Pouvez-vous reformuler ?<br><br>Vous pouvez me parler de :<br>• Votre <strong>score du bac</strong><br>• Un <strong>domaine d'études</strong> (médecine, informatique...)<br>• Une <strong>université spécifique</strong>";
}

function chatbotParScore($conn, $score) {
    $filieres = getFilieresByScore($conn, $score, '');
    if (empty($filieres)) {
        return "Avec un score de <strong>{$score}/20</strong>, je vous recommande de consulter les filières de formation professionnelle ou les instituts techniques. <a href='recherche.php' style='color:var(--red)'>Voir toutes les options →</a>";
    }
    $result = "Avec votre score de <strong>{$score}/20</strong>, voici les filières compatibles :<br><br>";
    foreach ($filieres as $f) {
        $result .= "• <strong>{$f['titre']}</strong> — {$f['univ_sigle']} <span style='font-size:12px;color:#6B7A99'>(min: {$f['score_min']})</span><br>";
    }
    $result .= "<br><a href='recherche.php?score={$score}' style='color:var(--red);font-weight:700'>Voir toutes les filières compatibles →</a>";
    return $result;
}