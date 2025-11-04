<?php
// Test PHP a SQLite funkčnosti
echo "<h1>Test PHP a SQLite</h1>\n";

// Test základní PHP funkčnosti
echo "<h2>PHP Info:</h2>\n";
echo "PHP verze: " . phpversion() . "<br>\n";
echo "Aktuální čas: " . date('Y-m-d H:i:s') . "<br>\n";

// Test SQLite rozšíření
echo "<h2>SQLite Test:</h2>\n";

// Kontrola dostupnosti SQLite
if (!extension_loaded('sqlite3')) {
    echo "<span style='color: red;'>CHYBA: SQLite3 rozšíření není dostupné!</span><br>\n";
    exit;
}

echo "<span style='color: green;'>✓ SQLite3 rozšíření je dostupné</span><br>\n";
echo "SQLite verze: " . SQLite3::version()['versionString'] . "<br>\n";

try {
    // Vytvoření testovací databáze v paměti
    $db = new SQLite3(':memory:');
    echo "<span style='color: green;'>✓ Připojení k SQLite databázi úspěšné</span><br>\n";
    
    // Vytvoření testovací tabulky
    $db->exec('CREATE TABLE test_table (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');
    echo "<span style='color: green;'>✓ Vytvoření tabulky úspěšné</span><br>\n";
    
    // Vložení testovacích dat
    $stmt = $db->prepare('INSERT INTO test_table (name) VALUES (?)');
    $testData = ['Test 1', 'Test 2', 'Test 3'];
    
    foreach ($testData as $name) {
        $stmt->bindValue(1, $name, SQLITE3_TEXT);
        $stmt->execute();
    }
    echo "<span style='color: green;'>✓ Vložení testovacích dat úspěšné</span><br>\n";
    
    // Načtení a zobrazení dat
    $result = $db->query('SELECT * FROM test_table ORDER BY id');
    echo "<h3>Testovací data:</h3>\n";
    echo "<table border='1' cellpadding='5'>\n";
    echo "<tr><th>ID</th><th>Název</th><th>Vytvořeno</th></tr>\n";
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // Test počtu záznamů
    $count = $db->querySingle('SELECT COUNT(*) FROM test_table');
    echo "<p>Celkem záznamů: <strong>$count</strong></p>\n";
    
    // Uzavření databáze
    $db->close();
    echo "<span style='color: green;'>✓ Databáze úspěšně uzavřena</span><br>\n";
    
    echo "<br><h2>Výsledek:</h2>\n";
    echo "<span style='color: green; font-weight: bold;'>✓ PHP a SQLite fungují správně!</span>\n";
    
} catch (Exception $e) {
    echo "<span style='color: red;'>CHYBA při práci s SQLite: " . htmlspecialchars($e->getMessage()) . "</span><br>\n";
}

// Test dalších užitečných informací
echo "<h2>Další informace:</h2>\n";
echo "Operační systém: " . php_uname() . "<br>\n";
echo "Dostupná paměť: " . ini_get('memory_limit') . "<br>\n";
echo "Upload max filesize: " . ini_get('upload_max_filesize') . "<br>\n";
echo "Post max size: " . ini_get('post_max_size') . "<br>\n";

// Kontrola dalších užitečných rozšíření
$extensions = ['pdo_sqlite', 'json', 'mbstring', 'curl'];
echo "<h3>Dostupná rozšíření:</h3>\n";
foreach ($extensions as $ext) {
    $status = extension_loaded($ext) ? '✓' : '✗';
    $color = extension_loaded($ext) ? 'green' : 'red';
    echo "<span style='color: $color;'>$status $ext</span><br>\n";
}
?>