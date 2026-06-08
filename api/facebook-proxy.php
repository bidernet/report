<?php
/**
 * /api/facebook-proxy.php
 * 
 * מתווך בין המערכת ל-Facebook Graph API
 * פותר את בעיית ה-CORS - השרת מבצע את הקריאות במקום הדפדפן
 * 
 *   POST /api/facebook-proxy.php?action=test     - בדיקת חיבור
 *   POST /api/facebook-proxy.php?action=sync     - סנכרון קמפיינים
 *   POST /api/facebook-proxy.php?action=accounts - רשימת חשבונות פרסום
 */

require_once __DIR__ . '/helpers.php';
handleCors();
requireAuth();

define('FB_API_VERSION', 'v19.0');
define('FB_GRAPH_URL', 'https://graph.facebook.com/' . FB_API_VERSION);

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'test':
            handleTest();
            break;
        case 'sync':
            handleSync();
            break;
        case 'accounts':
            handleListAccounts();
            break;
        default:
            jsonError('Invalid action', 404);
    }
} catch (Exception $e) {
    if (APP_ENV === 'development') {
        jsonError($e->getMessage(), 500);
    }
    error_log('Facebook proxy error: ' . $e->getMessage());
    jsonError('שגיאה: ' . $e->getMessage(), 500);
}

// =============================================================
function fbApiCall($path, $params = []) {
    $url = FB_GRAPH_URL . $path . '?' . http_build_query($params);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT => 'bidernet-reports/1.0',
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($response === false) {
        throw new Exception('שגיאת חיבור: ' . $error);
    }
    
    $data = json_decode($response, true);
    if (isset($data['error'])) {
        throw new Exception($data['error']['message'] ?? 'API Error');
    }
    
    if ($httpCode >= 400) {
        throw new Exception('Facebook API HTTP ' . $httpCode);
    }
    
    return $data;
}

// =============================================================
function handleTest() {
    $input = getJsonInput();
    $accountId = sanitize($input['ad_account_id'] ?? '');
    $token = $input['access_token'] ?? '';
    
    // If client_id provided, get from DB
    if (!empty($input['client_id'])) {
        $client = db()->fetch("SELECT ad_account_id, access_token FROM clients WHERE id = ?", [(int)$input['client_id']]);
        if (!$client) jsonError('לקוח לא נמצא');
        $accountId = $client['ad_account_id'];
        $token = decryptToken($client['access_token']);
    }
    
    if (empty($accountId) || empty($token)) {
        jsonError('יש למלא Ad Account ID ו-Access Token');
    }
    
    if (strpos($accountId, 'act_') !== 0) {
        jsonError('Ad Account ID חייב להתחיל ב-act_');
    }
    
    $data = fbApiCall("/$accountId", [
        'fields' => 'name,account_status,currency,timezone_name',
        'access_token' => $token
    ]);
    
    jsonSuccess(['account' => $data]);
}

// =============================================================
function handleListAccounts() {
    $input = getJsonInput();
    $token = $input['access_token'] ?? '';
    
    if (empty($token)) jsonError('יש למלא Access Token');
    
    $data = fbApiCall('/me/adaccounts', [
        'fields' => 'id,name,account_status,currency',
        'limit' => 100,
        'access_token' => $token
    ]);
    
    jsonSuccess(['accounts' => $data['data'] ?? []]);
}

