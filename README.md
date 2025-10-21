# 📋 Task Management API - Modern Stack

نظام **إدارة المهام (Task Management)** احترافي مبني بـ **Laravel 11** وخدمة **Node.js Microservice** للإشعارات.

## 🎯 نظرة عامة على المشروع

هذا المشروع عبارة عن **RESTful API** متكامل لإدارة المهام مع:
- ✅ **Backend قوي** باستخدام Laravel 11
- ✅ **Microservice مستقل** لمعالجة Webhooks بـ Node.js
- ✅ **اختبارات شاملة** (73 اختبار: 52 Laravel + 21 Node.js)
- ✅ **توثيق API كامل** بـ OpenAPI 3.1
- ✅ **CI/CD Pipeline** جاهز للإنتاج
- ✅ **Docker Containerization** كامل

---

## 🚀 التقنيات المستخدمة (Tech Stack)

### 🔵 Backend - Laravel 11
| التقنية | الإصدار | الاستخدام |
|---------|---------|-----------|
| **PHP** | 8.3.x | لغة البرمجة الأساسية |
| **Laravel** | 11.x | Framework الـ Backend |
| **PostgreSQL** | 16.x | قاعدة البيانات الرئيسية |
| **Redis** | 7.2.x | Cache & Session Storage |
| **Sanctum** | 4.0.x | Token Authentication |
| **Pest** | 3.x | Testing Framework (52 tests) |
| **PHPStan** | Level 8 | Static Analysis |
| **Laravel Pint** | Latest | Code Style (PSR-12) |
| **L5-Swagger** | Latest | OpenAPI 3.1 Documentation |

### 🟢 Microservice - Node.js 20
| التقنية | الإصدار | الاستخدام |
|---------|---------|-----------|
| **Node.js** | 20.x LTS | Runtime Environment |
| **Express** | 5.x | Web Framework |
| **Jest** | 29.x | Testing (21 tests) |
| **Supertest** | 6.x | HTTP Testing |
| **ESLint** | 8.56.x | Code Linting (Airbnb) |
| **Helmet** | 7.x | Security Headers |
| **CORS** | 2.x | Cross-Origin Resource Sharing |
| **Morgan** | 1.x | HTTP Request Logger |

### 🐳 Infrastructure & DevOps
- **Docker**: 24+ مع Docker Compose v2
- **GitHub Actions**: CI/CD Pipeline آلي
- **Makefile**: أوامر تطوير مبسطة
- **Git**: Version Control

---

## � هيكل المشروع (Project Structure)

