<?php
session_start();
require('config.php');

// Session chatbot
if (!isset($_SESSION['chat_id'])) {
    $_SESSION['chat_id'] = uniqid('chat_', true);
}
$chat_id = $_SESSION['chat_id'];
$user_id = isLoggedIn() ? intval($_SESSION['user_id']) : 'NULL';

// Charger historique
$messages = $conn->query("
    SELECT role, contenu, created_at
    FROM messages_chatbot
    WHERE session_id = '$chat_id'
    ORDER BY created_at ASC
    LIMIT 50
")->fetch_all(MYSQLI_ASSOC);

// Si pas d'historique, message de bienvenue
if (empty($messages)) {
    $welcome = "Bonjour ! 👋 Je suis <strong>TN Guide</strong>, votre assistant d'orientation universitaire.<br><br>Pour vous aider au mieux, dites-moi :<br>• Votre <strong>score du bac</strong> et votre série<br>• Votre <strong>domaine d'intérêt</strong><br>• Vos <strong>ambitions professionnelles</strong><br><br>Je suis là pour vous guider vers la meilleure filière ! 🎓";
    $conn->query("INSERT INTO messages_chatbot (utilisateur_id, session_id, role, contenu) VALUES ($user_id, '$chat_id', 'bot', '" . $conn->real_escape_string($welcome) . "')");
    $messages[] = ['role'=>'bot','contenu'=>$welcome,'created_at'=>date('Y-m-d H:i:s')];
}

// Traitement message entrant (AJAX ou POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $userMsg = trim($_POST['message']);
    if (!empty($userMsg) && mb_strlen($userMsg) <= 500) {
        // Sauvegarder message utilisateur
        $safeMsg = $conn->real_escape_string($userMsg);
        $conn->query("INSERT INTO messages_chatbot (utilisateur_id, session_id, role, contenu) VALUES ($user_id, '$chat_id', 'user', '$safeMsg')");

        // Générer réponse
        $botReponse = chatbotReponse($userMsg, $conn, isLoggedIn() ? $_SESSION : null);
        $safeBotReponse = $conn->real_escape_string($botReponse);
        $conn->query("INSERT INTO messages_chatbot (utilisateur_id, session_id, role, contenu) VALUES ($user_id, '$chat_id', 'bot', '$safeBotReponse')");

        // Réponse AJAX
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['reponse' => $botReponse]);
            exit();
        }
    }
    redirect('chatbot.php');
}

// Reset conversation
if (isset($_GET['reset'])) {
    $conn->query("DELETE FROM messages_chatbot WHERE session_id = '$chat_id'");
    unset($_SESSION['chat_id']);
    redirect('chatbot.php');
}

// Pré-remplissage depuis GET
$prefill = isset($_GET['prefill']) ? htmlspecialchars($_GET['prefill']) : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot IA — OrientTN</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .typing-indicator {
            display: none;
            gap: 5px;
            align-items: center;
            padding: 12px 15px;
            background: white;
            border-radius: 16px;
            border-bottom-left-radius: 4px;
            box-shadow: var(--shadow-sm);
            width: fit-content;
        }
        .typing-indicator.show { display: flex; }
        .typing-dot {
            width: 7px; height: 7px;
            background: var(--gray-text);
            border-radius: 50%;
            animation: typingBounce 1.2s infinite;
        }
        .typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-dot:nth-child(3) { animation-delay: 0.4s; }
        @keyframes typingBounce {
            0%,60%,100% { transform: translateY(0); }
            30%          { transform: translateY(-6px); }
        }
        .chatbot-msgs::-webkit-scrollbar { width: 4px; }
        .chatbot-msgs::-webkit-scrollbar-thumb { background: var(--gray-mid); border-radius: 2px; }
        .msg-time { font-size: 10px; color: var(--gray-text); margin-top: 3px; text-align: right; }
        .msg.bot .msg-time { text-align: left; }
    </style>
</head>
<body>
<?php require('nav.php'); ?>

<!-- BANNER -->
<div class="detail-banner" style="padding:50px 60px;">
    <div style="position:relative;z-index:1;max-width:1240px;margin:0 auto;">
        <div class="breadcrumb">
            <a href="index.php">Accueil</a>
            <span class="sep">/</span>
            <span>Chatbot IA</span>
        </div>
        <h1 style="font-family:var(--font-display);font-size:2.2rem;font-weight:700;color:white;margin-bottom:10px;">
            🤖 TN Guide — Assistant d'Orientation IA
        </h1>
        <p style="color:rgba(255,255,255,0.6);">Obtenez des recommandations personnalisées basées sur votre profil</p>
    </div>
    <div style="position:absolute;inset:0;background:radial-gradient(circle at 30% 50%,rgba(212,168,83,0.15) 0%,transparent 50%);pointer-events:none;"></div>
