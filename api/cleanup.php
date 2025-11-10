<?php
/**
 * QRPaste API - Cleanup Endpoint
 * GET/POST /api/cleanup.php
 * 
 * INTERNAL USE ONLY - Smazání expirovaných záznamů
 * Určeno pro CRON job nebo ruční údržbu
 * 
 * SETUP CRON:
 * Windows Task Scheduler: php C:\path\to\qrpaste\api\cleanup.php
 * Linux Crontab: 0 * * * * php /path/to/qrpaste/api/cleanup.php
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

// Nastavení error reportingu
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Bezpečnostní token (volitelné - doporučeno v produkci)
define('CLEANUP_TOKEN', 'CHANGE_THIS_SECRET_TOKEN'); // ⚠️ ZMĚŇ V PRODUKCI!

try {
    // Načtení konfigurace
    $config = require __DIR__ . '/config.php';
    
    // 1. OVĚŘENÍ AUTORIZACE (pro web přístup)
    if (php_sapi_name() !== 'cli') {
        // Pokud je spuštěno přes web, vyžaduj token
        $providedToken = $_GET['token'] ?? $_POST['token'] ?? null;
        
        if ($providedToken !== CLEANUP_TOKEN) {
            setCorsHeaders();
            jsonError('Unauthorized. Invalid or missing token.', 403);
        }
        
        setCorsHeaders();
    }
    
    $startTime = microtime(true);
    
    // 2. SMAZÁNÍ EXPIROVANÝCH PASTŮ
    $stmt = $pdo->prepare("DELETE FROM pastes WHERE expires_at < datetime('now')");
    $stmt->execute();
    $deletedPastes = $stmt->rowCount();
    
    // 3. ZÍSKÁNÍ STATISTIK
    $stmt = $pdo->query("
        SELECT COUNT(*) as total 
        FROM pastes 
        WHERE expires_at > datetime('now')
    ");
    $totalActive = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("
        SELECT 
            content_type,
            COUNT(*) as count,
            SUM(size_kb) as total_size_kb
        FROM pastes
        WHERE expires_at > datetime('now')
        GROUP BY content_type
    ");
    $byType = $stmt->fetchAll();
    
    // Velikost databáze
    $dbSize = filesize($dbPath);
    $dbSizeMb = round($dbSize / (1024 * 1024), 2);
    
    // 4. VACUUM (volitelně - náročné na výkon)
    $vacuumed = false;
    if ($config['cleanup']['vacuum_on_cleanup']) {
        $pdo->exec('VACUUM');
        $vacuumed = true;
    }
    
    $executionTime = round((microtime(true) - $startTime) * 1000, 2);
    
    // 5. LOG VÝSLEDKŮ
    $logMessage = sprintf(
        "[CLEANUP] Deleted: %d pastes | Active: %d | DB: %.2f MB | Time: %.2f ms",
        $deletedPastes,
        $totalActive,
        $dbSizeMb,
        $executionTime
    );
    error_log($logMessage);
    
    // 6. RESPONSE
    $response = [
        'success' => true,
        'deleted' => [
            'pastes' => $deletedPastes
        ],
        'database' => [
            'total_active_pastes' => $totalActive,
            'size_mb' => $dbSizeMb,
            'vacuumed' => $vacuumed
        ],
        'by_type' => $byType,
        'execution_time_ms' => $executionTime,
        'timestamp' => time()
    ];
    
    // Pokud je spuštěno z CLI, vypiš čitelný výstup
    if (php_sapi_name() === 'cli') {
        echo "\n=== QRPaste Cleanup Report ===\n";
        echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
        echo "Deleted pastes: {$deletedPastes}\n";
        echo "Active pastes: {$totalActive}\n";
        echo "Database size: {$dbSizeMb} MB\n";
        echo "Vacuumed: " . ($vacuumed ? 'Yes' : 'No') . "\n";
        echo "Execution time: {$executionTime} ms\n";
        echo "\nBreakdown by type:\n";
        foreach ($byType as $type) {
            echo "  - {$type['content_type']}: {$type['count']} pastes, " . 
                 round($type['total_size_kb'] / 1024, 2) . " MB\n";
        }
        echo "==============================\n\n";
        exit(0);
    }
    
    // Web response
    jsonSuccess($response);
    
} catch (PDOException $e) {
    error_log('Database error in cleanup.php: ' . $e->getMessage());
    
    if (php_sapi_name() === 'cli') {
        echo "ERROR: Database error - " . $e->getMessage() . "\n";
        exit(1);
    }
    
    jsonError('Database error during cleanup', 500, [
        'error_code' => $e->getCode()
    ]);
    
} catch (Exception $e) {
    error_log('Error in cleanup.php: ' . $e->getMessage());
    
    if (php_sapi_name() === 'cli') {
        echo "ERROR: " . $e->getMessage() . "\n";
        exit(1);
    }
    
    jsonError('Cleanup failed', 500);
}
