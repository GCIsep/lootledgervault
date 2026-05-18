const STORAGE_KEY = 'lootledgervault_database';

const defaultDatabase = [
    { img: 'images/jogo1.jpg', title: 'Elden Ring', platform: 'PS5', stock: 12, price: 59.99 },
    { img: 'images/jogo2.jpg', title: 'Retro World', platform: 'Switch', stock: 0, price: 25.00 }
];

function loadDatabase() {
    const saved = localStorage.getItem(STORAGE_KEY);
    return saved ? JSON.parse(saved) : defaultDatabase.slice();
}

function saveDatabase() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(database));
}

const database = loadDatabase();