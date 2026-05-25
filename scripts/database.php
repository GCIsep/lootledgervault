<?php
// Ativa a exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Usa __DIR__ para garantir o caminho absoluto da base de dados
$db_path = __DIR__ . '/lootledgervault.db';
$db = new SQLite3($db_path);

// 1. Tabela de Utilizadores (Com a coluna is_admin)
$query_users = "CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    is_admin INTEGER DEFAULT 0, -- 0: Normal, 1: Admin, 2: Super Admin
    ultimo_acesso DATETIME
)";
$db->exec($query_users);

// 2. Tabela de Inventário
$query_inventory = "CREATE TABLE IF NOT EXISTS inventory (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    item_name TEXT NOT NULL,
    quantity INTEGER DEFAULT 1,
    user_id INTEGER,
    FOREIGN KEY(user_id) REFERENCES users(id)
)";
$db->exec($query_inventory);

// 3. Tabela de Calendário
$query_events = "CREATE TABLE IF NOT EXISTS events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    event_date DATE NOT NULL,
    description TEXT NOT NULL,
    user_id INTEGER,
    FOREIGN KEY(user_id) REFERENCES users(id)
)";
$db->exec($query_events);


// =========================================
// CRIAÇÃO AUTOMÁTICA DO SUPER USER (ROOT)
// =========================================
$check_root = $db->querySingle("SELECT COUNT(*) FROM users WHERE username = 'root'");

if ($check_root == 0) {
    // Password visível conforme pediste
    $root_password = 'I12345678i';
    
    // Inserir com is_admin = 2 (Cargo Especial: Super Admin)
    $db->exec("INSERT INTO users (username, password, is_admin) VALUES ('root', '$root_password', 2)");
} else {
    // Se o root já existir de um teste anterior, garante que ele é promovido ao cargo 2 (Super Admin)
    $db->exec("UPDATE users SET is_admin = 2 WHERE username = 'root'");
}
?>