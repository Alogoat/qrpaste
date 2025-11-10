<?php
/**
 * QRPaste - Database Initialization
 * Jednoduchá inicializace SQLite databáze
 */

// Cesta k databázi
$dbPath = __DIR__ . '/../data/qrpaste.db';

// Vytvoř data složku pokud neexistuje
if (!file_exists(dirname($dbPath))) {
    mkdir(dirname($dbPath), 0755, true);
}

// PDO připojení
$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// SQLite optimalizace
$pdo->exec('PRAGMA foreign_keys = ON');
$pdo->exec('PRAGMA journal_mode = WAL');
$pdo->exec('PRAGMA synchronous = NORMAL');

// Vytvoř tabulku pokud neexistuje
$pdo->exec("
    CREATE TABLE IF NOT EXISTS pastes (
        id TEXT PRIMARY KEY,
        content TEXT NOT NULL,
        content_type TEXT DEFAULT 'text',
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        expires_at TEXT,
        size_kb INTEGER,
        password_hash TEXT
    )
");

// Vytvoř index pro rychlejší cleanup
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_expires_at ON pastes(expires_at)");

/**
 * Generování unikátního ID
 * @param int $length Délka ID
 * @return string
 */
function generateId($length = 8) {
    global $pdo;
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    
    for ($i = 0; $i < 10; $i++) {
        $id = '';
        for ($j = 0; $j < $length; $j++) {
            $id .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        // Kontrola unikátnosti
        $stmt = $pdo->prepare("SELECT id FROM pastes WHERE id = ?");
        $stmt->execute([$id]);
        
        if (!$stmt->fetch()) {
            return $id;
        }
    }
    
    return generateId($length + 1);
}
