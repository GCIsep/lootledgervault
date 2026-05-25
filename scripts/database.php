<?php
// Ativa a exibição de erros no browser para facilitar o debug (Guião W2)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Usa __DIR__ para garantir o caminho absoluto e evitar erros no Mac
$db_path = __DIR__ . '/lootledgervault.db';

// Estabelece a ligação com a base de dados SQLite3 (cria o ficheiro se não existir)
$db = new SQLite3($db_path);

// 1. Tabela de Utilizadores (Login)
$query_users = "CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    ultimo_acesso DATETIME
)";
$db->exec($query_users);

// 2. Tabela de Inventário (Jogos)
$query_inventory = "CREATE TABLE IF NOT EXISTS inventory (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    item_name TEXT NOT NULL,
    quantity INTEGER DEFAULT 1,
    user_id INTEGER,
    FOREIGN KEY(user_id) REFERENCES users(id)
)";
$db->exec($query_inventory);

// 3. Tabela de Calendário (Eventos)
$query_events = "CREATE TABLE IF NOT EXISTS events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    event_date DATE NOT NULL,
    description TEXT NOT NULL,
    user_id INTEGER,
    FOREIGN KEY(user_id) REFERENCES users(id)
)";
$db->exec($query_events);
?>