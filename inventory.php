<?php
// Ativar erros e ligar à Base de Dados
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Liga à BD (garante que a pasta scripts e o ficheiro database.php existem)
require_once 'scripts/database.php';

// Processar formulários (Adicionar / Remover)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    
    // AÇÃO: ADICIONAR
    if ($_POST['action'] === 'add') {
        $item_name = SQLite3::escapeString($_POST['item_name'] ?? '');
        $quantity = (int)($_POST['quantity'] ?? 1);

        if (!empty($item_name)) {
            $db->exec("INSERT INTO inventory (item_name, quantity) VALUES ('$item_name', $quantity)");
        }
    }
    
    // AÇÃO: REMOVER
    if ($_POST['action'] === 'delete') {
        $item_id = (int)($_POST['item_id'] ?? 0);
        if ($item_id > 0) {
            $db->exec("DELETE FROM inventory WHERE id = $item_id");
        }
    }

    // Refresh automático da página para evitar submissões duplas
    header("Location: inventory.php");
    exit();
}

// Ir buscar todos os jogos à Base de Dados
$result = $db->query("SELECT * FROM inventory");
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LootLedgerVault - Inventário</title>
    <link rel="stylesheet" href="styles/styles.css">
    <style>
        /* Pequenos ajustes caso o teu CSS não tenha estilos para tabelas */
        .inventory-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .inventory-table th, .inventory-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .inventory-table th { background-color: #f4f4f4; }
        .btn-delete { background-color: #ff4d4d; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 4px; }
        .btn-delete:hover { background-color: #cc0000; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Inventário de Jogos</h1>
        
        <section class="add-item-section">
            <h2>Adicionar Jogo</h2>
            <form action="inventory.php" method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group" style="margin-bottom: 10px;">
                    <label for="item_name">Nome do Jogo:</label><br>
                    <input type="text" id="item_name" name="item_name" placeholder="Ex: The Witcher 3" required style="padding: 8px; width: 250px;">
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="quantity">Quantidade:</label><br>
                    <input type="number" id="quantity" name="quantity" value="1" min="1" required style="padding: 8px; width: 100px;">
                </div>
                
                <button type="submit" class="btn" style="padding: 10px 15px; cursor: pointer;">Adicionar ao Inventário</button>
            </form>
        </section>

        <hr style="margin: 30px 0;">

        <section class="inventory-list-section">
            <h2>Os Meus Jogos Guardados</h2>
            <table class="inventory-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Quantidade</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $temJogos = false;
                    while ($row = $result->fetchArray(SQLITE3_ASSOC)): 
                        $temJogos = true;
                    ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                            <td><?php echo $row['quantity']; ?></td>
                            <td>
                                <form action="inventory.php" method="POST" style="margin: 0;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="item_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="btn-delete" onclick="return confirm('Tens a certeza que queres remover este jogo?');">Remover</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    
                    <?php if (!$temJogos): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 20px; color: #666;">O teu inventário está vazio.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
        
        <div style="margin-top: 30px;">
            <a href="index.html" class="btn">Voltar ao Início</a>
        </div>
    </div>
</body>
</html>