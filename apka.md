# Zadání

*QRPaste apka , která bude jednoduchá na používání . Bude pro rychlé sdílení obsahů , screenshotů a fotek mezi studenty a učiteli*

## Základní informace

- Extrémně jednoduchá na používání  stačí říct kód nebo poslat obsah

- Mobile-friendly - funguje na všech zařízeních

- Bez složitého nastavení - nasadí se na běžný hosting (jen PHP soubory)

- SQLite databáze - žádná instalace DB serveru, jen jeden soubor

- Přístupná i offline - část funguje i bez připojení k databázi

## Hlavní funkce

**Typy scénářů pro použití:**

- Student chce sdílet kód/poznámky se spolužáky

- Učitel chce rychle ukázat text/obrázek na TV

- Kdo má kód, může zobrazit obsah

## Režimy sdílení

1. Rychlý režim (bez serveru)

Obsah se zakóduje přímo do URL
Generuje se QR kód pro sdílení
Funguje i bez databáze
Limit: kratší texty a malé obrázky

2. Databázový režim

Větší obsah se uloží na server
Vrátí se krátké ID (např. ABC123)
Obsah má nastavenou dobu života
Umožňuje volitelné heslo

## Speciální funkce

- TV mód - plná obrazovka s velkým písmem pro projektory

- QR kód čitelný ze 2-3 metrů

- Drag & drop pro screenshoty

- Paste ze schránky


## Technické Informace

**Frontend (1 soubor):**

- HTML5 s moderními prvky

- Alpine.js pro reaktivitu (bez build procesu)

- Vanilla CSS s responzivním designem

- QR.js knihovna pro generování QR kódů

- Tainwind CSS - pro UI styling nahrát z CDN

**Backend (1 soubor):**

- PHP 8+ s jednoduchým API

- Bez frameworků - čistý PHP + PDO SQLite

- Žádná instalace DB - SQLite je součástí PHP

**Databáze (1 soubor):**

- SQLite databáze (jeden soubor .db)

-   Jednoduchá struktura - tabulka pro uložené příspěvky

**Nasazení:**

- Běžný webhosting s PHP (SQLite většinou podporováno)

- FTP upload - žádný CI/CD, jen PHP soubory + SQLite DB

- Žádné externí závislosti - SQLite funguje "out of the box"

## Struktura Projektu

Po dokončení bude váš projekt vypadat takto:

qrpaste/

├── index.html          # Hlavní HTML soubor s Alpine.js

├── backend.php         # PHP API pro databázové operace

├── qrpaste.db         # SQLite databáze (vytvoří se automaticky)

├── .htaccess          # Apache konfigurace (volitelné)

├── README.md          # Dokumentace pro uživatele

└── assets/            # Volitelná složka pro obrázky/ikony

    └── favicon.ico



# User Stories

## Školní scénáře (konkrétní použití)

**1. Sdílení kódu během programování**
- Jako student programování chci rychle sdílet můj kód se spolužáky během hodiny, aby mi mohli pomoct s chybou nebo abychom mohli porovnat řešení.

**2. Učitel prezentuje na TV/projektoru**
- Jako učitel chci zobrazit text nebo obrázek na TV pomocí QR kódu, aby studenti mohli rychle přistoupit k materiálům na svých telefonech místo opisování z tabule.

**3. Rychlé poznámky z tabule**
- Jako student chci vyfotit poznámky z tabule a okamžitě je sdílet se spolužáky pomocí kódu, aby nikdo nepřišel o důležité informace.

**4. Kolaborace na projektech**
- Jako člen projektového týmu chci sdílet kusy kódu nebo návrhy se spolužáky mezi hodinami, aby všichni byli synchronizovaní bez nutnosti složitých emailů.

**5. Domácí úkoly a řešení**
- Jako student chci rychle poslat spolužákovi své řešení úlohy pomocí krátké URL/QR kódu, aby si mohl zkontrolovat svou práci.

**6. Sdílení během prezentací**
- Jako student při prezentaci chci ukázat zdrojový kód nebo odkazy publiku pomocí QR kódu na slide, aby si ostatní mohli materiály prohlédnout na svých zařízeních.

**7. Nouzové sdílení bez internetu**
- Jako student v budově se špatným WiFi chci použít offline režim pro přípravu obsahu a později ho nahrát, aby techické problémy neomezily mou produktivitu.

**8. Rychlá výměna kontaktů/odkazů**
- Jako student chci sdílet užitečné odkazy na materiály nebo kontakty s celou třídou pomocí jednoduchého kódu, který řeknu nahlas.

## Obecné user stories

- Jako uživatel chci vložit text do textového pole, abych ho mohl později sdílet.

- Jako uživatel chci nahrát screenshot z mojí galerie, abych ho mohl sdílet pomocí kódu, QR nebo linku.

- Jako uživatel chci dostat unikátní kód (např. 6 znaků - G62FAF), pod kterým mohu obsah zobrazit.

- Jako uživatel chci mít možnost otevřít obsah podle kódu, QR nebo linku.

- Jako uživatel chci, aby aplikace fungovala i bez připojení k internetu – např. abych mohl psát a později obsah uložit.

- Jako uživatel chci jednoduché a čisté rozhraní bez registrace.

- Jako uživatel chci, aby lidi mohli rychle poslat svůj text, screenshot nebo fotko jen pomocí kódu nebo QR kódu nebo linku.