<?php
// Ativar erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// INICIAR A SESSÃO E VERIFICAR LOGIN
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Ligar à base de dados
require_once 'scripts/database.php';

// 1. Procurar jogos com Stock 0 (Em Falta)
$query_falta = $db->query("SELECT * FROM games_inventory WHERE stock = 0 ORDER BY title ASC LIMIT 5");

// 2. Procurar jogos com Stock > 50 (Em Excesso)
$limite_excesso = 50;
$query_excesso = $db->query("SELECT * FROM games_inventory WHERE stock > $limite_excesso ORDER BY stock DESC LIMIT 5");

// 3. Procurar os últimos 3 jogos adicionados (Stock Novo)
$query_novo = $db->query("SELECT * FROM games_inventory ORDER BY id DESC LIMIT 3");

// 4. LER TODOS OS EVENTOS PARA O CALENDÁRIO
$query_eventos = $db->query("SELECT event_date, description FROM events");
$eventos_calendario = [];

while ($row = $query_eventos->fetchArray(SQLITE3_ASSOC)) {
    $data = $row['event_date'];
    if (!isset($eventos_calendario[$data])) {
        $eventos_calendario[$data] = [];
    }
    // Adiciona o evento à data correspondente
    $eventos_calendario[$data][] = $row['description'];
}

