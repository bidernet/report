<?php
/**
 * פונקציות עזר ואימות משתמשים
 */

require_once __DIR__ . '/db.php';

// =============================================================
// תגובות JSON
// =============================================================
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function jsonError($message, $status = 400) {
    jsonResponse(['success' => false, 'error' => $message], $status);
}

function jsonSuccess($data = []) {
    jsonResponse(array_merge(['success' => true], is_array($data) ? $data : ['data' => $data]));
}

// =============================================================
// CORS
// =============================================================
function handleCors() {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (in_array($origin, ALLOWED_ORIGINS)) {
        header("Access-Control-Allow-Origin: $origin");
        header("Access-Control-Allow-Credentials: true");
    }
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

// =============================================================
// קלט JSON
// =============================================================
function getJsonInput() {
    $input = file_get_contents('php://input');
    if (empty($input)) return [];
    $data = json_decode($input, true);
    return is_array($data) ? $data : [];
}

// =============================================================
// אימות משתמש - Sessions
// =============================================================
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function loginUser($userId, $userData) {
    startSession();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_data'] = $userData;
    $_SESSION['logged_in_at'] = time();
    $_SESSION['last_activity'] = time();
}

function logoutUser() {
    startSession();
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

function isLoggedIn() {
    startSession();
    if (!isset($_SESSION['user_id'])) return false;
    
    // בדיקת תוקף סשן
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_DURATION)) {
        logoutUser();
        return false;
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

function requireAuth() {
    if (!isLoggedIn()) {
        jsonError('Unauthorized - please login', 401);
    }
}

function currentUser() {
    startSession();
    return $_SESSION['user_data'] ?? null;
}

function currentUserId() {
    startSession();
    return $_SESSION['user_id'] ?? null;
}

function requireAdmin() {
    requireAuth();
    $user = currentUser();
    if (!$user || $user['role'] !== 'admin') {
        jsonError('Forbidden - admin access required', 403);
    }
}

// =============================================================
// הצפנת טוקנים של Facebook
// =============================================================
function encryptToken($token) {
    if (empty($token)) return null;
    $key = hex2bin(ENCRYPTION_KEY);
    if (strlen($key) !== 32) {
        // fallback - השתמש כ-string
        $key = hash('sha256', ENCRYPTION_KEY, true);
    }
    $iv = random_bytes(16);
    $encrypted = openssl_encrypt($token, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $encrypted);
}

function decryptToken($encryptedToken) {
    if (empty($encryptedToken)) return null;
    $data = base64_decode($encryptedToken);
    if ($data === false || strlen($data) < 16) return null;
    
    $key = hex2bin(ENCRYPTION_KEY);
    if (strlen($key) !== 32) {
        $key = hash('sha256', ENCRYPTION_KEY, true);
    }
    
    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);
    return openssl_decrypt($encrypted, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
}

// =============================================================
// יומן פעילות
// =============================================================
function logActivity($action, $entityType = null, $entityId = null, $details = null) {
    try {
        $userId = currentUserId();
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        db()->query(
            "INSERT INTO activity_log (user_id, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)",
            [$userId, $action, $entityType, $entityId, $details, $ip]
        );
    } catch (Exception $e) {
        error_log('Activity log error: ' . $e->getMessage());
    }
}

// =============================================================
// סינון קלט
// =============================================================
function sanitize($value, $type = 'string') {
    if ($value === null) return null;
    switch ($type) {
        case 'int':
            return (int)$value;
        case 'float':
            return (float)$value;
        case 'bool':
            return (bool)$value;
        case 'email':
            return filter_var(trim($value), FILTER_SANITIZE_EMAIL);
        default:
            return trim((string)$value);
    }
}
