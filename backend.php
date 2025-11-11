<?php
/**
 * QRPaste Backend - All-in-One
 * Jednoduchý, bezpečný backend pro sdílení obsahu
 */

// === KONFIGURACE ===
define('DB_PATH', __DIR__ . '/data/qrpaste.db');
define('MAX_CONTENT_SIZE', 10 * 1024 * 1024); // 10 MB
define('MAX_TEXT_SIZE', 5 * 1024 * 1024);      // 5 MB pro text
define('MAX_CODE_SIZE', 2 * 1024 * 1024);      // 2 MB pro kód
define('MAX_IMAGE_SIZE', 10 * 1024 * 1024);    // 10 MB pro base64 obrázky
define('ID_LENGTH', 8);
define('DEFAULT_EXPIRY_DAYS', 7);
define('RATE_LIMIT_PER_HOUR', 20);
define('RATE_LIMIT_PER_MINUTE', 5);

// Povolené domény (upravit pro produkci!)
define('ALLOWED_ORIGINS', [
    'http://localhost',
    'http://localhost:3000',
    'http://127.0.0.1',
    // 'https://qrpaste.yourdomain.com' // Přidej produkční doménu
]);

// === SECURITY HEADERS ===
// Anti-clickjacking
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// CSP pro API (strict)
header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'");

// === CORS HANDLING ===
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigin = '*'; // Default pro development

// V produkci: ověř origin proti whitelistu
if (!empty($origin) && in_array($origin, ALLOWED_ORIGINS, true)) {
    $allowedOrigin = $origin;
    header('Access-Control-Allow-Credentials: true');
}

header('Access-Control-Allow-Origin: ' . $allowedOrigin);
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Max-Age: 86400'); // Cache preflight 24h
header('Content-Type: application/json; charset=utf-8');

// OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// === DATABASE INIT ===
function getDatabase() {
    try {
        // Vytvoř data složku pokud neexistuje
        $dataDir = dirname(DB_PATH);
        if (!file_exists($dataDir)) {
            mkdir($dataDir, 0755, true);
        }
        
        // PDO připojení
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        // SQLite optimalizace
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode = WAL');
        $pdo->exec('PRAGMA synchronous = NORMAL');
        
        // Vytvoř tabulku
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS pastes (
                id TEXT PRIMARY KEY,
                content TEXT NOT NULL,
                content_type TEXT DEFAULT 'text',
                password_hash TEXT DEFAULT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                expires_at TEXT NOT NULL,
                size_bytes INTEGER NOT NULL,
                ip_hash TEXT NOT NULL
            )
        ");
        
        // Index pro cleanup
        $pdo->exec("
            CREATE INDEX IF NOT EXISTS idx_expires 
            ON pastes(expires_at)
        ");
        
        return $pdo;
        
    } catch (PDOException $e) {
        sendError('Database initialization failed', 500);
    }
}

// === ID GENERATION ===
function generateId($length = ID_LENGTH) {
    $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $id = '';
    $max = strlen($chars) - 1;
    
    for ($i = 0; $i < $length; $i++) {
        $id .= $chars[random_int(0, $max)];
    }
    
    return $id;
}

function generateUniqueId($pdo, $maxAttempts = 10) {
    for ($i = 0; $i < $maxAttempts; $i++) {
        $id = generateId();
        
        // Zkontroluj kolizi
        $stmt = $pdo->prepare("SELECT id FROM pastes WHERE id = ?");
        $stmt->execute([$id]);
        
        if (!$stmt->fetch()) {
            return $id;
        }
    }
    
    sendError('Failed to generate unique ID', 500);
}

// === SECURITY ===
function validateInput($data) {
    // Zkontroluj required fields
    if (!isset($data['content']) || $data['content'] === '') {
        sendError('Content is required', 400);
    }
    
    // Zkontroluj typ content
    $type = $data['content_type'] ?? 'text';
    if (!validateContentType($type)) {
        sendError('Invalid content type', 400);
    }
    
    // Zkontroluj velikost podle typu
    $content = $data['content'];
    $size = strlen($content);
    
    if (!validateContentSize($type, $size)) {
        $maxSizes = [
            'text' => '5 MB',
            'code' => '2 MB',
            'image' => '10 MB'
        ];
        sendError('Content too large (max ' . $maxSizes[$type] . ')', 413);
    }
    
    // Validuj base64 image pokud je to obrázek
    if ($type === 'image' && !validateBase64Image($content)) {
        sendError('Invalid image format', 400);
    }
    
    // Validuj expiraci
    $expireDays = isset($data['expires_days']) ? (int)$data['expires_days'] : DEFAULT_EXPIRY_DAYS;
    if ($expireDays < 1 || $expireDays > 30) {
        sendError('Expiry must be between 1-30 days', 400);
    }
    
    // Validuj password (pokud existuje)
    $password = $data['password'] ?? null;
    if ($password !== null && $password !== '') {
        if (strlen($password) < 4 || strlen($password) > 100) {
            sendError('Password must be 4-100 characters', 400);
        }
        // Ověř že obsahuje alespoň něco (ne jen whitespace)
        if (trim($password) === '') {
            sendError('Password cannot be empty', 400);
        }
    } else {
        $password = null; // Normalizuj prázdný string na null
    }
    
    return [
        'content' => $content,
        'type' => $type,
        'expires_days' => $expireDays,
        'password' => $password,
        'size' => $size
    ];
}

function validateContentType($type) {
    return in_array($type, ['text', 'code', 'image'], true);
}

function validateContentSize($type, $size) {
    $limits = [
        'text' => MAX_TEXT_SIZE,
        'code' => MAX_CODE_SIZE,
        'image' => MAX_IMAGE_SIZE
    ];
    
    return $size <= ($limits[$type] ?? MAX_CONTENT_SIZE);
}

function validateBase64Image($content) {
    // Zkontroluj base64 data URL formát
    if (!preg_match('/^data:image\/(jpeg|jpg|png|gif|webp);base64,/', $content)) {
        return false;
    }
    
    // Zkontroluj validitu base64
    $base64Data = preg_replace('/^data:image\/[a-z]+;base64,/', '', $content);
    $decoded = base64_decode($base64Data, true);
    
    if ($decoded === false) {
        return false;
    }
    
    // Ověř že je to opravdu obrázek (magic bytes)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->buffer($decoded);
    
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    return in_array($mimeType, $allowedMimes, true);
}

function getClientIpHash() {
    // Získej IP adresu (bezpečně)
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    // Pokud je za proxy/load balancer, použij X-Forwarded-For
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $forwardedIps = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($forwardedIps[0]); // První IP v řetězci
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    }
    
    // Validuj IP formát
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        $ip = '0.0.0.0';
    }
    
    // Hash IP pro privacy (GDPR compliant)
    // Použij secret salt pro produkci!
    $salt = getenv('QRPASTE_SECRET') ?: 'change_me_in_production_2025';
    return hash('sha256', $ip . $salt);
}

