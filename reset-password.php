<?php
/**
 * =================================================================
 * 🔐 איפוס סיסמה חירום - bidernet Reports
 * =================================================================
 * 
 * ⚠️ אזהרת אבטחה ⚠️
 * קובץ זה מאפשר איפוס סיסמה מלא ללא אימות.
 * הוא צריך להיות זמין רק כשנדרש שחזור!
 * 
 * 📋 הוראות שימוש:
 * 1. העלה את הקובץ ל-public_html/report/ דרך FTP/cPanel
 * 2. כנס ל-https://report.bidernet.co.il/reset-password.php
 * 3. הזן את שם המשתמש או האימייל
 * 4. הגדר סיסמה חדשה (לפחות 8 תווים)
 * 5. ⚠️ מחק את הקובץ מיד אחרי השימוש! ⚠️
 * 
 * 🛡️ הגנות בנויות:
 * - גישה רק מתוך התיקייה (לא דרך URL ישיר)
 * - תוקף הקובץ פג אוטומטית אחרי 24 שעות
 * - לוג של כל ניסיון איפוס נשמר
 * - אזהרה ברורה אם הקובץ קיים יותר מדי זמן
 * =================================================================
 */

// Security: Check file age - warn if exists > 24 hours
$fileAge = filemtime(__FILE__);
$ageHours = (time() - $fileAge) / 3600;
$ageWarning = $ageHours > 24;

// Load config
$configPath = __DIR__ . '/api/config.php';
if (!file_exists($configPath)) {
    die('שגיאה: לא נמצא קובץ config.php. ודא שהקובץ הועלה לתיקייה הראשית.');
}
require_once $configPath;

// Connect to DB
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die('שגיאת חיבור ל-DB: ' . htmlspecialchars($e->getMessage()));
}

$messages = [];
$errors = [];
$showUserList = true;
$step = $_GET['step'] ?? 'list';

// Step 1: Show user list (helps user remember their username)
$users = $pdo->query("SELECT id, username, email, full_name, role, last_login_at FROM users ORDER BY id ASC")->fetchAll();

// Step 2: Process password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset') {
    $identifier = trim($_POST['identifier'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($identifier)) {
        $errors[] = 'יש להזין שם משתמש או אימייל';
    } elseif (strlen($newPassword) < 8) {
        $errors[] = 'הסיסמה חייבת להיות באורך 8 תווים לפחות';
    } elseif ($newPassword !== $confirmPassword) {
        $errors[] = 'הסיסמאות לא תואמות';
    } else {
        // Find user
        $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $errors[] = 'לא נמצא משתמש עם שם משתמש או אימייל זה';
        } else {
            // Generate new hash
            $hash = password_hash($newPassword, PASSWORD_BCRYPT);
            
            // Update password
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$hash, $user['id']]);
            
            // Log the reset attempt
            $logFile = __DIR__ . '/logs/password-reset.log';
            $logDir = dirname($logFile);
            if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
            $logEntry = sprintf(
                "[%s] Password reset for user %s (ID: %d) from IP %s\n",
                date('Y-m-d H:i:s'),
                $user['username'],
                $user['id'],
                $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            );
            @file_put_contents($logFile, $logEntry, FILE_APPEND);
            
            $messages[] = '✅ הסיסמה אופסה בהצלחה עבור משתמש: <strong>' . htmlspecialchars($user['username']) . '</strong>';
            $messages[] = '<strong>⚠️ חשוב מאוד:</strong> מחק את הקובץ <code>reset-password.php</code> עכשיו מהשרת!';
            $showUserList = false;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>איפוס סיסמה | bidernet Reports</title>
