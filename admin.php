<?php
// INICIAR A SESSÃO PARA SABER QUEM ESTÁ A ACEDER
session_start();

// VERIFICAÇÃO DE SEGURANÇA (Porteiro do Painel de Admin)
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] < 1) {
    header("Location: inventory.php");
    exit();
}

// Ativar erros para debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Liga à Base de Dados
require_once 'scripts/database.php';

$mensagem = '';

// Processar formulários (Adicionar / Remover Utilizadores)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'add_user') {
        $username = trim($_POST['username'] ?? '');
        $password_plain = $_POST['password'] ?? '';
        $is_admin = isset($_POST['is_admin']) ? 1 : 0;

        if (!empty($username) && !empty($password_plain)) {
            $username_safe = SQLite3::escapeString($username);
            $password_safe = SQLite3::escapeString($password_plain);

            $query = "INSERT INTO users (username, password, is_admin) VALUES ('$username_safe', '$password_safe', $is_admin)";
            
            if (@$db->exec($query)) {
                $mensagem = "Utilizador criado com sucesso!";
            } else {
                $mensagem = "Erro: Este Username já existe!";
            }
        }
    }
    
    if ($_POST['action'] === 'remove_user') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        
        if ($user_id > 0) {
            $check_role = $db->querySingle("SELECT is_admin FROM users WHERE id = $user_id");
            if ($check_role == 2) {
                $mensagem = "Acesso Negado: Contas Super Admin estão protegidas e não podem ser apagadas!";
            } else {
                $db->exec("DELETE FROM users WHERE id = $user_id");
                $mensagem = "Utilizador removido com sucesso!";
            }
        }
    }
}

// 1. Lista de utilizadores para a tabela de Gestão
$result_users = $db->query("SELECT * FROM users ORDER BY id ASC");

