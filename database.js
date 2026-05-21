const STORAGE_KEY = 'lootledgervault_database';

const defaultDatabase = [
    { 
        id: 1,
        img: 'images/jogo1.jpg', 
        title: 'Elden Ring', 
        platform: 'PS5', 
        stock: 12, 
        price: 59.99 
    },
    { 
        id: 2,
        img: 'images/jogo2.jpg', 
        title: 'Retro World', 
        platform: 'Switch', 
        stock: 0, 
        price: 25.00 
    }
];

let database = loadDatabase();

function loadDatabase() {
    const saved = localStorage.getItem(STORAGE_KEY);
    if (saved) return JSON.parse(saved);
    
    // Primeira vez: guarda os dados default
    saveDatabase(defaultDatabase);
    return defaultDatabase.slice();
}

function saveDatabase(data = database) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
    database = data; // mantém sincronizado
}

// === FUNÇÕES ÚTEIS PARA O PROJETO ===
function addItem(newItem) {
    const item = {
        id: Date.now(),
        ...newItem
    };
    database.push(item);
    saveDatabase();
    return item;
}

function updateStock(id, newStock) {
    const item = database.find(i => i.id === id);
    if (item) {
        item.stock = parseInt(newStock);
        saveDatabase();
    }
}

function deleteItem(id) {
    database = database.filter(i => i.id !== id);
    saveDatabase();
}

function searchItems(query) {
    const q = query.toLowerCase();
    return database.filter(item => 
        item.title.toLowerCase().includes(q) || 
        item.platform.toLowerCase().includes(q)
    );
}

// Exporta tudo para poder usar em qualquer página
window.db = {
    database,
    addItem,
    updateStock,
    deleteItem,
    searchItems,
    saveDatabase,
    loadDatabase
};