```
Modern-Stack/
├── 📂 docker/                          # Docker Configuration
│   ├── laravel.Dockerfile             # Laravel Container (PHP 8.3 + Extensions)
│   └── node.Dockerfile                # Node.js Container (Node 20 LTS)
│
├── 📂 laravel/                         # Laravel 11 Application
│   ├── 📂 app/
│   │   ├── 📂 Events/
│   │   │   └── TaskCompleted.php     # حدث إتمام المهمة
│   │   ├── 📂 Http/
│   │   │   ├── 📂 Controllers/
│   │   │   │   └── Api/
│   │   │   │       ├── AuthController.php      # تسجيل/دخول/خروج
│   │   │   │       └── TaskController.php      # CRUD للمهام
│   │   │   ├── 📂 Middleware/
│   │   │   │   └── LogApiRequests.php         # تسجيل طلبات API
│   │   │   ├── 📂 Requests/
│   │   │   │   ├── LoginRequest.php           # Validation الدخول
│   │   │   │   ├── RegisterRequest.php        # Validation التسجيل
│   │   │   │   ├── StoreTaskRequest.php       # Validation إنشاء مهمة
│   │   │   │   └── UpdateTaskRequest.php      # Validation تحديث مهمة
│   │   │   └── 📂 Resources/
│   │   │       ├── TaskResource.php           # JSON Response للمهمة
│   │   │       └── UserResource.php           # JSON Response للمستخدم
│   │   ├── 📂 Listeners/
│   │   │   └── SendTaskCompletedWebhook.php  # إرسال Webhook لـ Node.js
│   │   ├── 📂 Models/
│   │   │   ├── Task.php                       # Model المهام
│   │   │   └── User.php                       # Model المستخدمين
│   │   ├── 📂 Services/
│   │   │   ├── AuthService.php               # منطق المصادقة
│   │   │   └── TaskService.php               # منطق المهام (Service Pattern)
│   │   └── 📂 Traits/
│   │       └── ApiResponseTrait.php          # استجابات API موحدة
│   ├── 📂 config/
│   │   ├── cors.php                          # إعدادات CORS
│   │   ├── l5-swagger.php                    # إعدادات OpenAPI
│   │   ├── sanctum.php                       # إعدادات المصادقة
│   │   └── services.php                      # Webhook URL & Secret
│   ├── 📂 database/
│   │   ├── 📂 factories/
│   │   │   ├── TaskFactory.php               # Factory للمهام
│   │   │   └── UserFactory.php               # Factory للمستخدمين
│   │   ├── 📂 migrations/
│   │   │   ├── 0001_01_01_000000_create_users_table.php
│   │   │   ├── 0001_01_01_000001_create_cache_table.php
│   │   │   ├── 0001_01_01_000002_create_jobs_table.php
│   │   │   ├── 2025_10_20_000000_create_tasks_table.php
│   │   │   └── 2025_10_20_210145_create_personal_access_tokens_table.php
│   │   └── 📂 seeders/
│   │       └── DatabaseSeeder.php            # بيانات تجريبية
│   ├── 📂 routes/
│   │   ├── api.php                           # API Routes (11 endpoints)
│   │   ├── console.php                       # Artisan Commands
│   │   └── web.php                           # Web Routes
│   ├── 📂 tests/
│   │   ├── 📂 Feature/
│   │   │   ├── AuthenticationTest.php        # 11 اختبار للمصادقة
│   │   │   └── TaskManagementTest.php        # 20 اختبار للمهام
│   │   └── 📂 Unit/
│   │       ├── AuthServiceTest.php           # 8 اختبارات للـ Service
│   │       └── TaskServiceTest.php           # 11 اختبار للـ Service
│   ├── .env.example                          # مثال متغيرات البيئة
│   ├── composer.json                         # Dependencies الـ PHP
│   ├── phpstan.neon                          # إعدادات PHPStan Level 8
│   └── phpunit.xml                           # إعدادات Pest Testing
│
├── 📂 node-notify/                    # Node.js Notification Microservice
│   ├── 📂 src/
│   │   ├── index.js                          # Express Server
│   │   └── 📂 utils/
│   │       └── signature.js                  # HMAC Signature Utils
│   ├── 📂 tests/
│   │   ├── endpoints.test.js                 # 12 اختبار للـ Endpoints
│   │   └── signature.test.js                 # 9 اختبارات للـ Signature
│   ├── .env.example                          # مثال متغيرات البيئة
│   ├── .eslintrc.js                          # ESLint Config (Airbnb)
│   ├── jest.config.js                        # Jest Testing Config
│   └── package.json                          # Dependencies الـ Node
│
├── 📂 .github/workflows/              # CI/CD Pipeline
│   └── ci.yml                                # 3 Jobs: Laravel + Node + Docker
│
├── docker-compose.yml                 # تكوين الـ 4 Services
├── Makefile                           # أوامر التطوير المختصرة
└── README.md                          # هذا الملف
```

```

---

## 🏗️ معمارية النظام (System Architecture)

### � تدفق البيانات الكامل

```
┌─────────────────────────────────────────────────────────────────┐
│                        CLIENT / API Consumer                     │
└─────────────────────────────────────────────────────────────────┘
                                  │
                    HTTP Request (JSON + Bearer Token)
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────┐
│                    🌐 LARAVEL 11 API (Port 8000)                │
├─────────────────────────────────────────────────────────────────┤
│  Middleware Stack:                                              │
│  ├─ HandleCors           → إدارة CORS                         │
│  ├─ Sanctum Auth         → التحقق من Token                    │
│  ├─ ThrottleRequests     → Rate Limiting (10/min auth, 60/min) │
│  └─ LogApiRequests       → تسجيل الطلبات                       │
├─────────────────────────────────────────────────────────────────┤
│  Routes (api.php):                                              │
│  ├─ POST /api/register   → AuthController@register            │
│  ├─ POST /api/login      → AuthController@login               │
│  ├─ POST /api/logout     → AuthController@logout              │
│  ├─ GET  /api/auth/me    → AuthController@me                  │
│  └─ Resource /api/tasks  → TaskController (CRUD + Complete)   │
├─────────────────────────────────────────────────────────────────┤
│  Controllers → Services → Models → Database                     │
│  ├─ AuthController   → AuthService   → User Model             │
│  └─ TaskController   → TaskService   → Task Model             │
├─────────────────────────────────────────────────────────────────┤
│  Events & Listeners:                                            │
│  └─ TaskCompleted Event → SendTaskCompletedWebhook Listener   │
└─────────────────────────────────────────────────────────────────┘
                                  │
                   HMAC-Signed Webhook (HTTP POST)
                   X-Signature: sha256=<hash>
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────┐
│              🟢 NODE.JS MICROSERVICE (Port 3001)                │
├─────────────────────────────────────────────────────────────────┤
│  Express Routes:                                                │
│  ├─ GET  /health          → Health Check                       │
│  ├─ POST /notify          → استقبال Webhook (HMAC Verified)   │
│  └─ GET  /notifications   → قائمة الإشعارات                   │
├─────────────────────────────────────────────────────────────────┤
│  Middleware:                                                    │
│  ├─ Helmet         → Security Headers                          │
│  ├─ CORS           → Cross-Origin                              │
│  ├─ Morgan         → HTTP Logging                              │
│  └─ verifySignature→ HMAC Verification                         │
├─────────────────────────────────────────────────────────────────┤
│  Storage: In-Memory Array (notifications[])                     │
│  يمكن استبداله بـ MongoDB/Redis في الإنتاج                   │
└─────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
        ┌──────────────────┬──────────────────┐
        │                  │                  │
        ▼                  ▼                  ▼
┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│  PostgreSQL  │  │    Redis     │  │   Logs       │
│   Port 5433  │  │  Port 6380   │  │  (Morgan)    │
│              │  │              │  │              │
│  • Users     │  │  • Sessions  │  │  • API Logs  │
│  • Tasks     │  │  • Cache     │  │  • Webhooks  │
│  • Tokens    │  │  • Queue     │  │  • Errors    │
└──────────────┘  └──────────────┘  └──────────────┘
```

### 🔐 HMAC Signature Flow (الأمان)

```
Laravel:                          Node.js:
─────────                         ────────

1. إنشاء Payload                1. استقبال Request
   payload = {                     headers['X-Signature']
     user_id: 1,                   body = JSON
     task_id: 123,
     message: "...",            2. إعادة حساب الـ Hash
     timestamp: "..."              expected = sha256(
   }                                 secret + body
                                   )
2. تحويل إلى JSON
   json = JSON.stringify()      3. المقارنة
                                   if (received === expected)
3. حساب HMAC                        ✅ Valid
   hash = HMAC-SHA256(              process webhook
     secret: "xxx",              else
     data: json                      ❌ Invalid (401)
   )                                 reject request

4. إضافة Header
   X-Signature: sha256={hash}

5. إرسال POST Request
   → http://node-notify:3001/notify
```

---

## 🔧 المتطلبات (Prerequisites)

قبل البدء، تأكد من تثبيت:

- ✅ **Docker** 24+ و **Docker Compose** v2
- ✅ **Make** (اختياري لتسهيل الأوامر)
- ✅ **Git**
- ✅ **curl** (للاختبار)

---

## 🚀 التشغيل السريع (Quick Start)

### 1️⃣ استنساخ المشروع

```bash
git clone https://github.com/Abdelrahman842003/Modern-Stack.git
cd Modern-Stack
```

### 2️⃣ الإعداد الآلي (مع Make)

```bash
make setup
```

**هذا الأمر سيقوم بـ:**
- ✅ تشغيل 4 خدمات Docker (Laravel, Node, PostgreSQL, Redis)
- ✅ نسخ ملفات `.env.example` → `.env`
- ✅ تثبيت Dependencies (Composer + NPM)
- ✅ توليد Laravel App Key
- ✅ تشغيل Migrations + Seeders
- ✅ تجهيز النظام للاستخدام

### 3️⃣ الإعداد اليدوي (بدون Make)

```bash
# 1. تشغيل Services
docker compose up -d

# 2. الانتظار حتى يجهز PostgreSQL
sleep 10

# 3. نسخ ملفات البيئة
cp laravel/.env.example laravel/.env
cp node-notify/.env.example node-notify/.env

# 4. تثبيت Laravel Dependencies
docker compose exec laravel-app composer install

# 5. تثبيت Node.js Dependencies  
docker compose exec node-notify npm install

# 6. توليد App Key
docker compose exec laravel-app php artisan key:generate

# 7. تشغيل Migrations والـ Seeders
docker compose exec laravel-app php artisan migrate:fresh --seed
```

### 4️⃣ التحقق من التشغيل

```bash
# فحص الـ Services
docker compose ps

# اختبار Laravel API
curl http://localhost:8000/api/health

# اختبار Node.js Service
curl http://localhost:3001/health
```

---

## 🌐 نقاط الوصول (Access Points)

| الخدمة | URL | الوصف |
|--------|-----|-------|
| 🔵 **Laravel API** | http://localhost:8000 | الـ Backend الرئيسي |
| 📚 **API Documentation** | http://localhost:8000/api/documentation | توثيق OpenAPI 3.1 |
| 🟢 **Node.js Service** | http://localhost:3001 | خدمة الإشعارات |
| 💚 **Node.js Health** | http://localhost:3001/health | فحص صحة Node |
| 🐘 **PostgreSQL** | localhost:5433 | قاعدة البيانات |
| 🔴 **Redis** | localhost:6380 | Cache & Sessions |

---

## 📚 API Endpoints (11 Endpoint)

### 🔐 Authentication (المصادقة)

#### 1. تسجيل مستخدم جديد
```http
POST /api/register
Content-Type: application/json

