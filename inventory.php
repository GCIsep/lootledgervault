<?php
// Ativar erros para debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Liga à Base de Dados
require_once 'scripts/database.php';

// Criar a tabela específica para o teu design
$db->exec("CREATE TABLE IF NOT EXISTS games_inventory (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    platform TEXT,
    stock INTEGER DEFAULT 0,
    price REAL DEFAULT 0.0,
    img TEXT
)");

// Processar formulários (Adicionar / Remover)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    
    // AÇÃO: ADICIONAR
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
        }
    }
    
    // AÇÃO: REMOVER
    if ($_POST['action'] === 'remove') {
        $item_id = (int)($_POST['item_id'] ?? 0);
        if ($item_id > 0) {
            $db->exec("DELETE FROM games_inventory WHERE id = $item_id");
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
        /* Ajustes base para o formulário */
        button { background-color: #ffaa00; color: #1a1a1a; font-weight: bold; padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #e69900; }
        input { padding: 5px; margin: 5px 0; border-radius: 3px; border: 1px solid #444; background: #333; color: white; }
        
        /* Placeholder para jogos sem capa */
        .sem-capa { width: 60px; height: 80px; background-color: #333; border: 1px dashed #555; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #888; text-align: center; }
        
        /* =========================================
           Lógica da Coluna Remover (Esconder/Mostrar)
           ========================================= */
        .col-remover { display: none !important; }
        .modo-remover .col-remover { display: table-cell !important; }
        @media screen and (max-width: 768px) {
            .modo-remover .col-remover { display: block !important; }
        }
    </style>
</head>
<body>
    <header>
        <h1>Gestão de Inventário</h1>
        <nav>
            <a href="index.html">Início</a>
            <a href="calendario.html">Calendário</a>
        </nav>
    </header>

    <main>
        <div class="controls-panel">
            <label><input type="checkbox" id="toggle-add" /> Adicionar</label>
            <label><input type="checkbox" id="toggle-remove" /> Remover</label>
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
                        <th class="col-remover">Ação</th>
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
                            <td data-label="Título"><?php echo htmlspecialchars($item['title'] ?? ''); ?></td>
                            <td data-label="Plataforma"><?php echo htmlspecialchars($item['platform'] ?? ''); ?></td>
                            <td class="stock" data-label="Stock"><?php echo htmlspecialchars($item['stock'] ?? ''); ?></td>
                            <td class="preco" data-label="Preço"><?php echo number_format((float)($item['price'] ?? 0), 2, '.', ''); ?></td>
                            <td class="col-remover" data-label="Ação">
                                <form method="POST" action="inventory.php" style="margin: 0;">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($item['id'] ?? ''); ?>">
                                    <button type="submit" onclick="return confirm('Apagar o jogo <?php echo addslashes(htmlspecialchars($item['title'])); ?>?');" style="background-color: #ff4d4d; color: white;">Apagar</button>
                                </form>
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
            // Lógica do botão Adicionar (Esconder/Mostrar Formulário)
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

            // Lógica do botão Remover (Esconder/Mostrar Coluna)
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