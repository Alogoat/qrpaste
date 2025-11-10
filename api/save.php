<?php
/**
 * QRPaste API - Save Endpoint
 * POST /api/save.php
 * 
 * Vytvoření nového paste
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

// Nastavení error reportingu
error_reporting(E_ALL);
ini_set('display_errors', 0);

// CORS a OPTIONS handling
handleOptionsRequest();
setCorsHeaders();

// Pouze POST metoda
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed. Use POST.', 405);
}

try {
    // Načtení konfigurace
    $config = require __DIR__ . '/config.php';
    
    // Získání JSON dat
    $input = getJsonInput();
    
    // Validace povinných polí
    if (!isset($input['content']) || !isset($input['type'])) {
        jsonError($config['errors']['invalid_request'], 400, [
            'required_fields' => ['content', 'type']
        ]);
    }
    
    $content = $input['content'];
    $contentType = $input['type'];
    $password = $input['password'] ?? null;
    $expireDays = $input['expires_days'] ?? null;
    $language = $input['language'] ?? null;
    
    // 1. VALIDACE CONTENT TYPE
    if (!validateContentType($contentType)) {
        jsonError($config['errors']['invalid_content_type'], 400, [
            'allowed_types' => array_keys($config['content_types'])
        ]);
    }
    
    // 2. VALIDACE OBSAHU PRO IMAGE
    if ($contentType === 'image') {
        $imageValidation = validateBase64Image($content);
        if (!$imageValidation['valid']) {
            jsonError($imageValidation['error'], 400);
        }
    }
    
    // 3. VALIDACE VELIKOSTI
    $sizeValidation = validateContentSize($content, $contentType);
    if (!$sizeValidation['valid']) {
        jsonError($config['errors']['content_too_large'], 413, [
            'current_size_mb' => $sizeValidation['size_mb'],
            'max_size_mb' => $sizeValidation['max_size_mb']
        ]);
    }
    
    // 4. VALIDACE HESLA
    $passwordValidation = validatePassword($password);
    if (!$passwordValidation['valid']) {
        jsonError($passwordValidation['error'], 400);
    }
    
    // 5. VALIDACE EXPIRACE
    $expirationValidation = validateExpiration($expireDays);
    if (!$expirationValidation['valid']) {
        jsonError($expirationValidation['error'], 400, [
            'min_days' => $config['expiration']['min_days'],
            'max_days' => $config['expiration']['max_days']
        ]);
    }
    
    // 6. VALIDACE JAZYKA (pro code)
    if ($contentType === 'code' && !empty($language)) {
        if (!validateLanguage($language)) {
            jsonError('Invalid programming language', 400, [
                'allowed_languages' => $config['content_types']['code']['languages']
            ]);
        }
    }
    
    // 7. RATE LIMITING - ZJEDNODUŠENO (bez access_log)
    // V produkci můžeš přidat session-based rate limiting
    
    // 8. VYTVOŘENÍ PASTE V DATABÁZI
    // $pdo je globální z db.php
    
    // Generuj unikátní ID (8 znaků)
    $id = generateId(8);
    
    // Hash hesla pokud je nastaveno
    $passwordHash = null;
    if (!empty($password)) {
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    }
    
    // Výpočet expires_at (ISO 8601 formát)
    $expiresAt = date('Y-m-d H:i:s', $expirationValidation['expires_at']);
    
    // Výpočet velikosti v KB
    $sizeKb = round($sizeValidation['size'] / 1024, 2);
    
    // INSERT do databáze
    $stmt = $pdo->prepare("
        INSERT INTO pastes 
        (id, content, content_type, expires_at, size_kb, password_hash)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $id,
        $content,
        $contentType,
        $expiresAt,
        $sizeKb,
        $passwordHash
    ]);
    
    // 9. GENEROVÁNÍ URL
    $url = generateUrl($id);
    
    // 10. SUCCESS RESPONSE
    jsonSuccess([
        'id' => $id,
        'url' => $url,
        'qr_data' => $url,
        'expires_at' => $expirationValidation['expires_at'],
        'expires_in_days' => $expirationValidation['days'],
        'size_bytes' => $sizeValidation['size'],
        'size_kb' => $sizeKb,
        'has_password' => !empty($password)
    ], 201);
    
} catch (PDOException $e) {
    error_log('Database error in save.php: ' . $e->getMessage());
    jsonError($config['errors']['database_error'], 500, [
        'error_code' => $e->getCode()
    ]);
    
} catch (Exception $e) {
    error_log('Error in save.php: ' . $e->getMessage());
    jsonError('Internal server error', 500);
}