</div>

<section class="section section-alt">
    <div class="container">
        <div style="display:grid;grid-template-columns:1fr 320px;gap:32px;align-items:start;">

            <!-- CHATBOT PRINCIPAL -->
            <div>
                <div class="chatbot-wrap">
                    <!-- Header -->
                    <div class="chatbot-hdr">
                        <div class="chatbot-ava">🤖</div>
                        <div class="chatbot-hdr-info">
                            <h3>TN Guide</h3>
                            <p>Assistant d'orientation universitaire</p>
                        </div>
                        <div class="online-dot" title="En ligne"></div>
                        <a href="chatbot.php?reset=1"
                           style="margin-left:12px;color:rgba(255,255,255,0.4);font-size:12px;transition:color 0.2s;"
                           onmouseover="this.style.color='white'" onmouseout="this.style.color='rgba(255,255,255,0.4)'"
                           title="Nouvelle conversation">🔄 Réinitialiser</a>
                    </div>

                    <!-- Suggestions rapides -->
                    <div class="chatbot-sug" id="suggestions">
                        <span style="font-size:12px;color:var(--gray-text);font-weight:600;width:100%;margin-bottom:4px;">Suggestions :</span>
                        <button class="sug-chip" onclick="sendSuggestion(this)">Mon score est 15.5 en math</button>
                        <button class="sug-chip" onclick="sendSuggestion(this)">Filières médecine disponibles</button>
                        <button class="sug-chip" onclick="sendSuggestion(this)">Informatique et IA</button>
                        <button class="sug-chip" onclick="sendSuggestion(this)">Quelle université choisir ?</button>
                        <button class="sug-chip" onclick="sendSuggestion(this)">Score minimum pour ingénierie</button>
                    </div>

                    <!-- Messages -->
                    <div class="chatbot-msgs" id="chatMessages">
                        <?php foreach($messages as $msg): ?>
                        <div class="msg <?= $msg['role'] ?>">
                            <div class="msg-ava <?= $msg['role'] === 'bot' ? 'bot-ava' : 'user-ava' ?>">
                                <?= $msg['role'] === 'bot' ? '🤖' : '👤' ?>
                            </div>
                            <div>
                                <div class="msg-bubble"><?= $msg['contenu'] ?></div>
                                <div class="msg-time"><?= date('H:i', strtotime($msg['created_at'])) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <!-- Indicateur de frappe -->
                        <div class="msg bot" id="typingMsg" style="display:none;">
                            <div class="msg-ava bot-ava">🤖</div>
                            <div class="typing-indicator show">
                                <div class="typing-dot"></div>
                                <div class="typing-dot"></div>
                                <div class="typing-dot"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Zone de saisie -->
                    <div class="chatbot-inp">
                        <input type="text" id="chatInput" class="chatbot-inp input"
                               placeholder="Écrivez votre message... (Ex: Mon score est 14.5, quelle filière ?)"
                               value="<?= $prefill ?>"
                               maxlength="400"
                               autocomplete="off">
                        <button onclick="sendMessage()" class="btn btn-primary btn-sm" id="sendBtn">
                            ➤
                        </button>
                    </div>
                </div>
            </div>

            <!-- PANNEAU LATÉRAL -->
            <div style="display:flex;flex-direction:column;gap:20px;">

                <!-- Profil rapide -->
                <?php if(isLoggedIn()): ?>
                <div class="info-card">
                    <h4 style="font-family:var(--font-display);font-size:1.1rem;color:var(--text);margin-bottom:16px;">👤 Votre profil</h4>
                    <div style="display:flex;flex-direction:column;gap:10px;font-size:14px;">
                        <div style="display:flex;justify-content:space-between;">
                            <span style="color:var(--gray-text);">Nom</span>
                            <strong><?= htmlspecialchars(($_SESSION['prenom']??'').' '.($_SESSION['nom']??'')) ?></strong>
                        </div>
                        <?php
                        $userData = $conn->query("SELECT bac_score, bac_serie FROM utilisateurs WHERE id=".intval($_SESSION['user_id']))->fetch_assoc();
                        if($userData && $userData['bac_score']):
                        ?>
                        <div style="display:flex;justify-content:space-between;">
                            <span style="color:var(--gray-text);">Score Bac</span>
                            <strong style="color:var(--red);"><?= number_format($userData['bac_score'],3) ?> / 20</strong>
                        </div>
                        <?php endif; ?>
                        <?php if($userData && $userData['bac_serie']): ?>
                        <div style="display:flex;justify-content:space-between;">
                            <span style="color:var(--gray-text);">Série</span>
                            <strong><?= htmlspecialchars($userData['bac_serie']) ?></strong>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if($userData && $userData['bac_score']): ?>
                        <a href="recherche.php?score=<?= $userData['bac_score'] ?>"
                           class="btn btn-primary btn-sm btn-full" style="margin-top:14px;">
                            Voir mes filières compatibles →
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Conseils d'utilisation -->
                <div class="info-card" style="background:linear-gradient(135deg,var(--navy),var(--navy-mid));border:none;">
                    <h4 style="font-family:var(--font-display);font-size:1rem;color:white;margin-bottom:14px;">💡 Conseils d'utilisation</h4>
                    <ul style="display:flex;flex-direction:column;gap:10px;">
                        <?php
                        $tips = [
                            ['icon'=>'📊','text'=>'Mentionnez votre score exact (ex: 15.750)'],
                            ['icon'=>'🎓','text'=>'Précisez votre série du bac'],
                            ['icon'=>'💼','text'=>'Décrivez vos aspirations professionnelles'],
                            ['icon'=>'📍','text'=>'Indiquez votre région préférée'],
                        ];
                        foreach($tips as $tip):
                        ?>
                        <li style="display:flex;align-items:flex-start;gap:10px;font-size:13px;color:rgba(255,255,255,0.7);">
                            <span style="font-size:16px;"><?= $tip['icon'] ?></span>
                            <?= $tip['text'] ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Liens rapides -->
                <div class="info-card">
                    <h4 style="font-family:var(--font-display);font-size:1rem;color:var(--text);margin-bottom:14px;">🔗 Liens rapides</h4>
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        <a href="recherche.php" class="btn btn-outline btn-sm btn-full">🔍 Recherche avancée</a>
                        <a href="filieres.php"  class="btn btn-dark btn-sm btn-full">📚 Toutes les filières</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require('footer.php'); ?>

