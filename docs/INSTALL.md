# 📘 מדריך התקנה מפורט - bidernet Reports

מדריך זה מיועד למנהל השרת או חברת ההוסטינג שלך.

---

## 🎯 דרישות מקדימות

### 1. שרת
- ✅ cPanel רגיל / Linux Hosting
- ✅ PHP 7.4 ומעלה (מומלץ 8.0+)
- ✅ MySQL 5.7 / MariaDB 10.2+
- ✅ Apache + mod_rewrite
- ✅ SSL/HTTPS פעיל

### 2. PHP Extensions נדרשים
```
- PDO
- PDO_MySQL
- OpenSSL (להצפנה)
- cURL (לקריאות Facebook)
- JSON
- mbstring
```

### 3. דומיין
- **דומיין יעד:** `report.bidernet.co.il`
- ניתן להגדיר כ-subdomain ב-cPanel

---

## 📦 שלב 1: הקמת Subdomain

### דרך cPanel:

1. היכנס ל-**cPanel** של `bidernet.co.il`
2. חפש **"Subdomains"**
3. צור Subdomain חדש:
   - **Subdomain:** `report`
   - **Domain:** `bidernet.co.il`
   - **Document Root:** `public_html/report` (יצור אוטומטית)
4. שמור

✅ עכשיו `report.bidernet.co.il` מפנה ל-`public_html/report/`

---

## 📊 שלב 2: יצירת מסד נתונים

### ב-cPanel → MySQL Databases:

1. **יצירת מסד חדש:**
   - שם: `bidernet_reports`
   - הקש "Create Database"
   - ⚠️ השם המלא יהיה: `cpaneluser_bidernet_reports`

2. **יצירת משתמש:**
   - שם משתמש: `bidernet_user`
   - סיסמה: השתמש בכלי הגרלת הסיסמאות (לפחות 16 תווים)
   - **שמור את הסיסמה במקום בטוח!**
   - השם המלא: `cpaneluser_bidernet_user`

3. **קישור משתמש למסד:**
   - בחר את המשתמש
   - בחר את המסד
   - לחץ "Add"
   - בחר **"ALL PRIVILEGES"** ✅
   - שמור

### ✅ רישום הפרטים:
```
DB Host: localhost
DB Name: cpaneluser_bidernet_reports
DB User: cpaneluser_bidernet_user
DB Pass: [הסיסמה שיצרת]
```

---

## 🔒 שלב 3: SSL/HTTPS

### דרך cPanel → SSL/TLS Status:

1. בחר ב-`report.bidernet.co.il`
2. הפעל **Let's Encrypt** (חינם)
3. ודא שמופיע "Active" ירוק
4. הפעל **"Force HTTPS Redirect"**

---

## 📤 שלב 4: העלאת קבצים

### דרך cPanel File Manager:

1. עבור ל-`public_html/report/`
2. העלה את **כל** התוכן של תיקיית `bidernet-deploy/`
3. אם העלית ZIP - חלץ אותו שם

### או דרך FTP (מומלץ למהירות):

```
Host: ftp.bidernet.co.il
User: [user cPanel]
Pass: [pass cPanel]
Path: /public_html/report/
```

### לאחר ההעלאה, ודא שהקבצים נמצאים בנתיב הנכון:

```
public_html/report/
  ├── index.html          ✓
  ├── login.html          ✓
  ├── install.php         ✓
  ├── .htaccess           ✓
  ├── api/                ✓
  │   ├── config.php
  │   └── ...
  ├── sql/                ✓
  └── README.md
```

⚠️ **חשוב:** ודא שקובץ `.htaccess` עלה גם הוא (לפעמים FTP מסתיר קבצים שמתחילים בנקודה).

---

## ⚙️ שלב 5: הגדרת config.php

1. פתח את הקובץ **`api/config.php`** ב-cPanel File Manager (לחץ "Edit")

