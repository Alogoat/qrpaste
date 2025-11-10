<?php
/**
 * QRPaste Helper Functions
 * Utility funkce pro validaci, rate limiting, error handling
 */

/**
 * Nastavení CORS headers
 */
function setCorsHeaders() {
    $config = require __DIR__ . '/config.php';
    
    if ($config['cors']['enabled']) {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        // Kontrola povoleného origin
        if (in_array($origin, $config['security']['allowed_origins']) || 
            empty($config['security']['allowed_origins'])) {
            header('Access-Control-Allow-Origin: ' . ($origin ?: '*'));
        }
        
        header('Access-Control-Allow-Methods: ' . implode(', ', $config['cors']['allowed_methods']));
        header('Access-Control-Allow-Headers: ' . implode(', ', $config['cors']['allowed_headers']));
        header('Access-Control-Max-Age: ' . $config['cors']['max_age']);
    }
    
    header('Content-Type: application/json; charset=utf-8');
}

/**
 * Zpracování OPTIONS request (CORS preflight)
 */
function handleOptionsRequest() {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        setCorsHeaders();
        http_response_code(204);
        exit;
    }
}

/**
 * JSON error response
 * @param string $message Error message
 * @param int $code HTTP status code
 * @param array $details Další detaily
 */
function jsonError($message, $code = 400, $details = []) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message,
        'code' => $code,
        'details' => $details,
        'timestamp' => time()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * JSON success response
 * @param array $data Data k vrácení
 * @param int $code HTTP status code
 */
function jsonSuccess($data, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => true,
        'data' => $data,
        'timestamp' => time()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Validace content type
 * @param string $type Content type
 * @return bool
 */
function validateContentType($type) {
    $config = require __DIR__ . '/config.php';
    return isset($config['content_types'][$type]) && $config['content_types'][$type]['allowed'];
}

/**
 * Validace velikosti obsahu
 * @param string $content Obsah
 * @param string $type Content type
 * @return array [valid, size, max_size]
 */
function validateContentSize($content, $type) {
    $config = require __DIR__ . '/config.php';
    $size = strlen($content);
    $maxSize = $config['content_types'][$type]['max_size'] ?? $config['limits']['max_text_size'];
    
    return [
        'valid' => $size <= $maxSize,
        'size' => $size,
        'max_size' => $maxSize,
        'size_mb' => round($size / (1024 * 1024), 2),
        'max_size_mb' => round($maxSize / (1024 * 1024), 2)
    ];
}

/**
 * Validace hesla
 * @param string $password Heslo
 * @return array [valid, error]
 */
function validatePassword($password) {
    if (empty($password)) {
        return ['valid' => true, 'error' => null]; // Heslo je volitelné
    }
    
    $config = require __DIR__ . '/config.php';
    $len = strlen($password);
    
    if ($len < $config['security']['password_min_length'] || 
        $len > $config['security']['password_max_length']) {
        return [
            'valid' => false,
            'error' => $config['errors']['invalid_password']
        ];
    }
    
    return ['valid' => true, 'error' => null];
}

/**
 * Validace expirace
 * @param int $days Počet dní do expirace
 * @return array [valid, days, expires_at]
 */
function validateExpiration($days) {
    $config = require __DIR__ . '/config.php';
    
    // Default pokud není zadáno
    if (empty($days)) {
        $days = $config['expiration']['default_days'];
    }
    
    $days = (int)$days;
    
    if ($days < $config['expiration']['min_days'] || 
        $days > $config['expiration']['max_days']) {
        return [
            'valid' => false,
            'error' => $config['errors']['invalid_expiration'],
            'days' => null,
            'expires_at' => null
        ];
    }
    
    return [
        'valid' => true,
        'error' => null,
        'days' => $days,
        'expires_at' => time() + ($days * 24 * 3600)
    ];
}

/**
 * Validace jazyka (pro code content type)
 * @param string $language Jazyk
 * @return bool
 */
function validateLanguage($language) {
    if (empty($language)) {
        return true; // Jazyk je volitelný
    }
    
    $config = require __DIR__ . '/config.php';
    return in_array(strtolower($language), $config['content_types']['code']['languages']);
}

/**
 * Sanitizace a validace base64 image
 * @param string $content Base64 image data
 * @return array [valid, mime_type, size]
 */
function validateBase64Image($content) {
    // Kontrola base64 data URL formátu
    if (!preg_match('/^data:image\/(jpeg|png|gif|webp);base64,(.+)$/i', $content, $matches)) {
        return [
            'valid' => false,
            'error' => 'Invalid image format. Expected base64 data URL.'
        ];
    }
    
    $mimeType = 'image/' . strtolower($matches[1]);
    $base64Data = $matches[2];
    
    // Validace MIME type
    $config = require __DIR__ . '/config.php';
    if (!in_array($mimeType, $config['content_types']['image']['mime_types'])) {
        return [
            'valid' => false,
            'error' => 'Unsupported image type. Allowed: JPEG, PNG, GIF, WebP'
        ];
    }
    
    // Validace base64
    $decoded = base64_decode($base64Data, true);
    if ($decoded === false) {
        return [
            'valid' => false,
            'error' => 'Invalid base64 encoding'
        ];
    }
    
    return [
        'valid' => true,
        'mime_type' => $mimeType,
        'size' => strlen($content)
    ];
}

/**
 * Bezpečné získání POST dat
 * @return array Dekódovaná JSON data
 */
function getJsonInput() {
    $input = file_get_contents('php://input');
    
    if (empty($input)) {
        jsonError('Empty request body', 400);
    }
    
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        jsonError('Invalid JSON: ' . json_last_error_msg(), 400);
    }
    
    return $data;
}

/**
 * Získání IP adresy (i za proxy)
 * @return string IP adresa
 */
function getClientIp() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    return $_SERVER['REMOTE_ADDR'];
}

/**
 * Generování QR kódu URL
 * @param string $shortId Short ID
 * @return string Plná URL
 */
function generateUrl($shortId) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = dirname(dirname($_SERVER['REQUEST_URI']));
    
    return $protocol . '://' . $host . $baseUrl . '?id=' . $shortId;
}
