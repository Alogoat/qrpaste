# QRPaste API - Ultra Simple Setup

## ✅ Co bylo změněno

**Před:** Složitá `Database.php` třída (singleton, 200+ řádků)  
**Po:** Jednoduchý `db.php` soubor (50 řádků) s přímým PDO připojením

---

## 📁 Struktura

```
api/
├── db.php          ⭐ NOVÝ - Jednoduchá DB inicializace
├── config.php      ✅ Konfigurace
├── helpers.php     ✅ Utility funkce
├── save.php        ✅ POST endpoint (používá db.php)
├── get.php         ✅ GET endpoint (používá db.php)
├── cleanup.php     ✅ CRON cleanup (používá db.php)
└── .htaccess       ✅ Security

data/
└── qrpaste.db      (auto-create)
```

---

## 🚀 Jak to funguje

### 1. **api/db.php** - Automatická inicializace

```php
// Vytvoří databázi pokud neexistuje
$pdo = new PDO('sqlite:' . __DIR__ . '/../data/qrpaste.db');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Vytvoří tabulku
$pdo->exec("CREATE TABLE IF NOT EXISTS pastes (...)");

// Funkce pro generování ID
function generateId($length = 8) { ... }
```

### 2. **Každý endpoint používá db.php**

```php
// save.php, get.php, cleanup.php
require_once __DIR__ . '/db.php';  // $pdo je teď dostupné globálně

// Použití
$stmt = $pdo->prepare("SELECT * FROM pastes WHERE id = ?");
$id = generateId(8);
```

---

## 🧪 Test

```bash
# Základní test
php test-simple.php

# Výstup:
# ✓ Database file created
# ✓ Table 'pastes' exists
# ✓ Paste created with ID: aB3xY9Kp
# ✓ All Tests Passed
```

---

## 📡 API Použití

### Vytvoření paste (POST)

```bash
curl -X POST http://localhost/qrpaste/api/save.php \
  -H "Content-Type: application/json" \
  -d '{"content":"Hello","type":"text","expires_days":7}'

# Response:
# {"success":true,"data":{"id":"aB3xY9Kp","url":"..."}}
```

### Načtení paste (GET)

```bash
curl "http://localhost/qrpaste/api/get.php?id=aB3xY9Kp"

# Response:
# {"success":true,"data":{"content":"Hello","type":"text",...}}
```

### Cleanup expirovaných

```bash
php api/cleanup.php

# Output:
# Deleted pastes: 5
# Active pastes: 120
```

---

## 🔑 Výhody zjednodušení

✅ **Žádná třída** - Přímé PDO připojení  
✅ **Auto-create** - Databáze a tabulka se vytvoří automaticky  
✅ **Globální $pdo** - Dostupné všude po `require 'db.php'`  
✅ **Méně kódu** - 50 řádků místo 200+  
✅ **Jednodušší debugging** - Přímočaré SQL dotazy  

---

## 📝 Databázové schéma

```sql
CREATE TABLE IF NOT EXISTS pastes (
    id TEXT PRIMARY KEY,              -- "aB3xY9Kp"
    content TEXT NOT NULL,
    content_type TEXT DEFAULT 'text',
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    expires_at TEXT,
    size_kb INTEGER,
    password_hash TEXT
);

CREATE INDEX IF NOT EXISTS idx_expires_at ON pastes(expires_at);
```

---

## 🎯 Next Steps

1. **Otestuj API:**
   ```bash
   php test-simple.php
   ```

2. **Integrace s frontendem:**
   ```javascript
   // V index.html
   const response = await fetch('/api/save.php', {
       method: 'POST',
       body: JSON.stringify({content, type:'text', expires_days:7})
   });
   const {data} = await response.json();
   console.log(data.id); // "aB3xY9Kp"
   ```

3. **Nastav CRON:**
   ```bash
   # Každou hodinu
   0 * * * * php /path/to/api/cleanup.php
   ```

---

## 🔒 Bezpečnost (nezměněno)

✅ Prepared statements (SQL injection protection)  
✅ Password hashing (Bcrypt)  
✅ Input validation (helpers.php)  
✅ CORS headers  
✅ .htaccess protection  

---

**Změněno:** 10. listopadu 2025  
**Verze:** Ultra Simple Edition  
**Status:** ✅ Připraveno k použití
