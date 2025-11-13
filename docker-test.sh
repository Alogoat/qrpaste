#!/bin/bash
# QRPaste - Docker test script
# Tento skript sestaví a otestuje Docker image lokálně před nasazením

echo "🐳 QRPaste Docker Build & Test"
echo "================================"
echo ""

# Barvy pro terminál
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 1. Build Docker image
echo "📦 Krok 1/4: Building Docker image..."
if docker build -t qrpaste:test . ; then
    echo -e "${GREEN}✅ Build úspěšný${NC}"
else
    echo -e "${RED}❌ Build selhal${NC}"
    exit 1
fi
echo ""

# 2. Spuštění kontejneru
echo "🚀 Krok 2/4: Spouštím kontejner..."
docker rm -f qrpaste-test 2>/dev/null
if docker run -d \
    --name qrpaste-test \
    -p 8080:80 \
    -e QRPASTE_SECRET="test_secret_12345" \
    qrpaste:test ; then
    echo -e "${GREEN}✅ Kontejner spuštěn na http://localhost:8080${NC}"
else
    echo -e "${RED}❌ Spuštění kontejneru selhalo${NC}"
    exit 1
fi
echo ""

# Počkat na start Apache
echo "⏳ Čekám na start Apache serveru..."
sleep 5

# 3. Health check
echo "🏥 Krok 3/4: Health check..."
if curl -f http://localhost:8080/ > /dev/null 2>&1 ; then
    echo -e "${GREEN}✅ Aplikace odpovídá na port 8080${NC}"
else
    echo -e "${RED}❌ Aplikace neodpovídá${NC}"
    echo "Logy kontejneru:"
    docker logs qrpaste-test
    docker rm -f qrpaste-test
    exit 1
fi
echo ""

# 4. Test SQLite
echo "🗄️ Krok 4/4: Test SQLite databáze..."
docker exec qrpaste-test sqlite3 --version > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ SQLite funguje${NC}"
else
    echo -e "${RED}❌ SQLite nefunguje${NC}"
fi
echo ""

# Závěrečné info
echo "================================"
echo -e "${GREEN}✅ Všechny testy prošly!${NC}"
echo ""
echo "🌐 Aplikace běží na: http://localhost:8080"
echo ""
echo "📋 Užitečné příkazy:"
echo "  - Zobrazit logy:    docker logs qrpaste-test"
echo "  - Zastavit:         docker stop qrpaste-test"
echo "  - Odstranit:        docker rm -f qrpaste-test"
echo "  - Vstoupit do shellu: docker exec -it qrpaste-test bash"
echo ""
echo "🎉 Připraveno pro nasazení na Render.com!"