// =============================================================
function handleSync() {
    $input = getJsonInput();
    $clientId = (int)($input['client_id'] ?? 0);
    $startDate = $input['start_date'] ?? '';
    $endDate = $input['end_date'] ?? '';
    
    if (!$clientId) jsonError('יש לבחור לקוח');
    if (!$startDate || !$endDate) jsonError('יש לבחור טווח תאריכים');
    
    $client = db()->fetch("SELECT * FROM clients WHERE id = ?", [$clientId]);
    if (!$client) jsonError('לקוח לא נמצא', 404);
    
    $token = decryptToken($client['access_token']);
    $accountId = $client['ad_account_id'];
    
    if (empty($token) || empty($accountId)) {
        jsonError('הלקוח לא מחובר ל-Facebook API');
    }
    
    // Step 1: Verify account
    $accountInfo = fbApiCall("/$accountId", [
        'fields' => 'name,currency',
        'access_token' => $token
    ]);
    
    // Step 2: Fetch campaigns
    $campaignsData = fbApiCall("/$accountId/campaigns", [
        'fields' => 'id,name,objective,status,start_time,stop_time,created_time',
        'time_range' => json_encode(['since' => $startDate, 'until' => $endDate]),
        'limit' => 200,
        'access_token' => $token
    ]);
    
    $fbCampaigns = $campaignsData['data'] ?? [];
    $imported = [];
    $errors = [];
    
    foreach ($fbCampaigns as $camp) {
        try {
            // Fetch insights
            $insightsData = fbApiCall("/{$camp['id']}/insights", [
                'fields' => 'reach,impressions,clicks,spend,actions,inline_link_clicks,date_start,date_stop',
                'time_range' => json_encode(['since' => $startDate, 'until' => $endDate]),
                'access_token' => $token
            ]);
            
            $ins = $insightsData['data'][0] ?? [];
            
            // Parse actions
            $leads = 0; $comments = 0; $likes = 0; $shares = 0; $conversions = 0;
            if (!empty($ins['actions'])) {
                foreach ($ins['actions'] as $a) {
                    $type = $a['action_type'];
                    $value = (int)($a['value'] ?? 0);
                    if (in_array($type, ['lead','leadgen.other'])) $leads += $value;
                    if ($type === 'comment') $comments += $value;
                    if (in_array($type, ['like','post_reaction'])) $likes += $value;
                    if ($type === 'post') $shares += $value;
                    if (in_array($type, ['offsite_conversion','purchase'])) $conversions += $value;
                }
            }
            
            // Detect platform
            $name = strtolower($camp['name']);
            $platform = (strpos($name, 'instagram') !== false || strpos($name, 'ig') !== false) ? 'instagram' : 'facebook';
            
            // Map objective
            $objMap = [
                'OUTCOME_LEADS' => 'leads', 'LEAD_GENERATION' => 'leads',
                'OUTCOME_AWARENESS' => 'awareness', 'BRAND_AWARENESS' => 'awareness', 'REACH' => 'awareness',
                'OUTCOME_SALES' => 'conversions', 'CONVERSIONS' => 'conversions',
                'OUTCOME_ENGAGEMENT' => 'engagement', 'POST_ENGAGEMENT' => 'engagement', 'PAGE_LIKES' => 'engagement',
                'OUTCOME_TRAFFIC' => 'traffic', 'LINK_CLICKS' => 'traffic'
            ];
            $campType = $objMap[$camp['objective'] ?? ''] ?? 'awareness';
            
            $startDateClean = !empty($camp['start_time']) ? substr($camp['start_time'], 0, 10) : 
                              (!empty($ins['date_start']) ? $ins['date_start'] : $startDate);
            $endDateClean = !empty($camp['stop_time']) ? substr($camp['stop_time'], 0, 10) :
                            (!empty($ins['date_stop']) ? $ins['date_stop'] : $endDate);
            
            // Check if exists (by fb_campaign_id + client)
            $existing = db()->fetch(
                "SELECT id FROM campaigns WHERE fb_campaign_id = ? AND client_id = ? LIMIT 1",
                [$camp['id'], $clientId]
            );
            
            $reach = (int)($ins['reach'] ?? 0);
            $impressions = (int)($ins['impressions'] ?? 0);
            $clicks = (int)($ins['clicks'] ?? $ins['inline_link_clicks'] ?? 0);
            $engagement = $likes + $comments + $shares;
            $spend = (float)($ins['spend'] ?? 0);
            
            $now = date('Y-m-d H:i:s');
            $noteText = "סונכרן מ-Facebook ב-" . date('d/m/Y H:i');
            
            if ($existing) {
                db()->query(
                    "UPDATE campaigns SET name=?, platform=?, campaign_type=?, start_date=?, end_date=?,
                     reach=?, impressions=?, clicks=?, likes=?, comments=?, shares=?, engagement=?,
                     leads=?, conversions=?, budget=?, notes=?, synced_at=? WHERE id=?",
                    [$camp['name'], $platform, $campType, $startDateClean, $endDateClean,
                     $reach, $impressions, $clicks, $likes, $comments, $shares, $engagement,
                     $leads, $conversions, $spend, $noteText, $now, $existing['id']]
                );
                $imported[] = ['id' => (int)$existing['id'], 'name' => $camp['name'], 'is_new' => false,
                               'leads' => $leads, 'budget' => $spend, 'platform' => $platform,
                               'start_date' => $startDateClean, 'end_date' => $endDateClean];
            } else {
                $newId = db()->insert(
                    "INSERT INTO campaigns 
                     (client_id, fb_campaign_id, name, platform, campaign_type, start_date, end_date,
                      reach, impressions, clicks, likes, comments, shares, engagement, leads, conversions,
                      budget, notes, synced_at, created_by)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$clientId, $camp['id'], $camp['name'], $platform, $campType,
                     $startDateClean, $endDateClean,
                     $reach, $impressions, $clicks, $likes, $comments, $shares, $engagement,
                     $leads, $conversions, $spend, $noteText, $now, currentUserId()]
                );
                $imported[] = ['id' => (int)$newId, 'name' => $camp['name'], 'is_new' => true,
                               'leads' => $leads, 'budget' => $spend, 'platform' => $platform,
                               'start_date' => $startDateClean, 'end_date' => $endDateClean];
            }
        } catch (Exception $e) {
            $errors[] = ['campaign' => $camp['name'] ?? '?', 'error' => $e->getMessage()];
            error_log("Sync error for campaign {$camp['id']}: " . $e->getMessage());
        }
    }
    
    // Update last sync
    db()->query("UPDATE clients SET last_synced_at = NOW() WHERE id = ?", [$clientId]);
    
    logActivity('sync_facebook', 'client', $clientId, count($imported) . ' campaigns');
    
    jsonSuccess([
        'account' => $accountInfo,
        'total_campaigns' => count($fbCampaigns),
        'imported' => $imported,
        'errors' => $errors
    ]);
}