<script>
const chatMessages = document.getElementById('chatMessages');
const chatInput    = document.getElementById('chatInput');
const typingMsg    = document.getElementById('typingMsg');
const sendBtn      = document.getElementById('sendBtn');

// Scroll bas
function scrollBottom() {
    chatMessages.scrollTop = chatMessages.scrollHeight;
}
scrollBottom();

// Envoyer message
async function sendMessage() {
    const text = chatInput.value.trim();
    if (!text) return;

    appendMessage('user', text, new Date().toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit'}));
    chatInput.value = '';
    sendBtn.disabled = true;
    document.getElementById('suggestions').style.display = 'none';

    // Afficher indicateur de frappe
    typingMsg.style.display = 'flex';
    scrollBottom();

    try {
        const response = await fetch('chatbot.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'message=' + encodeURIComponent(text)
        });
        const data = await response.json();
        typingMsg.style.display = 'none';
        appendMessage('bot', data.reponse, new Date().toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit'}));
    } catch(e) {
        typingMsg.style.display = 'none';
        appendMessage('bot', '⚠️ Une erreur s\'est produite. Veuillez réessayer.', '');
    }
    sendBtn.disabled = false;
    chatInput.focus();
}

function appendMessage(role, content, time) {
    const ava = role === 'bot' ? '🤖' : '👤';
    const bubbleClass = role === 'bot' ? 'background:white;color:var(--text);box-shadow:var(--shadow-sm);border-bottom-left-radius:4px;' : 'background:var(--navy);color:white;border-bottom-right-radius:4px;';
    const alignStyle  = role === 'user' ? 'flex-direction:row-reverse;margin-left:auto;' : '';
    const timeAlign   = role === 'user' ? 'text-align:right;' : 'text-align:left;';
    const avaClass    = role === 'bot' ? 'background:linear-gradient(135deg,var(--red),var(--red-dark));' : 'background:linear-gradient(135deg,var(--navy),var(--navy-mid));color:white;';

    const msgEl = document.createElement('div');
    msgEl.className = 'msg ' + role;
    msgEl.style.cssText = `display:flex;gap:10px;align-items:flex-end;max-width:82%;animation:msgIn 0.3s ease;${alignStyle}`;
    msgEl.innerHTML = `
        <div class="msg-ava" style="width:30px;height:30px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0;${avaClass}">${ava}</div>
        <div>
            <div class="msg-bubble" style="padding:11px 15px;border-radius:16px;font-size:14px;line-height:1.6;${bubbleClass}">${content}</div>
            <div style="font-size:10px;color:var(--gray-text);margin-top:3px;${timeAlign}">${time}</div>
        </div>
    `;
    chatMessages.insertBefore(msgEl, typingMsg);
    scrollBottom();
}

function sendSuggestion(btn) {
    chatInput.value = btn.textContent;
    sendMessage();
}

// Entrée clavier
chatInput.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
});

// Si pré-remplissage
<?php if($prefill): ?>
window.addEventListener('load', () => { setTimeout(sendMessage, 600); });
<?php endif; ?>
</script>
</body>
</html>