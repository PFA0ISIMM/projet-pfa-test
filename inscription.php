<?php
session_start();
require('config.php');

// Déjà connecté → redirection
if (isLoggedIn()) redirect('index.php');

$errors  = [];
$success = false;
$data    = ['prenom'=>'','nom'=>'','email'=>'','serie'=>'','score'=>'','gouvernorat'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les champs
    $data['prenom']      = trim($_POST['prenom']      ?? '');
    $data['nom']         = trim($_POST['nom']         ?? '');
    $data['email']       = trim($_POST['email']       ?? '');
    $data['serie']       = trim($_POST['serie']       ?? '');
    $data['score']       = trim($_POST['score']       ?? '');
    $data['gouvernorat'] = trim($_POST['gouvernorat'] ?? '');
    $password            = trim($_POST['password']    ?? '');
    $password_confirm    = trim($_POST['password_confirm'] ?? '');

    // Validation
    if (empty($data['prenom']))   $errors[] = "Le prénom est requis.";
    if (empty($data['nom']))      $errors[] = "Le nom est requis.";
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL))
        $errors[] = "Adresse e-mail invalide.";
    if (strlen($password) < 6)   $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
    if ($password !== $password_confirm) $errors[] = "Les mots de passe ne correspondent pas.";

    if (!empty($data['score'])) {
        $s = (float) str_replace(',', '.', $data['score']);
        if ($s < 0 || $s > 20) $errors[] = "Le score doit être entre 0 et 20.";
        $data['score'] = $s;
    }

    if (empty($errors)) {
        // Vérifier e-mail unique
        $check = $conn->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $check->bind_param("s", $data['email']);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $errors[] = "Cette adresse e-mail est déjà utilisée.";
        }
    }

    if (empty($errors)) {
        $hash  = hashPassword($password);
        $score = $data['score'] !== '' ? (float)$data['score'] : null;
        $stmt  = $conn->prepare("
            INSERT INTO utilisateurs (prenom, nom, email, mot_de_passe, serie, score_bac, gouvernorat, role, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'etudiant', NOW())
        ");
        $stmt->bind_param("sssssds",
            $data['prenom'], $data['nom'], $data['email'],
            $hash, $data['serie'], $score, $data['gouvernorat']
        );

        if ($stmt->execute()) {
            $uid = $conn->insert_id;
            $_SESSION['user_id'] = $uid;
            $_SESSION['prenom']  = $data['prenom'];
            $_SESSION['nom']     = $data['nom'];
            $_SESSION['role']    = 'etudiant';
            $_SESSION['email']   = $data['email'];
            setFlash('success', 'Bienvenue sur OrientTN, ' . $data['prenom'] . ' ! 🎉');
            redirect('index.php');
        } else {
            $errors[] = "Une erreur est survenue. Veuillez réessayer.";
        }
    }
}

