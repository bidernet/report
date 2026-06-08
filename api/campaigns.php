<?php
/**
 * /api/campaigns.php
 * 
 * ניהול דוחות:
 *   GET    /api/campaigns.php                - כל הדוחות
 *   GET    /api/campaigns.php?client_id=123  - דוחות של לקוח
 *   GET    /api/campaigns.php?id=456         - דוח בודד
 *   POST   /api/campaigns.php                - הוספת דוח
 *   PUT    /api/campaigns.php?id=456         - עדכון דוח
 *   DELETE /api/campaigns.php?id=456         - מחיקת דוח
 */

require_once __DIR__ . '/helpers.php';
handleCors();
requireAuth();

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$clientId = isset($_GET['client_id']) ? (int)$_GET['client_id'] : null;

try {
    switch ($method) {
        case 'GET':
            if ($id) {
                getCampaign($id);
            } else {
                listCampaigns($clientId);
            }
            break;
        case 'POST':
            createCampaign();
            break;
        case 'PUT':
            if (!$id) jsonError('Campaign ID required');
            updateCampaign($id);
            break;
        case 'DELETE':
            if (!$id) jsonError('Campaign ID required');
            deleteCampaign($id);
            break;
        default:
            jsonError('Method not allowed', 405);
    }
} catch (Exception $e) {
    if (APP_ENV === 'development') {
        jsonError($e->getMessage(), 500);
    }
    error_log('Campaigns API error: ' . $e->getMessage());
    jsonError('שגיאת שרת', 500);
}

// -------------------------------------------------------------
function listCampaigns($clientId = null) {
    $sql = "SELECT c.*, cl.name AS client_name, cl.logo AS client_logo
            FROM campaigns c
            LEFT JOIN clients cl ON cl.id = c.client_id";
    $params = [];
    
    if ($clientId) {
        $sql .= " WHERE c.client_id = ?";
        $params[] = $clientId;
    }
    
    $sql .= " ORDER BY c.start_date DESC";
    
    $campaigns = db()->fetchAll($sql, $params);
    
    // Cast types
    foreach ($campaigns as &$c) {
        $c['id'] = (int)$c['id'];
        $c['client_id'] = (int)$c['client_id'];
        foreach (['reach','impressions','clicks','likes','comments','shares','engagement','leads','conversions'] as $f) {
            $c[$f] = (int)$c[$f];
        }
        $c['budget'] = (float)$c['budget'];
    }
    
    jsonSuccess(['campaigns' => $campaigns]);
}

function getCampaign($id) {
    $c = db()->fetch("SELECT * FROM campaigns WHERE id = ?", [$id]);
    if (!$c) jsonError('דוח לא נמצא', 404);
    
    $c['id'] = (int)$c['id'];
    $c['client_id'] = (int)$c['client_id'];
    foreach (['reach','impressions','clicks','likes','comments','shares','engagement','leads','conversions'] as $f) {
        $c[$f] = (int)$c[$f];
    }
    $c['budget'] = (float)$c['budget'];
    
    jsonSuccess(['campaign' => $c]);
}

