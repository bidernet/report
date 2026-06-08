<?php
/**
 * api/config.sample.php
 * 
 * זהו קובץ דוגמא בלבד. צור עותק שלו בשם config.php ועדכן את הערכים.
 * אסור להעלות ל-Git את הקובץ config.php עם הסיסמאות האמיתיות!
 */

// =============================================================
// הגדרות מסד נתונים
// =============================================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
define('DB_CHARSET', 'utf8mb4');

// =============================================================
// הגדרות אבטחה - חובה לשנות!
// =============================================================
define('ENCRYPTION_KEY', 'CHANGE_TO_64_CHAR_HEX_STRING_USE_bin2hex_random_bytes_32');
define('JWT_SECRET', 'CHANGE_TO_ANOTHER_RANDOM_STRING');
define('SESSION_DURATION', 60 * 60 * 24 * 7); // 7 days

// =============================================================
// הגדרות כלליות
// =============================================================
define('APP_NAME', 'bidernet Reports');
define('APP_URL', 'https://report.bidernet.co.il');
define('APP_TIMEZONE', 'Asia/Jerusalem');
define('APP_ENV', 'production'); // 'production' או 'development'

define('ALLOWED_ORIGINS', [
    'https://report.bidernet.co.il',
    'http://localhost:3000',
]);

// =============================================================
// אתחול
// =============================================================
date_default_timezone_set(APP_TIMEZONE);
mb_internal_encoding('UTF-8');

if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../logs/php-errors.log');
}

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', APP_ENV === 'production' ? '1' : '0');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_samesite', 'Strict');
