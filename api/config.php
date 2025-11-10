<?php
/**
 * QRPaste Configuration
 * Centrální konfigurace pro API
 */

return [
    // Databáze
    'database' => [
        'path' => __DIR__ . '/../data/qrpaste.db',
    ],
    
    // Limity velikosti
    'limits' => [
        'max_text_size' => 5 * 1024 * 1024,      // 5 MB pro text
        'max_image_size' => 10 * 1024 * 1024,    // 10 MB pro obrázky
        'max_code_size' => 2 * 1024 * 1024,      // 2 MB pro kód
    ],
    
    // Rate limiting
    'rate_limit' => [
        'per_hour' => 10,           // Max 10 vytvoření za hodinu z jedné IP
        'per_day' => 50,            // Max 50 vytvoření za den z jedné IP
        'view_per_minute' => 30,    // Max 30 zobrazení za minutu z jedné IP
    ],
    
    // Expirace
    'expiration' => [
        'min_days' => 1,            // Minimální expirace
        'max_days' => 30,           // Maximální expirace
        'default_days' => 7,        // Default expirace
    ],
    
    // Bezpečnost
    'security' => [
        'ip_salt' => 'CHANGE_THIS_TO_RANDOM_STRING_IN_PRODUCTION', // ⚠️ ZMĚŇ V PRODUKCI!
        'short_id_length' => 6,
        'password_min_length' => 4,
        'password_max_length' => 100,
        'allowed_origins' => [
            'http://localhost',
            'http://127.0.0.1',
            // Přidej tvé produkční domény
        ],
    ],
    
    // Cleanup
    'cleanup' => [
        'access_log_retention_days' => 30,
        'vacuum_on_cleanup' => false,  // VACUUM jednou týdně ručně
    ],
    
    // Validované content types
    'content_types' => [
        'text' => [
            'max_size' => 5 * 1024 * 1024,
            'allowed' => true,
        ],
        'image' => [
            'max_size' => 10 * 1024 * 1024,
            'allowed' => true,
            'mime_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        ],
        'code' => [
            'max_size' => 2 * 1024 * 1024,
            'allowed' => true,
            'languages' => ['javascript', 'python', 'php', 'java', 'cpp', 'csharp', 'html', 'css', 'sql', 'json', 'xml'],
        ],
    ],
    
    // Error messages
    'errors' => [
        'invalid_content_type' => 'Invalid content type',
        'content_too_large' => 'Content size exceeds limit',
        'rate_limit_exceeded' => 'Rate limit exceeded. Try again later.',
        'invalid_password' => 'Password must be between 4-100 characters',
        'invalid_expiration' => 'Expiration must be between 1-30 days',
        'paste_not_found' => 'Paste not found',
        'paste_expired' => 'Paste has expired',
        'wrong_password' => 'Incorrect password',
        'database_error' => 'Database error occurred',
        'invalid_request' => 'Invalid request format',
    ],
    
    // CORS
    'cors' => [
        'enabled' => true,
        'allowed_methods' => ['GET', 'POST', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization'],
        'max_age' => 3600,
    ],
];