{
  "name": "أحمد محمد",
  "email": "ahmed@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response 201:**
```json
{
  "status": 201,
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "أحمد محمد",
      "email": "ahmed@example.com"
    },
    "token": "1|abc123..."
  }
}
```

#### 2. تسجيل الدخول
```http
POST /api/login
Content-Type: application/json

{
  "email": "ahmed@example.com",
  "password": "password123"
}
```

#### 3. تسجيل الخروج
```http
POST /api/logout
Authorization: Bearer {token}
```

#### 4. الحصول على بيانات المستخدم الحالي
```http
GET /api/auth/me
Authorization: Bearer {token}
```

---

### 📋 Tasks (المهام) - تتطلب مصادقة

#### 5. قائمة المهام (مع Filtering & Pagination)
```http
GET /api/tasks?status=pending&page=1&per_page=15
Authorization: Bearer {token}
```

**Query Parameters:**
- `status` - فلترة حسب الحالة (`pending` | `done`)
- `due_from` - من تاريخ (YYYY-MM-DD)
- `due_to` - إلى تاريخ (YYYY-MM-DD)
- `page` - رقم الصفحة
- `per_page` - عدد العناصر في الصفحة (افتراضي: 15)

**Response 200:**
```json
{
  "status": 200,
  "message": "Tasks retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "إنهاء المشروع",
      "description": "استكمال API إدارة المهام",
      "due_date": "2025-12-31",
      "status": "pending",
      "is_pending": true,
      "is_done": false,
      "created_at": "2025-10-20T10:00:00Z",
      "updated_at": "2025-10-20T10:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 42,
    "last_page": 3
  }
}
```

#### 6. إنشاء مهمة جديدة
```http
POST /api/tasks
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "مهمة جديدة",
  "description": "وصف المهمة",
  "due_date": "2025-12-31",
  "status": "pending"
}
```

#### 7. الحصول على تفاصيل مهمة
```http
GET /api/tasks/{id}
Authorization: Bearer {token}
```

#### 8. تحديث مهمة
```http
PUT /api/tasks/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "عنوان محدث",
  "description": "وصف جديد",
  "due_date": "2026-01-15"
}
```

#### 9. حذف مهمة (Soft Delete)
```http
DELETE /api/tasks/{id}
Authorization: Bearer {token}
```

#### 10. إتمام مهمة (Mark as Complete)
```http
POST /api/tasks/{id}/complete
Authorization: Bearer {token}
```

**Response 200:**
```json
{
  "status": 200,
  "message": "Task marked as complete successfully",
  "data": {
    "id": 1,
    "title": "إنهاء المشروع",
    "status": "done",
    "is_done": true
  }
}
```

**⚡ ملاحظة:** عند إتمام المهمة، يتم:
1. ✅ تحديث حالة المهمة إلى `done`
2. ✅ إطلاق حدث `TaskCompleted`
3. ✅ إرسال Webhook إلى Node.js Service
4. ✅ Node.js يخزن الإشعار

---

### 🟢 Node.js Microservice Endpoints

#### 11. فحص الصحة (Health Check)
```http
GET http://localhost:3001/health
```

**Response 200:**
```json
{
  "status": "OK",
  "timestamp": "2025-10-20T21:35:50.326Z",
  "uptime": 3600.5,
  "service": "task-notification-service"
}
```

#### استقبال Webhook (داخلي - من Laravel فقط)
```http
POST http://localhost:3001/notify
Content-Type: application/json
X-Signature: sha256=<hmac_hash>

{
  "userId": 1,
  "taskId": 123,
  "message": "Task 'إنهاء المشروع' has been completed",
  "timestamp": "2025-10-20T21:35:50.326Z"
}
```

#### قائمة الإشعارات
```http
GET http://localhost:3001/notifications
```

**Response 200:**
```json
{
  "data": [
    {
      "id": 1,
      "userId": 1,
      "taskId": 123,
      "message": "Task 'إنهاء المشروع' has been completed",
      "timestamp": "2025-10-20T21:35:50.326Z",
      "received_at": "2025-10-20T21:35:50.378Z"
    }
  ],
  "count": 1
}
```

---

## 🧪 الاختبارات (Testing)

المشروع يحتوي على **73 اختبار شامل**:
- 🔵 **52 اختبار Laravel** (Pest)
- 🟢 **21 اختبار Node.js** (Jest)

### 🚀 تشغيل جميع الاختبارات

```bash
# تشغيل جميع الاختبارات (Laravel + Node.js)
make test

# أو يدوياً:
docker compose exec laravel-app php artisan test
docker compose exec node-notify npm test
```

### 🔵 اختبارات Laravel (Pest)

```bash
# جميع الاختبارات
make test-laravel

# اختبارات Feature فقط (API Testing)
docker compose exec laravel-app php artisan test --testsuite=Feature

# اختبارات Unit فقط
docker compose exec laravel-app php artisan test --testsuite=Unit

# تشغيل اختبار محدد
docker compose exec laravel-app php artisan test --filter=TaskControllerTest

# مع تغطية Coverage
docker compose exec laravel-app php artisan test --coverage
```

**محتوى الاختبارات:**
- ✅ API Endpoints (Auth, Tasks CRUD, Complete Task)
- ✅ Request Validation Rules
- ✅ Authentication & Authorization (Sanctum)
- ✅ Service Layer (TaskService)
- ✅ Event Listeners (TaskCompleted → Webhook)
- ✅ Database Operations (Eloquent Models)
- ✅ Error Handling & Exceptions

### 🟢 اختبارات Node.js (Jest)

```bash
# جميع الاختبارات
make test-node

# مع تغطية Coverage
docker compose exec node-notify npm test -- --coverage

# تشغيل اختبار محدد
docker compose exec node-notify npm test -- signature.test.js

# Watch Mode (إعادة تشغيل تلقائي عند التعديل)
docker compose exec node-notify npm test -- --watch
```

**محتوى الاختبارات:**
- ✅ HMAC Signature Generation & Verification
- ✅ Webhook Endpoint Security
- ✅ Health Check Endpoint
- ✅ Notifications Storage & Retrieval
- ✅ Invalid Request Handling
- ✅ Middleware (verifySignature, CORS)

---

## 🛠️ أدوات جودة الكود (Code Quality Tools)

### 📐 Laravel Code Quality

```bash
# تشغيل جميع أدوات الفحص
make ci

# Laravel Pint (Code Formatter - PSR-12)
make lint
# إصلاح تلقائي للأخطاء
docker compose exec laravel-app ./vendor/bin/pint

# PHPStan (Static Analysis - Level 8)
make analyse
# مستوى عالٍ من الفحص
docker compose exec laravel-app ./vendor/bin/phpstan analyse

# Pest (Unit & Feature Tests)
make test-laravel
```

### � Node.js Code Quality

```bash
# ESLint (Airbnb Style Guide)
make lint-node
# إصلاح تلقائي
docker compose exec node-notify npm run lint:fix

# Jest Tests
make test-node
```

---

## 🐳 أوامر Docker الإضافية

### إدارة الحاويات

```bash
# إيقاف جميع الحاويات
make down

# إعادة بناء الحاويات
make rebuild

# عرض سجلات الحاويات
docker compose logs -f laravel-app
docker compose logs -f node-notify
docker compose logs -f postgres
docker compose logs -f redis

# الدخول إلى حاوية Laravel (Bash)
docker compose exec laravel-app bash

# الدخول إلى حاوية Node.js (Shell)
docker compose exec node-notify sh
```

### 🗄️ إدارة قاعدة البيانات

```bash
# تشغيل Migrations
docker compose exec laravel-app php artisan migrate

# التراجع عن Migration الأخير
docker compose exec laravel-app php artisan migrate:rollback

# إعادة إنشاء قاعدة البيانات من الصفر
docker compose exec laravel-app php artisan migrate:fresh

# إعادة إنشاء + Seeders
docker compose exec laravel-app php artisan migrate:fresh --seed

# الاتصال بـ PostgreSQL مباشرة
docker compose exec postgres psql -U taskuser -d taskdb
```

### 🧹 أوامر Utility

```bash
# تنظيف Cache
docker compose exec laravel-app php artisan cache:clear
docker compose exec laravel-app php artisan config:clear
docker compose exec laravel-app php artisan route:clear

# تحسين التطبيق للـ Production
docker compose exec laravel-app php artisan optimize

# عرض جميع Routes
docker compose exec laravel-app php artisan route:list

# Tinker (Laravel REPL)
docker compose exec laravel-app php artisan tinker
```

---

## 🔐 متغيرات البيئة (Environment Variables)

### Laravel (.env)

```env
# === Application ===
APP_NAME="Modern Stack"
APP_ENV=local                    # local | production
APP_KEY=base64:...              # يتم توليده تلقائياً
APP_DEBUG=true                  # true في Development فقط
APP_TIMEZONE=UTC
APP_URL=http://localhost:8000

# === Database (PostgreSQL) ===
DB_CONNECTION=pgsql
DB_HOST=postgres                # اسم الحاوية في Docker
DB_PORT=5432                    # داخل Docker Network
DB_DATABASE=taskdb
DB_USERNAME=taskuser
DB_PASSWORD=taskpass

# === Redis ===
REDIS_CLIENT=phpredis
REDIS_HOST=redis                # اسم الحاوية في Docker
REDIS_PORT=6379                 # داخل Docker Network
REDIS_PASSWORD=null
CACHE_DRIVER=redis              # استخدام Redis للـ Cache
SESSION_DRIVER=redis            # استخدام Redis للـ Sessions

# === Webhook (Node.js Microservice) ===
WEBHOOK_URL=http://node-notify:3001/notify  # داخل Docker Network
WEBHOOK_SECRET=your-secret-key-here-change-in-production
# ⚠️ IMPORTANT: نفس المفتاح في Laravel و Node.js

# === Queue (Redis) ===
QUEUE_CONNECTION=redis          # معالجة Jobs بواسطة Redis

# === Mail (Development) ===
MAIL_MAILER=log                 # إرسال الإيميلات إلى Log
```

### Node.js (.env)

```env
# === Server Configuration ===
PORT=3001                       # منفذ Node.js Service
NODE_ENV=development            # development | production

# === Security ===
WEBHOOK_SECRET=your-secret-key-here-change-in-production
# ⚠️ يجب أن يطابق WEBHOOK_SECRET في Laravel .env

# === CORS ===
CORS_ORIGIN=http://localhost:8000  # السماح لـ Laravel بالوصول
```

### ⚠️ ملاحظات أمان مهمة:

1. **WEBHOOK_SECRET** يجب أن يكون:
   - عشوائياً (استخدم `openssl rand -hex 32`)
   - متطابقاً في Laravel و Node.js
   - مخفياً (لا تدفعه إلى Git)

2. **APP_KEY** في Laravel:
   ```bash
   docker compose exec laravel-app php artisan key:generate
   ```

3. **Production Environment**:
   - `APP_DEBUG=false`
   - `APP_ENV=production`
   - استخدم كلمات مرور قوية لـ Database
   - قم بتفعيل HTTPS
   - استخدم `CACHE_DRIVER=redis` لتحسين الأداء

---

## 🔒 الأمان (Security Features)

### 🛡️ الحماية المطبقة

#### 1. **HMAC-SHA256 Webhook Signatures**
```php
// Laravel - إنشاء التوقيع
$signature = hash_hmac('sha256', $payload, config('services.webhook.secret'));
```

```javascript
// Node.js - التحقق من التوقيع
const expectedSignature = crypto
  .createHmac('sha256', process.env.WEBHOOK_SECRET)
  .update(JSON.stringify(req.body))
  .digest('hex');
```

**الفوائد:**
- ✅ منع التلاعب بالـ Payload
- ✅ التأكد من أن الطلب قادم من Laravel
- ✅ منع Replay Attacks (يمكن إضافة Timestamp)

#### 2. **Laravel Sanctum (Token-Based Auth)**
- ✅ Personal Access Tokens للـ API
- ✅ Stateless Authentication
- ✅ CSRF Protection مضمن
- ✅ Token Abilities (Permissions)

```php
// إنشاء Token
$token = $user->createToken('api-token')->plainTextToken;

// حماية Route
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/tasks', [TaskController::class, 'index']);
});
```

#### 3. **Request Validation**
جميع Requests محمية بـ Form Request Classes:

```php
// laravel/app/Http/Requests/StoreTaskRequest.php
public function rules(): array
{
    return [
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'due_date' => 'required|date|after:today',
        'status' => 'required|in:pending,done',
    ];
}
```

#### 4. **Node.js Security Middleware**

```javascript
// Helmet (HTTP Security Headers)
app.use(helmet());

// CORS (Cross-Origin Resource Sharing)
app.use(cors({ origin: process.env.CORS_ORIGIN }));

// Body Parser Limit (منع Large Payloads)
app.use(express.json({ limit: '1mb' }));
```

#### 5. **Database Security**
- ✅ Eloquent ORM (منع SQL Injection)
- ✅ Prepared Statements
- ✅ Mass Assignment Protection (`$fillable`)
- ✅ Soft Deletes (البيانات لا تُحذف نهائياً)

```php
// laravel/app/Models/Task.php
protected $fillable = [
    'title',
    'description',
    'due_date',
    'status',
    'user_id',
];
```

#### 6. **Environment Variables Protection**
- ✅ ملفات `.env` في `.gitignore`
- ✅ لا يتم دفع الأسرار إلى Git
- ✅ استخدام `config()` بدلاً من `env()` في الكود

### � Best Practices المطبقة

✅ **Authentication First** - جميع Routes محمية بـ Sanctum  
✅ **Input Validation** - كل Request لديه Rules  
✅ **Webhook Verification** - HMAC لكل Webhook  
✅ **Error Handling** - لا تُعرض تفاصيل Exceptions في Production  
✅ **Logging** - جميع الأحداث المهمة مسجلة  
✅ **Rate Limiting** - Laravel throttle middleware  
✅ **HTTPS Ready** - التطبيق جاهز للـ Production مع SSL

---

## 📊 تصميم قاعدة البيانات (Database Design)

### جدول Users (المستخدمين)

```sql
CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### جدول Tasks (المهام)

```sql
CREATE TABLE tasks (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    due_date DATE NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,  -- Soft Delete
    
    CONSTRAINT fk_user
        FOREIGN KEY(user_id) 
        REFERENCES users(id)
        ON DELETE CASCADE,
    
    CONSTRAINT check_status
        CHECK (status IN ('pending', 'done'))
);

CREATE INDEX idx_tasks_user_id ON tasks(user_id);
CREATE INDEX idx_tasks_status ON tasks(status);
CREATE INDEX idx_tasks_due_date ON tasks(due_date);
CREATE INDEX idx_tasks_deleted_at ON tasks(deleted_at);
```

### جدول Personal Access Tokens (Sanctum)

```sql
CREATE TABLE personal_access_tokens (
    id BIGSERIAL PRIMARY KEY,
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    token VARCHAR(64) UNIQUE NOT NULL,
    abilities TEXT NULL,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT idx_tokenable
        INDEX (tokenable_type, tokenable_id)
);
```

### 🔗 العلاقات (Relationships)

```
┌──────────┐         ┌──────────┐
│  Users   │         │  Tasks   │
├──────────┤    1:N  ├──────────┤
│ id  (PK) │◄────────│ id  (PK) │
│ name     │         │ user_id  │ (FK)
│ email    │         │ title    │
│ password │         │ status   │
└──────────┘         │ due_date │
                     │ deleted_at│ (Soft Delete)
                     └──────────┘
```

### 📝 Indexes (الفهارس)

- **Primary Keys**: `users.id`, `tasks.id`
- **Unique**: `users.email`, `personal_access_tokens.token`
- **Performance**: `tasks.user_id`, `tasks.status`, `tasks.due_date`
- **Soft Deletes**: `tasks.deleted_at` (لتجاهل المحذوفة)

---

## 💡 أمثلة الاستخدام (Usage Examples)

### 1️⃣ تسجيل مستخدم وإنشاء مهمة

```bash
# تسجيل مستخدم جديد
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "أحمد محمد",
    "email": "ahmed@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'

# Response:
# {
#   "status": 201,
#   "data": {
#     "user": { "id": 1, "name": "أحمد محمد", "email": "ahmed@example.com" },
#     "token": "1|abc123xyz..."
#   }
# }
```

### 2️⃣ تسجيل الدخول

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "ahmed@example.com",
    "password": "password123"
  }'

# احفظ الـ Token من الـ Response
export TOKEN="1|abc123xyz..."
```

### 3️⃣ إنشاء مهمة

```bash
curl -X POST http://localhost:8000/api/tasks \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "إنهاء المشروع",
    "description": "استكمال API إدارة المهام",
    "due_date": "2025-12-31",
    "status": "pending"
  }'
```

### 4️⃣ قائمة المهام (مع Filtering)

```bash
# جميع المهام
curl -X GET "http://localhost:8000/api/tasks" \
  -H "Authorization: Bearer $TOKEN"

# المهام المعلقة فقط
curl -X GET "http://localhost:8000/api/tasks?status=pending" \
  -H "Authorization: Bearer $TOKEN"

# المهام بين تاريخين
curl -X GET "http://localhost:8000/api/tasks?due_from=2025-01-01&due_to=2025-12-31" \
  -H "Authorization: Bearer $TOKEN"

# مع Pagination
curl -X GET "http://localhost:8000/api/tasks?page=2&per_page=10" \
  -H "Authorization: Bearer $TOKEN"
```

### 5️⃣ إتمام مهمة (ويُرسل Webhook)

```bash
# عند إتمام المهمة، Laravel يرسل Webhook إلى Node.js
curl -X POST http://localhost:8000/api/tasks/1/complete \
  -H "Authorization: Bearer $TOKEN"

# الآن تحقق من الإشعار في Node.js
curl -X GET http://localhost:3001/notifications
```

### 6️⃣ حذف مهمة (Soft Delete)

```bash
curl -X DELETE http://localhost:8000/api/tasks/1 \
  -H "Authorization: Bearer $TOKEN"

# المهمة لا تُحذف من Database، فقط يتم تعيين deleted_at
```

---

## 🚀 CI/CD (GitHub Actions)

### 📋 الـ Workflow الكامل

الملف: `.github/workflows/ci.yml`

```yaml
name: CI Pipeline

on: [push, pull_request]

jobs:
  # Job 1: Laravel Tests
  laravel-tests:
    runs-on: ubuntu-latest
    services:
      postgres:
        image: postgres:16
        env:
          POSTGRES_USER: taskuser
          POSTGRES_PASSWORD: taskpass
          POSTGRES_DB: taskdb
        ports:
          - 5432:5432
      redis:
        image: redis:7.2
        ports:
          - 6379:6379
    steps:
      - Checkout code
      - Setup PHP 8.3
      - Install Composer dependencies
      - Run Laravel Pint (Formatter)
      - Run PHPStan (Static Analysis Level 8)
      - Run Pest Tests (52 tests)
  
  # Job 2: Node.js Tests
  node-tests:
    runs-on: ubuntu-latest
    steps:
      - Checkout code
      - Setup Node.js 20
      - Install NPM dependencies
      - Run ESLint (Airbnb Style)
      - Run Jest Tests (21 tests)
  
  # Job 3: Docker Build
  docker-build:
    runs-on: ubuntu-latest
    steps:
      - Checkout code
      - Build Docker images
      - Test Docker Compose setup
```

### ✅ الفحوصات التي تُجرى

| الخطوة | الأداة | الوصف |
|--------|-------|-------|
| 🎨 Code Style | Laravel Pint | PSR-12 formatting |
| 🔍 Static Analysis | PHPStan | Level 8 (أعلى مستوى) |
| 🧪 Laravel Tests | Pest | 52 اختبار (Feature + Unit) |
| 🎨 Node.js Style | ESLint | Airbnb Style Guide |
| 🧪 Node.js Tests | Jest | 21 اختبار |
| 🐳 Docker Build | Docker Compose | التحقق من البناء |

### 📊 النتائج الحالية

```
✅ Laravel Tests: 52 passed
✅ Node.js Tests: 21 passed
✅ Pint: 0 errors
✅ PHPStan: 0 errors
✅ ESLint: 0 warnings
✅ Docker Build: Success
```

---

## 🤝 المساهمة (Contributing)

نرحب بمساهماتك! اتبع الخطوات التالية:

### 1️⃣ Fork المشروع

```bash
# اضغط على Fork في GitHub
# ثم استنسخ الـ Fork الخاص بك
git clone https://github.com/YOUR_USERNAME/Modern-Stack.git
cd Modern-Stack
```

### 2️⃣ أنشئ Branch جديد

```bash
git checkout -b feature/amazing-feature
```

### 3️⃣ اكتب الكود والاختبارات

```bash
# Laravel Tests
docker compose exec laravel-app php artisan test

# Node.js Tests
docker compose exec node-notify npm test

# Linting
make lint
make lint-node
```

### 4️⃣ Commit التغييرات

```bash
git add .
git commit -m "✨ Add amazing feature"
```

### 5️⃣ Push إلى GitHub

```bash
git push origin feature/amazing-feature
```

### 6️⃣ افتح Pull Request

اذهب إلى GitHub وافتح Pull Request من Branch الخاص بك.

### 📝 معايير المساهمة

✅ **Code Style**: يجب أن يتبع PSR-12 (Laravel) و Airbnb (Node.js)  
✅ **Tests**: أضف اختبارات لأي Feature جديد  
✅ **Documentation**: حدّث README.md إذا لزم الأمر  
✅ **Commits**: استخدم Conventional Commits (`feat:`, `fix:`, `docs:`)  
✅ **CI**: تأكد من أن جميع Checks تمر بنجاح

---

## 📄 الترخيص (License)

MIT License

---

## 👨‍💻 المطور (Developer)

**Abdelrahman**  
📧 Email: [Contact](mailto:abdelrahman842003@gmail.com)  
🐙 GitHub: [@Abdelrahman842003](https://github.com/Abdelrahman842003)

---

## 📚 مصادر إضافية (Additional Resources)

### 📖 التوثيق الرسمي

- [Laravel 11 Documentation](https://laravel.com/docs/11.x)
- [Laravel Sanctum](https://laravel.com/docs/11.x/sanctum)
- [Pest PHP Testing](https://pestphp.com/docs)
- [PHPStan Documentation](https://phpstan.org/user-guide/getting-started)
- [Express.js 5 Docs](https://expressjs.com/en/5x/api.html)
- [Jest Testing Framework](https://jestjs.io/docs/getting-started)
- [Docker Compose](https://docs.docker.com/compose/)
- [PostgreSQL 16](https://www.postgresql.org/docs/16/)
- [Redis Documentation](https://redis.io/docs/)

### 🎓 تعلّم المزيد

- **HMAC Signatures**: [RFC 2104](https://datatracker.ietf.org/doc/html/rfc2104)
- **RESTful API Design**: [REST API Tutorial](https://restfulapi.net/)
- **Microservices Architecture**: [Martin Fowler - Microservices](https://martinfowler.com/articles/microservices.html)
- **Docker Best Practices**: [Docker Docs](https://docs.docker.com/develop/dev-best-practices/)
- **PSR-12 Coding Standard**: [PHP-FIG PSR-12](https://www.php-fig.org/psr/psr-12/)

---

## ⭐ إذا أعجبك المشروع

إذا وجدت هذا المشروع مفيداً، لا تنسَ إعطائه ⭐ على GitHub!

```bash
# استنسخ المشروع وابدأ التطوير
git clone https://github.com/Abdelrahman842003/Modern-Stack.git
cd Modern-Stack
make setup
```

---

<div align="center">

**بُني بـ ❤️ باستخدام Laravel 11, Node.js, PostgreSQL, Redis & Docker**

**Made with ❤️ using Laravel 11, Node.js, PostgreSQL, Redis & Docker**

---

![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Node.js](https://img.shields.io/badge/Node.js-20.x-339933?style=for-the-badge&logo=node.js&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?style=for-the-badge&logo=postgresql&logoColor=white)
![Redis](https://img.shields.io/badge/Redis-7.2-DC382D?style=for-the-badge&logo=redis&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?style=for-the-badge&logo=docker&logoColor=white)

[![Tests](https://img.shields.io/badge/Tests-73%20Passing-success?style=for-the-badge)](https://github.com/Abdelrahman842003/Modern-Stack)
[![License](https://img.shields.io/badge/License-MIT-blue?style=for-the-badge)](LICENSE)

</div>
