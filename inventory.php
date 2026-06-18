<?php
// INICIAR A SESSÃO PARA SABER QUEM ESTÁ A ACEDER
session_start();

// VERIFICAÇÃO DE SEGURANÇA
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Ativar erros para debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Liga à Base de Dados
require_once 'scripts/database.php';

// Criar a tabela específica (caso não exista)
$db->exec("CREATE TABLE IF NOT EXISTS games_inventory (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    platform TEXT,
    stock INTEGER DEFAULT 0,
    price REAL DEFAULT 0.0,
    img TEXT
)");

// Processar formulários (Adicionar / Remover / Atualizar Stock / Atualizar Preço)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    
    $hoje = date('Y-m-d'); // Guarda a data de hoje
    $user_id = $_SESSION['user_id']; // Sabe quem fez a ação
    
    // AÇÃO: ADICIONAR JOGO NOVO
    if ($_POST['action'] === 'add') {
        $title = SQLite3::escapeString($_POST['title'] ?? '');
        $platform = SQLite3::escapeString($_POST['platform'] ?? '');
        $stock = (int)($_POST['stock'] ?? 0);
        $price = (float)($_POST['price'] ?? 0.0);
        
        $imgPath = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK && !empty($_FILES['image']['name'])) {
            if (!is_dir('images')) mkdir('images', 0777, true);
            $imgPath = 'images/' . basename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'], $imgPath);
        }

        if (!empty($title)) {
            $db->exec("INSERT INTO games_inventory (title, platform, stock, price, img) 
                       VALUES ('$title', '$platform', $stock, $price, '$imgPath')");
                       
            // REGISTAR NO CALENDÁRIO
            $desc = SQLite3::escapeString("🎮 Novo jogo: $title ($stock un.)");
            $db->exec("INSERT INTO events (event_date, description, user_id) VALUES ('$hoje', '$desc', $user_id)");
        }
    }
    
    // AÇÃO: ATUALIZAR UNIDADES DE STOCK (+ ou -)
    if ($_POST['action'] === 'update_stock') {
        $item_id = (int)($_POST['item_id'] ?? 0);
        $qty = (int)($_POST['qty'] ?? 0);
        $operation = $_POST['operation'] ?? '';

        if ($item_id > 0 && $qty > 0) {
            $jogo = $db->querySingle("SELECT title, stock FROM games_inventory WHERE id = $item_id", true);
            
            if ($jogo) {
                $current_stock = $jogo['stock'];
                $nome_jogo = SQLite3::escapeString($jogo['title']);
                $desc = '';

                if ($operation === 'add') {
                    $new_stock = $current_stock + $qty;
                    $desc = "📦 Entrada: $nome_jogo (+$qty)";
                } elseif ($operation === 'sub') {
                    $new_stock = max(0, $current_stock - $qty);
                    $desc = "📤 Saída: $nome_jogo (-$qty)";
                } else {
                    $new_stock = $current_stock;
                }
                
                $db->exec("UPDATE games_inventory SET stock = $new_stock WHERE id = $item_id");
                
                // REGISTAR NO CALENDÁRIO
                if (!empty($desc)) {
                    $db->exec("INSERT INTO events (event_date, description, user_id) VALUES ('$hoje', '$desc', $user_id)");
                }
            }
        }
    }

    // AÇÃO: ATUALIZAR PREÇO (EXCLUSIVO PARA ADMINS)
    if ($_POST['action'] === 'update_price') {
        // Bloqueio de segurança: Se não for admin, ignora a ação
        if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] > 0) {
            $item_id = (int)($_POST['item_id'] ?? 0);
            $new_price = (float)($_POST['price'] ?? 0.0);

            if ($item_id > 0 && $new_price >= 0) {
                $jogo_title = $db->querySingle("SELECT title FROM games_inventory WHERE id = $item_id");
                
                if ($jogo_title) {
                    $db->exec("UPDATE games_inventory SET price = $new_price WHERE id = $item_id");
                    
                    // Registar no calendário
                    $nome_jogo = SQLite3::escapeString($jogo_title);
                    $desc = SQLite3::escapeString("💰 Preço alterado: $nome_jogo para " . number_format($new_price, 2) . "€");
                    $db->exec("INSERT INTO events (event_date, description, user_id) VALUES ('$hoje', '$desc', $user_id)");
                }
            }
        }
    }

    // AÇÃO: REMOVER JOGO INTEIRO
    if ($_POST['action'] === 'remove') {
        $item_id = (int)($_POST['item_id'] ?? 0);
        if ($item_id > 0) {
            $titulo = $db->querySingle("SELECT title FROM games_inventory WHERE id = $item_id");
            if ($titulo) {
                $db->exec("DELETE FROM games_inventory WHERE id = $item_id");
                
                // REGISTAR NO CALENDÁRIO
                $desc = SQLite3::escapeString("🗑️ Removido: $titulo");
                $db->exec("INSERT INTO events (event_date, description, user_id) VALUES ('$hoje', '$desc', $user_id)");
            }
        }
    }

    header("Location: inventory.php");
    exit();
}