function checkRateLimit($pdo, $ipHash) {
    try {
        // Minutový limit (DDoS ochrana)
        $oneMinuteAgo = date('Y-m-d H:i:s', strtotime('-1 minute'));
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM pastes 
            WHERE ip_hash = ? AND created_at > ?
        ");
        $stmt->execute([$ipHash, $oneMinuteAgo]);
        $result = $stmt->fetch();
        
        if ($result['count'] >= RATE_LIMIT_PER_MINUTE) {
            sendError('Too many requests. Please slow down.', 429);
        }
        
        // Hodinový limit (spam ochrana)
        $oneHourAgo = date('Y-m-d H:i:s', strtotime('-1 hour'));
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM pastes 
            WHERE ip_hash = ? AND created_at > ?
        ");
        $stmt->execute([$ipHash, $oneHourAgo]);
        $result = $stmt->fetch();
        
        if ($result['count'] >= RATE_LIMIT_PER_HOUR) {
            sendError('Hourly limit exceeded. Try again later.', 429);
        }
        
    } catch (PDOException $e) {
        // Rate limit selhání neblokuje request (fail open)
        error_log('Rate limit check failed: ' . $e->getMessage());
    }
}

// === RESPONSE HELPERS ===
function sendSuccess($data, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => true,
        'data' => $data
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function sendError($message, $code = 400) {
    http_response_code($code);
    
    // Log detailed error server-side (bez info leaku)
    if ($code >= 500) {
        error_log("QRPaste Error [$code]: $message - " . json_encode([
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]));
        
        // Generický error pro klienta (žádné detaily)
        $message = 'An error occurred. Please try again later.';
    }
    
    echo json_encode([
        'success' => false,
        'error' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// === API ENDPOINTS ===

// POST /backend.php?action=save
function handleSave($pdo) {
    // Ověř Content-Type header
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') === false) {
        sendError('Content-Type must be application/json', 415);
    }
    
    // Získej JSON input
    $input = file_get_contents('php://input');
    
    // Zkontroluj velikost raw inputu (před parsováním)
    if (strlen($input) > MAX_CONTENT_SIZE + 1024) { // +1KB buffer pro JSON overhead
        sendError('Request too large', 413);
    }
    
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendError('Invalid JSON format', 400);
    }
    
    // Validace
    $validated = validateInput($data);
    
    // Rate limiting
    $ipHash = getClientIpHash();
    checkRateLimit($pdo, $ipHash);
    
    // Generuj ID
    $id = generateUniqueId($pdo);
    
    // Vypočti expiraci
    $expiresAt = date('Y-m-d H:i:s', strtotime('+' . $validated['expires_days'] . ' days'));
    
    // Hash password (bcrypt - production ready)
    $passwordHash = null;
    if ($validated['password'] !== null) {
        $passwordHash = password_hash($validated['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        
        if ($passwordHash === false) {
            sendError('Password hashing failed', 500);
        }
    }
    
    // Ulož do DB
    try {
        $stmt = $pdo->prepare("
            INSERT INTO pastes (id, content, content_type, password_hash, expires_at, size_bytes, ip_hash)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $id,
            $validated['content'],
            $validated['type'],
            $passwordHash,
            $expiresAt,
            $validated['size'],
            $ipHash
        ]);
        
        // Cleanup starých záznamů (async)
        cleanupExpired($pdo);
        
        // Response (bez citlivých dat)
        sendSuccess([
            'id' => $id,
            'url' => getBaseUrl() . '?id=' . $id,
            'expires_at' => $expiresAt,
            'size_kb' => round($validated['size'] / 1024, 2),
            'has_password' => ($passwordHash !== null)
        ], 201);
        
    } catch (PDOException $e) {
        // Log server-side, generický error pro klienta
        error_log('Save failed: ' . $e->getMessage());
        sendError('Failed to save content', 500);
    }
}

// GET /backend.php?action=get&id=xxx
function handleGet($pdo) {
    $id = $_GET['id'] ?? '';
    
    // Validace ID formátu (bezpečnost + prevence SQL injection)
    if (empty($id) || !preg_match('/^[a-zA-Z0-9]{6,8}$/', $id)) {
        sendError('Invalid ID format', 400);
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, content, content_type, password_hash, created_at, expires_at, size_bytes
            FROM pastes
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        $paste = $stmt->fetch();
        
        if (!$paste) {
            sendError('Paste not found', 404);
        }
        
        // Zkontroluj expiraci
        if (strtotime($paste['expires_at']) < time()) {
            // Smaž expirovaný paste
            $deleteStmt = $pdo->prepare("DELETE FROM pastes WHERE id = ?");
            $deleteStmt->execute([$id]);
            
            sendError('Paste has expired', 410);
        }
        
        // Zkontroluj password
        $hasPassword = !empty($paste['password_hash']);
        if ($hasPassword) {
            $providedPassword = $_GET['password'] ?? '';
            
            if (empty($providedPassword)) {
                // Neříkej že existuje password (info leak prevence)
                sendError('Access denied', 401);
            }
            
            // Timing-safe comparison pomocí password_verify
            if (!password_verify($providedPassword, $paste['password_hash'])) {
                // Generický error (neříkej jestli paste existuje)
                sendError('Access denied', 403);
            }
        }
        
        // Response (nikdy neposílej password_hash!)
        sendSuccess([
            'id' => $paste['id'],
            'content' => $paste['content'],
            'type' => $paste['content_type'],
            'created_at' => $paste['created_at'],
            'expires_at' => $paste['expires_at'],
            'size_kb' => round($paste['size_bytes'] / 1024, 2)
        ]);
        
    } catch (PDOException $e) {
        error_log('Get failed: ' . $e->getMessage());
        sendError('Failed to retrieve content', 500);
    }
}

// === CLEANUP ===
function cleanupExpired($pdo) {
    try {
        $stmt = $pdo->prepare("DELETE FROM pastes WHERE expires_at < datetime('now')");
        $stmt->execute();
    } catch (PDOException $e) {
        error_log('Cleanup failed: ' . $e->getMessage());
    }
}

// === HELPERS ===
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = dirname($_SERVER['SCRIPT_NAME']);
    return $protocol . '://' . $host . rtrim($script, '/');
}

// === ROUTER ===
try {
    // Request size check (před DB připojením)
    $contentLength = $_SERVER['CONTENT_LENGTH'] ?? 0;
    if ($contentLength > MAX_CONTENT_SIZE + 2048) { // +2KB pro JSON overhead
        sendError('Request too large', 413);
    }
    
    $pdo = getDatabase();
    
    $action = $_GET['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Route handling s metodou + akcí
    if ($method === 'POST' && $action === 'save') {
        handleSave($pdo);
    } elseif ($method === 'GET' && $action === 'get') {
        handleGet($pdo);
    } elseif ($method === 'GET' && $action === '') {
        // API info (public)
        sendSuccess([
            'name' => 'QRPaste API',
            'version' => '1.0',
            'endpoints' => [
                'save' => 'POST /backend.php?action=save',
                'get' => 'GET /backend.php?action=get&id={id}'
            ],
            'limits' => [
                'text' => '5 MB',
                'code' => '2 MB',
                'image' => '10 MB'
            ]
        ]);
    } else {
        // Neplatná kombinace metody + akce
        sendError('Invalid request', 400);
    }
    
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    sendError('Database error', 500);
} catch (Exception $e) {
    error_log('Backend error: ' . $e->getMessage());
    sendError('Internal server error', 500);
}
