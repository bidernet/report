<?php
/**
 * /api/clients.php
 * 
 * ניהול לקוחות:
 *   GET    /api/clients.php           - רשימת לקוחות
 *   GET    /api/clients.php?id=123    - לקוח בודד
 *   POST   /api/clients.php           - הוספת לקוח
 *   PUT    /api/clients.php?id=123    - עדכון לקוח
 *   DELETE /api/clients.php?id=123    - מחיקת לקוח
 */

require_once __DIR__ . '/helpers.php';
handleCors();
requireAuth();

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

try {
    switch ($method) {
        case 'GET':
            $id ? getClient($id) : listClients();
            break;
        case 'POST':
            createClient();
            break;
        case 'PUT':
            if (!$id) jsonError('Client ID required');
            updateClient($id);
            break;
        case 'DELETE':
            if (!$id) jsonError('Client ID required');
            deleteClient($id);
            break;
        default:
            jsonError('Method not allowed', 405);
    }
} catch (Exception $e) {
    if (APP_ENV === 'development') {
        jsonError($e->getMessage(), 500);
    }
    error_log('Clients API error: ' . $e->getMessage());
    jsonError('שגיאת שרת', 500);
}

// -------------------------------------------------------------
function listClients() {
    $clients = db()->fetchAll("
        SELECT c.*,
               (SELECT COUNT(*) FROM campaigns WHERE client_id = c.id) AS reports_count,
               (SELECT COALESCE(SUM(reach),0) FROM campaigns WHERE client_id = c.id) AS total_reach,
               (SELECT COALESCE(SUM(leads),0) FROM campaigns WHERE client_id = c.id) AS total_leads,
               (SELECT COALESCE(SUM(budget),0) FROM campaigns WHERE client_id = c.id) AS total_budget
        FROM clients c
        ORDER BY c.created_at DESC
    ");
    
    // Hide tokens from list (only show "has_token" boolean)
    foreach ($clients as &$c) {
        $c['has_api_connection'] = !empty($c['access_token']) && !empty($c['ad_account_id']);
        unset($c['access_token']);
        $c['id'] = (int)$c['id'];
        $c['reports_count'] = (int)$c['reports_count'];
        $c['total_reach'] = (int)$c['total_reach'];
        $c['total_leads'] = (int)$c['total_leads'];
        $c['total_budget'] = (float)$c['total_budget'];
    }
    
    jsonSuccess(['clients' => $clients]);
}

function getClient($id) {
    $client = db()->fetch("SELECT * FROM clients WHERE id = ?", [$id]);
    if (!$client) jsonError('לקוח לא נמצא', 404);
    
    $client['id'] = (int)$client['id'];
    $client['has_api_connection'] = !empty($client['access_token']) && !empty($client['ad_account_id']);
    
    // Decrypt token only if specifically requested (for editing)
    $withToken = isset($_GET['with_token']) && $_GET['with_token'] === '1';
    if ($withToken) {
        $client['access_token'] = $client['access_token'] ? decryptToken($client['access_token']) : '';
    } else {
        unset($client['access_token']);
    }
    
    jsonSuccess(['client' => $client]);
}

function createClient() {
    $input = getJsonInput();
    
    $name = sanitize($input['name'] ?? '');
    if (empty($name)) jsonError('שם הלקוח חובה');
    
    $logo = $input['logo'] ?? null;
    $adAccountId = sanitize($input['ad_account_id'] ?? '');
    $adAccountName = sanitize($input['ad_account_name'] ?? '');
    $accessToken = $input['access_token'] ?? '';
    
    $encryptedToken = $accessToken ? encryptToken($accessToken) : null;
    
    $id = db()->insert(
        "INSERT INTO clients (name, logo, ad_account_id, ad_account_name, access_token, created_by) 
         VALUES (?, ?, ?, ?, ?, ?)",
        [$name, $logo, $adAccountId, $adAccountName, $encryptedToken, currentUserId()]
    );
    
    logActivity('create_client', 'client', $id, $name);
    jsonSuccess(['id' => (int)$id, 'message' => 'הלקוח נוסף בהצלחה']);
}

function updateClient($id) {
    $client = db()->fetch("SELECT * FROM clients WHERE id = ?", [$id]);
    if (!$client) jsonError('לקוח לא נמצא', 404);
    
    $input = getJsonInput();
    
    $name = sanitize($input['name'] ?? $client['name']);
    $logo = array_key_exists('logo', $input) ? $input['logo'] : $client['logo'];
    $adAccountId = sanitize($input['ad_account_id'] ?? $client['ad_account_id']);
    $adAccountName = sanitize($input['ad_account_name'] ?? $client['ad_account_name']);
    
    // Token: only update if explicitly provided
    $encryptedToken = $client['access_token'];
    if (array_key_exists('access_token', $input)) {
        $encryptedToken = !empty($input['access_token']) ? encryptToken($input['access_token']) : null;
    }
    
    db()->query(
        "UPDATE clients SET name=?, logo=?, ad_account_id=?, ad_account_name=?, access_token=? WHERE id=?",
        [$name, $logo, $adAccountId, $adAccountName, $encryptedToken, $id]
    );
    
    logActivity('update_client', 'client', $id, $name);
    jsonSuccess(['message' => 'הלקוח עודכן בהצלחה']);
}

function deleteClient($id) {
    $client = db()->fetch("SELECT name FROM clients WHERE id = ?", [$id]);
    if (!$client) jsonError('לקוח לא נמצא', 404);
    
    db()->query("DELETE FROM clients WHERE id = ?", [$id]);
    logActivity('delete_client', 'client', $id, $client['name']);
    jsonSuccess(['message' => 'הלקוח נמחק בהצלחה']);
}