// Converte a lista de eventos para JSON para o JavaScript poder ler!
$eventos_json = json_encode($eventos_calendario);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Stock - Calendário</title>
    <link rel="stylesheet" href="styles/style.css">
    
    <style>
        .calendar-wrapper { display: flex; gap: 24px; max-width: 1200px; margin: 40px auto; padding: 0 20px; align-items: flex-start; flex-wrap: wrap; }
        .panel { flex: 1; background-color: #262626; border-radius: 8px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.6); padding: 25px; min-width: 320px; }
        .left-panel { flex: 1; }
        .right-panel { flex: 1.5; }

        .calendar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .calendar-header h2 { margin: 0; color: #ffaa00; border: none; padding: 0; }
        .calendar-nav button { background-color: transparent; color: #ffaa00; border: 1px solid #ffaa00; padding: 8px 15px; border-radius: 20px; cursor: pointer; transition: background 0.3s, color 0.3s; font-weight: bold; }
        .calendar-nav button:hover { background-color: #ffaa00; color: #1a1a1a; }
        
        .calendar-table { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 10px; }
        .calendar-table th { background-color: #333; color: #ffaa00; padding: 12px; text-align: center; border: 1px solid #404040; }
        .calendar-table td { border: 1px solid #404040; padding: 5px; text-align: left; height: 100px; vertical-align: top; background-color: #2e2e2e; color: #e0e0e0; transition: background 0.2s; position: relative; }
        .calendar-table td.other-month { background-color: #1a1a1a; color: #555; }
        .calendar-table td:hover:not(.other-month) { background-color: #3d3d3d; cursor: pointer; }
        .calendar-table td.today { background-color: rgba(255, 170, 0, 0.15); border: 2px solid #ffaa00; color: #fff; font-weight: bold; }
        
        .day-number { margin-bottom: 5px; font-size: 1.1em; text-align: right; width: 100%; display: block;}

        .event-badge {
            background-color: rgba(255, 170, 0, 0.15);
            color: #ffcc66;
            font-size: 0.75rem;
            padding: 3px 4px;
            margin-top: 3px;
            border-radius: 3px;
            border-left: 2px solid #ffaa00;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-family: sans-serif;
        }

        .news-section { margin-bottom: 25px; }
        .news-section h2 { font-size: 1.3rem; border-bottom: 2px solid #404040; padding-bottom: 8px; margin-bottom: 15px; }
        .news-item { background-color: #333; padding: 15px; margin-bottom: 12px; border-left: 4px solid #ffaa00; border-radius: 6px; transition: transform 0.2s ease, background-color 0.2s ease; }
        .news-item:hover { transform: translateX(4px); background-color: #3d3d3d; }

        .stock-falta h2 { color: #ff4d4d; border-bottom-color: rgba(255, 77, 77, 0.3); }
        .stock-falta .news-item { border-left-color: #ff4d4d; }
        .stock-excesso h2 { color: #ffaa00; border-bottom-color: rgba(255, 170, 0, 0.3); }
        .stock-excesso .news-item { border-left-color: #ffaa00; }
        .stock-novo h2 { color: #00cc66; border-bottom-color: rgba(0, 204, 102, 0.3); }
        .stock-novo .news-item { border-left-color: #00cc66; }

        .news-item strong { display: block; color: #fff; margin-bottom: 5px; font-size: 1.1rem; }
        .news-item p { margin: 0; color: #bbb; }
        .news-item small { display: block; margin-top: 6px; color: #888; font-size: 0.85rem; }
        .empty-msg { color: #888; font-style: italic; padding: 10px; }

        @media (max-width: 768px) {
            .calendar-wrapper { flex-direction: column; }
            .left-panel, .right-panel { min-width: 100%; }
        }
    </style>
</head>
<body>
    <header>
        <h1>Gestão de Inventário</h1>
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
        <div class="calendar-wrapper">
            
            <div class="panel left-panel">
                
                <div class="news-section stock-falta">
                    <h2>⚠️ Stock em Falta</h2>
                    <?php 
                    $temFalta = false;
                    while ($jogo = $query_falta->fetchArray(SQLITE3_ASSOC)): 
                        $temFalta = true;
                    ?>
                        <div class="news-item">
                            <strong><?php echo htmlspecialchars($jogo['title']); ?></strong>
                            <p>Plataforma: <?php echo htmlspecialchars($jogo['platform']); ?></p>
                            <small>Preço atual: <?php echo number_format($jogo['price'], 2, ',', '.'); ?>€</small>
                        </div>
                    <?php endwhile; ?>
                    <?php if (!$temFalta) echo '<p class="empty-msg">Nenhum jogo esgotado.</p>'; ?>
                </div>

                <div class="news-section stock-excesso">
                    <h2>📦 Stock em Excesso (><?php echo $limite_excesso; ?>)</h2>
                    <?php 
                    $temExcesso = false;
                    while ($jogo = $query_excesso->fetchArray(SQLITE3_ASSOC)): 
                        $temExcesso = true;
                    ?>
                        <div class="news-item">
                            <strong><?php echo htmlspecialchars($jogo['title']); ?></strong>
                            <p>Quantidade atual: <?php echo $jogo['stock']; ?> unidades</p>
                            <small>Excesso detetado no sistema.</small>
                        </div>
                    <?php endwhile; ?>
                    <?php if (!$temExcesso) echo '<p class="empty-msg">Stock sob controlo.</p>'; ?>
                </div>

                <div class="news-section stock-novo">
                    <h2>✨ Últimos Jogos Adicionados</h2>
                    <?php 
                    $temNovo = false;
                    while ($jogo = $query_novo->fetchArray(SQLITE3_ASSOC)): 
                        $temNovo = true;
                    ?>
                        <div class="news-item">
                            <strong><?php echo htmlspecialchars($jogo['title']); ?></strong>
                            <p>Entrada inicial: <?php echo $jogo['stock']; ?> unidades</p>
                            <small>Plataforma: <?php echo htmlspecialchars($jogo['platform']); ?></small>
                        </div>
                    <?php endwhile; ?>
                    <?php if (!$temNovo) echo '<p class="empty-msg">Ainda não adicionaste jogos.</p>'; ?>
                </div>
            </div>

            <div class="panel right-panel">
                <div class="calendar-header">
                    <h2>📅 Calendário</h2>
                    <div class="calendar-nav">
                        <button onclick="previousMonth()">← Anterior</button>
                        <button onclick="nextMonth()">Próximo →</button>
                    </div>
                </div>
                <table class="calendar-table">
                    <thead>
                        <tr>
                            <th>Dom</th><th>Seg</th><th>Ter</th><th>Qua</th><th>Qui</th><th>Sex</th><th>Sáb</th>
                        </tr>
                    </thead>
                    <tbody id="calendar-body">
                        </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        const dbEvents = <?php echo $eventos_json; ?>;
        
        let currentDate = new Date();

        function generateCalendar() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const daysInPrevMonth = new Date(year, month, 0).getDate();
            const cells = [];

            for (let i = firstDay - 1; i >= 0; i--) {
                cells.push(`<td class="other-month"><div class="day-number">${daysInPrevMonth - i}</div></td>`);
            }

            const today = new Date();
            for (let day = 1; day <= daysInMonth; day++) {
                const isToday = day === today.getDate() && month === today.getMonth() && year === today.getFullYear();
                
                const dateString = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                
                let eventHtml = '';
                if (dbEvents[dateString]) {
                    dbEvents[dateString].forEach(evt => {
                        eventHtml += `<div class="event-badge" title="${evt}">${evt}</div>`;
                    });
                }

                cells.push(`<td class="${isToday ? 'today' : ''}">
                    <span class="day-number">${day}</span>
                    ${eventHtml}
                </td>`);
            }

            const totalCells = firstDay + daysInMonth;
            const remainingCells = totalCells % 7 === 0 ? 0 : 7 - (totalCells % 7);
            for (let day = 1; day <= remainingCells; day++) {
                cells.push(`<td class="other-month"><div class="day-number">${day}</div></td>`);
            }

            let html = '';
            for (let i = 0; i < cells.length; i++) {
                if (i % 7 === 0) html += '<tr>';
                html += cells[i];
                if (i % 7 === 6) html += '</tr>';
            }
            if (cells.length % 7 !== 0) html += '</tr>';

            document.getElementById('calendar-body').innerHTML = html;

            const monthNames = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
            document.querySelector('.calendar-header h2').textContent = `📅 ${monthNames[month]} ${year}`;
        }

        function previousMonth() { currentDate.setMonth(currentDate.getMonth() - 1); generateCalendar(); }
        function nextMonth() { currentDate.setMonth(currentDate.getMonth() + 1); generateCalendar(); }
        
        generateCalendar();
    </script>
</body>
</html>