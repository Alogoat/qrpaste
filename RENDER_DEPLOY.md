# QRPaste - Nasazení na Render.com 🚀

Tento návod popisuje, jak nasadit QRPaste aplikaci na Render.com (zdarma nebo placený plán).

---

## 📋 Co je potřeba

1. **GitHub účet** - pro verzování kódu
2. **Render.com účet** - [render.com](https://render.com) (zdarma)
3. **Git nainstalovaný** - pro push do GitHubu

---

## 🚀 Rychlé nasazení (5 minut)

### Krok 1: Nahraj projekt na GitHub

```bash
# V adresáři projektu
git init
git add .
git commit -m "Initial commit - QRPaste aplikace"

# Vytvoř nové repo na GitHubu: github.com/new
# Pak nahraj kód:
git remote add origin https://github.com/TVOJE_JMENO/qrpaste.git
git branch -M main
git push -u origin main
```

### Krok 2: Připojení na Render.com

1. Jdi na [render.com](https://render.com) a přihlas se
2. Klikni na **"New +"** → **"Blueprint"**
3. Připoj svůj GitHub účet (pokud ještě není)
4. Vyber repozitář `qrpaste`
5. Render automaticky detekuje `render.yaml` a nastaví vše

### Krok 3: Nasazení

1. Klikni **"Apply"**
2. Render začne:
   - Buildovat Docker image (2-5 minut)
   - Vytvářet disk pro databázi
   - Generovat secret klíč
3. Počkej na dokončení deploye
4. Klikni na URL (např. `https://qrpaste.onrender.com`)

✅ **Hotovo!** Aplikace je živá na internetu.

---

## 🔧 Pokročilá konfigurace

### Environment proměnné

V Render dashboardu můžeš nastavit:

| Proměnná | Popis | Výchozí |
|----------|-------|---------|
| `QRPASTE_SECRET` | Secret pro hashování IP | Auto-generováno |
| `PHP_MEMORY_LIMIT` | Paměť pro PHP | 128M |
| `PHP_UPLOAD_MAX_FILESIZE` | Max velikost uploadu | 10M |
| `PHP_POST_MAX_SIZE` | Max velikost POST requestu | 11M |

### Vlastní doména

1. V Render dashboardu jdi na svou službu
2. **Settings** → **Custom Domain**
3. Přidej svou doménu (např. `qrpaste.example.com`)
4. Nastav DNS záznamy u svého registrátora:
   ```
   CNAME qrpaste -> your-app.onrender.com
   ```
5. Render automaticky vytvoří SSL certifikát (zdarma)

### CORS nastavení pro vlastní doménu

Edituj `backend.php` (řádek 16):

```php
define('ALLOWED_ORIGINS', [
    'https://qrpaste.example.com'  // Tvoje doména
]);
```

Commit a push:
```bash
git add backend.php
git commit -m "Update CORS for custom domain"
git push
```

Render automaticky redeployuje.

---

## 💰 Cena a limity

### Free Tier (zdarma)
- ✅ 750 hodin běhu měsíčně
- ✅ 1 GB disku (SQLite databáze)
- ✅ HTTPS certifikát zdarma
- ✅ Vlastní doména
- ⚠️ App "spí" po 15 minutách neaktivity (1. request po probuzení trvá ~30s)
- ⚠️ Omezený compute (sdílené CPU)

### Starter ($7/měsíc)
- ✅ Neomezené hodiny
- ✅ Žádné spaní
- ✅ 10 GB disku
- ✅ Rychlejší CPU

### Standard ($25/měsíc)
- ✅ Vše z Starter
- ✅ 20 GB disku
- ✅ Dedikovaný CPU

---

## 📊 Monitoring

### Logy

V Render dashboardu:
1. **Logs** tab - real-time výstup aplikace
2. Sleduj chyby, requesty, atd.

### Metriky

1. **Metrics** tab - CPU, paměť, síť
2. **Events** - deploy historie

### Alerts

Nastav notifikace pro:
- Selhání deploye
- High CPU/memory usage
- Dostupnost služby

---

## 🔄 Aktualizace aplikace

### Automatické nasazení z Gitu

Render automaticky redeployuje při každém pushu do main branche:

```bash
# Udělej změny v kódu
git add .
git commit -m "Nová funkce"
git push

# Render automaticky builduje a deployuje
```

### Manuální redeploy

V Render dashboardu:
1. **Manual Deploy** → **Deploy latest commit**

---

## 🐛 Řešení problémů

### Build selhává

**Chyba:** Docker build fails  
**Řešení:**
1. Zkontroluj, že všechny soubory jsou commitnuty
2. Ověř, že `Dockerfile` je v root složce
3. Podívej se do build logů v Render

### Aplikace se nenačte

**Chyba:** 502 Bad Gateway  
**Řešení:**
1. Zkontroluj logy v Render dashboardu
2. Ověř, že port 80 je správně exponován
3. Zkus manuální redeploy

### Databáze se neinicializuje

**Chyba:** SQLite errors v lozích  
**Řešení:**
1. Ověř, že disk je připojen: **Settings** → **Disks**
2. Zkontroluj mount path: `/var/www/html/data`
3. Ověř oprávnění v `Dockerfile`

### Free tier spaní

**Problém:** První request po 15 min. trvá dlouho  
**Řešení:**
- Upgraduj na Starter plán ($7/měsíc)
- Nebo použij cron job pro ping každých 10 minut

---

## 🔒 Bezpečnost v produkci

### 1. Změň SECRET

Vygeneruj silný secret:

```bash
openssl rand -base64 32
```

Nastav v Render:
1. **Environment** → `QRPASTE_SECRET`
2. Vlož vygenerovaný secret
3. **Save Changes**

### 2. Nastav CORS

Edituj `backend.php` (řádek 16):

```php
define('ALLOWED_ORIGINS', [
    'https://tvoje-domena.onrender.com'
]);
```

### 3. Monitoring

Nastav alerts v Render pro:
- Abnormální traffic
- High error rate
- CPU/memory spike

---

## 📚 Další zdroje

- [Render.com dokumentace](https://render.com/docs)
- [Docker best practices](https://docs.docker.com/develop/dev-best-practices/)
- [PHP security checklist](https://www.php.net/manual/en/security.php)

---

## 🆘 Podpora

Problémy s deployem?

1. Zkontroluj [Render Status](https://status.render.com/)
2. Podívej se do [Render Community](https://community.render.com/)
3. Vytvoř issue na [GitHub](https://github.com/Alogoat/qrpaste/issues)

---

**Úspěšný deploy!** 🎉

Tvoje aplikace je teraz live na: `https://your-app.onrender.com`
