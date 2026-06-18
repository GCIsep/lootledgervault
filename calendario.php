<?php
// INICIAR A SESSÃO PARA SABER QUEM ESTÁ A ACEDER
session_start();

// VERIFICAÇÃO DE SEGURANÇA
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Ligar à Base de Dados SQLite (Exatamente como fazes no inventory.php)
require_once 'scripts/database.php';

// Garantir que a tabela events existe (caso ainda não tenha sido criada)
$db->exec("CREATE TABLE IF NOT EXISTS events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    event_date TEXT NOT NULL,
    description TEXT NOT NULL,
    user_id INTEGER
)");

$user_id = $_SESSION['user_id'];

// ==========================================
// PROCESSAR AÇÕES DO CALENDÁRIO (ADICIONAR/APAGAR)
// ==========================================

// Adicionar evento manualmente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_event') {
    $event_date = SQLite3::escapeString($_POST['event_date']);
    $title = SQLite3::escapeString($_POST['title']);
    $time = SQLite3::escapeString($_POST['time']);
    $desc = SQLite3::escapeString($_POST['desc']);
    
    // Formatar a descrição para incluir a hora e os detalhes
    $full_description = "📅 " . $title;
    if (!empty($time)) {
        $full_description = "[" . $time . "] " . $full_description;
    }
    if (!empty($desc)) {
        $full_description .= " | " . $desc;
    }

    $db->exec("INSERT INTO events (event_date, description, user_id) VALUES ('$event_date', '$full_description', $user_id)");
    
    // Recarregar a página para evitar re-submissões
    header("Location: calendario.php");
    exit();
}

// Apagar evento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_event') {
    $event_id = (int)$_POST['event_id'];
    $db->exec("DELETE FROM events WHERE id = $event_id");
    header("Location: calendario.php");
    exit();
}

// ==========================================
// 1. BUSCAR EVENTOS PARA O CALENDÁRIO
// ==========================================
$events_result = $db->query("SELECT id, event_date, description FROM events");
$events_array = [];

while ($row = $events_result->fetchArray(SQLITE3_ASSOC)) {
    $date = $row['event_date']; // Formato YYYY-MM-DD
    if (!isset($events_array[$date])) {
        $events_array[$date] = [];
    }
    $events_array[$date][] = [
        'id' => $row['id'],
        'title' => htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8')
    ];
}
$events_json = json_encode($events_array);

// ==========================================
// 2. BUSCAR ESTATÍSTICAS PARA A BARRA LATERAL
// ==========================================

// A) Stock em Falta (Esgotado)
$esgotados_result = $db->query("SELECT title, platform FROM games_inventory WHERE stock <= 0 LIMIT 5");
$esgotados = [];
while ($row = $esgotados_result->fetchArray(SQLITE3_ASSOC)) { $esgotados[] = $row; }

// B) Stock em Excesso (> 50)
$excesso_result = $db->query("SELECT title, platform, stock FROM games_inventory WHERE stock > 50 LIMIT 5");
$excesso = [];
while ($row = $excesso_result->fetchArray(SQLITE3_ASSOC)) { $excesso[] = $row; }

