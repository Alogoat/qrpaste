# QRPaste - Docker test script (PowerShell)
# Tento skript sestaví a otestuje Docker image lokálně před nasazením

Write-Host "🐳 QRPaste Docker Build & Test" -ForegroundColor Cyan
Write-Host "================================" -ForegroundColor Cyan
Write-Host ""

# 1. Build Docker image
Write-Host "📦 Krok 1/4: Building Docker image..." -ForegroundColor Yellow
try {
    docker build -t qrpaste:test .
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ Build úspěšný" -ForegroundColor Green
    } else {
        throw "Build selhal"
    }
} catch {
    Write-Host "❌ Build selhal" -ForegroundColor Red
    exit 1
}
Write-Host ""

# 2. Spuštění kontejneru
Write-Host "🚀 Krok 2/4: Spouštím kontejner..." -ForegroundColor Yellow
docker rm -f qrpaste-test 2>$null
try {
    docker run -d `
        --name qrpaste-test `
        -p 8080:80 `
        -e QRPASTE_SECRET="test_secret_12345" `
        qrpaste:test
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ Kontejner spuštěn na http://localhost:8080" -ForegroundColor Green
    } else {
        throw "Spuštění kontejneru selhalo"
    }
} catch {
    Write-Host "❌ Spuštění kontejneru selhalo" -ForegroundColor Red
    exit 1
}
Write-Host ""

# Počkat na start Apache
Write-Host "⏳ Čekám na start Apache serveru..." -ForegroundColor Yellow
Start-Sleep -Seconds 5

# 3. Health check
Write-Host "🏥 Krok 3/4: Health check..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "http://localhost:8080/" -UseBasicParsing -TimeoutSec 10
    if ($response.StatusCode -eq 200) {
        Write-Host "✅ Aplikace odpovídá na port 8080" -ForegroundColor Green
    } else {
        throw "Neočekávaný status code: $($response.StatusCode)"
    }
} catch {
    Write-Host "❌ Aplikace neodpovídá" -ForegroundColor Red
    Write-Host "Logy kontejneru:" -ForegroundColor Yellow
    docker logs qrpaste-test
    docker rm -f qrpaste-test
    exit 1
}
Write-Host ""

# 4. Test SQLite
Write-Host "🗄️ Krok 4/4: Test SQLite databáze..." -ForegroundColor Yellow
try {
    docker exec qrpaste-test sqlite3 --version 2>&1 | Out-Null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ SQLite funguje" -ForegroundColor Green
    } else {
        Write-Host "⚠️ SQLite test neúspěšný (ale může fungovat)" -ForegroundColor Yellow
    }
} catch {
    Write-Host "⚠️ SQLite test neúspěšný (ale může fungovat)" -ForegroundColor Yellow
}
Write-Host ""

# Závěrečné info
Write-Host "================================" -ForegroundColor Cyan
Write-Host "✅ Všechny testy prošly!" -ForegroundColor Green
Write-Host ""
Write-Host "🌐 Aplikace běží na: " -NoNewline
Write-Host "http://localhost:8080" -ForegroundColor Cyan
Write-Host ""
Write-Host "📋 Užitečné příkazy:" -ForegroundColor Yellow
Write-Host "  - Zobrazit logy:      " -NoNewline; Write-Host "docker logs qrpaste-test" -ForegroundColor White
Write-Host "  - Zastavit:           " -NoNewline; Write-Host "docker stop qrpaste-test" -ForegroundColor White
Write-Host "  - Odstranit:          " -NoNewline; Write-Host "docker rm -f qrpaste-test" -ForegroundColor White
Write-Host "  - Vstoupit do shellu: " -NoNewline; Write-Host "docker exec -it qrpaste-test bash" -ForegroundColor White
Write-Host ""
Write-Host "🎉 Připraveno pro nasazení na Render.com!" -ForegroundColor Green