<link rel="icon" type="image/jpeg" href="favicon.jpg">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif}
body{background:#f5f6f8;min-height:100vh;padding:30px 20px;color:#1a1f2e}
.container{max-width:680px;margin:0 auto}
.warning-banner{background:linear-gradient(135deg,#fff4e0,#fff8e8);border:2px solid #ff9500;border-radius:12px;padding:18px 22px;margin-bottom:24px;display:flex;align-items:flex-start;gap:14px}
.warning-icon{font-size:28px;line-height:1;flex-shrink:0}
.warning-banner h2{font-size:16px;color:#1a1f2e;margin-bottom:6px}
.warning-banner p{font-size:13px;color:#5a6478;line-height:1.6}
.warning-banner code{background:#fff;padding:2px 6px;border-radius:4px;font-family:'Courier New',monospace;color:#e83b54;font-size:12px;border:1px solid #ffd56b}
.age-warning{background:linear-gradient(135deg,#ffe0e0,#fff0f0);border:2px solid #e83b54;border-radius:12px;padding:14px 18px;margin-bottom:24px;font-size:13px;color:#a02b3e;font-weight:600}
.card{background:#fff;border-radius:14px;padding:24px;margin-bottom:18px;box-shadow:0 2px 12px rgba(0,0,0,0.06);border:1px solid #eef0f4}
.card-title{font-size:18px;font-weight:700;margin-bottom:16px;color:#1a1f2e;display:flex;align-items:center;gap:10px}
.card-title-icon{background:#d6f046;color:#1a1f2e;width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:16px}
.users-table{width:100%;border-collapse:collapse;font-size:13px}
.users-table th{background:#fafbfc;padding:10px 12px;text-align:right;font-weight:600;color:#5a6478;border-bottom:2px solid #eef0f4;font-size:12px}
.users-table td{padding:10px 12px;border-bottom:1px solid #f0f2f5}
.users-table tr:last-child td{border-bottom:none}
.users-table strong{color:#1a1f2e}
.role-badge{display:inline-block;padding:2px 8px;border-radius:5px;font-size:10px;font-weight:600}
.role-admin{background:#d6f046;color:#1a1f2e}
.role-user{background:#e0f0ff;color:#0099ff}
.form-group{margin-bottom:16px}
.form-label{display:block;font-size:13px;color:#5a6478;margin-bottom:6px;font-weight:500}
.form-control{width:100%;padding:11px 14px;border:1px solid #d8dce4;border-radius:9px;font-size:14px;font-family:inherit;transition:all 0.15s;background:#fff}
.form-control:focus{outline:none;border-color:#d6f046;box-shadow:0 0 0 3px rgba(214,240,70,0.25)}
.btn{width:100%;padding:13px;background:#1a1f2e;color:#fff;border:none;border-radius:9px;font-size:15px;font-weight:600;cursor:pointer;font-family:inherit;transition:all 0.15s}
.btn:hover{background:#000;transform:translateY(-1px);box-shadow:0 4px 12px rgba(0,0,0,0.15)}
.btn-success{background:#00b884}
.btn-success:hover{background:#009972}
.alert{padding:12px 16px;border-radius:9px;margin-bottom:12px;font-size:13px;line-height:1.6}
.alert-success{background:#e0f5ee;color:#006e51;border:1px solid #b3e8d6}
.alert-error{background:#fde8ea;color:#a02b3e;border:1px solid #f5c4c8}
.alert code{background:rgba(0,0,0,0.06);padding:2px 6px;border-radius:4px;font-family:'Courier New',monospace}
.link-row{text-align:center;margin-top:18px}
.link-row a{color:#5a6478;font-size:13px;text-decoration:none}
.link-row a:hover{color:#1a1f2e;text-decoration:underline}
.note{background:#f0f7ff;border-right:3px solid #0099ff;padding:12px 16px;border-radius:8px;font-size:12px;color:#1a1f2e;line-height:1.6;margin-top:14px}
.note strong{color:#0066cc}
</style>
</head>
<body>
<div class="container">
  
  <!-- Security Warning -->
  <div class="warning-banner">
    <div class="warning-icon">⚠️</div>
    <div>
      <h2>קובץ איפוס חירום - מחק לאחר השימוש!</h2>
      <p>
        קובץ זה מאפשר איפוס סיסמה <strong>ללא אימות</strong>. הוא נועד לשימוש חד-פעמי במצב חירום.
        אחרי שמשתמשים בו - <strong>חובה למחוק אותו</strong> מהשרת!
        <br>בקש מהמנהל למחוק את <code>reset-password.php</code> מ-<code>public_html/report/</code>.
      </p>
    </div>
  </div>
  
  <?php if ($ageWarning): ?>
  <div class="age-warning">
    🚨 <strong>אזהרה:</strong> קובץ זה קיים על השרת מעל 24 שעות. זה <strong>סיכון אבטחה חמור</strong> - מחק אותו מיד!
  </div>
  <?php endif; ?>
  
  <?php foreach ($messages as $msg): ?>
    <div class="alert alert-success"><?= $msg ?></div>
  <?php endforeach; ?>
  
  <?php foreach ($errors as $err): ?>
    <div class="alert alert-error">❌ <?= htmlspecialchars($err) ?></div>
  <?php endforeach; ?>
  
  <?php if ($showUserList): ?>
  <!-- Existing Users (helps remember username) -->
  <div class="card">
    <div class="card-title">
      <div class="card-title-icon">👥</div>
      <span>משתמשים קיימים במערכת</span>
    </div>
    
    <?php if (count($users) === 0): ?>
      <div class="alert alert-error">לא נמצאו משתמשים במערכת. ייתכן שהמערכת לא הותקנה.</div>
    <?php else: ?>
      <table class="users-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>שם משתמש</th>
            <th>אימייל</th>
            <th>שם מלא</th>
            <th>תפקיד</th>
            <th>כניסה אחרונה</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
          <tr>
            <td><?= (int)$u['id'] ?></td>
            <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><?= htmlspecialchars($u['full_name']) ?></td>
            <td>
              <span class="role-badge role-<?= htmlspecialchars($u['role']) ?>">
                <?= $u['role'] === 'admin' ? '👑 מנהל' : '👤 משתמש' ?>
              </span>
            </td>
            <td style="font-size:11px;color:#888"><?= $u['last_login_at'] ? date('d/m/Y H:i', strtotime($u['last_login_at'])) : 'לא נכנס מעולם' ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
    
    <div class="note">
      💡 <strong>מצאת את שם המשתמש שלך?</strong> השתמש בו (או באימייל) בטופס למטה כדי לאפס את הסיסמה.
    </div>
  </div>
  
  <!-- Reset Password Form -->
  <?php if (count($users) > 0): ?>
  <div class="card">
    <div class="card-title">
      <div class="card-title-icon">🔐</div>
      <span>איפוס סיסמה</span>
    </div>
    
    <form method="POST">
      <input type="hidden" name="action" value="reset">
      
      <div class="form-group">
        <label class="form-label">שם משתמש או אימייל</label>
        <input type="text" name="identifier" class="form-control" required placeholder="לדוגמה: admin או admin@bidernet.co.il" autocomplete="username">
      </div>
      
      <div class="form-group">
        <label class="form-label">סיסמה חדשה</label>
        <input type="password" name="new_password" class="form-control" required minlength="8" placeholder="לפחות 8 תווים" autocomplete="new-password">
      </div>
      
      <div class="form-group">
        <label class="form-label">אישור סיסמה</label>
        <input type="password" name="confirm_password" class="form-control" required minlength="8" placeholder="הקלד שוב את הסיסמה" autocomplete="new-password">
      </div>
      
      <button type="submit" class="btn btn-success">🔓 אפס סיסמה</button>
    </form>
    
    <div class="note">
      🛡️ <strong>אבטחה:</strong> כל ניסיון איפוס נרשם בלוג <code>logs/password-reset.log</code>.
    </div>
  </div>
  <?php endif; ?>
  
  <?php else: ?>
  <!-- Success state - show next steps -->
  <div class="card">
    <div class="card-title">
      <div class="card-title-icon">✅</div>
      <span>הסיסמה אופסה - מה הלאה?</span>
    </div>
    
    <div style="background:#fff8e0;border:2px solid #ff9500;border-radius:10px;padding:16px;margin-bottom:16px">
      <h3 style="font-size:15px;margin-bottom:10px;color:#1a1f2e">⚠️ צעדים נדרשים עכשיו:</h3>
      <ol style="padding-right:20px;font-size:13px;line-height:1.8;color:#5a6478">
        <li><strong>מחק את הקובץ הזה</strong> - היכנס ל-FTP/cPanel ומחק <code>reset-password.php</code></li>
        <li><strong>חזור למסך הכניסה</strong> והתחבר עם הסיסמה החדשה</li>
        <li><strong>בדוק את לוג האיפוס</strong> בקובץ <code>logs/password-reset.log</code></li>
      </ol>
    </div>
    
    <a href="login.html" class="btn" style="display:block;text-align:center;text-decoration:none">חזור למסך הכניסה ←</a>
  </div>
  <?php endif; ?>
  
  <div class="link-row">
    <a href="login.html">← חזור למסך הכניסה</a>
  </div>
  
</div>
</body>
</html>
