# 🐳 Docker & Render.com - Rychlý start

Tento dokument obsahuje **rychlý průvodce** pro nasazení QRPaste na Render.com pomocí Dockeru.

---

## ⚡ Nejrychlejší cesta (3 kroky)

### 1. Push na GitHub
```bash
git add .
git commit -m "Deploy to Render.com"
git push origin main
```

### 2. Připoj na Render.com
- Jdi na [dashboard.render.com](https://dashboard.render.com)
- Klikni **"New +"** → **"Blueprint"**
- Vyber svůj GitHub repo `qrpaste`

### 3. Počkej na deploy
- Render automaticky builduje (2-5 min)
- Klikni na URL a hotovo! 🎉

---

## 🧪 Lokální testování (před deployem)

### Windows (PowerShell):
```powershell
.\docker-test.ps1
```

### Linux/Mac (Bash):
```bash
chmod +x docker-test.sh
./docker-test.sh
```

Otevři: http://localhost:8080

---

## 📁 Soubory pro Render.com

Projekt obsahuje tyto konfigurační soubory:

| Soubor | Účel |
|--------|------|
| `Dockerfile` | Docker image konfigurace |
| `render.yaml` | Render.com Blueprint |
| `.dockerignore` | Soubory ignorované při buildu |
| `RENDER_DEPLOY.md` | Detailní návod k deployi |

---

## 🔧 Co dělá Dockerfile?

1. **Base image**: PHP 8.2 + Apache
2. **Instaluje**: SQLite, PDO rozšíření
3. **Povoluje**: mod_rewrite pro .htaccess
4. **Vytváří**: `data/` složku pro databázi
5. **Nastavuje**: upload limity (10MB)
6. **Zabezpečuje**: skryje PHP verzi, nastaví CORS

---

## 🌍 Environment proměnné

Render.com automaticky nastaví:

- `QRPASTE_SECRET` - Náhodný secret (auto)
- `PHP_MEMORY_LIMIT` - 128M
- `PHP_UPLOAD_MAX_FILESIZE` - 10M
- `PHP_POST_MAX_SIZE` - 11M

Můžeš upravit v Render dashboardu: **Environment** tab

---

## 💾 Persistentní disk

Render.com automaticky připojí 1GB disk na `/var/www/html/data` pro SQLite databázi.

**Free tier**: 1GB (cca 10,000-50,000 záznamů)  
**Paid tier**: až 512GB

---

## 🚨 Řešení problémů

### Build fails
```bash
# Otestuj lokálně:
docker build -t qrpaste:test .
```

### 502 Gateway error
- Zkontroluj logy v Render dashboardu
- Ověř že port 80 je EXPOSE v Dockerfile

### SQLite chyby
- Zkontroluj disk mounting v `render.yaml`
- Ověř oprávnění (777 nebo 755) na `data/` složce

---

## 📊 Monitoring

V Render dashboardu máš přístup k:

- **Logs** - Real-time logy aplikace
- **Metrics** - CPU, RAM, síť
- **Events** - Deploy historie
- **Shell** - Přístup do běžícího kontejneru

---

## 💰 Ceny (2025)

| Plán | Cena | Výhody |
|------|------|--------|
| **Free** | $0 | 750h/měsíc, spí po 15 min |
| **Starter** | $7/měsíc | 24/7, bez spaní |
| **Standard** | $25/měsíc | Dedikovaný CPU |

**Tip**: Free tier stačí pro většinu školních projektů!

---

## 🔗 Užitečné odkazy

- 📚 [Render.com Docs](https://render.com/docs)
- 🐳 [Docker Docs](https://docs.docker.com/)
- 💬 [Render Community](https://community.render.com/)
- 🆘 [GitHub Issues](https://github.com/Alogoat/qrpaste/issues)

---

## ✅ Checklist před deployem

- [ ] Commit všech změn do Gitu
- [ ] Push na GitHub
- [ ] Test lokálně pomocí `docker-test.ps1/sh`
- [ ] Připravený Render.com účet
- [ ] GitHub účet připojený na Render

---

**Happy deploying!** 🚀
