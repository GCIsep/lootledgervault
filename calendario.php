<?php
// INICIAR A SESSÃO PARA SABER QUEM ESTÁ A ACEDER (Igual ao inventory.php)
session_start();

// VERIFICAÇÃO DE SEGURANÇA
if (!isset($_SESSION['user_id'])) {
    // Se não usares login para o calendário, podes apagar ou comentar estas linhas
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendário - Gestão de Inventário</title>
    <style>
        /* =========================================
           ESTILOS GERAIS (Baseados no teu CSS)
           ========================================= */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #1a1a1a;
            color: #e0e0e0;
            margin: 0;
            padding: 0; 
            line-height: 1.6;
        }

        h1, h2, h3 {
            color: #ffaa00;
        }

        /* =========================================
           CABEÇALHO E NAVEGAÇÃO
           ========================================= */
        header {
            background-color: #0d0d0d;
            padding: 20px;
            text-align: center;
            border-bottom: 3px solid #ffaa00;
        }

        header nav {
            display: flex; 
            align-items: center; 
            justify-content: center;
            gap: 15px; 
            flex-wrap: wrap;
            margin-top: 15px;
        }

        header nav a {
            color: #ffffff;
            text-decoration: none;
            margin: 0 15px;
            font-weight: bold;
            text-transform: uppercase;
            transition: color 0.3s;
        }

        header nav a:hover, header nav a.active {
            color: #ffaa00;
        }

        /* =========================================
           ESTRUTURA PRINCIPAL
           ========================================= */
        .main-content {
            display: flex;
            gap: 30px;
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
            flex-wrap: wrap;
        }

        /* Sidebar esquerda */
        .sidebar {
            flex: 1;
            min-width: 300px;
            max-width: 400px;
            background-color: #262626;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.6);
            height: fit-content;
        }
        
        .section { margin-bottom: 30px; }
        .section h3 { margin-bottom: 15px; font-size: 1.4rem; border-bottom: 1px solid #404040; padding-bottom: 5px; }
        
        .sidebar-card {
            background: #333333;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 4px;
        }

        /* =========================================
           CALENDÁRIO
           ========================================= */
        .calendar-container {
            flex: 2;
            min-width: 600px;
            background-color: #262626;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.6);
        }
        
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .calendar-header h2 {
            margin: 0;
            font-size: 2rem;
            text-transform: uppercase;
        }
        
        button {
            background-color: #ffaa00; 
            color: #1a1a1a; 
            font-weight: bold; 
            padding: 8px 16px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            transition: background 0.2s;
            font-family: inherit;
        }
        
        button:hover { background-color: #e69900; }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
        }
        
        .day-header {
            text-align: center;
            padding: 12px;
            background-color: #333333;
            color: #ffaa00;
            text-transform: uppercase;
            font-weight: bold;
            border-radius: 4px;
        }
        
        .day {
            background-color: #2e2e2e;
            min-height: 120px;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .day:hover { background-color: #3d3d3d; border-color: #ffaa00; }
        .day.today { border: 2px solid #ffaa00; background-color: #332b1a; }
        .day.other-month { opacity: 0.4; }
        
        .day-number { font-size: 1.2rem; font-weight: bold; margin-bottom: 8px; color: #fff; }
        
        .event {
            font-size: 0.85rem;
            padding: 4px 8px;
            margin: 4px 0;
            background-color: #404040;
            border-left: 3px solid #ffaa00;
            border-radius: 3px;
            color: #e0e0e0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* =========================================
           MODAL
           ========================================= */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: #262626;
            border-top: 4px solid #ffaa00;
            border-radius: 8px;
            padding: 30px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.8);
        }

        .modal-content h2 { margin-top: 0; text-align: center; }
        
        input, textarea {
            width: 100%;
            padding: 10px;
            margin: 8px 0 15px 0;
            background: #333;
            border: 1px solid #444;
            color: white;
            border-radius: 4px;
            font-family: inherit;
            box-sizing: border-box;
        }

        input:focus, textarea:focus {
            outline: none;
            border-color: #ffaa00;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .btn-close {
            background-color: #444;
            color: #fff;
        }
        .btn-close:hover { background-color: #555; }
        .btn-delete {
            background-color: #ff4d4d;
            color: white;
            padding: 4px 8px;
            font-size: 0.85rem;
            float: right;
        }
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
                <div class="sidebar-card" style="border-left: 4px solid #ff4d4d;">
                    <p style="margin:0;">Nenhum jogo esgotado.</p>
                </div>
            </div>
            
            <div class="section">
                <h3>📦 Stock em Excesso (&gt;50)</h3>
                <div class="sidebar-card" style="border-left: 4px solid #ffaa00;">
                    <strong>Elden Ring</strong><br>
                    <span style="font-size: 0.9em; color: #aaa;">Quantidade atual: 60 unidades<br>
                    Excesso detetado no sistema.</span>
                </div>
            </div>
            
            <div class="section">
                <h3 style="color:#00cc66;">✨ Últimos Adicionados</h3>
                <div class="sidebar-card" style="border-left: 4px solid #00cc66;">
                    <strong>Forza Horizon 6</strong><br>
                    <span style="font-size: 0.9em; color: #aaa;">Entrada inicial: 30 un.<br>XBOX</span>
                </div>
                <div class="sidebar-card" style="border-left: 4px solid #00cc66;">
                    <strong>Elden Ring</strong><br>
                    <span style="font-size: 0.9em; color: #aaa;">Entrada inicial: 60 un.<br>XBOX</span>
                </div>
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
            <label style="font-size: 0.9rem; font-weight: bold;">Título do evento</label>
            <input type="text" id="eventTitle" placeholder="Ex: Reposição de Stock">
            
            <label style="font-size: 0.9rem; font-weight: bold;">Hora</label>
            <input type="time" id="eventTime">
            
            <label style="font-size: 0.9rem; font-weight: bold;">Descrição (opcional)</label>
            <textarea id="eventDesc" rows="3" placeholder="Detalhes do evento..."></textarea>
            
            <div class="modal-buttons">
                <button style="flex: 1;" onclick="addEventToDay()">Salvar Evento</button>
                <button style="flex: 1;" class="btn-close" onclick="closeModal()">Cancelar</button>
            </div>
        </div>
    </div>

    <script>
        // LÓGICA DO CALENDÁRIO INTACTA
        let currentDate = new Date();
        let selectedDate = null;
        let events = JSON.parse(localStorage.getItem('vault_events')) || {};

        function saveEvents() { localStorage.setItem('vault_events', JSON.stringify(events)); }

        function renderCalendar() {
            const grid = document.getElementById('calendarGrid');
            grid.innerHTML = '';
            
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            
            document.getElementById('monthTitle').innerHTML = 
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
            
            const today = new Date();
            
            for (let i = firstDay - 1; i >= 0; i--) grid.appendChild(createDayElement(daysInPrevMonth - i, true, year, month - 1));
            for (let day = 1; day <= daysInMonth; day++) grid.appendChild(createDayElement(day, false, year, month));
            
            const remaining = 42 - grid.children.length;
            for (let day = 1; day <= remaining; day++) grid.appendChild(createDayElement(day, true, year, month + 1));
        }

        function createDayElement(day, isOtherMonth, currentYear, currentMonth) {
            const el = document.createElement('div');
            
            // Ajuste para meses
            let dYear = currentYear;
            let dMonth = currentMonth;
            if(dMonth < 0) { dMonth = 11; dYear--; }
            if(dMonth > 11) { dMonth = 0; dYear++; }

            const isToday = !isOtherMonth && 
                            day === new Date().getDate() && 
                            dMonth === new Date().getMonth() && 
                            dYear === new Date().getFullYear();

            el.className = `day ${isOtherMonth ? 'other-month' : ''} ${isToday ? 'today' : ''}`;
            
            const dateKey = `${dYear}-${String(dMonth+1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
            
            el.innerHTML = `<div class="day-number">${day}</div><div id="events-${dateKey}"></div>`;
            
            if (events[dateKey]) {
                const container = el.querySelector(`#events-${dateKey}`);
                events[dateKey].slice(0, 3).forEach(ev => {
                    const e = document.createElement('div');
                    e.className = 'event';
                    e.textContent = ev.time ? `${ev.time} ${ev.title}` : ev.title;
                    container.appendChild(e);
                });
                if(events[dateKey].length > 3) {
                    const more = document.createElement('div');
                    more.style.fontSize = "0.8rem";
                    more.style.color = "#aaa";
                    more.style.textAlign = "center";
                    more.textContent = `+${events[dateKey].length - 3} eventos`;
                    container.appendChild(more);
                }
            }
            
            el.onclick = () => openDayModal(dateKey);
            return el;
        }

        function openDayModal(dateKey) {
            selectedDate = dateKey;
            
            const ptDateParts = dateKey.split('-');
            const formattedDate = `${ptDateParts[2]}/${ptDateParts[1]}/${ptDateParts[0]}`;
            document.getElementById('modalDate').textContent = `Eventos: ${formattedDate}`;
            
            const list = document.getElementById('eventsList');
            list.innerHTML = events[dateKey] && events[dateKey].length > 0 
                ? events[dateKey].map((ev, i) => `
                    <div style="margin-bottom:10px; padding:12px; background:#333; border-radius:4px; border-left: 3px solid #ffaa00;">
                        <button class="btn-delete" onclick="deleteEvent('${dateKey}', ${i}); event.stopPropagation();">X</button>
                        <strong style="color:#ffaa00;">${ev.time || ''} ${ev.title}</strong><br>
                        <span style="font-size:0.9rem; color:#ccc;">${ev.desc || 'Sem descrição'}</span>
                    </div>`).join('')
                : '<p style="color:#aaa; text-align:center; font-style:italic;">Nenhum evento agendado para este dia.</p>';
            
            document.getElementById('eventModal').style.display = 'flex';
        }

        function addEventToDay() {
            const title = document.getElementById('eventTitle').value.trim();
            const time = document.getElementById('eventTime').value;
            const desc = document.getElementById('eventDesc').value.trim();
            
            if (!title) return alert("Por favor, escreve um título para o evento!");
            
            if (!events[selectedDate]) events[selectedDate] = [];
            events[selectedDate].push({ title, time, desc });
            
            // Ordenar por hora (se existir)
            events[selectedDate].sort((a, b) => (a.time || "24:00").localeCompare(b.time || "24:00"));
            
            saveEvents();
            closeModal();
            renderCalendar();
        }

        function deleteEvent(dateKey, index) {
            if (confirm("Tens a certeza que queres eliminar este evento?")) {
                events[dateKey].splice(index, 1);
                if (events[dateKey].length === 0) delete events[dateKey];
                saveEvents();
                openDayModal(dateKey); // Atualiza a modal
                renderCalendar(); // Atualiza o calendário por trás
            }
        }

        function closeModal() {
            document.getElementById('eventModal').style.display = 'none';
            document.getElementById('eventTitle').value = '';
            document.getElementById('eventTime').value = '';
            document.getElementById('eventDesc').value = '';
        }

        function prevMonth() { currentDate.setMonth(currentDate.getMonth() - 1); renderCalendar(); }
        function nextMonth() { currentDate.setMonth(currentDate.getMonth() + 1); renderCalendar(); }

        // Inicializar
        renderCalendar();
    </script>
</body>
</html>