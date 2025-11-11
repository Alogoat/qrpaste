# 🔒 QRPaste Security Guide

## ✅ Implementovaná bezpečnostní opatření

### 1. **CORS Protection**
```php
// Konfiguruj povolené domény
define('ALLOWED_ORIGINS', [
    'http://localhost',
    'https://qrpaste.yourdomain.com'  // ⚠️ ZMĚŇ!
]);
```

**Funkce:**
- Whitelist povolených origin domén
- Preflight request handling (OPTIONS)
- 24h cache pro preflight
- Credentials support pro produkční domény

---

### 2. **Content-Type Validation**

**Server-side:**
- ✅ Ověření `Content-Type: application/json` headeru
- ✅ Base64 image validation (magic bytes check)
- ✅ MIME type detection pro obrázky
- ✅ Type-specific size limits:
  - Text: 5 MB
  - Code: 2 MB  
  - Image: 10 MB (base64)

**Client-side:**
```javascript
// Přidej do frontendu
if (content.length > 5 * 1024 * 1024) {
    alert('Content too large!');
    return;
}
```

---

### 3. **File Size Limits**

**Multi-layer protection:**

**Layer 1: .htaccess**
```apache
php_value upload_max_filesize 10M
php_value post_max_size 10M
```

**Layer 2: PHP začátek requestu**
```php
if ($_SERVER['CONTENT_LENGTH'] > MAX_SIZE) {
    sendError('Request too large', 413);
}
```

**Layer 3: Po parsování JSON**
```php
if (strlen($content) > MAX_SIZE_FOR_TYPE) {
    sendError('Content too large', 413);
}
```

---

### 4. **Rate Limiting (IP-based)**

**Dvouvrstvý systém:**

```php
// Minutový limit (DDoS ochrana)
RATE_LIMIT_PER_MINUTE = 5 requestů

// Hodinový limit (spam ochrana)  
RATE_LIMIT_PER_HOUR = 20 requestů
```

**IP anonymizace (GDPR compliant):**
```php
$ipHash = hash('sha256', $ip . $secret_salt);
// Raw IP se NIKDY neukládá!
```

**Konfigurace:**
```bash
# Nastav environment variable
export QRPASTE_SECRET="tvuj_nahodny_secret_2025"
```

---

### 5. **Error Messages (Info Leak Prevention)**

**❌ ŠPATNĚ:**
```json
{"error": "Database connection failed: SQLSTATE[HY000] [2002] No such file"}
{"error": "Password incorrect for user admin"}
```

**✅ SPRÁVNĚ:**
```json
{"error": "An error occurred. Please try again later."}
{"error": "Access denied"}
```

**Implementace:**
```php
// 5xx errors → generický message
if ($code >= 500) {
    error_log("Detail: $originalMessage");  // Server log
    $message = "An error occurred. Please try again later."; // Client
}
```

---

### 6. **Password Hashing**

**Bcrypt s cost 12:**
```php
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// Timing-safe comparison
if (password_verify($provided, $hash)) {
    // OK
}
```

**Minimální požadavky:**
- 4-100 znaků
- Nesmí být jen whitespace
- Nesmí být prázdný string

**Info leak protection:**
```php
// ❌ ŠPATNĚ - leak že existuje password
if ($hasPassword && empty($provided)) {
    return "Password required";
}

// ✅ SPRÁVNĚ - generický error
if ($hasPassword && !password_verify($provided, $hash)) {
    return "Access denied";  // Nerozlišuj "wrong password" vs "no password"
}
```

---

## 🔧 Production Checklist

### Před nasazením do produkce:

- [ ] **Změň ALLOWED_ORIGINS v backend.php**
  ```php
  define('ALLOWED_ORIGINS', [
      'https://qrpaste.yourdomain.com'
  ]);
  ```

- [ ] **Nastav SECRET pro IP hashing**
  ```bash
  # .env nebo server config
  QRPASTE_SECRET="generated_random_string_min_32_chars"
  ```

- [ ] **Vypni display_errors**
  ```php
  // V .htaccess nebo php.ini
  display_errors = Off
  log_errors = On
  ```

