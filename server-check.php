<?php
/**
 * server-check.php - בדיקת תאימות שרת
 * 
 * הרץ אותו לפני ההתקנה כדי לוודא שהכל מותקן.
 * אחרי שווידאת שהכל ירוק - מחק גם את הקובץ הזה.
 */

$checks = [];

// PHP Version
$phpVersion = phpversion();
$checks[] = [
    'name' => 'PHP גרסה',
    'value' => $phpVersion,
    'status' => version_compare($phpVersion, '7.4.0', '>=') ? 'ok' : 'fail',
    'required' => '7.4+'
];

// PHP Extensions
$requiredExt = ['pdo', 'pdo_mysql', 'openssl', 'curl', 'json', 'mbstring'];
foreach ($requiredExt as $ext) {
    $checks[] = [
        'name' => "Extension: $ext",
        'value' => extension_loaded($ext) ? 'מותקן' : 'חסר',
        'status' => extension_loaded($ext) ? 'ok' : 'fail',
        'required' => 'מותקן'
    ];
}

// Apache mod_rewrite
$modRewrite = function_exists('apache_get_modules') ? 
    in_array('mod_rewrite', apache_get_modules()) : 'לא ניתן לבדוק';
$checks[] = [
    'name' => 'mod_rewrite',
    'value' => is_bool($modRewrite) ? ($modRewrite ? 'פעיל' : 'לא פעיל') : $modRewrite,
    'status' => $modRewrite === true ? 'ok' : ($modRewrite === false ? 'fail' : 'warn'),
    'required' => 'פעיל'
];

// HTTPS
$isHttps = !empty($_SERVER['HTTPS']) || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
$checks[] = [
    'name' => 'HTTPS',
    'value' => $isHttps ? 'פעיל' : 'לא פעיל',
    'status' => $isHttps ? 'ok' : 'warn',
    'required' => 'פעיל (חובה לפרודקשן)'
];

// File permissions
$writable = is_writable(__DIR__);
$checks[] = [
    'name' => 'הרשאות תיקיה',
    'value' => $writable ? 'ניתן לכתיבה' : 'לא ניתן לכתיבה',
    'status' => $writable ? 'ok' : 'warn',
    'required' => 'לכתיבה'
];

// Memory limit
$memLimit = ini_get('memory_limit');
$checks[] = [
    'name' => 'Memory Limit',
    'value' => $memLimit,
    'status' => 'ok',
    'required' => '64M+'
];

// Max execution time
$maxExec = ini_get('max_execution_time');
$checks[] = [
    'name' => 'Max Execution Time',
    'value' => $maxExec . ' שניות',
    'status' => $maxExec >= 30 ? 'ok' : 'warn',
    'required' => '30+ שניות'
];

// Upload max
$uploadMax = ini_get('upload_max_filesize');
$checks[] = [
    'name' => 'Upload Max Filesize',
    'value' => $uploadMax,
    'status' => 'ok',
    'required' => '2M+'
];

$allOk = true;
foreach ($checks as $c) {
    if ($c['status'] === 'fail') $allOk = false;
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
<meta charset="UTF-8">
<title>בדיקת שרת | bidernet Reports</title>
<style>
body { font-family: Arial, sans-serif; background: #f5f6f8; padding: 30px; }
.box { background: white; max-width: 700px; margin: 0 auto; padding: 32px; border-radius: 14px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
h1 { color: #1a1f2e; margin-bottom: 4px; }
.subtitle { color: #5a6478; margin-bottom: 24px; }
table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
th, td { padding: 12px; text-align: right; border-bottom: 1px solid #e5e8ef; }
th { background: #f8f9fb; font-size: 13px; color: #5a6478; }
.status { padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-block; }
.status.ok { background: #e0f5ee; color: #00b884; }
.status.fail { background: #fde7eb; color: #e83b54; }
.status.warn { background: #fff2dc; color: #ff9500; }
.alert { padding: 16px; border-radius: 10px; margin-top: 16px; }
.alert.success { background: #e0f5ee; color: #00b884; }
.alert.error { background: #fde7eb; color: #e83b54; }
.warn-note { background: #fff8e0; border-right: 3px solid #ff9500; padding: 14px; border-radius: 8px; font-size: 13px; color: #5a6478; margin-top: 16px; }
.btn { background: #d6f046; color: #1a1f2e; padding: 10px 20px; border-radius: 8px; text-decoration: none; display: inline-block; font-weight: 600; margin-top: 16px; }
</style>
</head>
<body>
<div class="box">
<h1>🔍 בדיקת תאימות שרת</h1>
<p class="subtitle">בדיקת דרישות לפני התקנת bidernet Reports</p>

<table>
<thead>
<tr><th>בדיקה</th><th>ערך נוכחי</th><th>נדרש</th><th>סטטוס</th></tr>
</thead>
<tbody>
<?php foreach ($checks as $c): ?>
<tr>
    <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
    <td><?= htmlspecialchars($c['value']) ?></td>
    <td><?= htmlspecialchars($c['required']) ?></td>
    <td>
        <?php if ($c['status'] === 'ok'): ?>
            <span class="status ok">✓ תקין</span>
        <?php elseif ($c['status'] === 'fail'): ?>
            <span class="status fail">✗ חסר</span>
        <?php else: ?>
            <span class="status warn">⚠ אזהרה</span>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<?php if ($allOk): ?>
    <div class="alert success">✅ <strong>השרת תואם!</strong> ניתן להמשיך להתקנה.</div>
    <a href="install.php" class="btn">המשך להתקנה ←</a>
<?php else: ?>
    <div class="alert error">⚠️ <strong>חסרים רכיבים נדרשים.</strong> אנא פנה לחברת ההוסטינג להפעלת ה-extensions החסרים.</div>
<?php endif; ?>

<div class="warn-note">
    🚨 <strong>חשוב:</strong> מחק את הקובץ הזה (server-check.php) אחרי הבדיקה - הוא חושף מידע על השרת.
</div>
</div>
</body>
</html>
