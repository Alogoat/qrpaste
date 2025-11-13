# QRPaste

**Rychlé sdílení textu, kódu a obrázků pomocí QR kódů**

QRPaste je jednoduchá webová aplikace určená primárně pro školy, která umožňuje okamžité sdílení obsahu mezi studenty a učiteli pomocí QR kódů, krátkých URL adres nebo 8-znakových kódů.

---

## 📋 Obsah

- [Co to je?](#-co-to-je)
- [Klíčové funkce](#-klíčové-funkce)
- [Jak to používat?](#-jak-to-používat)
- [Instalace](#-instalace)
- [Požadavky](#-požadavky)
- [Režimy sdílení](#-režimy-sdílení)
- [TV režim](#-tv-režim)
- [Bezpečnost](#-bezpečnost)
- [Řešení problémů](#-řešení-problémů)
- [Technické informace](#-technické-informace)

---

## 🎯 Co to je?

QRPaste je webová aplikace navržená pro **jednoduché a rychlé sdílení obsahu** bez nutnosti registrace nebo přihlášení. Ideální pro:

- **Studenty** - sdílení poznámek, kódu nebo screenshotů se spolužáky
- **Učitele** - zobrazování materiálů na projektoru pomocí QR kódů
- **Týmovou práci** - rychlá výměna informací během projektů
- **Prezentace** - sdílení odkazů nebo kódu s publikem

### Proč QRPaste?

✅ **Žádná registrace** - začnete okamžitě  
✅ **Funguje offline** - část aplikace pracuje i bez internetu  
✅ **Mobile-friendly** - optimalizováno pro telefony i tablety  
✅ **Bezpečné** - automatická expirce obsahu

✅ **Rychlé** - od nahrání po sdílení během sekund  

---

## ✨ Klíčové funkce

### Typy obsahu
- **Text a kód** - poznámky, programovací kód, odkazy (až 50 000 znaků)
- **Obrázky** - screenshoty, fotky, diagramy (až 10 MB)

### Způsoby sdílení
1. **QR kód** - naskenujte kamerou telefonu
2. **Krátká URL** - zkopírujte a pošlete odkaz
3. **8-znakový kód** - zadejte ručně (např. `ge9rg4t2`)

### Speciální funkce
- **TV režim** - zobrazení na celou obrazovku pro projektory
- **Drag & drop** - přetáhněte obrázek do aplikace
- **Paste ze schránky** - `Ctrl+V` pro vložení screenshotu
- **Automatická komprese** - zkrácení URL až o 90%
- **Offline podpora** - kratší texty fungují bez serveru

---

## 📖 Jak to používat?

### Základní použití (krok za krokem)

#### 1. Otevřete aplikaci
Spusťte aplikaci v prohlížeči: `http://localhost/qrpaste` (nebo vaše doménové jméno)

#### 2. Vložte obsah

**Text nebo kód:**
- Napište nebo vložte text do textového pole
- Podporuje: poznámky, kód, odkazy, JSON, atd.

**Obrázek:**
- Klikněte na "Vyfotit" (použije kameru)
- Klikněte na "Nahrát" (vyberte ze zařízení)
- Přetáhněte obrázek do vyznačené oblasti
- Stiskněte `Ctrl+V` pro vložení ze schránky

#### 3. Vyberte režim sdílení

**URL režim** (doporučeno pro kratší texty):
- Data jsou zakódována přímo v URL
- Funguje i offline
- Limit: kratší texty a malé obrázky (~2000 znaků v URL)

**Databázový režim** (pro větší soubory):
- Data se uloží na server
- Podporuje větší obrázky a dlouhé texty
- Volitelné heslo pro ochranu
- Automatická expirce po 7 dnech

#### 4. Generujte sdílení
- Klikněte na **"Vygenerovat QR, URL a kód"**
- Aplikace vytvoří:
  - **QR kód** pro naskenování
  - **URL adresu** pro zkopírování
  - **8-znakový kód** (pouze v DB režimu)

#### 5. Sdílejte
- **QR kód**: Ostatní jej naskenují kamerou telefonu
- **URL**: Zkopírujte a pošlete (email, chat, atd.)
- **Kód**: Řekněte nahlas nebo napište na tabuli

---

### Zobrazení sdíleného obsahu

Existují **3 způsoby**, jak zobrazit sdílený obsah:

#### Metoda 1: Naskenování QR kódu
1. Otevřete kameru na telefonu
2. Nasměrujte na QR kód
3. Klikněte na notifikaci/odkaz
4. Obsah se zobrazí automaticky

#### Metoda 2: Otevření URL
1. Zkopírujte URL adresu
2. Vložte do prohlížeče
3. Obsah se načte automaticky

#### Metoda 3: Zadání kódu
1. Na homepage zadejte 8-znakový kód
2. Klikněte "Zobrazit"
3. Obsah se zobrazí

---

## 🚀 Instalace

### Rychlá instalace (základní hosting)

QRPaste je navržen pro **snadné nasazení** bez složité konfigurace.

#### Požadavky
- **Webový hosting** s podporou PHP 8.0+
- **SQLite podpora** (většinou součástí PHP)
- **FTP/SFTP přístup** nebo panel hostingu

#### Kroky instalace

**1. Stáhněte soubory**
```bash
git clone https://github.com/Alogoat/qrpaste.git
# nebo stáhněte ZIP z GitHubu
```

**2. Nahrajte na hosting**
Přes FTP nahrajte tyto soubory:
```
qrpaste/
├── index.html          # Hlavní aplikace
├── backend.php         # API server
├── qrcode.min.js       # QR kód knihovna
├── styles.css          # Styly
├── .htaccess           # Apache konfigurace (volitelné)
└── data/               # Vytvořte prázdnou složku (pro SQLite DB)
```

**3. Nastavte oprávnění**
```bash
chmod 755 data/
chmod 755 backend.php
```

**4. Otevřete v prohlížeči**
```
https://vase-domena.cz/qrpaste/
```

✅ **Hotovo!** Aplikace je připravena k použití.

---

### Pokročilá instalace (lokální vývoj)

#### Použití PHP vestavěného serveru

```bash
# 1. Naklonujte repo
git clone https://github.com/Alogoat/qrpaste.git
cd qrpaste

# 2. Vytvořte data složku
mkdir data

# 3. Spusťte PHP server
php -S localhost:8000

# 4. Otevřete prohlížeč
http://localhost:8000
```

#### Docker kontejner (coming soon)

```bash
docker run -p 8080:80 -v $(pwd)/data:/app/data qrpaste/qrpaste
```

---

## 📦 Požadavky

### Minimální požadavky

**Server:**
- PHP 8.0 nebo vyšší
- SQLite 3 (obvykle součástí PHP)
- Apache nebo Nginx
- Min. 50 MB volného místa

**Klient (prohlížeč):**
- Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
- JavaScript povolen
- Podpora HTML5 a Canvas API

### Doporučené nastavení

**Server:**
- PHP 8.2+
- SQLite 3.35+
- HTTPS certifikát (Let's Encrypt zdarma)
- Cron job pro automatický cleanup

**Klient:**
- Moderní prohlížeč s aktualizacemi
- Kamera (pro focení a skenování QR)
- Připojení k internetu (pro DB režim)

---

## 🔄 Režimy sdílení

### URL režim (Offline)

**Jak to funguje:**
- Obsah je zakódován přímo do URL adresy
- Používá base64 a LZ-String kompresi
- Data se ukládají v URL hash (část za `#`)

**Výhody:**
✅ Funguje offline  
✅ Žádná databáze potřeba  
✅ Okamžité sdílení  
✅ Žádná expirce  

**Nevýhody:**
❌ Limit ~2000 znaků  
❌ Dlouhé URL pro větší obsah  
❌ Nelze heslo ochrana  

**Ideální pro:**
- Krátké texty a poznámky
- Malé screenshoty
- Rychlé sdílení kódu
- Offline použití

### Databázový režim (Server)

**Jak to funguje:**
- Obsah se uloží do SQLite databáze na serveru
- Vrátí se krátké ID (např. `ge9rg4t2`)
- Data automaticky expirují po 7 dnech

**Výhody:**
✅ Velké soubory (až 10 MB)  
✅ Krátké URL vždy  
✅ Volitelné heslo  
✅ Automatická expirce  

**Nevýhody:**
❌ Vyžaduje server  
❌ Vyžaduje připojení  
❌ Omezený počet requestů  

**Ideální pro:**
- Velké obrázky
- Dlouhé texty
- Chráněný obsah (heslem)
- Sdílení s expirací

---

## 📺 TV režim

**TV režim** je speciální fullscreen zobrazení optimalizované pro projektory a televizory ve třídách.

### Jak aktivovat TV režim

1. Zobrazťe si sdílený obsah
2. Klikněte na tlačítko **"TV"** v záhlaví
3. Aplikace se přepne na celou obrazovku

### Funkce TV režimu

- **Velké písmo** - čitelné ze 3+ metrů
- **QR kód v rohu** - pro snadné naskenování studenty
- **Auto-refresh** - volitelná automatická aktualizace každých 30s
- **Ovládání**:
  - `ESC` nebo `F11` - ukončit TV režim
  - Toggle QR - zobrazit/skrýt QR kód
  - Toggle Auto-refresh - zapnout/vypnout automatickou aktualizaci

### Použití pro učitele

**Scénář:** Chcete zobrazit text/kód na projektoru

1. Vytvořte sdílení (text nebo obrázek)
2. Klikněte "TV režim"
3. Projektor zobrazí obsah velkým písmem
4. V rohu je QR kód - studenti ho naskenují
5. Studenti mají obsah na svých telefonech

---

## 🔒 Bezpečnost

QRPaste je navržen s důrazem na bezpečnost a ochranu soukromí.

### Implementovaná ochrana

**Vstupní validace:**
- ✅ Whitelist povolených typů souborů
- ✅ Kontrola velikosti obsahu
- ✅ Sanitizace uživatelského vstupu
- ✅ XSS ochrana (escapování HTML)

**Rate limiting:**
- ✅ Max 5 requestů za minutu
- ✅ Max 20 requestů za hodinu
- ✅ Ochrana proti DDoS útokům

**Ochrana dat:**
- ✅ Automatická expirce (7 dní default)
- ✅ Volitelné heslo (bcrypt hash)
- ✅ IP adresy hashované (GDPR)
- ✅ SQLite injection ochrana (prepared statements)

**HTTP security headers:**
- ✅ CSP (Content Security Policy)
- ✅ X-Frame-Options: DENY
- ✅ X-Content-Type-Options: nosniff
- ✅ X-XSS-Protection

**HTTPS:**
- ⚠️ **Doporučeno pro produkci!**
- Zabezpečí přenos dat
- Umožní použití kamery (required by browsers)

### Doporučení pro produkci

1. **Změňte secret v `backend.php`:**
   ```php
   // Řádek 186 v backend.php
   $salt = getenv('QRPASTE_SECRET') ?: 'change_me_in_production_2025';
   ```
   Nastavte environment proměnnou `QRPASTE_SECRET` s náhodným stringem.

2. **Povolte pouze vaši doménu v CORS:**
   ```php
   // Řádek 16 v backend.php
   define('ALLOWED_ORIGINS', [
       'https://vase-domena.cz'
   ]);
   ```

3. **Nastavte HTTPS:**
   - Použijte Let's Encrypt (zdarma)
   - Povolte HTTPS redirect v `.htaccess`

4. **Pravidelný cleanup:**
   Nastavte cron job pro mazání expirovaných záznamů:
   ```bash
   # Každý den ve 3:00
   0 3 * * * php /cesta/k/qrpaste/cleanup.php
   ```

---

## 🔧 Řešení problémů

### Aplikace se nenačte

**Problém:** Bílá stránka nebo chyba  
**Řešení:**
1. Zkontrolujte PHP verzi: `php -v` (musí být 8.0+)
2. Zkontrolujte chybový log: `/var/log/apache2/error.log`
3. Ověřte oprávnění: `data/` složka musí být zapisovatelná

### QR kód se negeneruje

**Problém:** Po kliknutí na "Vygenerovat" se nic nestane  
**Řešení:**
1. Otevřete konzoli prohlížeče (F12)
2. Zkontrolujte chyby v konzoli
3. Ověřte, že `qrcode.min.js` se načetl správně
4. Zkuste vymazat cache prohlížeče

### Databázový režim nefunguje

**Problém:** Chyba "Failed to save content"  
**Řešení:**
1. Zkontrolujte, že složka `data/` existuje
2. Ověřte oprávnění: `chmod 755 data/`
3. Zkontrolujte, že SQLite je povoleno: `php -m | grep sqlite`
4. Zkontrolujte error log v `backend.php`

### Kamera nefunguje

**Problém:** Nelze použít "Vyfotit" tlačítko  
**Řešení:**
1. Použijte **HTTPS** (browsers vyžadují)
2. Povolte kamera permissions v prohlížeči
3. Zkontrolujte, že kamera není používána jinou aplikací

### URL je příliš dlouhá

**Problém:** "URL je příliš dlouhá" chyba  
**Řešení:**
1. Použijte **databázový režim** místo URL režimu
2. Zkraťte text
3. Zmenšete obrázek (komprese)

### Rate limit exceeded

**Problém:** "Too many requests" chyba  
**Řešení:**
1. Počkejte 1 hodinu
2. Neposílejte příliš mnoho requestů najednou
3. Kontaktujte administrátora pro zvýšení limitu

---

## 💻 Technické informace

### Architektura

**Frontend (Single Page App):**
- HTML5 + Tailwind CSS (utility-first styling)
- Alpine.js (reaktivní framework, 15KB)
- QRCode.js (node-qrcode library)
- LZ-String (kompresní algoritmus)

**Backend (REST API):**
- PHP 8+ (bez frameworků)
- SQLite 3 (embedded databáze)
- PDO (prepared statements)

### Struktura projektu

```
qrpaste/
├── index.html          # Hlavní SPA aplikace (3495 řádků)
├── backend.php         # REST API (500+ řádků)
├── qrcode.min.js       # QR kód generátor (minified)
├── styles.css          # Custom CSS styly
├── .htaccess           # Apache konfigurace
├── .git/               # Git repozitář
├── assets/             # Statické soubory (ikony, atd.)
└── data/               # SQLite databáze (vytvoří se auto)
    └── qrpaste.db      # SQLite soubor
```

### Databázová schema

```sql
CREATE TABLE pastes (
    id TEXT PRIMARY KEY,              -- 8-znakové ID (a-zA-Z0-9)
    content TEXT NOT NULL,            -- Obsah (text nebo base64 image)
    content_type TEXT DEFAULT 'text', -- 'text' | 'code' | 'image'
    password_hash TEXT DEFAULT NULL,  -- bcrypt hash (optional)
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    expires_at TEXT NOT NULL,         -- ISO 8601 datetime
    size_bytes INTEGER NOT NULL,      -- Velikost obsahu
    ip_hash TEXT NOT NULL             -- SHA256 hash IP (GDPR)
);
```

### API Endpoints

#### POST /backend.php?action=save
Uloží nový obsah do databáze.

**Request:**
```json
{
  "content": "Text nebo base64 obrázek",
  "content_type": "text",
  "expires_days": 7,
  "password": "optional_password"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": "ge9rg4t2",
    "url": "http://localhost/qrpaste?id=ge9rg4t2",
    "expires_at": "2025-11-20 14:30:00",
    "size_kb": 12.5,
    "has_password": false
  }
}
```

#### GET /backend.php?action=get&id=xxx
Načte obsah z databáze.

**Request:**
```
GET /backend.php?action=get&id=ge9rg4t2&password=optional
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": "ge9rg4t2",
    "content": "Sdílený obsah",
    "type": "text",
    "created_at": "2025-11-13 14:30:00",
    "expires_at": "2025-11-20 14:30:00",
    "size_kb": 12.5
  }
}
```

### Limity

| Typ | URL režim | Databázový režim |
|-----|-----------|------------------|
| Text | ~2000 znaků | 5 MB |
| Kód | ~2000 znaků | 2 MB |
| Obrázek | Malé (~100KB) | 10 MB |
| Expirce | Nikdy | 7 dní (default) |
| Heslo | ❌ | ✅ |

### Kompatibilita prohlížečů

| Prohlížeč | Minimální verze |
|-----------|----------------|
| Chrome | 90+ |
| Firefox | 88+ |
| Safari | 14+ |
| Edge | 90+ |
| Opera | 76+ |

**Mobile:**
- iOS Safari 14+
- Chrome Android 90+
- Samsung Internet 14+

---

## 📄 Licence

Tento projekt je open-source a dostupný pod MIT licencí.

---

## 👨‍💻 Autor

**QRPaste** - Vytvořeno pro studenty a učitele

GitHub: [github.com/Alogoat/qrpaste](https://github.com/Alogoat/qrpaste)

---

## 🤝 Podpora

Pokud máte **problémy, dotazy nebo návrhy**, vytvořte issue na GitHubu:

👉 [github.com/Alogoat/qrpaste/issues](https://github.com/Alogoat/qrpaste/issues)

---

## 📝 Changelog

### v1.0.0 (Listopad 2025)
- ✅ Základní funkcionalita (URL + DB režim)
- ✅ QR kód generování
- ✅ TV režim pro projektory
- ✅ Drag & drop upload
- ✅ Paste ze schránky (Ctrl+V)
- ✅ Automatická komprese (LZ-String)
- ✅ Rate limiting
- ✅ Bezpečnostní features
- ✅ Mobile-friendly UI
- ✅ Offline podpora (URL režim)

---

**Děkujeme, že používáte QRPaste!** 🎉
