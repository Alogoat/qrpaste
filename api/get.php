<?php
/**
 * QRPaste API - Get Endpoint
 * GET /api/get.php?id=ABC123&password=xxx
 * 
 * Načtení existujícího paste
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

// Nastavení error reportingu
error_reporting(E_ALL);
ini_set('display_errors', 0);

// CORS a OPTIONS handling
handleOptionsRequest();
setCorsHeaders();

// Pouze GET metoda
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed. Use GET.', 405);
}

try {
    // Načtení konfigurace
    $config = require __DIR__ . '/config.php';
    
    // Validace povinného parametru 'id'
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        jsonError($config['errors']['invalid_request'], 400, [
            'required_params' => ['id']
        ]);
    }
    
    $id = trim($_GET['id']);
    $password = $_GET['password'] ?? null;
    
    // 1. NAČTENÍ Z DATABÁZE
    // $pdo je globální z db.php
    
    $stmt = $pdo->prepare("
        SELECT 
            id,
            content,
            content_type,
            password_hash,
            expires_at,
            created_at,
            size_kb
        FROM pastes 
        WHERE id = ?
    ");
    $stmt->execute([$id]);
    $paste = $stmt->fetch();
    
    // 2. KONTROLA EXISTENCE
    if (!$paste) {
        jsonError($config['errors']['paste_not_found'], 404);
    }
    
    // 3. KONTROLA EXPIRACE
    if (strtotime($paste['expires_at']) < time()) {
        jsonError($config['errors']['paste_expired'], 410, [
            'expired_at' => strtotime($paste['expires_at']),
            'expired_ago_seconds' => time() - strtotime($paste['expires_at'])
        ]);
    }
    
    // 4. KONTROLA HESLA
    if (!empty($paste['password_hash'])) {
        // Pokud paste má heslo, ale nebylo poskytnuto
        if (empty($password)) {
            jsonError('password_required', 403, [
                'message' => 'This paste is password protected',
                'password_required' => true
            ]);
        }
        
        // Ověření hesla
        if (!password_verify($password, $paste['password_hash'])) {
            jsonError($config['errors']['wrong_password'], 403, [
                'password_required' => true
            ]);
        }
    }
    
    // 5. VÝPOČET EXPIRATION INFO
    $expiresAt = strtotime($paste['expires_at']);
    $createdAt = strtotime($paste['created_at']);
    $expiresIn = $expiresAt - time();
    $expiresInDays = round($expiresIn / 86400, 1);
    $expiresInHours = round($expiresIn / 3600, 1);
    
    // 6. SUCCESS RESPONSE
    jsonSuccess([
        'id' => $paste['id'],
        'content' => $paste['content'],
        'type' => $paste['content_type'],
        'created_at' => $createdAt,
        'expires_at' => $expiresAt,
        'expires_in_seconds' => $expiresIn,
        'expires_in_hours' => $expiresInHours,
        'expires_in_days' => $expiresInDays,
        'size_kb' => $paste['size_kb'],
        'has_password' => !empty($paste['password_hash']),
        'created_ago_seconds' => time() - $createdAt
    ]);
    
} catch (PDOException $e) {
    error_log('Database error in get.php: ' . $e->getMessage());
    jsonError($config['errors']['database_error'], 500, [
        'error_code' => $e->getCode()
    ]);
    
} catch (Exception $e) {
    error_log('Error in get.php: ' . $e->getMessage());
    jsonError('Internal server error', 500);
}
