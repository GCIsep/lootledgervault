<?php
$mysqli = new mysqli('localhost', 'root', '', 'lootledhervault');
if ($mysqli->connect_error) {
    die('Erro de conexão: (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'remove' && isset($_POST['item_id'])) {
        $stmt = $mysqli->prepare('DELETE FROM inventory WHERE id = ?');
        $stmt->bind_param('i', $_POST['item_id']);
        $stmt->execute();
        $stmt->close();
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $title = trim($_POST['title'] ?? $_POST['item_name'] ?? '');
        $platform = trim($_POST['platform'] ?? 'Desconhecido');
        $stock = (int) ($_POST['stock'] ?? $_POST['quantity'] ?? 0);
        $price = (float) ($_POST['price'] ?? 0);
        $img = trim($_POST['image_url'] ?? '');

        if ($title !== '') {
            $stmt = $mysqli->prepare('INSERT INTO inventory (title, platform, stock, price, img) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('ssids', $title, $platform, $stock, $price, $img);
            $stmt->execute();
            $stmt->close();
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}

$result = $mysqli->query('SELECT * FROM inventory');
$inventoryItems = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>


