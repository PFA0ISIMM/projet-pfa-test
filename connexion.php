<?php
session_start();
require('config.php');

// Déjà connecté → redirection
if (isLoggedIn()) redirect('index.php');

$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email))    $errors[] = "L'adresse e-mail est requise.";
    if (empty($password)) $errors[] = "Le mot de passe est requis.";

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT * FROM utilisateurs WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && verifyPassword($password, $user['mot_de_passe'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['prenom']  = $user['prenom'];
            $_SESSION['nom']     = $user['nom'];
            $_SESSION['role']    = $user['role'];
            $_SESSION['email']   = $user['email'];

            $redirect = $_GET['redirect'] ?? 'index.php';
            redirect($redirect);
        } else {
            $errors[] = "E-mail ou mot de passe incorrect.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — OrientTN</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .password-wrap { position: relative; }
        .password-wrap input { padding-right: 46px; }
        .toggle-pw {
            position: absolute; right: 13px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer; font-size: 16px; color: var(--gray-text);
            transition: color var(--transition);
        }
        .toggle-pw:hover { color: var(--red); }
        .social-btn {
            display: flex; align-items: center; justify-content: center; gap: 10px;
            width: 100%; padding: 11px; border-radius: var(--radius-sm);
            border: 1.5px solid var(--gray-mid); background: white; cursor: pointer;
            font-family: var(--font-body); font-size: 14px; font-weight: 600; color: var(--text);
            transition: all var(--transition);
        }
        .social-btn:hover { border-color: var(--gray-text); background: var(--gray-soft); }
        .remember-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 22px; }
        .remember-row label { display: flex; align-items: center; gap: 8px; font-size: 14px; color: var(--text); cursor: pointer; }
        .remember-row a { font-size: 14px; color: var(--red); font-weight: 600; }
        .remember-row a:hover { color: var(--red-dark); }
        .auth-features { display: flex; flex-direction: column; gap: 14px; margin-bottom: 36px; }
        .auth-feat {
            display: flex; align-items: flex-start; gap: 12px;
            background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.1);
            border-radius: var(--radius-md); padding: 14px 16px;
        }
        .auth-feat-ico { font-size: 20px; flex-shrink: 0; margin-top: 2px; }
        .auth-feat-ttl { font-size: 13px; font-weight: 700; color: white; margin-bottom: 2px; }
        .auth-feat-txt { font-size: 12px; color: rgba(255,255,255,0.55); line-height: 1.5; }
    </style>
</head>
<body>
<?php require('nav.php'); ?>

<div class="auth-layout">

    <!-- LEFT — Visuel & avantages -->
    <div class="auth-left">
        <div class="auth-left-content">
            <div class="logo" style="margin-bottom:40px;">
                <div class="logo-mark">O</div>
                <span class="logo-text">Orient<span>TN</span></span>
            </div>

            <h2>Bienvenue sur<br><em>OrientTN</em></h2>
            <p>Connectez-vous pour accéder à votre espace personnalisé et gérer vos filières favorites.</p>

            <div class="auth-features">
                <div class="auth-feat">
                    <span class="auth-feat-ico">❤️</span>
                    <div>
                        <div class="auth-feat-ttl">Sauvegarder vos favoris</div>
                        <div class="auth-feat-txt">Enregistrez les filières qui vous intéressent et retrouvez-les facilement.</div>
                    </div>
                </div>
                <div class="auth-feat">
                    <span class="auth-feat-ico">🤖</span>
                    <div>
                        <div class="auth-feat-ttl">Chatbot IA personnalisé</div>
                        <div class="auth-feat-txt">Obtenez des recommandations adaptées à votre profil unique.</div>
                    </div>
                </div>
                <div class="auth-feat">
                    <span class="auth-feat-ico">📊</span>
                    <div>
                        <div class="auth-feat-ttl">Suivi de vos candidatures</div>
                        <div class="auth-feat-txt">Gérez votre liste de vœux et suivez votre dossier d'orientation.</div>
                    </div>
                </div>
            </div>

            <div class="testimonial">
                <blockquote>« OrientTN m'a aidé à choisir la bonne filière en quelques minutes. Le chatbot IA est vraiment impressionnant ! »</blockquote>
                <div class="testimonial-author">
                    <div class="t-ava">S</div>
                    <div>
                        <div class="t-name">Sara Ben Ali</div>
                        <div class="t-role">Étudiante, Informatique — ENIT</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT — Formulaire -->
    <div class="auth-right">
        <div class="auth-box">
            <h1>Connexion</h1>
            <p class="auth-sub">Pas encore inscrit ? <a href="inscription.php" style="color:var(--red);font-weight:700;">Créer un compte →</a></p>

            <?php if(!empty($errors)): ?>
                <div class="alert alert-error">
                    ❌ <?= htmlspecialchars($errors[0]) ?>
                </div>
            <?php endif; ?>

            <?php getFlash(); ?>

            <form method="POST" novalidate>
                <div class="form-group">
                    <label for="email">Adresse e-mail</label>
                    <input type="email" id="email" name="email"
                           value="<?= htmlspecialchars($email) ?>"
                           placeholder="votre@email.com" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <div class="password-wrap">
                        <input type="password" id="password" name="password"
                               placeholder="Votre mot de passe" required>
                        <button type="button" class="toggle-pw" onclick="togglePw(this)" title="Afficher/masquer">👁️</button>
                    </div>
                </div>

                <div class="remember-row">
                    <label>
                        <input type="checkbox" name="remember" style="width:auto;accent-color:var(--red);">
                        Se souvenir de moi
                    </label>
                    <a href="reset-password.php">Mot de passe oublié ?</a>
                </div>

                <button type="submit" class="btn btn-primary btn-full btn-lg">
                    Se connecter →
                </button>
            </form>

            <div class="auth-sep"><span>ou continuer avec</span></div>

            <div style="display:flex;flex-direction:column;gap:10px;">
                <button class="social-btn">
                    <span>🌐</span> Continuer avec Google
                </button>
                <button class="social-btn">
                    <span>📘</span> Continuer avec Facebook
                </button>
            </div>

            <p class="auth-footer-link">
                En vous connectant, vous acceptez nos
                <a href="#">Conditions d'utilisation</a> et notre
                <a href="#">Politique de confidentialité</a>.
            </p>
        </div>
    </div>

</div>

<script>
function togglePw(btn) {
    var input = btn.previousElementSibling;
    if (input.type === 'password') {
        input.type = 'text';
        btn.textContent = '🙈';
    } else {
        input.type = 'password';
        btn.textContent = '👁️';
    }
}
</script>
</body>
</html>