2. שנה את הערכים הבאים:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'cpaneluser_bidernet_reports');   // ⚠️ עם prefix!
define('DB_USER', 'cpaneluser_bidernet_user');      // ⚠️ עם prefix!
define('DB_PASS', 'הסיסמה_שלך_כאן');
```

3. **צור מפתחות אקראיים:**

הרץ באתר https://www.random.org/strings/ או השתמש בקוד הזה ב-PHP זמני:
```php
<?php echo bin2hex(random_bytes(32)); ?>
```

קבל 2 מפתחות אקראיים והכנס:
```php
define('ENCRYPTION_KEY', 'המפתח_הראשון_64_תווים_hex');
define('JWT_SECRET', 'המפתח_השני_אקראי_כלשהו');
```

4. ודא:
```php
define('APP_ENV', 'production');
define('APP_URL', 'https://report.bidernet.co.il');
```

5. שמור את הקובץ

---

## 🚀 שלב 6: הרצת ההתקנה

### פתח בדפדפן:
```
https://report.bidernet.co.il/install.php
```

### תראה מסך התקנה - מלא:
- **שם מלא:** השם שלך (לדוגמה: "שרון בידר")
- **שם משתמש:** `admin` (או אחר)
- **אימייל:** המייל שלך
- **סיסמה:** סיסמה חזקה (8+ תווים)

### לחץ "🚀 התקן את המערכת"

✅ אם הכל תקין, תראה הודעות ירוקות:
- "✓ סכמת מסד הנתונים נוצרה בהצלחה"
- "✓ משתמש אדמין נוצר בהצלחה"
- "🎉 ההתקנה הושלמה!"

---

## 🚨 שלב 7: מחיקת install.php

### ⚠️ **זה החשוב ביותר!**

אחרי שההתקנה הסתיימה - **חובה למחוק את `install.php`**:

1. עבור ל-cPanel File Manager
2. נווט ל-`public_html/report/`
3. מצא את `install.php`
4. לחץ "Delete"
5. אישור

**למה זה חשוב?** אם תשאיר אותו, כל מי שיגיע ל-`/install.php` יוכל לאפס את המערכת.

---

## ✅ שלב 8: בדיקת המערכת

### פתח:
```
https://report.bidernet.co.il/
```

### תועבר לדף login - התחבר עם:
- שם משתמש: `admin` (או מה שיצרת)
- סיסמה: מה שהזנת בהתקנה

### אם הכל בסדר, תראה את הדשבורד 🎉

---

## 🔧 שלב 9: יצירת משתמשים נוספים

(רק אדמין יכול):

1. עדיין לא בנינו ממשק UI לניהול משתמשים - אבל ה-API קיים
2. ניתן להוסיף משתמשים דרך:
   - SQL ישיר ב-phpMyAdmin
   - API call ל-`/api/users.php`

לדוגמה ב-phpMyAdmin:
```sql
INSERT INTO users (username, email, password_hash, full_name, role) 
VALUES (
  'user2',
  'user2@bidernet.co.il',
  '$2y$10$....',  -- צור עם password_hash() ב-PHP
  'שם מלא',
  'user'
);
```

---

## 🎯 שלב 10: סנכרון ראשון עם Facebook

1. **הוסף לקוח חדש** בעמוד "לקוחות"
2. **מלא את שדות API:**
   - Ad Account ID: `act_xxxxxxxxx`
   - Access Token: ה-token שלך
3. **לחץ "בדוק חיבור"** - אמור להופיע ירוק ✓
4. **שמור** את הלקוח
5. **היכנס לפרופיל הלקוח** → **"🔄 סנכרון מ-Facebook"**
6. **בחר טווח תאריכים** ולחץ "התחל סנכרון"

---

## 🐛 פתרון בעיות

### בעיה: "Database connection failed"
**פתרון:**
- ודא שהפרטים ב-`config.php` נכונים
- בדוק שהקידומת של cPanel נכללה (לדוגמה: `username_dbname`)
- ודא שהמשתמש מקושר למסד עם ALL PRIVILEGES

### בעיה: לחיצה על "כניסה" לא עושה כלום
**פתרון:**
- בדוק שיש HTTPS פעיל
- פתח Developer Tools (F12) ובדוק את ה-Console
- בדוק שה-`.htaccess` הועלה

### בעיה: 500 Internal Server Error
**פתרון:**
- בדוק את הלוג ב-cPanel → Error Log
- ודא ש-PHP 7.4+ פעיל
- בדוק שכל ה-extensions מותקנים

### בעיה: Facebook sync לא עובד
**פתרון:**
- ודא ש-cURL מותקן בשרת
- ודא שה-Token תקף (חדש כל 60 יום)
- בדוק את `logs/php-errors.log`

### בעיה: Session לא נשמר
**פתרון:**
- ודא שיש HTTPS
- בדוק שתיקיית sessions פתוחה לכתיבה
- בדוק את `cookie_secure` ב-`config.php`

---

## 🔄 גיבויים

### מומלץ:
1. **גיבוי DB יומי** - דרך cPanel → Backup
2. **גיבוי קבצים שבועי** - דאונלוד של `public_html/report/`

### לוגי פעילות:
ניתן לראות את כל הפעולות במערכת בטבלת `activity_log` ב-DB.

---

## 📊 ניטור

### מומלץ לבדוק חודשית:
- 📈 גודל מסד הנתונים
- 📁 גודל תיקיית הקבצים
- 📝 לוגי שגיאות (`logs/php-errors.log`)
- 🔒 תוקף ה-SSL certificate (Let's Encrypt מתחדש אוטומטית)

---

**שאלות? פנה אלינו!**