// C) Últimos Adicionados (Ordenados pelo ID decrescente)
$recentes_result = $db->query("SELECT title, platform, stock FROM games_inventory ORDER BY id DESC LIMIT 3");
$recentes = [];
while ($row = $recentes_result->fetchArray(SQLITE3_ASSOC)) { $recentes[] = $row; }

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendário - Gestão de Inventário</title>
    <style>
        /* ESTILOS GERAIS */
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #1a1a1a; color: #e0e0e0; margin: 0; padding: 0; line-height: 1.6; }
        h1, h2, h3 { color: #ffaa00; }

        /* CABEÇALHO */
        header { background-color: #0d0d0d; padding: 20px; text-align: center; border-bottom: 3px solid #ffaa00; }
        header nav { display: flex; align-items: center; justify-content: center; gap: 15px; flex-wrap: wrap; margin-top: 15px; }
        header nav a { color: #ffffff; text-decoration: none; margin: 0 15px; font-weight: bold; text-transform: uppercase; transition: color 0.3s; }
        header nav a:hover, header nav a.active { color: #ffaa00; }

        /* ESTRUTURA */
        .main-content { display: flex; gap: 30px; max-width: 1400px; margin: 40px auto; padding: 0 20px; flex-wrap: wrap; }
        .sidebar { flex: 1; min-width: 300px; max-width: 400px; background-color: #262626; border-radius: 8px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.6); height: fit-content; }
        .section { margin-bottom: 30px; }
        .section h3 { margin-bottom: 15px; font-size: 1.4rem; border-bottom: 1px solid #404040; padding-bottom: 5px; }
        .sidebar-card { background: #333333; padding: 15px; margin-bottom: 10px; border-radius: 4px; }

        /* CALENDÁRIO */
        .calendar-container { flex: 2; min-width: 600px; background-color: #262626; border-radius: 8px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.6); }
        .calendar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .calendar-header h2 { margin: 0; font-size: 2rem; text-transform: uppercase; }
        button { background-color: #ffaa00; color: #1a1a1a; font-weight: bold; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; transition: background 0.2s; font-family: inherit; }
        button:hover { background-color: #e69900; }
        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 10px; }
        .day-header { text-align: center; padding: 12px; background-color: #333333; color: #ffaa00; text-transform: uppercase; font-weight: bold; border-radius: 4px; }
        .day { background-color: #2e2e2e; min-height: 120px; padding: 10px; border-radius: 4px; border: 1px solid transparent; cursor: pointer; transition: all 0.2s ease; }
        .day:hover { background-color: #3d3d3d; border-color: #ffaa00; }
        .day.today { border: 2px solid #ffaa00; background-color: #332b1a; }
        .day.other-month { opacity: 0.4; }
        .day-number { font-size: 1.2rem; font-weight: bold; margin-bottom: 8px; color: #fff; }
        .event { font-size: 0.85rem; padding: 4px 8px; margin: 4px 0; background-color: #404040; border-left: 3px solid #ffaa00; border-radius: 3px; color: #e0e0e0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        
        /* MODAL */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 10000; align-items: center; justify-content: center; }
        .modal-content { background: #262626; border-top: 4px solid #ffaa00; border-radius: 8px; padding: 30px; width: 90%; max-width: 500px; box-shadow: 0 10px 30px rgba(0,0,0,0.8); }
        .modal-content h2 { margin-top: 0; text-align: center; }
        input, textarea { width: 100%; padding: 10px; margin: 8px 0 15px 0; background: #333; border: 1px solid #444; color: white; border-radius: 4px; font-family: inherit; box-sizing: border-box; }
        input:focus, textarea:focus { outline: none; border-color: #ffaa00; }
        .modal-buttons { display: flex; gap: 10px; margin-top: 10px; }
        .btn-close { background-color: #444; color: #fff; }
        .btn-close:hover { background-color: #555; }
        .btn-delete { background-color: #ff4d4d; color: white; padding: 4px 8px; font-size: 0.85rem; float: right; border:none; border-radius:3px; cursor:pointer;}
        .btn-delete:hover { background-color: #cc0000; }

        @media screen and (max-width: 900px) {
            .main-content { flex-direction: column; }
            .sidebar { max-width: 100%; }
            .calendar-container { min-width: 100%; }
        }
    </style>
</head>
<body>
    <header>
        <h1>Gestão de Inventário</h1>
        <nav>
            <a href="index.html">Início</a>
            <a href="inventory.php">Inventário</a>
            <a href="calendario.php" class="active">Calendário</a>
            
            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] > 0): ?>
                <a href="admin.php">Admin</a>
            <?php endif; ?>
            
            <div style="margin-left: auto; display: flex; align-items: center; gap: 15px; padding-right: 20px;">
                <span style="color: #ffaa00; font-size: 1rem;">
                    👤 Bem-vindo, <strong style="color: #fff;"><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Utilizador'; ?></strong>
                </span>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="logout.php" style="color: #ff4d4d; font-weight: bold; padding: 5px 10px; border: 1px solid #ff4d4d; border-radius: 4px; text-decoration: none; margin: 0;">Sair</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <div class="main-content">
        <div class="sidebar">
            
            <div class="section">
                <h3 style="color:#ff4d4d;">⚠ Stock em Falta</h3>
                <?php if (count($esgotados) > 0): ?>
                    <?php foreach ($esgotados as $jogo): ?>
                        <div class="sidebar-card" style="border-left: 4px solid #ff4d4d;">
                            <strong style="color: #fff;"><?php echo htmlspecialchars($jogo['title']); ?></strong><br>
                            <span style="font-size: 0.9em; color: #aaa;"><?php echo htmlspecialchars($jogo['platform']); ?> - Esgotado!</span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="sidebar-card" style="border-left: 4px solid #ff4d4d;">
                        <p style="margin:0; color:#aaa;">Nenhum jogo esgotado.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="section">
                <h3>📦 Stock em Excesso (&gt;50)</h3>
                <?php if (count($excesso) > 0): ?>
                    <?php foreach ($excesso as $jogo): ?>
                        <div class="sidebar-card" style="border-left: 4px solid #ffaa00;">
                            <strong style="color: #fff;"><?php echo htmlspecialchars($jogo['title']); ?></strong><br>
                            <span style="font-size: 0.9em; color: #aaa;">Quantidade: <strong style="color:#ffaa00;"><?php echo $jogo['stock']; ?></strong> un.<br><?php echo htmlspecialchars($jogo['platform']); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="sidebar-card" style="border-left: 4px solid #ffaa00;">
                        <p style="margin:0; color:#aaa;">Nenhum excesso detetado.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="section">
                <h3 style="color:#00cc66;">✨ Últimos Adicionados</h3>
                <?php if (count($recentes) > 0): ?>
                    <?php foreach ($recentes as $jogo): ?>
                        <div class="sidebar-card" style="border-left: 4px solid #00cc66;">
                            <strong style="color: #fff;"><?php echo htmlspecialchars($jogo['title']); ?></strong><br>
                            <span style="font-size: 0.9em; color: #aaa;">Stock Atual: <?php echo $jogo['stock']; ?> un.<br><?php echo htmlspecialchars($jogo['platform']); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="sidebar-card" style="border-left: 4px solid #00cc66;">
                        <p style="margin:0; color:#aaa;">Ainda não há jogos.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <div class="calendar-container">
            <div class="calendar-header">
                <button onclick="prevMonth()">‹ Anterior</button>
                <h2 id="monthTitle"></h2>
                <button onclick="nextMonth()">Próximo ›</button>
            </div>
            
            <div class="calendar-grid" id="calendarGrid"></div>
        </div>
    </div>

    <div id="eventModal" class="modal">
        <div class="modal-content">
            <h2 id="modalDate"></h2>
            
            <div id="eventsList" style="margin-bottom: 20px; max-height: 200px; overflow-y: auto;"></div>
            
            <h3 style="font-size: 1.2rem; border-bottom: 1px solid #404040; padding-bottom: 5px;">Adicionar Evento</h3>
            <form method="POST" action="calendario.php">
                <input type="hidden" name="action" value="add_event">
                <input type="hidden" name="event_date" id="formEventDate">
                
                <label style="font-size: 0.9rem; font-weight: bold;">Título do evento</label>
                <input type="text" name="title" placeholder="Ex: Reunião com fornecedor" required>
                
                <label style="font-size: 0.9rem; font-weight: bold;">Hora</label>
                <input type="time" name="time">
                
                <label style="font-size: 0.9rem; font-weight: bold;">Descrição (opcional)</label>
                <textarea name="desc" rows="3" placeholder="Detalhes do evento..."></textarea>
                
                <div class="modal-buttons">
                    <button type="submit" style="flex: 1;">Salvar Evento</button>
                    <button type="button" style="flex: 1;" class="btn-close" onclick="closeModal()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Carrega as datas e jogos da Base de Dados
        const events = <?php echo $events_json; ?>;
        
        let currentDate = new Date();
        currentDate.setDate(1); 
        
        function renderCalendar() {
            const grid = document.getElementById('calendarGrid');
            grid.innerHTML = ''; 
            
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            
            document.getElementById('monthTitle').textContent = 
                currentDate.toLocaleString('pt-PT', { month: 'long', year: 'numeric' });
            
            const daysOfWeek = ['DOM','SEG','TER','QUA','QUI','SEX','SÁB'];
            daysOfWeek.forEach(d => {
                const el = document.createElement('div');
                el.className = 'day-header';
                el.textContent = d;
                grid.appendChild(el);
            });
            
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const daysInPrevMonth = new Date(year, month, 0).getDate();
            
            for (let i = firstDay; i > 0; i--) {
                grid.appendChild(createDayElement(daysInPrevMonth - i + 1, true, year, month - 1));
            }
            
            for (let day = 1; day <= daysInMonth; day++) {
                grid.appendChild(createDayElement(day, false, year, month));
            }
            
            const remaining = 49 - grid.children.length;
            for (let day = 1; day <= remaining; day++) {
                grid.appendChild(createDayElement(day, true, year, month + 1));
            }
        }

        function createDayElement(day, isOtherMonth, currentYear, currentMonth) {
            const el = document.createElement('div');
            
            let dYear = currentYear;
            let dMonth = currentMonth;
            if(dMonth < 0) { dMonth = 11; dYear--; }
            if(dMonth > 11) { dMonth = 0; dYear++; }

            const realToday = new Date();
            const isToday = !isOtherMonth && 
                            day === realToday.getDate() && 
                            dMonth === realToday.getMonth() && 
                            dYear === realToday.getFullYear();

            el.className = `day ${isOtherMonth ? 'other-month' : ''} ${isToday ? 'today' : ''}`;
            
            let mesFormatado = (dMonth + 1).toString().padStart(2, '0');
            let diaFormatado = day.toString().padStart(2, '0');
            const dateKey = `${dYear}-${mesFormatado}-${diaFormatado}`;
            
            const numDiv = document.createElement('div');
            numDiv.className = 'day-number';
            numDiv.textContent = day;
            el.appendChild(numDiv);

            if (events[dateKey] && events[dateKey].length > 0) {
                const maxDisplay = 3;
                events[dateKey].slice(0, maxDisplay).forEach(ev => {
                    const e = document.createElement('div');
                    e.className = 'event';
                    e.textContent = ev.title;
                    el.appendChild(e);
                });
                
                if (events[dateKey].length > maxDisplay) {
                    const more = document.createElement('div');
                    more.style.fontSize = "0.8rem";
                    more.style.color = "#aaa";
                    more.style.textAlign = "center";
                    more.textContent = `+${events[dateKey].length - maxDisplay} eventos`;
                    el.appendChild(more);
                }
            }
            
            el.onclick = () => openDayModal(dateKey);
            return el;
        }

        function openDayModal(dateKey) {
            const ptDateParts = dateKey.split('-');
            document.getElementById('modalDate').textContent = `Dia: ${ptDateParts[2]}/${ptDateParts[1]}/${ptDateParts[0]}`;
            
            document.getElementById('formEventDate').value = dateKey;
            
            const list = document.getElementById('eventsList');
            if (events[dateKey] && events[dateKey].length > 0) {
                list.innerHTML = events[dateKey].map((ev) => `
                    <div style="margin-bottom:10px; padding:12px; background:#333; border-radius:4px; border-left: 3px solid #ffaa00;">
                        <form method="POST" action="calendario.php" style="display:inline;">
                            <input type="hidden" name="action" value="delete_event">
                            <input type="hidden" name="event_id" value="${ev.id}">
                            <button class="btn-delete" type="submit" onclick="return confirm('Apagar este registo?'); event.stopPropagation();">X</button>
                        </form>
                        <strong style="color:#ffaa00;">${ev.title}</strong>
                    </div>`).join('');
            } else {
                list.innerHTML = '<p style="color:#aaa; text-align:center; font-style:italic;">Sem registos neste dia.</p>';
            }
            
            document.getElementById('eventModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('eventModal').style.display = 'none';
        }

        function prevMonth() { 
            currentDate.setDate(1); 
            currentDate.setMonth(currentDate.getMonth() - 1); 
            renderCalendar(); 
        }
        
        function nextMonth() { 
            currentDate.setDate(1); 
            currentDate.setMonth(currentDate.getMonth() + 1); 
            renderCalendar(); 
        }

        renderCalendar();
    </script>
</body>
</html>