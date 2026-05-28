<?php
// tou cansada de editar esta merda, deixa-me em paz porra nixxie
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendário - Loot Ledger Vault</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=VT323&display=swap');
        
        :root { --arcane-gold: #F5D21D; }
        
        * { margin:0; padding:0; box-sizing:border-box; }
        
        body {
            background: #111;
            color: var(--arcane-gold);
            font-family: 'VT323', monospace;
        }
        
        .dashboard {
            max-width: 1450px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #1a1a1a;
            padding: 18px 35px;
            border-bottom: 5px solid var(--arcane-gold);
            margin-bottom: 30px;
            border-radius: 8px;
        }
        
        .nav a {
            color: var(--arcane-gold);
            text-decoration: none;
            margin: 0 22px;
            font-size: 1.7rem;
        }
        .nav a:hover, .nav a.active { color: #fff; text-shadow: 0 0 15px var(--arcane-gold); }
        
        .main-content {
            display: flex;
            gap: 30px;
        }
        
        /* Sidebar esquerda */
        .sidebar {
            width: 390px;
            background: rgba(20,20,20,0.97);
            border: 4px solid var(--arcane-gold);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 0 50px var(--arcane-gold);
        }
        
        .section { margin-bottom: 40px; }
        .section h3 { margin-bottom: 12px; font-size: 1.9rem; }
        
        /* Calendário */
        .calendar-container {
            flex: 1;
            background: rgba(20,20,20,0.97);
            border: 5px solid var(--arcane-gold);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 0 60px rgba(245,210,29,0.5);
        }
        
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            font-size: 3rem;
        }
        
        .nav-btn {
            background: transparent;
            border: 3px solid var(--arcane-gold);
            color: var(--arcane-gold);
            padding: 12px 40px;
            font-size: 1.9rem;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
        }
        .nav-btn:hover { background: var(--arcane-gold); color: #000; }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
            background: #111;
            padding: 12px;
            border-radius: 10px;
        }
        
        .day-header {
            text-align: center;
            padding: 18px 10px;
            background: linear-gradient(#2a2a2a, #1f1f1f);
            font-size: 1.6rem;
            border: 2px solid var(--arcane-gold);
            border-radius: 6px;
            text-shadow: 0 0 12px var(--arcane-gold);
        }
        
        .day {
            background: #1f1f1f;
            min-height: 140px;
            padding: 12px;
            border-radius: 8px;
            border: 2px solid #333;
            cursor: pointer;
            transition: all 0.25s ease;
        }
        .day:hover { border-color: var(--arcane-gold); transform: translateY(-4px); }
        .day.today { background: rgba(245,210,29,0.2); border-color: var(--arcane-gold); }
        .day.other-month { opacity: 0.35; }
        
        .day-number { font-size: 2.1rem; margin-bottom: 10px; }
        
        .event {
            font-size: 1.05rem;
            padding: 4px 10px;
            margin: 4px 0;
            background: rgba(245,210,29,0.18);
            border-left: 5px solid var(--arcane-gold);
            border-radius: 4px;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.93);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: #1a1a1a;
            border: 5px solid var(--arcane-gold);
            border-radius: 12px;
            padding: 40px;
            width: 90%;
            max-width: 640px;
            color: var(--arcane-gold);
            box-shadow: 0 0 80px var(--arcane-gold);
        }
        
        input, textarea, button {
            font-family: 'VT323', monospace;
            width: 100%;
            padding: 14px;
            margin: 10px 0;
            background: #111;
            border: 3px solid var(--arcane-gold);
            color: var(--arcane-gold);
            font-size: 1.5rem;
            border-radius: 6px;
        }
        
        button { cursor: pointer; }
        button:hover { background: var(--arcane-gold); color: #000; }
    </style>
</head>
<body>
    <div class="dashboard">
        <header>
            <h1 style="color:#F5D21D;">Gestão de Inventário</h1>
            <nav class="nav">
                <a href="index.php">INÍCIO</a>
                <a href="inventario.php">INVENTÁRIO</a>
                <a href="calendario.php" class="active">CALENDÁRIO</a>
                <a href="admin.php">ADMIN</a>
            </nav>
            <div>Bem-vindo, Ana</div>
        </header>

        <div class="main-content">
            <!-- Sidebar esquerda -->
            <div class="sidebar">
                <div class="section">
                    <h3 style="color:#ff4444;">⚠ Stock em Falta</h3>
                    <p>Nenhum jogo esgotado.</p>
                </div>
                
                <div class="section">
                    <h3 style="color:#F5D21D;">📦 Stock em Excesso (&gt;50)</h3>
                    <div style="background:#222; padding:18px; margin-bottom:15px; border-left:5px solid #F5D21D;">
                        <strong>Elden Ring</strong><br>
                        Quantidade atual: 60 unidades<br>
                        Excesso detetado no sistema.
                    </div>
                </div>
                
                <div class="section">
                    <h3 style="color:#00ff88;">✨ Últimos Jogos Adicionados</h3>
                    <div style="background:#222; padding:18px; margin-bottom:15px; border-left:5px solid #00ff88;">
                        Forza Horizon 6<br>
                        Entrada inicial: 30 unidades<br>
                        Plataforma: XBOX
                    </div>
                    <div style="background:#222; padding:18px; border-left:5px solid #00ff88;">
                        Elden Ring<br>
                        Entrada inicial: 60 unidades<br>
                        Plataforma: XBOX
                    </div>
                </div>
            </div>

            <!-- Calendário Interativo -->
            <div class="calendar-container">
                <div class="calendar-header">
                    <button class="nav-btn" onclick="prevMonth()">‹ Anterior</button>
                    <div id="monthTitle"></div>
                    <button class="nav-btn" onclick="nextMonth()">Próximo ›</button>
                </div>
                
                <div class="calendar-grid" id="calendarGrid"></div>
            </div>
        </div>
    </div>

    <!-- Modal para editar eventos -->
    <div id="eventModal" class="modal">
        <div class="modal-content">
            <h2 id="modalDate" style="margin-bottom:25px; text-align:center;"></h2>
            <div id="eventsList"></div>
            
            <h3 style="margin:30px 0 15px;">Adicionar Evento</h3>
            <input type="text" id="eventTitle" placeholder="Título do evento">
            <input type="time" id="eventTime">
            <textarea id="eventDesc" rows="3" placeholder="Descrição (opcional)"></textarea>
            
            <button onclick="addEventToDay()">Adicionar Evento</button>
            <button onclick="closeModal()" style="background:#333; margin-left:15px;">Fechar</button>
        </div>
    </div>

    <script>
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
                currentDate.toLocaleString('pt-PT', { month: 'long', year: 'numeric' }).toUpperCase();
            
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
            
            for (let i = firstDay - 1; i >= 0; i--) grid.appendChild(createDayElement(daysInPrevMonth - i, true));
            for (let day = 1; day <= daysInMonth; day++) grid.appendChild(createDayElement(day, false));
            const remaining = 42 - grid.children.length;
            for (let day = 1; day <= remaining; day++) grid.appendChild(createDayElement(day, true));
        }

        function createDayElement(day, isOtherMonth) {
            const el = document.createElement('div');
            el.className = `day ${isOtherMonth ? 'other-month' : ''}`;
            
            const dateKey = `${currentDate.getFullYear()}-${String(currentDate.getMonth()+1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
            
            el.innerHTML = `<div class="day-number">${day}</div><div id="events-${dateKey}" style="font-size:0.95rem;"></div>`;
            
            if (events[dateKey]) {
                const container = el.querySelector(`#events-${dateKey}`);
                events[dateKey].slice(0, 3).forEach(ev => {
                    const e = document.createElement('div');
                    e.className = 'event';
                    e.textContent = ev.time ? `${ev.time} ${ev.title}` : ev.title;
                    container.appendChild(e);
                });
            }
            
            el.onclick = () => openDayModal(dateKey);
            return el;
        }

        function openDayModal(dateKey) {
            selectedDate = dateKey;
            document.getElementById('modalDate').textContent = `Eventos - ${dateKey}`;
            
            const list = document.getElementById('eventsList');
            list.innerHTML = events[dateKey] && events[dateKey].length > 0 
                ? events[dateKey].map((ev, i) => `
                    <div style="margin-bottom:18px; padding:10px; background:#222; border-radius:6px;">
                        <strong>${ev.time || ''} ${ev.title}</strong><br>
                        ${ev.desc || ''}
                        <button onclick="deleteEvent('${dateKey}', ${i});" style="float:right; padding:6px 14px; font-size:1rem;">Remover</button>
                    </div>`).join('')
                : '<p style="opacity:0.6; text-align:center;">Nenhum evento neste dia.</p>';
            
            document.getElementById('eventModal').style.display = 'flex';
        }

        function addEventToDay() {
            const title = document.getElementById('eventTitle').value.trim();
            const time = document.getElementById('eventTime').value;
            const desc = document.getElementById('eventDesc').value.trim();
            
            if (!title) return alert("Escreve um título para o evento!");
            
            if (!events[selectedDate]) events[selectedDate] = [];
            events[selectedDate].push({ title, time, desc });
            
            saveEvents();
            closeModal();
            renderCalendar();
        }

        function deleteEvent(dateKey, index) {
            if (confirm("Eliminar este evento?")) {
                events[dateKey].splice(index, 1);
                if (events[dateKey].length === 0) delete events[dateKey];
                saveEvents();
                openDayModal(dateKey);
                renderCalendar();
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

        renderCalendar();
    </script>
</body>
</html>