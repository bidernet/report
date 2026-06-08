<?php
/**
 * install.php - סקריפט התקנה ראשונית
 * 
 * ⚠️ מחק את הקובץ הזה אחרי ההתקנה!
 * 
 * הסקריפט הזה:
 * 1. בודק חיבור למסד הנתונים
 * 2. מריץ את ה-schema (אם הטבלאות לא קיימות)
 * 3. יוצר משתמש אדמין ראשון
 * 4. מאמת שהכל עובד
 */

require_once __DIR__ . '/api/config.php';

// Block if already installed (admin user with proper hash exists)
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die("<h1>שגיאת חיבור למסד הנתונים</h1><p>" . htmlspecialchars($e->getMessage()) . "</p><p>אנא בדוק את ההגדרות בקובץ api/config.php</p>");
}

$messages = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? 'admin');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $fullName = trim($_POST['full_name'] ?? '');
    
    if (empty($email) || empty($password) || empty($fullName)) {
        $errors[] = 'יש למלא את כל השדות';
    } elseif (strlen($password) < 8) {
        $errors[] = 'הסיסמה חייבת להיות באורך 8 תווים לפחות';
    } else {
        try {
            // Run schema
            $sql = file_get_contents(__DIR__ . '/sql/schema.sql');
            // Remove the demo INSERT (we'll create our own)
            $sql = preg_replace('/INSERT INTO `users`.*?\);/s', '', $sql);
            
            // Execute each statement
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($statements as $stmt) {
                if (!empty($stmt) && stripos($stmt, 'SET ') !== 0) {
                    try {
                        $pdo->exec($stmt);
                    } catch (PDOException $e) {
                        // Ignore "already exists" errors
                        if (strpos($e->getMessage(), 'already exists') === false) {
                            throw $e;
                        }
                    }
                }
            }
            $messages[] = '✓ סכמת מסד הנתונים נוצרה בהצלחה';
            
            // Check if admin already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $errors[] = "משתמש עם שם משתמש או אימייל זה כבר קיים. אם המערכת כבר הותקנה - מחק את install.php.";
            } else {
                // Create admin user
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, full_name, role) VALUES (?, ?, ?, ?, 'admin')");
                $stmt->execute([$username, $email, $hash, $fullName]);
                $messages[] = '✓ משתמש אדמין נוצר בהצלחה';
                
                $messages[] = '<strong>🎉 ההתקנה הושלמה!</strong>';
                $messages[] = '<strong style="color: #e83b54">⚠️ חשוב מאוד: מחק את הקובץ install.php עכשיו מהשרת!</strong>';
                $messages[] = '<a href="/" style="display:inline-block;background:#d6f046;color:#1a1f2e;padding:10px 24px;border-radius:8px;font-weight:600;text-decoration:none;margin-top:10px">כניסה למערכת ←</a>';
            }
        } catch (Exception $e) {
            $errors[] = 'שגיאה: ' . $e->getMessage();
        }
    }
}

// Check existing setup
$tablesExist = false;
$adminExists = false;
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $tablesExist = $stmt->rowCount() > 0;
    if ($tablesExist) {
        $stmt = $pdo->query("SELECT COUNT(*) AS c FROM users WHERE role = 'admin'");
        $adminExists = $stmt->fetch()['c'] > 0;
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
<meta charset="UTF-8">
<title>התקנת bidernet Reports</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Arial', sans-serif; background: #f5f6f8; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
.box { background: #fff; border-radius: 14px; padding: 32px; max-width: 520px; width: 100%; box-shadow: 0 12px 32px rgba(20,28,48,0.1); }
h1 { font-size: 24px; color: #1a1f2e; margin-bottom: 6px; }
.subtitle { color: #5a6478; margin-bottom: 24px; font-size: 14px; }
.alert { padding: 12px 16px; border-radius: 9px; margin-bottom: 14px; font-size: 14px; }
.alert.error { background: #fde7eb; color: #e83b54; border: 1px solid rgba(232,59,84,0.2); }
.alert.success { background: #e0f5ee; color: #00b884; border: 1px solid rgba(0,184,132,0.2); }
.alert.warning { background: #fff2dc; color: #ff9500; border: 1px solid rgba(255,149,0,0.2); }
.form-group { margin-bottom: 16px; }
label { display: block; font-size: 13px; color: #5a6478; margin-bottom: 5px; font-weight: 500; }
input { width: 100%; padding: 10px 14px; border-radius: 9px; border: 1px solid #e5e8ef; font-size: 14px; font-family: inherit; }
input:focus { outline: none; border-color: #d6f046; box-shadow: 0 0 0 3px rgba(214,240,70,0.25); }
.btn { background: #d6f046; color: #1a1f2e; padding: 11px 24px; border: none; border-radius: 9px; font-weight: 600; font-size: 14px; cursor: pointer; font-family: inherit; }
.btn:hover { background: #c5e234; }
.steps { background: #f8f9fb; border: 1px solid #e5e8ef; border-radius: 9px; padding: 16px; margin-bottom: 20px; font-size: 13px; line-height: 1.7; }
.steps strong { color: #1a1f2e; }
</style>
</head>
<body>
<div class="box">
<h1>🚀 התקנת bidernet Reports</h1>
<p class="subtitle">הגדרה ראשונית של המערכת</p>

<?php if (!empty($errors)): ?>
    <?php foreach ($errors as $err): ?>
        <div class="alert error">⚠️ <?= htmlspecialchars($err) ?></div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($messages)): ?>
    <?php foreach ($messages as $msg): ?>
        <div class="alert success"><?= $msg ?></div>
    <?php endforeach; ?>
<?php elseif ($adminExists): ?>
    <div class="alert warning">
        <strong>המערכת כבר הותקנה!</strong><br>
        אם זה לא נכון - מחק את משתמש האדמין במסד הנתונים ורענן את הדף.
    </div>
    <div class="steps">
        <strong>⚠️ חשוב מאוד:</strong> אנא מחק את הקובץ <code>install.php</code> מהשרת עכשיו!<br>
        השארתו פתוחה היא סיכון אבטחה חמור.
    </div>
    <a href="/" class="btn" style="display:inline-block;text-decoration:none">כניסה למערכת ←</a>
<?php else: ?>
    <div class="steps">
        <strong>✓ חיבור למסד הנתונים תקין</strong><br>
        כעת ניצור משתמש אדמין ראשון. סיסמה זו תשמש להתחברות למערכת.
    </div>

    <form method="POST">
        <div class="form-group">
            <label>שם מלא <span style="color:red">*</span></label>
            <input type="text" name="full_name" placeholder="לדוגמה: שרון בידר" required>
        </div>
        <div class="form-group">
            <label>שם משתמש <span style="color:red">*</span></label>
            <input type="text" name="username" value="admin" required>
        </div>
        <div class="form-group">
            <label>אימייל <span style="color:red">*</span></label>
            <input type="email" name="email" placeholder="admin@bidernet.co.il" required>
        </div>
        <div class="form-group">
            <label>סיסמה (8 תווים לפחות) <span style="color:red">*</span></label>
            <input type="password" name="password" minlength="8" required>
        </div>
        <button type="submit" class="btn">🚀 התקן את המערכת</button>
    </form>
<?php endif; ?>
</div>
</body>
</html>