function createCampaign() {
    $input = getJsonInput();
    
    $clientId = (int)($input['client_id'] ?? 0);
    if (!$clientId) jsonError('יש לבחור לקוח');
    
    // Verify client exists
    $client = db()->fetch("SELECT id FROM clients WHERE id = ?", [$clientId]);
    if (!$client) jsonError('לקוח לא נמצא', 404);
    
    $data = extractCampaignData($input);
    
    $sql = "INSERT INTO campaigns 
            (client_id, fb_campaign_id, name, platform, campaign_type, start_date, end_date,
             reach, impressions, clicks, likes, comments, shares, engagement, leads, conversions,
             budget, notes, synced_at, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $params = [
        $clientId,
        $data['fb_campaign_id'],
        $data['name'],
        $data['platform'],
        $data['campaign_type'],
        $data['start_date'],
        $data['end_date'],
        $data['reach'], $data['impressions'], $data['clicks'],
        $data['likes'], $data['comments'], $data['shares'],
        $data['engagement'], $data['leads'], $data['conversions'],
        $data['budget'], $data['notes'], $data['synced_at'],
        currentUserId()
    ];
    
    $id = db()->insert($sql, $params);
    
    logActivity('create_campaign', 'campaign', $id, $data['name']);
    jsonSuccess(['id' => (int)$id, 'message' => 'הדוח נשמר בהצלחה']);
}

function updateCampaign($id) {
    $existing = db()->fetch("SELECT * FROM campaigns WHERE id = ?", [$id]);
    if (!$existing) jsonError('דוח לא נמצא', 404);
    
    $input = getJsonInput();
    $data = extractCampaignData($input, $existing);
    
    $sql = "UPDATE campaigns SET
            fb_campaign_id=?, name=?, platform=?, campaign_type=?, start_date=?, end_date=?,
            reach=?, impressions=?, clicks=?, likes=?, comments=?, shares=?,
            engagement=?, leads=?, conversions=?, budget=?, notes=?, synced_at=?
            WHERE id=?";
    
    $params = [
        $data['fb_campaign_id'], $data['name'], $data['platform'], $data['campaign_type'],
        $data['start_date'], $data['end_date'],
        $data['reach'], $data['impressions'], $data['clicks'],
        $data['likes'], $data['comments'], $data['shares'],
        $data['engagement'], $data['leads'], $data['conversions'],
        $data['budget'], $data['notes'], $data['synced_at'],
        $id
    ];
    
    db()->query($sql, $params);
    logActivity('update_campaign', 'campaign', $id, $data['name']);
    jsonSuccess(['message' => 'הדוח עודכן בהצלחה']);
}

function deleteCampaign($id) {
    $c = db()->fetch("SELECT name FROM campaigns WHERE id = ?", [$id]);
    if (!$c) jsonError('דוח לא נמצא', 404);
    
    db()->query("DELETE FROM campaigns WHERE id = ?", [$id]);
    logActivity('delete_campaign', 'campaign', $id, $c['name']);
    jsonSuccess(['message' => 'הדוח נמחק בהצלחה']);
}

function extractCampaignData($input, $existing = null) {
    $defaults = $existing ?: [
        'fb_campaign_id' => null, 'name' => '', 'platform' => 'facebook',
        'campaign_type' => 'awareness', 'start_date' => null, 'end_date' => null,
        'reach' => 0, 'impressions' => 0, 'clicks' => 0,
        'likes' => 0, 'comments' => 0, 'shares' => 0,
        'engagement' => 0, 'leads' => 0, 'conversions' => 0,
        'budget' => 0, 'notes' => null, 'synced_at' => null
    ];
    
    $likes = (int)($input['likes'] ?? $defaults['likes']);
    $comments = (int)($input['comments'] ?? $defaults['comments']);
    $shares = (int)($input['shares'] ?? $defaults['shares']);
    
    return [
        'fb_campaign_id' => $input['fb_campaign_id'] ?? $defaults['fb_campaign_id'],
        'name' => sanitize($input['name'] ?? $defaults['name']),
        'platform' => $input['platform'] ?? $defaults['platform'],
        'campaign_type' => $input['campaign_type'] ?? $defaults['campaign_type'],
        'start_date' => $input['start_date'] ?? $defaults['start_date'],
        'end_date' => $input['end_date'] ?? $defaults['end_date'],
        'reach' => (int)($input['reach'] ?? $defaults['reach']),
        'impressions' => (int)($input['impressions'] ?? $defaults['impressions']),
        'clicks' => (int)($input['clicks'] ?? $defaults['clicks']),
        'likes' => $likes,
        'comments' => $comments,
        'shares' => $shares,
        'engagement' => $likes + $comments + $shares,
        'leads' => (int)($input['leads'] ?? $defaults['leads']),
        'conversions' => (int)($input['conversions'] ?? $defaults['conversions']),
        'budget' => (float)($input['budget'] ?? $defaults['budget']),
        'notes' => $input['notes'] ?? $defaults['notes'],
        'synced_at' => $input['synced_at'] ?? $defaults['synced_at']
    ];
}