- [ ] **Nastav HTTPS only**
  ```apache
  # .htaccess redirect
  RewriteCond %{HTTPS} off
  RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]
  ```

- [ ] **Ochrana data/ složky**
  ```apache
  # .htaccess
  RewriteRule ^data/ - [F,L]
  ```

- [ ] **Správná permissions**
  ```bash
  chmod 755 backend.php
  chmod 700 data/
  chmod 600 data/qrpaste.db
  ```

- [ ] **Error logging**
  ```php
  // php.ini
  error_log = /var/log/qrpaste/error.log
  ```

---

## 🛡️ Security Headers (všechny implementovány)

```http
X-Frame-Options: DENY                           # Anti-clickjacking
X-Content-Type-Options: nosniff                 # MIME sniffing prevence
X-XSS-Protection: 1; mode=block                 # XSS filter
Referrer-Policy: strict-origin-when-cross-origin
Content-Security-Policy: default-src 'none'     # Pro API
```

---

## 📊 Monitoring & Logging

**Logované události:**
- ✅ Rate limit violations
- ✅ Invalid authentication attempts
- ✅ Database errors (server-side only)
- ✅ Failed ID generation
- ✅ Invalid JSON/Content-Type requests

**Log formát:**
```
[2025-11-11 10:30:45] QRPaste Error [500]: Database error
  IP: 203.0.113.42 (hashed)
  Method: POST
  URI: /backend.php?action=save
  User-Agent: Mozilla/5.0...
```

---

## 🚨 Common Attacks & Protections

| Attack | Protection |
|--------|-----------|
| SQL Injection | ✅ Prepared statements všude |
| XSS | ✅ JSON output only, proper headers |
| CSRF | ✅ SameSite cookies (pokud implementováno) |
| Directory Traversal | ✅ Path validation, .htaccess |
| DoS | ✅ Rate limiting (minute + hour) |
| Brute Force | ✅ Rate limiting + timing-safe compare |
| Info Leak | ✅ Generic errors pro 5xx |
| Clickjacking | ✅ X-Frame-Options: DENY |
| MIME Confusion | ✅ X-Content-Type-Options |
| File Upload | ✅ Size limits + type validation |

---

## 📝 Testing Security

```bash
# Test rate limiting
for i in {1..30}; do
    curl -X POST http://localhost/qrpaste/backend.php?action=save \
         -H "Content-Type: application/json" \
         -d '{"content":"test"}' &
done
# Očekáváno: 429 po 5. requestu

# Test invalid Content-Type
curl -X POST http://localhost/qrpaste/backend.php?action=save \
     -H "Content-Type: text/plain" \
     -d '{"content":"test"}'
# Očekáváno: 415 Unsupported Media Type

# Test size limit
curl -X POST http://localhost/qrpaste/backend.php?action=save \
     -H "Content-Type: application/json" \
     -d "{\"content\":\"$(head -c 11M /dev/urandom | base64)\"}"
# Očekáváno: 413 Request Too Large

# Test password timing attack resistance
time curl "http://localhost/qrpaste/backend.php?action=get&id=test&password=wrong1"
time curl "http://localhost/qrpaste/backend.php?action=get&id=test&password=wrong2"
# Očekáváno: Stejný čas (timing-safe)
```

---

## 🔐 Best Practices

1. **Nikdy neloguj hesla nebo tokeny**
2. **Používej HTTPS v produkci (vždy!)**
3. **Pravidelně updatuj PHP na latest stable**
4. **Monitoruj error logy denně**
5. **Backup databáze pravidelně**
6. **Testuj security po každé změně**
7. **Rate limit IPs per endpoint**
8. **Používaj CSP headers správně**

---

## 📞 Incident Response

Pokud najdeš security issue:

1. **Nepoužívej aplikaci**
2. **Zálohuj databázi**
3. **Zkontroluj error logy**
4. **Fixni problém**
5. **Otestuj fix**
6. **Nasaď update**
7. **Notifikuj uživatele (pokud relevantní)**

---

**Last updated:** November 11, 2025  
**Version:** 1.0 - Production Ready