// 2. QUERY DO HISTÓRICO COMPLETO: Captura todos os logins (pesquisa pelo emoji da chave)
$result_logs = $db->query("
    SELECT e.event_date, e.description, u.username, u.is_admin 
    FROM events e 
    JOIN users u ON e.user_id = u.id 
    WHERE e.description LIKE '🔑%' 
    ORDER BY e.id DESC
");
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Gestão e Acessos</title>
    <link rel="stylesheet" href="styles/style.css">
    <style>
        button { background-color: #ffaa00; color: #1a1a1a; font-weight: bold; padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; transition: background 0.2s; }
        button:hover { background-color: #e69900; }
        input[type="text"], input[type="password"] { padding: 8px; margin: 5px 0 15px 0; border-radius: 3px; border: 1px solid #444; background: #333; color: white; width: 100%; max-width: 300px; display: block; }
        .hash-text { font-family: monospace; font-size: 0.9em; overflow-wrap: break-word; }
        .msg-aviso { background-color: #333; color: #ffaa00; padding: 10px; border-left: 4px solid #ffaa00; margin-bottom: 20px; font-weight: bold; max-width: 1000px; margin-left: auto; margin-right: auto;}
        .admin-section { margin-bottom: 40px; }
    </style>
</head>
<body>
    <header>
        <h1>Painel de Administração</h1>
        <nav style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
            <a href="index.html">Início</a>
            <a href="inventory.php">Inventário</a>
            <a href="calendario.php">Calendário</a>
            
            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] > 0): ?>
                <a href="admin.php">Admin</a>
            <?php endif; ?>
            
            <div style="margin-left: auto; display: flex; align-items: center; gap: 15px;">
                <span style="color: #ffaa00; font-family: 'VT323', monospace; font-size: 1.2rem;">
                    👤 Bem-vindo, <strong style="color: #fff;"><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                    <?php if ($_SESSION['is_admin'] == 2): ?>
                        <span style="color: #b300ff;" title="Super Admin">★</span>
                    <?php elseif ($_SESSION['is_admin'] == 1): ?>
                        <span style="color: #ff4d4d;" title="Admin">★</span>
                    <?php endif; ?>
                </span>
                <a href="logout.php" style="color: #ff4d4d; font-weight: bold; padding: 5px 10px; border: 1px solid #ff4d4d; border-radius: 4px; text-decoration: none;">Sair</a>
            </div>
        </nav>
    </header>

    <main>
        <?php if (!empty($mensagem)): ?>
            <div class="msg-aviso"><?php echo htmlspecialchars($mensagem); ?></div>
        <?php endif; ?>

        <section class="inventory-container admin-section" style="margin-top: 20px;">
            <h2>Criar Nova Conta</h2>
            <form action="admin.php" method="POST">
                <input type="hidden" name="action" value="add_user">
                
                <label for="username">Username:</label>
                <input type="text" name="username" id="username" placeholder="Nome de utilizador" required>
                
                <label for="password">Password:</label>
                <input type="password" name="password" id="password" placeholder="Palavra-passe secreta" required>
                
                <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px; cursor: pointer; color: white;">
                    <input type="checkbox" name="is_admin" value="1" style="width: 18px; height: 18px; accent-color: #ffaa00;">
                    Esta conta é de Administrador
                </label>
                
                <button type="submit">Criar Conta</button>
            </form>
        </section>

        <section class="inventory-container admin-section">
            <h2>Utilizadores Registados & Gestão</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Password</th>
                        <th>Tipo</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $temUsers = false;
                    while ($user = $result_users->fetchArray(SQLITE3_ASSOC)): 
                        $temUsers = true;
                    ?>
                        <tr>
                            <td data-label="ID"><?php echo $user['id']; ?></td>
                            <td data-label="Username" style="font-weight: bold; color: #00cc66;"><?php echo htmlspecialchars($user['username']); ?></td>
                            
                            <td data-label="Password">
                                <span class="hash-text" style="color: #ffaa00; font-weight: bold;">
                                    <?php echo htmlspecialchars($user['password']); ?>
                                </span>
                            </td>
                            
                            <td data-label="Tipo" style="font-weight: bold; <?php 
                                if ($user['is_admin'] == 2) echo 'color: #b300ff;'; 
                                elseif ($user['is_admin'] == 1) echo 'color: #ff4d4d;'; 
                                else echo 'color: #888;'; 
                            ?>">
                                <?php 
                                    if ($user['is_admin'] == 2) echo 'SUPER ADMIN';
                                    elseif ($user['is_admin'] == 1) echo 'Admin';
                                    else echo 'Normal';
                                ?>
                            </td>
                            
                            <td data-label="Ação">
                                <?php if ($user['is_admin'] == 2): ?>
                                    <span style="color: #b300ff; font-style: italic; font-weight: bold;">Protegido</span>
                                <?php else: ?>
                                    <form method="POST" action="admin.php" style="margin: 0;">
                                        <input type="hidden" name="action" value="remove_user">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" onclick="return confirm('Apagar o utilizador <?php echo addslashes(htmlspecialchars($user['username'])); ?>?');" style="background-color: #ff4d4d; color: white;">Apagar Conta</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    
                    <?php if (!$temUsers): ?>
                        <tr><td colspan="5" style="text-align: center;">Nenhum utilizador registado.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <section class="inventory-container admin-section">
            <h2>🔍 Registo de Acessos do Sistema (Histórico Completo)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Utilizador</th>
                        <th>Nível de Cargo</th>
                        <th>Data do Acesso</th>
                        <th>Registo do Terminal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $temLogs = false;
                    while ($log = $result_logs->fetchArray(SQLITE3_ASSOC)): 
                        $temLogs = true;
                    ?>
                        <tr>
                            <td data-label="Utilizador" style="font-weight: bold; color: #fff;"><?php echo htmlspecialchars($log['username']); ?></td>
                            
                            <td data-label="Nível de Cargo" style="font-weight: bold; <?php 
                                if ($log['is_admin'] == 2) echo 'color: #b300ff;'; 
                                elseif ($log['is_admin'] == 1) echo 'color: #ff4d4d;'; 
                                else echo 'color: #888;'; 
                            ?>">
                                <?php 
                                    if ($log['is_admin'] == 2) echo 'SUPER ADMIN';
                                    elseif ($log['is_admin'] == 1) echo 'Admin';
                                    else echo 'Normal';
                                ?>
                            </td>
                            
                            <td data-label="Data do Acesso" style="font-family: monospace; font-size: 1.1rem; color: #ffaa00;">
                                <?php echo htmlspecialchars($log['event_date']); ?>
                            </td>
                            
                            <td data-label="Registo do Terminal">
                                <span style="color: #00cc66; font-weight: bold;">
                                    <?php echo htmlspecialchars($log['description']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    
                    <?php if (!$temLogs): ?>
                        <tr><td colspan="4" style="text-align: center;">Nenhum registo de acesso encontrado.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

    </main>
</body>
</html>