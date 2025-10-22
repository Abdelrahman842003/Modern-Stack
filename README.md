# 📋 Task Management API - Modern Stack

**Production-grade RESTful API** لإدارة المهام مع Laravel 11 + Node.js Microservice + Docker

[![Tests](https://img.shields.io/badge/Tests-69%20Passing-success?style=flat-square)](#-الاختبارات)
[![License](https://img.shields.io/badge/License-MIT-blue?style=flat-square)](LICENSE)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=flat-square&logo=docker&logoColor=white)](#-التشغيل-السريع)

## 📦 Resources

- **📋 [Project Requirements](task.pdf)** - Full evaluation task specifications
- **🔥 [Postman Collection](Task-Management-API-Updated.postman_collection.json)** - Complete API endpoints

## 🎯 المميزات

✅ Laravel 11 + PostgreSQL + Redis  
✅ Node.js Microservice للإشعارات  
✅ HMAC Webhook Security + Idempotency  
✅ 69 اختبار (60 Pest + 9 Jest)  
✅ Docker Compose جاهز  
✅ PHPStan Level 8 + Laravel Pint

## 🚀 التقنيات

**Backend:** PHP 8.3, Laravel 11, Sanctum, PostgreSQL 16, Redis 7.2  
**Microservice:** Node.js 20, Express, HMAC-SHA256, Idempotency  
**Testing:** Pest (60 tests), Jest (9 tests), PHPStan Level 8  
**DevOps:** Docker Compose, Makefile

---

## 🏗️ المعمارية

### 📐 System Architecture

```
┌──────────┐
│  Client  │
└────┬─────┘
     │ HTTP Request + Bearer Token
     ▼
┌─────────────────────────────────────────┐
│        Laravel 11 API (Port 8000)       │
│  ┌────────────────────────────────┐    │
│  │  Middleware Stack              │    │
│  │  • CORS                         │    │
│  │  • Sanctum Auth                 │    │
│  │  • Rate Limiting                │    │
│  │  • Logging                      │    │
│  └────────────────────────────────┘    │
│  ┌────────────────────────────────┐    │
│  │  Controllers                    │    │
│  │  • AuthController               │    │
│  │  • TaskController               │    │
│  └────────┬───────────────────────┘    │
│           ▼                             │
│  ┌────────────────────────────────┐    │
│  │  Services (Business Logic)     │    │
│  │  • AuthService                  │    │
│  │  • TaskService                  │    │
│  └────────┬───────────────────────┘    │
│           ▼                             │
│  ┌────────────────────────────────┐    │
│  │  Models (Eloquent ORM)         │    │
│  │  • User                         │    │
│  │  • Task                         │    │
│  └────────┬───────────────────────┘    │
└───────────┼─────────────────────────────┘
            │
     ┌──────┴──────┐
     ▼             ▼
┌─────────┐   ┌─────────┐
│PostgreSQL│   │  Redis  │
│Port 5433│   │Port 6380│
│         │   │         │
│• Users  │   │• Cache  │
│• Tasks  │   │• Sessions│
│• Tokens │   │• Queue  │
└─────────┘   └─────────┘
```

### 🔄 Complete Request Flow

#### 1️⃣ **تسجيل المستخدم (Registration Flow)**

```
Client                    Laravel                Database
  │                         │                       │
  ├─POST /api/register──────►                       │
  │  {name, email, pass}    │                       │
  │                         │                       │
  │                    Middleware                   │
  │                      (CORS)                     │
  │                         │                       │
  │                    Validation                   │
  │                 (RegisterRequest)               │
  │                         │                       │
  │                    AuthController               │
  │                         │                       │
  │                    AuthService                  │
  │                    • Hash Password              │
  │                    • Create User────────────────►
  │                    • Generate Token             │
  │                         │                       │
  │◄────Response 201────────┤                       │
  │  {user, token}          │                       │
```

#### 2️⃣ **تسجيل الدخول (Login Flow)**

```
Client                    Laravel                Database/Redis
  │                         │                       │
  ├─POST /api/login─────────►                       │
  │  {email, password}      │                       │
  │                         │                       │
  │                    Validation                   │
  │                 (LoginRequest)                  │
  │                         │                       │
  │                    AuthService                  │
  │                    • Check Credentials──────────►
  │                    • Verify Password    PostgreSQL
  │                    • Create Token───────────────►
  │                         │              Sanctum Tokens
  │                    • Cache User─────────────────►
  │                         │                    Redis
  │◄────Response 200────────┤                       │
  │  {user, token}          │                       │
```

#### 3️⃣ **إنشاء مهمة (Create Task Flow)**

```
Client                    Laravel                Database
  │                         │                       │
  ├─POST /api/tasks─────────►                       │
  │  Bearer Token           │                       │
  │  {title, due_date}      │                       │
  │                         │                       │
  │                    Middleware                   │
  │                 • Sanctum Auth──────────────────►
  │                   (Verify Token)      Check Token
  │                 • Rate Limiting                 │
  │                         │                       │
  │                    Validation                   │
  │                 (StoreTaskRequest)              │
  │                         │                       │
  │                    TaskController               │
  │                         │                       │
  │                    TaskService                  │
  │                    • Create Task────────────────►
  │                    • Link to User    INSERT tasks
  │                         │                       │
  │◄────Response 201────────┤                       │
  │  {task}                 │                       │
```

#### 4️⃣ **إتمام مهمة + Webhook (Complete Task Flow)** 🔥

```
Client          Laravel              Database         Node.js Service
  │               │                     │                    │
  ├─POST /tasks/1/complete──────►       │                    │
  │  Bearer Token │                     │                    │
  │               │                     │                    │
  │          Sanctum Auth               │                    │
  │               │                     │                    │
  │          TaskService                │                    │
  │          • Find Task────────────────►                    │
  │          • Check Owner   SELECT     │                    │
  │          • Update Status────────────►                    │
  │               │          UPDATE tasks                    │
  │               │          SET status='done'               │
  │               │                     │                    │
  │          Event Fired                │                    │
  │          TaskCompleted              │                    │
  │               │                     │                    │
  │          Listener Triggered         │                    │
  │          SendTaskCompletedWebhook   │                    │
  │          • Build Payload            │                    │
  │          • Generate HMAC            │                    │
  │            sha256(secret+payload)   │                    │
  │          • Add X-Signature Header   │                    │
  │               │                     │                    │
  │               ├─────POST /notify────────────────────────►
  │               │     X-Signature: sha256=abc123           │
  │               │     {userId, taskId, message}            │
  │               │                     │                    │
  │               │                     │         Middleware │
  │               │                     │      • verifySignature
  │               │                     │        Calculate HMAC
  │               │                     │        Compare Signatures
  │               │                     │                    │
  │               │                     │      🔐 Idempotency Check
  │               │                     │      webhook:{userId}:{taskId}
  │               │                     │      If duplicate → Return 200
  │               │                     │                    │
  │               │                     │         ✅ Valid & Unique
  │               │                     │         Store in Redis
  │               │                     │         TTL: 600s
  │               │                     │                    │
  │               │◄────Response 200─────────────────────────┤
  │               │     {success: true} │                    │
  │               │                     │                    │
  │◄──Response 200─┤                     │                    │
  │  {task}       │                     │                    │
```

#### 5️⃣ **قراءة الإشعارات (Get Notifications Flow)**

```
Client                              Node.js Service
  │                                       │
  ├─GET /notifications──────────────────► │
  │                                       │
  │                                  Retrieve from
  │                                  Memory Array
  │                                       │
  │◄────Response 200──────────────────────┤
  │  {data: [...notifications]}           │
```

### 🔐 HMAC Security Flow (التفاصيل)

```
Laravel Side:                          Node.js Side:
─────────────                          ─────────────

1. Build Payload                       1. Receive Request
   payload = {                            headers['X-Signature']
     userId: 1,                           = "sha256=abc123..."
     taskId: 5,                           body = {userId, taskId...}
     message: "Task completed",
     timestamp: "2025-10-21..."        2. Calculate Expected Signature
   }                                      rawBody = JSON.stringify(body)
                                          expected = crypto
2. Convert to JSON                          .createHmac('sha256', SECRET)
   jsonPayload = JSON.stringify(payload)    .update(rawBody)
                                             .digest('hex')
3. Calculate HMAC
   signature = crypto                  3. Extract Received Signature
     .createHmac('sha256', SECRET)        received = headers['X-Signature']
     .update(jsonPayload)                           .replace('sha256=', '')
     .digest('hex')
                                       4. Compare (Timing-Safe)
4. Add Header                             if (received === expected) {
   headers = {                              ✅ VALID - Process webhook
     'X-Signature': `sha256=${signature}`,  store notification
     'Content-Type': 'application/json'   } else {
   }                                        ❌ INVALID - Return 401
                                            reject request
5. Send POST Request                     }
   HTTP POST to node-notify:3001/notify
```

### 📊 Data Flow Summary

```
┌─────────────────────────────────────────────────────────┐
│                    Complete Cycle                       │
└─────────────────────────────────────────────────────────┘

1. User registers → Laravel stores in PostgreSQL
2. User logs in → Laravel generates Sanctum token → Cached in Redis
3. User creates task → Laravel stores in PostgreSQL
4. User completes task → Laravel:
   • Updates task status in PostgreSQL
   • Fires TaskCompleted event
   • Listener sends HMAC-signed webhook to Node.js
5. Node.js receives webhook:
   • Verifies HMAC signature
   • Stores notification in memory
6. User can retrieve notifications from Node.js
```

### 🎯 Key Components Interaction

| Component | Role | Communication |
|-----------|------|---------------|
| **Client** | يرسل HTTP Requests | → Laravel API |
| **Laravel API** | معالجة Business Logic | → PostgreSQL, Redis, Node.js |
| **PostgreSQL** | تخزين البيانات الأساسية | ← Laravel |
| **Redis** | Cache + Sessions + Queue | ← Laravel |
| **Node.js Service** | استقبال Webhooks | ← Laravel (HMAC), → Memory |
| **Sanctum** | Token Authentication | Laravel ↔ Redis |
| **Event System** | Decoupling | Laravel Internal |

---

## ⚡ التشغيل السريع

### المتطلبات
- Docker & Docker Compose
- Make (اختياري)

### التثبيت

```bash
git clone https://github.com/Abdelrahman842003/Modern-Stack.git
cd Modern-Stack
make setup
```

**أو يدوياً:**
```bash
docker compose up -d
docker compose exec laravel-app composer install
docker compose exec node-notify npm install
docker compose exec laravel-app php artisan key:generate
docker compose exec laravel-app php artisan migrate:fresh --seed
```

### الوصول

| الخدمة | URL |
|--------|-----|
| Laravel API | http://localhost:8000 |
| Node.js | http://localhost:3001 |
| PostgreSQL | localhost:5433 |
| Redis | localhost:6380 |

---

## 📚 API Endpoints

### Authentication
```bash
# تسجيل
POST /api/register
{
  "name": "أحمد",
  "email": "ahmed@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}

# دخول
POST /api/login
{
  "email": "ahmed@example.com",
  "password": "password123"
}

# خروج
POST /api/logout
Authorization: Bearer {token}

# المستخدم الحالي
GET /api/auth/me
Authorization: Bearer {token}
```

### Tasks (تتطلب Token)
```bash
# قائمة المهام
GET /api/tasks?status=pending&page=1

# إنشاء مهمة
POST /api/tasks
{
  "title": "إنهاء المشروع",
  "description": "استكمال API",
  "due_date": "2025-12-31",
  "status": "pending"
}

# تفاصيل مهمة
GET /api/tasks/{id}

# تحديث
PUT /api/tasks/{id}

# حذف (Soft Delete)
DELETE /api/tasks/{id}

# إتمام (يرسل Webhook)
POST /api/tasks/{id}/complete
```

### Node.js Service
```bash
# Health Check
GET http://localhost:3001/health

# قائمة الإشعارات
GET http://localhost:3001/notifications
```

---

## 🧪 الاختبارات

```bash
# كل الاختبارات (69 test)
make test

# Laravel فقط (60 test)
make test-laravel

# Node.js فقط (9 test)
make test-node

# مع Coverage
docker compose exec laravel-app php artisan test --coverage
```

---

## 🛠️ أوامر التطوير

### Docker
```bash
make up          # تشغيل
make down        # إيقاف
make rebuild     # إعادة بناء
make logs        # السجلات
```

### Code Quality
```bash
make lint        # Laravel Pint (PSR-12)
make analyse     # PHPStan Level 8
make lint-node   # ESLint (Airbnb)
make ci          # كل الفحوصات
```

### Database
```bash
make migrate           # تشغيل Migrations
make migrate-fresh     # إعادة بناء + Seeders
make db-shell          # PostgreSQL Shell
```

---

## 🔐 الأمان

✅ **Sanctum Token Auth** - Personal Access Tokens  
✅ **HMAC Signatures** - SHA-256 للـ Webhooks  
✅ **Idempotency Keys** - منع الإشعارات المكررة  
✅ **Request Validation** - Form Request Classes  
✅ **Rate Limiting** - 60 req/min (API), 10 req/min (Auth)  
✅ **SQL Injection Protection** - Eloquent ORM  
✅ **CORS** - مضبوط  
✅ **Soft Deletes** - البيانات محفوظة

---

## 📊 Database Schema

```sql
-- Users
CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255),
    email VARCHAR(255) UNIQUE,
    password VARCHAR(255),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Tasks
CREATE TABLE tasks (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT REFERENCES users(id),
    title VARCHAR(255),
    description TEXT,
    due_date DATE,
    status VARCHAR(50) DEFAULT 'pending', -- pending | done
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP -- Soft Delete
);

-- Sanctum Tokens
CREATE TABLE personal_access_tokens (
    id BIGSERIAL PRIMARY KEY,
    tokenable_type VARCHAR(255),
    tokenable_id BIGINT,
    name VARCHAR(255),
    token VARCHAR(64) UNIQUE,
    abilities TEXT,
    created_at TIMESTAMP
);
```

---

## 💡 مثال سريع

```bash
# 1. تسجيل
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{"name":"أحمد","email":"ahmed@test.com","password":"pass123","password_confirmation":"pass123"}'

# 2. حفظ الـ Token
export TOKEN="1|abc123..."

# 3. إنشاء مهمة
curl -X POST http://localhost:8000/api/tasks \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"مهمة جديدة","due_date":"2025-12-31","status":"pending"}'

# 4. إتمام المهمة (webhook يُرسل تلقائياً)
curl -X POST http://localhost:8000/api/tasks/1/complete \
  -H "Authorization: Bearer $TOKEN"

# 5. التحقق من الإشعار في Node.js
curl http://localhost:3001/notifications
```

---

## � Documentation & Testing

### Postman Collection
استيراد [`Task-Management-API-Updated.postman_collection.json`](Task-Management-API-Updated.postman_collection.json) إلى Postman للحصول على:
- ✅ جميع الـ Endpoints (Authentication, Tasks, Node.js Service)
- ✅ Environment Variables تلقائية
- ✅ أمثلة جاهزة للتجربة
- ✅ Test Scripts

### Project Requirements
راجع [`task.pdf`](task.pdf) للاطلاع على:
- 📋 المتطلبات الكاملة للمشروع
- 🎯 معايير التقييم
- 🏗️ المواصفات التقنية المطلوبة

---

## 🎯 Code Quality

✅ Laravel Pint (PSR-12)  
✅ PHPStan Level 8  
✅ 60 Pest Tests (Laravel)  
✅ 9 Jest Tests (Node.js)  
✅ Zero Console Logs  
✅ Production-Ready

---

## 🔧 Environment Variables

### Laravel (.env)
```env
APP_KEY=base64:...                    # php artisan key:generate
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=taskdb
DB_USERNAME=taskuser
DB_PASSWORD=taskpass
REDIS_HOST=redis
WEBHOOK_URL=http://node-notify:3001/notify
WEBHOOK_SECRET=your-secret-key        # نفس المفتاح في Node.js
```

### Node.js (.env)
```env
PORT=3001
NODE_ENV=development
WEBHOOK_SECRET=your-secret-key        # نفس المفتاح في Laravel
CORS_ORIGIN=http://localhost:8000
```

⚠️ **مهم:** استخدم `openssl rand -hex 32` لتوليد `WEBHOOK_SECRET` قوي

---

## 👨‍💻 المطور

**Abdelrahman**  
📧 [aeid38858@gmail.com](mailto:aeid38858@gmail.com)  
🐙 [@Abdelrahman842003](https://github.com/Abdelrahman842003)

---

<div align="center">

**بُني بـ ❤️ باستخدام Laravel 11, Node.js, PostgreSQL, Redis & Docker**

![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=flat-square&logo=laravel&logoColor=white)
![Node.js](https://img.shields.io/badge/Node.js-20.x-339933?style=flat-square&logo=node.js&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?style=flat-square&logo=postgresql&logoColor=white)
![Redis](https://img.shields.io/badge/Redis-7.2-DC382D?style=flat-square&logo=redis&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=flat-square&logo=docker&logoColor=white)

</div>
