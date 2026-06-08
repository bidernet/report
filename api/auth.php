<?php
/**
 * /api/auth.php
 * 
 * נקודות קצה לאימות:
 *   POST /api/auth.php?action=login    - התחברות
 *   POST /api/auth.php?action=logout   - התנתקות
 *   GET  /api/auth.php?action=me       - פרטי משתמש נוכחי
 *   POST /api/auth.php?action=change-password - שינוי סיסמה
 */

require_once __DIR__ . '/helpers.php';
handleCors();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        handleLogin();
        break;
    case 'logout':
        handleLogout();
        break;
    case 'me':
        handleMe();
        break;
    case 'change-password':
        handleChangePassword();
        break;
    default:
        jsonError('Invalid action', 404);
}

// -------------------------------------------------------------
function handleLogin() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonError('Method not allowed', 405);
    }
    
    $input = getJsonInput();
    $username = sanitize($input['username'] ?? '');
    $password = $input['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        jsonError('יש למלא שם משתמש וסיסמה');
    }
    
    // האם זה אימייל או שם משתמש?
    $field = filter_var($username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
    
    $user = db()->fetch(
        "SELECT id, username, email, password_hash, full_name, role, is_active FROM users WHERE $field = ? LIMIT 1",
        [$username]
    );
    
    if (!$user) {
        jsonError('שם משתמש או סיסמה שגויים', 401);
    }
    
    if (!$user['is_active']) {
        jsonError('המשתמש מושבת', 403);
    }
    
    if (!password_verify($password, $user['password_hash'])) {
        jsonError('שם משתמש או סיסמה שגויים', 401);
    }
    
    // עדכון last_login
    db()->query("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
    
    $userData = [
        'id' => (int)$user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'full_name' => $user['full_name'],
        'role' => $user['role']
    ];
    
    loginUser($user['id'], $userData);
    logActivity('login');
    
    jsonSuccess(['user' => $userData]);
}

function handleLogout() {
    logActivity('logout');
    logoutUser();
    jsonSuccess(['message' => 'הותנתקת בהצלחה']);
}

function handleMe() {
    requireAuth();
    jsonSuccess(['user' => currentUser()]);
}

function handleChangePassword() {
    requireAuth();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonError('Method not allowed', 405);
    }
    
    $input = getJsonInput();
    $currentPassword = $input['current_password'] ?? '';
    $newPassword = $input['new_password'] ?? '';
    
    if (strlen($newPassword) < 8) {
        jsonError('הסיסמה החדשה חייבת להיות באורך 8 תווים לפחות');
    }
    
    $userId = currentUserId();
    $user = db()->fetch("SELECT password_hash FROM users WHERE id = ?", [$userId]);
    
    if (!password_verify($currentPassword, $user['password_hash'])) {
        jsonError('סיסמה נוכחית שגויה', 401);
    }
    
    $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
    db()->query("UPDATE users SET password_hash = ? WHERE id = ?", [$newHash, $userId]);
    
    logActivity('password_changed');
    jsonSuccess(['message' => 'הסיסמה שונתה בהצלחה']);
}
