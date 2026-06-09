<?php
// Ativar erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// INICIAR SESSÃO
session_start();

// ==========================================
// ATALHO: SE JÁ ESTÁ LOGADO, SALTA O LOGIN
// ==========================================
if (isset($_SESSION['user_id'])) {
    header("Location: inventory.php");
    exit();
}

// Ligar à base de dados
require_once 'scripts/database.php';

$erro_login = '';

// PROCESSAR O LOGIN
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        $username_safe = SQLite3::escapeString($username);
        
        // Procurar o utilizador
        $query = "SELECT * FROM users WHERE username = '$username_safe'";
        $result = $db->querySingle($query, true);
        
        // Verificar se encontrou e se a password está correta
        if ($result && $result['password'] === $password) {
            
            // 🛡️ A NOVA LINHA DE SEGURANÇA ENTRA AQUI 🛡️
            session_regenerate_id(true);
            
            // LOGIN COM SUCESSO!
            // 1. Guardar info na sessão
            $_SESSION['user_id'] = $result['id'];
            $_SESSION['username'] = $result['username'];
            $_SESSION['is_admin'] = $result['is_admin'];
            
            // 2. Atualizar o Último Acesso
            date_default_timezone_set('Europe/Lisbon');
            $agora = date('Y-m-d H:i:s');
            $db->exec("UPDATE users SET ultimo_acesso = '$agora' WHERE id = " . $result['id']);
            
            // ==========================================================
            // 2.5 REGISTAR O LOGIN NO HISTÓRICO DA BASE DE DADOS (NOVO)
            // ==========================================================
            $user_id_log = (int)$result['id'];
            $descricao_login = "🔑 Efetuou Login com Sucesso";
            $db->exec("INSERT INTO events (user_id, event_date, description) VALUES ($user_id_log, '$agora', '$descricao_login')");
            // ==========================================================
            
            // 3. Redirecionar
            header("Location: inventory.php");
            exit();
        } else {
            $erro_login = 'ACCESS DENIED: Username ou Password incorretos.';
        }
    } else {
        $erro_login = 'SYSTEM ERROR: Preencha todos os campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loot Ledger Vault • Login</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=VT323&display=swap');
        
        :root {
            --arcane-gold: #F5D21D;
            --error-red: #ff4d4d;
        }
        
        body {
            margin: 0;
            padding: 0;
            background: #000 url('images/fundo_do_Index_estrelas.png') center/cover no-repeat fixed;
            color: var(--arcane-gold);
            font-family: 'VT323', monospace;
            overflow: hidden;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        #canvas {
            position: absolute;
            top: 0; left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            opacity: 0.35;
            mix-blend-mode: screen;
        }
        
        .login-container {
            z-index: 2;
            width: 420px;
            text-align: center;
            border: 3px solid var(--arcane-gold);
            padding: 50px 40px;
            background: rgba(10, 10, 10, 0.92);
            box-shadow: 0 0 50px var(--arcane-gold);
        }
        
        h1 {
            font-size: 3.2rem;
            margin: 0 0 30px 0;
            text-shadow: 0 0 25px var(--arcane-gold);
            letter-spacing: 6px;
        }
        
        .subtitle {
            font-size: 1.6rem;
            margin-bottom: 40px;
            opacity: 0.9;
        }
        
        .input-group {
            margin-bottom: 25px;
            text-align: left;
        }
        
        label {
            display: block;
            font-size: 1.4rem;
            margin-bottom: 8px;
            color: var(--arcane-gold);
        }
        
        input {
            width: 100%;
            padding: 14px;
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid var(--arcane-gold);
            color: var(--arcane-gold);
            font-family: 'VT323', monospace;
            font-size: 1.5rem;
            box-shadow: 0 0 15px rgba(245, 210, 29, 0.3);
            box-sizing: border-box;
        }
        
        input:focus {
            outline: none;
            box-shadow: 0 0 25px var(--arcane-gold);
            border-color: #fff;
        }
        
        .login-btn {
            width: 100%;
            padding: 18px;
            font-size: 2rem;
            background: transparent;
            color: var(--arcane-gold);
            border: 3px solid var(--arcane-gold);
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            margin-top: 20px;
        }
        
        .login-btn:hover {
            background: var(--arcane-gold);
            color: #000;
            box-shadow: 0 0 50px var(--arcane-gold);
        }
        
        .scanline {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                to bottom,
                transparent 50%,
                rgba(245, 210, 29, 0.08) 50%
            );
            background-size: 100% 6px;
            pointer-events: none;
            animation: scan 4s linear infinite;
            z-index: 3;
        }
        
        @keyframes scan {
            0% { background-position: 0 0; }
            100% { background-position: 0 100%; }
        }
        
        .note {
            font-size: 1rem;
            margin-top: 30px;
            opacity: 0.6;
        }

        .error-msg {
            color: var(--error-red);
            font-size: 1.2rem;
            margin-bottom: 20px;
            text-shadow: 0 0 10px var(--error-red);
            border: 1px solid var(--error-red);
            padding: 10px;
            background: rgba(255, 77, 77, 0.1);
        }
    </style>
