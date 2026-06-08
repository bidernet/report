# 📋 CHANGELOG - bidernet Reports

תיעוד שינויים בכל גרסה.

---

## v1.0.2 (08/06/2026)

### 🚀 GitHub Integration
- **חדש:** קובץ `.cpanel.yml` ל-Deployment אוטומטי מ-GitHub
- **חדש:** עדכון `.gitignore` להגנת סיסמאות וקבצים רגישים
- כעת ניתן לעדכן את האתר בלחיצה אחת מ-cPanel

### 🔒 שיפורי אבטחה
- `api/config.php` לא יועלה ל-GitHub יותר (סיסמאות DB לא יחשפו)
- `install.php` ו-`server-check.php` ב-`.gitignore`
- הגדרת הרשאות אוטומטית בעת Deploy (`chmod 600` ל-config)

### 📁 קבצים שנוספו
- `.cpanel.yml` - הגדרות Deploy
- `.gitignore` (מעודכן)

### 🔄 איך לעדכן מ-v1.0.1
**שים לב - דורש פעולה ידנית!**
1. ראה `docs/GITHUB_SETUP.md` למדריך מלא
2. תיקון נושא האבטחה: הסרת `config.php` מהיסטוריית Git
3. הפעלת Deploy אוטומטי

---

## v1.0.1 (08/06/2026)

### 🐛 תיקוני באגים
- **תוקן:** סנכרון מ-Facebook נכשל עם שגיאה "יש להגדיר Ad Account ID ו-Access Token" גם כשהחיבור היה תקין
- **הסיבה:** הקוד בדפדפן ניסה לבדוק את ה-Token המוצפן שלא נשלח לדפדפן (מסיבות אבטחה)
- **התיקון:** בדיקת חיבור עכשיו מתבססת על דגל `has_api_connection` שהשרת מחזיר

### 🔧 שיפורי תצוגה
- אינדיקטור "🔗 API" על כרטיסי לקוחות מציג נכון
- "🔗 מחובר ל-Facebook API" בפרופיל הלקוח מציג נכון

### 📁 קבצים שהשתנו
- `index.html` בלבד

### 🔄 איך לעדכן מ-v1.0.0
**פשוט:** החלף רק את הקובץ `index.html` בשרת.
- העלה את `index.html` מהחבילה החדשה לתיקיית `public_html/report/`
- אין צורך לעדכן DB / config / API
- אין צורך להריץ install.php מחדש

---

## v1.0.0 (08/06/2026) - הגרסה הראשונה 🎉

### ✨ תכונות
- מערכת התחברות עם סיסמאות מוצפנות (bcrypt)
- ניהול לקוחות מלא עם לוגו
- דוחות קמפיינים (Facebook + Instagram)
- חיבור Facebook Graph API דרך השרת (פותר CORS)
- סנכרון אוטומטי של קמפיינים
- ייצוא PDF ממותג
- ניהול משתמשים (admin/user)
- יומן פעילות (activity log)
- HTTPS + Security Headers
- מערכת התקנה אוטומטית (install.php)

### 🏗️ ארכיטקטורה
- **Frontend:** HTML/CSS/JavaScript (Single Page)
- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+
- **Auth:** PHP Sessions
- **Encryption:** AES-256-CBC (Tokens), bcrypt (Passwords)

### 📁 מבנה
```
api/
  ├── config.php          (הגדרות)
  ├── db.php              (חיבור DB)
  ├── helpers.php         (פונקציות עזר)
  ├── auth.php            (התחברות)
  ├── clients.php         (לקוחות)
  ├── campaigns.php       (דוחות)
  ├── users.php           (משתמשים)
  └── facebook-proxy.php  (מתווך FB API)
sql/schema.sql            (סכמת DB)
index.html                (המערכת)
login.html                (התחברות)
install.php               (התקנה)
server-check.php          (בדיקת תאימות)
```

---

## 🔮 מתוכנן לגרסאות הבאות

### v1.1.0 - ייבוא CSV (תכנון)
- כפתור "ייבוא CSV מ-Ads Manager" בעמוד הלקוח
- זיהוי אוטומטי של עמודות
- preview לפני אישור

### v1.2.0 - ניהול משתמשים UI (תכנון)
- ממשק גרפי לאדמין להוספת/עריכת משתמשים
- שכחת סיסמה (Reset password)

### v1.3.0 - דוחות מתקדמים (תכנון)
- גרפי השוואת לקוחות
- דוח חודשי אוטומטי
- שליחת PDF במייל
