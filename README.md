# 🚀 bidernet Reports - מערכת דוחות קמפיינים

מערכת ניהול דוחות קמפיינים לפייסבוק ואינסטגרם.
**דומיין:** report.bidernet.co.il

---

## 📦 מה כלול בחבילה?

```
bidernet-deploy/
├── index.html                  # המערכת הראשית (Frontend)
├── login.html                  # דף ההתחברות
├── install.php                 # סקריפט התקנה ראשונית (למחיקה אחרי!)
├── .htaccess                   # הגדרות Apache
├── api/                        # קוד צד שרת (PHP)
│   ├── .htaccess              # הגנת תיקיית API
│   ├── config.php             # ⚠️ הגדרות - יש לעדכן!
│   ├── db.php                 # חיבור למסד נתונים
│   ├── helpers.php            # פונקציות עזר
│   ├── auth.php               # התחברות/התנתקות
│   ├── clients.php            # ניהול לקוחות
│   ├── campaigns.php          # ניהול דוחות
│   ├── users.php              # ניהול משתמשים
│   └── facebook-proxy.php     # מתווך ל-Facebook API
├── sql/
│   └── schema.sql             # סכמת מסד נתונים
├── docs/
│   ├── INSTALL.md             # מדריך התקנה מפורט
│   └── API.md                 # תיעוד ה-API
└── logs/                      # יומני שגיאות (ייווצר אוטומטית)
```

---

## ⚡ התקנה מהירה (5 דקות)

### 1️⃣ צור מסד נתונים ב-cPanel

1. היכנס ל-**cPanel** → **MySQL Databases**
2. צור מסד נתונים בשם: `bidernet_reports` (או דומה)
3. צור משתמש חדש עם **סיסמה חזקה**
4. **הוסף את המשתמש למסד עם כל ההרשאות (ALL PRIVILEGES)**
5. **שמור** את הפרטים: שם DB, שם משתמש, סיסמה

### 2️⃣ ערוך את `api/config.php`

פתח את `api/config.php` בעורך והחלף את הערכים הבאים:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'username_bidernet_reports');  // ⚠️ עם הקידומת של cPanel
define('DB_USER', 'username_bidernet_user');     // ⚠️ עם הקידומת של cPanel
define('DB_PASS', 'הסיסמה_שיצרת');

// צור מפתחות אקראיים (אפשר ב: https://www.random.org/strings/)
define('ENCRYPTION_KEY', 'מחרוזת_אקראית_64_תווים');
define('JWT_SECRET', 'מחרוזת_אקראית_אחרת');
```

### 3️⃣ העלה את הקבצים לשרת

העלה את כל התוכן של תיקיית `bidernet-deploy` לתוך התיקייה של הדומיין:

- אם זה **דומיין משנה (subdomain)** `report.bidernet.co.il` → לתיקיית `public_html/report/`
- אם זה **דומיין ראשי** → לתיקיית `public_html/`

### 4️⃣ הרץ את סקריפט ההתקנה

פתח בדפדפן:
```
https://report.bidernet.co.il/install.php
```

- מלא את פרטי האדמין
- לחץ "התקן את המערכת"
- **🚨 חשוב מאוד: מחק את הקובץ `install.php` אחרי ההתקנה!**

### 5️⃣ היכנס למערכת

פתח:
```
https://report.bidernet.co.il/
```

- התחבר עם השם משתמש והסיסמה שיצרת
- **מומלץ:** ערוך את המשתמש שלך ושנה סיסמה חזקה יותר

---

## 🔐 דרישות שרת

| דרישה | גרסה מינימלית |
|---|---|
| PHP | 7.4+ (מומלץ 8.0+) |
| MySQL / MariaDB | 5.7+ / 10.2+ |
| Apache | עם mod_rewrite |
| PHP Extensions | PDO, PDO_MySQL, OpenSSL, cURL, JSON |
| HTTPS | חובה (SSL) |

---

## 🌟 תכונות עיקריות

### דשבורד
- 📊 סקירת ביצועים כללית (תפוצה, מעורבות, לידים, תקציב)
- 🎯 סינון לפי לקוח ופלטפורמה
- 📈 גרפי מגמות ועוגות
- ⚖️ השוואת תקופות

### ניהול לקוחות
- 👥 פרופיל מפורט לכל לקוח
- 🖼️ העלאת לוגו ללקוח
- 📜 היסטוריית דוחות מסודרת לפי תאריכים
- 🔗 חיבור Facebook API לכל לקוח

### Facebook Integration
- 🔄 סנכרון אוטומטי של קמפיינים
- 📊 ייבוא Reach, Impressions, Clicks, Leads, Engagement
- 🔒 Tokens מוצפנים בשרת (AES-256)

### דוחות PDF
- 📄 ייצוא PDF ממותג ללקוח ספציפי
- 🎨 עיצוב מקצועי עם לוגו

### ניהול משתמשים (אדמין)
- 👤 הוספת משתמשים חדשים (עד 5 מומלץ)
- 🔐 ניהול הרשאות (admin/user)
- 📝 יומן פעילות

---

## 🆘 פתרון בעיות

### "Database connection failed"
- בדוק את הפרטים ב-`api/config.php`
- ודא שהמשתמש מקושר למסד עם כל ההרשאות
- בדוק שה-prefix של cPanel נכלל בשמות

### "Failed to fetch" ב-Facebook sync
- ודא שה-PHP cURL מותקן בשרת
- בדוק שיש לך SSL פעיל בדומיין
- בדוק שה-Token תקף (יש לחדש כל 60 יום)

### "Forbidden" / 403
- ודא שיש HTTPS פעיל
- בדוק את הרשאות הקבצים (`644` לקבצים, `755` לתיקיות)

### לוגים
שגיאות PHP נשמרות ב-`logs/php-errors.log` (אם מוגדר ב-config).

---

## 🔄 חיבור ל-GitHub (אופציונלי)

לאחר ההתקנה, אם תרצה לעדכן בעתיד דרך GitHub:

1. צור **Repository פרטי** ב-GitHub
2. העלה את הקבצים שם
3. **🚨 חשוב:** הוסף את `api/config.php` ל-`.gitignore` (אל תעלה סיסמאות!)
4. השתמש ב-cPanel Git Version Control לסנכרון אוטומטי

---

## 📞 תמיכה

- **מסמך התקנה מפורט:** ראה `docs/INSTALL.md`
- **תיעוד API:** ראה `docs/API.md`
- **בעיה / שאלה:** פנה אלינו

---

## 📋 רשימת בדיקה לפני העלאה לפרודקשן

- [ ] עדכון `api/config.php` עם פרטי DB נכונים
- [ ] יצירת `ENCRYPTION_KEY` ו-`JWT_SECRET` אקראיים
- [ ] הקמת מסד נתונים ב-cPanel
- [ ] העלאת קבצים ב-FTP/cPanel
- [ ] התקנה דרך `install.php`
- [ ] **מחיקת `install.php`** מהשרת
- [ ] ודא שיש SSL/HTTPS פעיל
- [ ] בדיקת התחברות לאתר
- [ ] שינוי סיסמת אדמין ראשונה
- [ ] הוספת לקוח לדוגמה ובדיקת המערכת

---

**© 2026 bidernet group | report.bidernet.co.il**
