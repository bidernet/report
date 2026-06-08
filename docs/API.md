# 📡 תיעוד API - bidernet Reports

כל ה-API endpoints משתמשים ב-JSON ובסשנים של PHP. כל הבקשות דורשות אימות (חוץ מ-login).

**Base URL:** `https://report.bidernet.co.il/api`

---

## 🔐 Authentication

### POST /api/auth.php?action=login
התחברות למערכת.

**Request:**
```json
{
  "username": "admin",
  "password": "your_password"
}
```

**Response:**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "username": "admin",
    "email": "admin@bidernet.co.il",
    "full_name": "שרון בידר",
    "role": "admin"
  }
}
```

### POST /api/auth.php?action=logout
התנתקות.

### GET /api/auth.php?action=me
פרטי משתמש נוכחי.

### POST /api/auth.php?action=change-password
שינוי סיסמה.
```json
{
  "current_password": "old",
  "new_password": "new_minimum_8_chars"
}
```

---

## 👥 Clients

### GET /api/clients.php
רשימת כל הלקוחות.

**Response:**
```json
{
  "success": true,
  "clients": [
    {
      "id": 1,
      "name": "ליידי קומפורט",
      "logo": "data:image/...",
      "ad_account_id": "act_123",
      "ad_account_name": "Account Name",
      "has_api_connection": true,
      "last_synced_at": "2026-06-08 12:00:00",
      "reports_count": 5,
      "total_reach": 250000,
      "total_leads": 87,
      "total_budget": 12500.00,
      "created_at": "2026-01-15 10:00:00"
    }
  ]
}
```

### GET /api/clients.php?id=1
לקוח בודד.

### GET /api/clients.php?id=1&with_token=1
לקוח כולל ה-Access Token (להצגה בעריכה).

### POST /api/clients.php
הוספת לקוח.

**Request:**
```json
{
  "name": "שם הלקוח",
  "logo": "data:image/jpeg;base64,...",
  "ad_account_id": "act_123456",
  "ad_account_name": "Account Name",
  "access_token": "EAAxxxxxx..."
}
```

### PUT /api/clients.php?id=1
עדכון לקוח.

### DELETE /api/clients.php?id=1
מחיקת לקוח (מוחק גם את כל הדוחות שלו).

---

## 📊 Campaigns (Reports)

### GET /api/campaigns.php
כל הדוחות.

### GET /api/campaigns.php?client_id=1
דוחות של לקוח ספציפי.

### GET /api/campaigns.php?id=10
דוח בודד.

### POST /api/campaigns.php
הוספת דוח חדש.

**Request:**
```json
{
  "client_id": 1,
  "name": "מבצע קיץ",
  "platform": "facebook",
  "campaign_type": "leads",
  "start_date": "2026-06-01",
  "end_date": "2026-06-15",
  "reach": 50000,
  "impressions": 120000,
  "clicks": 2300,
  "likes": 1200,
  "comments": 340,
  "shares": 180,
  "leads": 87,
  "conversions": 32,
  "budget": 4500,
  "notes": "טקסט הערות"
}
```

### PUT /api/campaigns.php?id=10
עדכון דוח.

### DELETE /api/campaigns.php?id=10
מחיקת דוח.

---

## 🔄 Facebook Proxy

מתווך לפייסבוק (פותר CORS).

### POST /api/facebook-proxy.php?action=test
בדיקת חיבור.

**Request (אפשרות א - עם פרטים ישירים):**
```json
{
  "ad_account_id": "act_123",
  "access_token": "EAAxxxxx..."
}
```

**Request (אפשרות ב - עם client_id):**
```json
{
  "client_id": 1
}
```

**Response:**
```json
{
  "success": true,
  "account": {
    "name": "Account Name",
    "account_status": 1,
    "currency": "ILS",
    "timezone_name": "Asia/Jerusalem"
  }
}
```

### POST /api/facebook-proxy.php?action=sync
סנכרון קמפיינים מ-Facebook.

**Request:**
```json
{
  "client_id": 1,
  "start_date": "2026-05-01",
  "end_date": "2026-06-01"
}
```

**Response:**
```json
{
  "success": true,
  "account": { "name": "...", "currency": "ILS" },
  "total_campaigns": 12,
  "imported": [
    {
      "id": 25,
      "name": "Campaign Name",
      "is_new": true,
      "leads": 15,
      "budget": 1200,
      "platform": "facebook",
      "start_date": "2026-05-01",
      "end_date": "2026-05-15"
    }
  ],
  "errors": []
}
```

### POST /api/facebook-proxy.php?action=accounts
רשימת חשבונות פרסום של המשתמש (לפי token).

```json
{
  "access_token": "EAAxxxxx..."
}
```

---

## 👤 Users (Admin only)

### GET /api/users.php
רשימת משתמשים.

### POST /api/users.php
הוספת משתמש.
```json
{
  "username": "newuser",
  "email": "user@bidernet.co.il",
  "password": "min_8_chars",
  "full_name": "שם מלא",
  "role": "user"
}
```

### PUT /api/users.php?id=2
עדכון משתמש.

### DELETE /api/users.php?id=2
מחיקת משתמש.

---

## ⚠️ קודי שגיאה

| HTTP | משמעות |
|---|---|
| 200 | הצלחה |
| 400 | בקשה לא תקינה (חסרים נתונים) |
| 401 | לא מאומת (יש להתחבר) |
| 403 | אין הרשאה (admin בלבד) |
| 404 | לא נמצא |
| 405 | Method לא תומך |
| 500 | שגיאת שרת |

---

## 🔒 אבטחה

- **כל הטוקנים של Facebook מוצפנים** ב-DB עם AES-256-CBC
- **סיסמאות מוצפנות** עם bcrypt
- **Sessions** מאובטחים עם httpOnly cookies
- **HTTPS חובה** - cookies לא יישלחו ב-HTTP
- **SQL Injection** - מוגן עם Prepared Statements
- **XSS** - הגנה עם Content Security Policy headers

---

## 📝 דוגמת שימוש ב-JavaScript

```javascript
async function api(path, options = {}) {
  const resp = await fetch('/api' + path, {
    credentials: 'include',
    headers: { 'Content-Type': 'application/json', ...options.headers },
    ...options,
    body: options.body ? JSON.stringify(options.body) : undefined
  });
  const data = await resp.json();
  if (!resp.ok) throw new Error(data.error);
  return data;
}

// התחברות
await api('/auth.php?action=login', {
  method: 'POST',
  body: { username: 'admin', password: '...' }
});

// קבלת לקוחות
const { clients } = await api('/clients.php');

// סנכרון
const result = await api('/facebook-proxy.php?action=sync', {
  method: 'POST',
  body: { client_id: 1, start_date: '2026-05-01', end_date: '2026-06-01' }
});
```
