<?php
/**
 * /api/users.php
 * 
 * ניהול משתמשים (לאדמין בלבד):
 *   GET    /api/users.php           - רשימת משתמשים
 *   POST   /api/users.php           - הוספת משתמש
 *   PUT    /api/users.php?id=123    - עדכון משתמש
 *   DELETE /api/users.php?id=123    - מחיקת משתמש
 */

require_once __DIR__ . '/helpers.php';
handleCors();
requireAdmin();

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

try {
    switch ($method) {
        case 'GET':
            listUsers();
            break;
        case 'POST':
            createUser();
            break;
        case 'PUT':
            if (!$id) jsonError('User ID required');
            updateUser($id);
            break;
        case 'DELETE':
            if (!$id) jsonError('User ID required');
            deleteUser($id);
            break;
        default:
            jsonError('Method not allowed', 405);
    }
} catch (Exception $e) {
    if (APP_ENV === 'development') {
        jsonError($e->getMessage(), 500);
    }
    error_log('Users API error: ' . $e->getMessage());
    jsonError('שגיאת שרת', 500);
}

function listUsers() {
    $users = db()->fetchAll(
        "SELECT id, username, email, full_name, role, is_active, last_login, created_at FROM users ORDER BY id ASC"
    );
    foreach ($users as &$u) {
        $u['id'] = (int)$u['id'];
        $u['is_active'] = (bool)$u['is_active'];
    }
    jsonSuccess(['users' => $users]);
}

function createUser() {
    $input = getJsonInput();
    
    $username = sanitize($input['username'] ?? '');
    $email = sanitize($input['email'] ?? '', 'email');
    $password = $input['password'] ?? '';
    $fullName = sanitize($input['full_name'] ?? '');
    $role = in_array($input['role'] ?? '', ['admin','user']) ? $input['role'] : 'user';
    
    if (empty($username) || empty($email) || empty($fullName)) {
        jsonError('שם משתמש, אימייל ושם מלא הם שדות חובה');
    }
    
    if (strlen($password) < 8) {
        jsonError('הסיסמה חייבת להיות באורך 8 תווים לפחות');
    }
    
    // Check duplicates
    $existing = db()->fetch(
        "SELECT id FROM users WHERE username = ? OR email = ?",
        [$username, $email]
    );
    if ($existing) jsonError('שם משתמש או אימייל כבר קיימים במערכת');
    
    $hash = password_hash($password, PASSWORD_BCRYPT);
    
    $id = db()->insert(
        "INSERT INTO users (username, email, password_hash, full_name, role) VALUES (?, ?, ?, ?, ?)",
        [$username, $email, $hash, $fullName, $role]
    );
    
    logActivity('create_user', 'user', $id, $username);
    jsonSuccess(['id' => (int)$id, 'message' => 'המשתמש נוסף בהצלחה']);
}

function updateUser($id) {
    $user = db()->fetch("SELECT * FROM users WHERE id = ?", [$id]);
    if (!$user) jsonError('משתמש לא נמצא', 404);
    
    $input = getJsonInput();
    
    $email = sanitize($input['email'] ?? $user['email'], 'email');
    $fullName = sanitize($input['full_name'] ?? $user['full_name']);
    $role = isset($input['role']) && in_array($input['role'], ['admin','user']) ? $input['role'] : $user['role'];
    $isActive = isset($input['is_active']) ? (int)(bool)$input['is_active'] : $user['is_active'];
    
    // Update fields
    db()->query(
        "UPDATE users SET email=?, full_name=?, role=?, is_active=? WHERE id=?",
        [$email, $fullName, $role, $isActive, $id]
    );
    
    // Reset password if provided
    if (!empty($input['password'])) {
        if (strlen($input['password']) < 8) {
            jsonError('הסיסמה חייבת להיות באורך 8 תווים לפחות');
        }
        $hash = password_hash($input['password'], PASSWORD_BCRYPT);
        db()->query("UPDATE users SET password_hash = ? WHERE id = ?", [$hash, $id]);
    }
    
    logActivity('update_user', 'user', $id, $user['username']);
    jsonSuccess(['message' => 'המשתמש עודכן בהצלחה']);
}

function deleteUser($id) {
    if ($id === currentUserId()) {
        jsonError('לא ניתן למחוק את עצמך');
    }
    
    $user = db()->fetch("SELECT username FROM users WHERE id = ?", [$id]);
    if (!$user) jsonError('משתמש לא נמצא', 404);
    
    db()->query("DELETE FROM users WHERE id = ?", [$id]);
    logActivity('delete_user', 'user', $id, $user['username']);
    jsonSuccess(['message' => 'המשתמש נמחק בהצלחה']);
}