$result = $db->query("SELECT * FROM games_inventory");
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventário - Loja de Jogos</title>
    <link rel="stylesheet" href="styles/style.css">
    <style>
        button { background-color: #ffaa00; color: #1a1a1a; font-weight: bold; padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; transition: background 0.2s;}
        button:hover { background-color: #e69900; }
        input { padding: 5px; margin: 5px 0; border-radius: 3px; border: 1px solid #444; background: #333; color: white; }
        
        .sem-capa { width: 60px; height: 80px; background-color: #333; border: 1px dashed #555; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #888; text-align: center; }
        
        .col-remover { display: none !important; }
        .modo-remover .col-remover { display: table-cell !important; }
        @media screen and (max-width: 768px) {
            .modo-remover .col-remover { display: block !important; }
        }

        .acoes-container { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .qtd-input { width: 50px; text-align: center; padding: 6px; margin: 0; font-family: 'VT323', monospace; font-size: 1.1rem;}
        
        /* ESTILOS DE PREÇO (VISÍVEL VS MODO EDIÇÃO) */
        .price-input { width: 70px; text-align: center; padding: 4px; font-size: 1rem; color: #00cc66; font-weight: bold; border: 1px solid #00cc66; background: #262626;}
        .price-form { display: none !important; }
        .price-static { display: inline !important; }
        
        .modo-remover .price-form { display: flex !important; margin: 0; gap: 5px; align-items: center; justify-content: flex-start; }
        .modo-remover .price-static { display: none !important; }

        .btn-add { background-color: #00cc66; color: white; padding: 6px 10px; font-size: 1.2rem; }
        .btn-add:hover { background-color: #00994d; }
        .btn-sub { background-color: #ffaa00; color: white; padding: 6px 10px; font-size: 1.2rem; }
        .btn-sub:hover { background-color: #cc8800; }
        .btn-del { background-color: #ff4d4d; color: white; padding: 6px 10px; font-size: 1.1rem; }
        .btn-del:hover { background-color: #cc0000; }
        .btn-save-price { background-color: #00cc66; color: white; padding: 5px 8px; font-size: 0.9rem; }
        .btn-save-price:hover { background-color: #00994d; }
    </style>
</head>
<body>
    <header>
        <h1>Gestão de Inventário</h1>
        <nav style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
            <a href="index.html">Início</a>
            <a href="inventory.php" style="color: #ffaa00;">Inventário</a>
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
        <div class="controls-panel">
            <label><input type="checkbox" id="toggle-add" /> Adicionar Jogo</label>
            <label><input type="checkbox" id="toggle-remove" /> Gerir Stock / Apagar</label>
        </div>

        <section id="add-item" class="add-item hidden">
            <h2>Novo jogo</h2>
            <form id="add-form" action="inventory.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                
                <label>Título <input type="text" name="title" id="title" required></label>
                <label>Plataforma <input type="text" name="platform" id="platform" required></label>
                <label>Stock <input type="number" name="stock" id="stock" min="0" required></label>
                <label>Preço <input type="number" name="price" id="price" step="0.01" min="0" required></label>
                <label>Imagem (Opcional) <input type="file" name="image" id="image" accept="image/*"></label>
                
                <button type="submit">Salvar</button>
            </form>
        </section>

        <section class="inventory-container">
            <h2>Inventário Atual</h2>
            <table id="tabela-jogos">
                <thead>
                    <tr>
                        <th>Capa</th>
                        <th>Título</th>
                        <th>Plataforma</th>
                        <th>Stock</th>
                        <th>Preço</th>
                        <th class="col-remover">Ações</th>
                    </tr>
                </thead>
                <tbody id="inventory-body">
                    <?php 
                    $temJogos = false;
                    while ($item = $result->fetchArray(SQLITE3_ASSOC)): 
                        $temJogos = true;
                        $classeEsgotado = ($item['stock'] <= 0) ? 'item-esgotado' : '';
                    ?>
                        <tr class="<?php echo $classeEsgotado; ?>">
                            <td data-label="Capa">
                                <?php if (!empty($item['img'])): ?>
                                    <img src="<?php echo htmlspecialchars($item['img']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="capa-jogo">
                                <?php else: ?>
                                    <div class="sem-capa">Sem Capa</div>
                                <?php endif; ?>
                            </td>
                            <td data-label="Título" style="font-weight: bold;"><?php echo htmlspecialchars($item['title'] ?? ''); ?></td>
                            <td data-label="Plataforma"><?php echo htmlspecialchars($item['platform'] ?? ''); ?></td>
                            <td class="stock" data-label="Stock" style="font-family: 'VT323', monospace; font-size: 1.2rem;"><?php echo htmlspecialchars($item['stock'] ?? ''); ?></td>
                            
                            <td class="preco" data-label="Preço">
                                <?php if ($_SESSION['is_admin'] > 0): ?>
                                    <span class="price-static" style="font-size: 1.1rem; color: #00cc66; font-weight: bold;">
                                        <?php echo number_format((float)($item['price'] ?? 0), 2, '.', ''); ?>€
                                    </span>
                                    <form method="POST" action="inventory.php" class="price-form">
                                        <input type="hidden" name="action" value="update_price">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <input type="number" name="price" value="<?php echo number_format((float)($item['price'] ?? 0), 2, '.', ''); ?>" step="0.01" min="0" class="price-input" title="Editar Preço">
                                        <button type="submit" class="btn-save-price" title="Guardar Novo Preço">💾</button>
                                    </form>
                                <?php else: ?>
                                    <span style="font-size: 1.1rem; color: #00cc66; font-weight: bold;">
                                        <?php echo number_format((float)($item['price'] ?? 0), 2, '.', ''); ?>€
                                    </span>
                                <?php endif; ?>
                            </td>
                            
                            <td class="col-remover" data-label="Ações">
                                <div class="acoes-container">
                                    <form method="POST" action="inventory.php" style="margin: 0; display: flex; gap: 4px; align-items: center;">
                                        <input type="hidden" name="action" value="update_stock">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <input type="number" name="qty" class="qtd-input" value="1" min="1" required title="Quantidade">
                                        <button type="submit" name="operation" value="add" class="btn-add" title="Adicionar Unidades">+</button>
                                        <button type="submit" name="operation" value="sub" class="btn-sub" title="Remover Unidades">-</button>
                                    </form>

                                    <form method="POST" action="inventory.php" style="margin: 0; margin-left: 10px;">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="btn-del" onclick="return confirm('ATENÇÃO: Apagar o jogo <?php echo addslashes(htmlspecialchars($item['title'])); ?> da base de dados?');" title="Apagar Jogo">🗑️</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    
                    <?php if (!$temJogos): ?>
                        <tr><td colspan="6" style="text-align: center;">Nenhum jogo no inventário.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggleAddCheckbox = document.getElementById('toggle-add');
            const addSection = document.getElementById('add-item');

            toggleAddCheckbox.addEventListener('change', () => {
                if (toggleAddCheckbox.checked) {
                    addSection.classList.remove('hidden');
                    addSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                } else {
                    addSection.classList.add('hidden');
                }
            });

            const toggleRemoveCheckbox = document.getElementById('toggle-remove');
            const tabelaInventario = document.getElementById('tabela-jogos');

            toggleRemoveCheckbox.addEventListener('change', () => {
                if (toggleRemoveCheckbox.checked) {
                    tabelaInventario.classList.add('modo-remover');
                } else {
                    tabelaInventario.classList.remove('modo-remover');
                }
            });
        });
    </script>
</body>
</html>