</head>
<body>
    <canvas id="canvas"></canvas>
    
    <div class="login-container">
        <h1>LOOT LEDGER VAULT</h1>
        <p class="subtitle">ACCESS TERMINAL</p>
        
        <?php if (!empty($erro_login)): ?>
            <div class="error-msg"><?php echo htmlspecialchars($erro_login); ?></div>
        <?php endif; ?>
        
        <form action="login.php" method="POST" id="loginForm" novalidate>
            <div class="input-group">
                <label>USERNAME</label>
                <input type="text" name="username" id="username" placeholder="contractor_42">
            </div>
            
            <div class="input-group">
                <label>PASSWORD</label>
                <input type="password" name="password" id="password" placeholder="••••••••">
            </div>
            
            <button type="submit" class="login-btn">ENTER THE VAULT</button>
        </form>
        
        <p class="note">
            Informação do utilizador é temporária.<br>
            Máximo anonimato ativado.
        </p>
        
        <p style="font-size:0.9rem; margin-top:40px; opacity:0.5;">
            Grupo 4 • Ana Luísa • João Afonso • André Assunção
        </p>
    </div>
    
    <div class="scanline"></div>

    <script>
        const canvas = document.getElementById('canvas');
        const ctx = canvas.getContext('2d');
        
        function resizeCanvas() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }
        
        const characters = 'ᚠᚢᚦᚨᚱᚲᚷᚹᚺᚾᛁᛃᛇᛈᛉᛊᛋᛏᛒᛖᛗᛚᛜᛝᛞᛟ☿♀♂♃♄♅♆♇🔮✧⚝☯♾️🜁🜂🜃🜄🜅01★';
        const fontSize = 17;
        let drops = [];
        
        function initRain() {
            drops = [];
            const columns = canvas.width / fontSize;
            for (let x = 0; x < columns; x++) {
                drops[x] = Math.floor(Math.random() * canvas.height / fontSize);
            }
        }
        
        function draw() {
            ctx.fillStyle = 'rgba(0, 0, 0, 0.035)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            ctx.fillStyle = '#F5D21D';
            ctx.font = `${fontSize}px monospace`;
            
            for (let i = 0; i < drops.length; i++) {
                const text = characters[Math.floor(Math.random() * characters.length)];
                ctx.fillText(text, i * fontSize, drops[i] * fontSize);
                
                if (drops[i] * fontSize > canvas.height && Math.random() > 0.975) {
                    drops[i] = 0;
                }
                drops[i]++;
            }
        }
        
        function animate() {
            draw();
            requestAnimationFrame(animate);
        }
        
        window.addEventListener('resize', () => {
            resizeCanvas();
            initRain();
        });
        
        resizeCanvas();
        initRain();
        animate();
        
    console.log('%cLoot Ledger Vault – Login Terminal loaded', 'color:#F5D21D; font-family:monospace');
    </script>
    
    <script src="scripts/login_validation.js"></script>
</body>
</html>