$gouvernorats_tn = ['Tunis','Ariana','Ben Arous','Manouba','Nabeul','Zaghouan','Bizerte','Béja','Jendouba','Le Kef','Siliana','Sousse','Monastir','Mahdia','Sfax','Kairouan','Kasserine','Sidi Bouzid','Gabès','Medenine','Tataouine','Gafsa','Tozeur','Kébili'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription — OrientTN</title>
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

        .pw-strength { margin-top: 6px; display: flex; gap: 4px; }
        .pw-bar { flex: 1; height: 3px; border-radius: 3px; background: var(--gray-mid); transition: background 0.3s ease; }
        .pw-bar.active-weak   { background: #ef4444; }
        .pw-bar.active-medium { background: var(--gold); }
        .pw-bar.active-strong { background: #10b981; }
        .pw-hint { font-size: 11px; color: var(--gray-text); margin-top: 4px; }

        .step-indicator {
            display: flex; align-items: center; gap: 0; margin-bottom: 30px;
        }
        .step { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: var(--gray-text); }
        .step-num {
            width: 28px; height: 28px; border-radius: 50%; border: 2px solid var(--gray-mid);
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 700; flex-shrink: 0;
            background: white; color: var(--gray-text); transition: all 0.3s;
        }
        .step.active .step-num { background: var(--red); border-color: var(--red); color: white; }
        .step.active { color: var(--text); }
        .step.done .step-num  { background: #10b981; border-color: #10b981; color: white; }
        .step-line { flex: 1; height: 2px; background: var(--gray-mid); margin: 0 8px; }

        .social-btn {
            display: flex; align-items: center; justify-content: center; gap: 10px;
            width: 100%; padding: 11px; border-radius: var(--radius-sm);
            border: 1.5px solid var(--gray-mid); background: white; cursor: pointer;
            font-family: var(--font-body); font-size: 14px; font-weight: 600; color: var(--text);
            transition: all var(--transition);
        }
        .social-btn:hover { border-color: var(--gray-text); background: var(--gray-soft); }

        .auth-perks { display: flex; flex-direction: column; gap: 12px; margin-bottom: 36px; }
        .perk {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 16px; background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.1); border-radius: var(--radius-md);
        }
        .perk-ico { font-size: 20px; flex-shrink: 0; }
        .perk-lbl { font-size: 13px; font-weight: 600; color: white; }
        .perk-txt { font-size: 11px; color: rgba(255,255,255,0.5); margin-top: 1px; }

        .stats-mini { display: flex; gap: 24px; margin-top: 36px; }
        .smt-num { font-family: var(--font-display); font-size: 1.8rem; font-weight: 700; color: var(--gold); }
        .smt-lbl { font-size: 11px; color: rgba(255,255,255,0.45); margin-top: 2px; }

        .auth-right-wide { padding: 50px 60px; overflow-y: auto; }
        @media (max-width: 768px) {
            .auth-right-wide { padding: 30px 20px; }
        }
    </style>
</head>
<body>
<?php require('nav.php'); ?>

<div class="auth-layout">

    <!-- LEFT -->
    <div class="auth-left">
        <div class="auth-left-content">
            <div class="logo" style="margin-bottom:40px;">
                <div class="logo-mark">O</div>
                <span class="logo-text">Orient<span>TN</span></span>
            </div>

            <h2>Rejoignez<br><em>OrientTN</em></h2>
            <p>Créez votre compte gratuit et bénéficiez d'une orientation universitaire personnalisée en Tunisie.</p>

            <div class="auth-perks">
                <div class="perk">
                    <span class="perk-ico">🎓</span>
                    <div>
                        <div class="perk-lbl">Orientation personnalisée</div>
                        <div class="perk-txt">Filières adaptées à votre score et vos centres d'intérêt</div>
                    </div>
                </div>
                <div class="perk">
                    <span class="perk-ico">❤️</span>
                    <div>
                        <div class="perk-lbl">Liste de favoris</div>
                        <div class="perk-txt">Sauvegardez et comparez les formations qui vous correspondent</div>
                    </div>
                </div>
                <div class="perk">
                    <span class="perk-ico">🤖</span>
                    <div>
                        <div class="perk-lbl">Chatbot IA intelligent</div>
                        <div class="perk-txt">Conseils illimités de notre assistant d'orientation</div>
                    </div>
                </div>
                <div class="perk">
                    <span class="perk-ico">📊</span>
                    <div>
                        <div class="perk-lbl">Suivi de vos vœux</div>
                        <div class="perk-txt">Gérez votre dossier d'orientation étape par étape</div>
                    </div>
                </div>
            </div>

            <div class="stats-mini">
                <div>
                    <div class="smt-num">1200+</div>
                    <div class="smt-lbl">Étudiants inscrits</div>
                </div>
                <div>
                    <div class="smt-num">100%</div>
                    <div class="smt-lbl">Gratuit</div>
                </div>
                <div>
                    <div class="smt-num">24</div>
                    <div class="smt-lbl">Gouvernorats</div>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT -->
    <div class="auth-right auth-right-wide">
        <div class="auth-box" style="max-width:480px;">
            <h1>Créer un compte</h1>
            <p class="auth-sub">Déjà inscrit ? <a href="connexion.php" style="color:var(--red);font-weight:700;">Se connecter →</a></p>

            <?php if(!empty($errors)): ?>
                <div class="alert alert-error">
                    ❌ <?= htmlspecialchars($errors[0]) ?>
                    <?php if(count($errors) > 1): ?>
                        <ul style="margin:8px 0 0 16px;padding:0;">
                            <?php foreach(array_slice($errors,1) as $e): ?>
                                <li style="font-size:13px;margin-bottom:4px;"><?= htmlspecialchars($e) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <!-- Nom & Prénom -->
                <div class="form-row-2">
                    <div class="form-group">
                        <label for="prenom">Prénom <span style="color:var(--red)">*</span></label>
                        <input type="text" id="prenom" name="prenom"
                               value="<?= htmlspecialchars($data['prenom']) ?>"
                               placeholder="Ahmed" required autofocus>
                    </div>
                    <div class="form-group">
                        <label for="nom">Nom <span style="color:var(--red)">*</span></label>
                        <input type="text" id="nom" name="nom"
                               value="<?= htmlspecialchars($data['nom']) ?>"
                               placeholder="Ben Salem" required>
                    </div>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="email">Adresse e-mail <span style="color:var(--red)">*</span></label>
                    <input type="email" id="email" name="email"
                           value="<?= htmlspecialchars($data['email']) ?>"
                           placeholder="votre@email.com" required>
                </div>

                <!-- Mot de passe -->
                <div class="form-group">
                    <label for="password">Mot de passe <span style="color:var(--red)">*</span></label>
                    <div class="password-wrap">
                        <input type="password" id="password" name="password"
                               placeholder="Minimum 6 caractères" required
                               oninput="checkStrength(this.value)">
                        <button type="button" class="toggle-pw" onclick="togglePw('password',this)">👁️</button>
                    </div>
                    <div class="pw-strength">
                        <div class="pw-bar" id="bar1"></div>
                        <div class="pw-bar" id="bar2"></div>
                        <div class="pw-bar" id="bar3"></div>
                    </div>
                    <div class="pw-hint" id="pwHint">Entrez un mot de passe</div>
                </div>

                <div class="form-group">
                    <label for="password_confirm">Confirmer le mot de passe <span style="color:var(--red)">*</span></label>
                    <div class="password-wrap">
                        <input type="password" id="password_confirm" name="password_confirm"
                               placeholder="Répétez le mot de passe" required>
                        <button type="button" class="toggle-pw" onclick="togglePw('password_confirm',this)">👁️</button>
                    </div>
                </div>

                <!-- Profil bac (optionnel) -->
                <div style="background:var(--gray-soft);border-radius:var(--radius-md);padding:18px;margin-bottom:18px;border:1px solid var(--gray-mid);">
                    <p style="font-size:12px;font-weight:700;color:var(--gray-text);text-transform:uppercase;letter-spacing:0.6px;margin-bottom:14px;">🎓 Profil baccalauréat (optionnel)</p>
                    <div class="form-row-2">
                        <div class="form-group" style="margin-bottom:0;">
                            <label for="serie">Série du bac</label>
                            <select id="serie" name="serie">
                                <option value="">Choisir...</option>
                                <option value="Mathématiques"  <?= $data['serie']==='Mathématiques'?'selected':'' ?>>Mathématiques</option>
                                <option value="Sciences"       <?= $data['serie']==='Sciences'?'selected':'' ?>>Sciences Expérimentales</option>
                                <option value="Technique"      <?= $data['serie']==='Technique'?'selected':'' ?>>Technique</option>
                                <option value="Informatique"   <?= $data['serie']==='Informatique'?'selected':'' ?>>Informatique</option>
                                <option value="Economie"       <?= $data['serie']==='Economie'?'selected':'' ?>>Économie et Gestion</option>
                                <option value="Lettres"        <?= $data['serie']==='Lettres'?'selected':'' ?>>Lettres</option>
                                <option value="Sport"          <?= $data['serie']==='Sport'?'selected':'' ?>>Sport</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label for="score">Score du bac (/20)</label>
                            <input type="number" id="score" name="score" step="0.001" min="0" max="20"
                                   value="<?= htmlspecialchars($data['score']) ?>"
                                   placeholder="Ex: 15.250">
                        </div>
                    </div>
                    <div class="form-group" style="margin-top:12px;margin-bottom:0;">
                        <label for="gouvernorat">Gouvernorat</label>
                        <select id="gouvernorat" name="gouvernorat">
                            <option value="">Choisir votre gouvernorat...</option>
                            <?php foreach($gouvernorats_tn as $g): ?>
                                <option value="<?= $g ?>" <?= $data['gouvernorat']===$g?'selected':'' ?>><?= $g ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- CGU -->
                <div class="form-group" style="margin-bottom:22px;">
                    <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;font-weight:400;">
                        <input type="checkbox" name="cgu" required style="width:auto;margin-top:3px;accent-color:var(--red);">
                        <span style="font-size:13px;color:var(--gray-text);">
                            J'accepte les <a href="#" style="color:var(--red);font-weight:600;">Conditions d'utilisation</a>
                            et la <a href="#" style="color:var(--red);font-weight:600;">Politique de confidentialité</a> d'OrientTN.
                        </span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-full btn-lg">
                    Créer mon compte →
                </button>
            </form>

            <div class="auth-sep"><span>ou continuer avec</span></div>

            <div style="display:flex;flex-direction:column;gap:10px;">
                <button class="social-btn"><span>🌐</span> Continuer avec Google</button>
                <button class="social-btn"><span>📘</span> Continuer avec Facebook</button>
            </div>
        </div>
    </div>

</div>

<script>
function togglePw(id, btn) {
    var input = document.getElementById(id);
    if (input.type === 'password') {
        input.type = 'text'; btn.textContent = '🙈';
    } else {
        input.type = 'password'; btn.textContent = '👁️';
    }
}

function checkStrength(val) {
    var bars  = [document.getElementById('bar1'), document.getElementById('bar2'), document.getElementById('bar3')];
    var hint  = document.getElementById('pwHint');
    bars.forEach(b => { b.className = 'pw-bar'; });
    if (val.length === 0) { hint.textContent = 'Entrez un mot de passe'; return; }
    var score = 0;
    if (val.length >= 6) score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val) && /[0-9]/.test(val)) score++;
    var cls   = ['active-weak','active-medium','active-strong'];
    var msgs  = ['Faible','Moyen','Fort'];
    var color = ['#ef4444','var(--gold)','#10b981'];
    for (var i = 0; i < score; i++) bars[i].classList.add(cls[score-1]);
    hint.textContent = 'Force : ' + msgs[score-1];
    hint.style.color = color[score-1];
}
</script>
</body>
</html>
