# 📋 Task Management API

A production-grade Task Management API built with **Laravel 11** and a lightweight **Node.js** notification microservice.

## 🚀 Tech Stack

### Backend (Laravel)
- **PHP**: 8.3.x
- **Laravel**: 11.x
- **Database**: PostgreSQL 16.x
- **Cache**: Redis 7.2.x
- **Authentication**: Laravel Sanctum
- **Testing**: Pest 3.x
- **Code Quality**: PHPStan (level 8), Laravel Pint
- **API Docs**: L5-Swagger (OpenAPI 3.1)

### Microservice (Node.js)
- **Node.js**: 20.x LTS
- **Framework**: Express 5.x
- **Testing**: Jest 29.x + Supertest

### Infrastructure
- **Docker**: 24+ with Docker Compose v2
- **CI/CD**: GitHub Actions

## 📦 Project Structure

```
task-management-api/
├── laravel/                 # Laravel 11 application
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── routes/
│   └── tests/
├── node-notify/             # Node.js notification service
│   ├── src/
│   │   └── index.js        # Express server with HMAC verification
│   ├── tests/
│   └── package.json
├── docker/                  # Dockerfiles
│   ├── laravel.Dockerfile
│   └── node.Dockerfile
├── .github/workflows/       # CI/CD pipelines
├── docker-compose.yml       # Docker Compose configuration
├── Makefile                 # Development commands
└── README.md
```

## 🔧 Prerequisites

- Docker 24+ and Docker Compose v2
- Make (optional, for easier command execution)
- Git

## 🏃 Quick Start

### 1. Clone the repository

```bash
git clone <repository-url>
cd task-management-api
```

### 2. Initial setup (automated)

```bash
make setup
```

This will:
- Start all Docker services (PostgreSQL, Redis, Laravel, Node.js)
- Copy `.env.example` files
- Install dependencies (Laravel + Node.js)
- Generate Laravel app key
- Run database migrations and seeders
- Start all services

### 3. Manual setup (if not using Make)

```bash
# Start services
docker compose up -d

# Wait for services
sleep 10

# Copy environment files
cp laravel/.env.example laravel/.env
cp node-notify/.env.example node-notify/.env

# Install Laravel dependencies
docker compose exec laravel-app composer install

# Install Node.js dependencies
docker compose exec node-notify npm install

# Generate app key
docker compose exec laravel-app php artisan key:generate

# Run migrations and seeders
docker compose exec laravel-app php artisan migrate:fresh --seed
```

## 🌐 Access Points

- **Laravel API**: http://localhost:8000
- **API Documentation**: http://localhost:8000/api/documentation
- **Node.js Service**: http://localhost:3001
- **Node.js Health**: http://localhost:3001/health
- **PostgreSQL**: localhost:5432
- **Redis**: localhost:6379

## 📚 API Endpoints

### Authentication
- `POST /api/register` - Register new user
- `POST /api/login` - Login
- `POST /api/logout` - Logout

### Tasks (requires authentication)
- `GET /api/tasks` - List tasks with filtering and pagination
- `POST /api/tasks` - Create new task
- `GET /api/tasks/{id}` - Get task details
- `PUT /api/tasks/{id}` - Update task
- `DELETE /api/tasks/{id}` - Delete task (soft delete)
- `POST /api/tasks/{id}/complete` - Mark task as completed

### Query Parameters (GET /api/tasks)
- `status` - Filter by status (pending|done)
- `due_from` - Filter by due date from (YYYY-MM-DD)
- `due_to` - Filter by due date to (YYYY-MM-DD)
- `page` - Pagination page number

### Node.js Service
- `GET /health` - Health check
- `POST /notify` - Receive webhook notifications (internal)
- `GET /notifications` - List all received notifications

## 🧪 Testing

### Run all tests
```bash
make test
```

### Laravel tests only
```bash
make test-laravel
# or
docker compose exec laravel-app php artisan test
```

### Node.js tests only
```bash
make test-node
# or
docker compose exec node-notify npm test
```

### With coverage
```bash
make test-coverage
```

## 🎨 Code Quality

### Lint code
```bash
make lint
```

### Check code style
```bash
make lint-check
```

### Static analysis
```bash
make analyse
```

### Run full CI pipeline
```bash
make ci
```

## 🛠️ Development Commands

### Docker Management
```bash
make up          # Start services
make down        # Stop services
make restart     # Restart services
make logs        # View all logs
make clean       # Clean up everything
```

### Database
```bash
make migrate          # Run migrations
make migrate-fresh    # Fresh migration with seeding
make seed            # Run seeders
make db-shell        # PostgreSQL shell
```

### Utilities
```bash
make shell-laravel   # Access Laravel container
make shell-node      # Access Node.js container
make tinker          # Laravel Tinker
```

## 🔐 Environment Variables

### Laravel (.env)
Key variables:
- `DB_*` - Database configuration
- `REDIS_*` - Redis configuration
- `SANCTUM_STATEFUL_DOMAINS` - Sanctum domains
- `WEBHOOK_SECRET` - Shared secret for webhook signing
- `NODE_NOTIFY_URL` - Node.js service URL

### Node.js (.env)
- `PORT` - Server port (default: 3001)
- `WEBHOOK_SECRET` - Must match Laravel's secret
- `NODE_ENV` - Environment (development/production)

## 🔒 Security Features

- ✅ Token-based authentication (Laravel Sanctum)
- ✅ HMAC-signed webhooks
- ✅ Rate limiting
- ✅ CORS configuration
- ✅ Input validation
- ✅ SQL injection protection (Eloquent ORM)

## 📊 Database Design

### Users Table
- id, name, email, password, timestamps

### Tasks Table
- id, user_id, title, description
- due_date (nullable)
- status (enum: pending, done)
- created_at, updated_at, deleted_at

## 🎯 Features

- ✅ RESTful API design
- ✅ Token authentication (Sanctum)
- ✅ Task CRUD operations
- ✅ Soft deletes
- ✅ Filtering and pagination
- ✅ Webhook notifications
- ✅ HMAC signature verification
- ✅ Comprehensive testing
- ✅ API documentation (OpenAPI 3.1)
- ✅ Docker containerization
- ✅ CI/CD pipeline

## 📝 Example Usage

### Register a user
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

### Login
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

### Create a task
```bash
curl -X POST http://localhost:8000/api/tasks \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Complete project",
    "description": "Finish the task management API",
    "due_date": "2025-12-31",
    "status": "pending"
  }'
```

### Mark task as complete
```bash
curl -X POST http://localhost:8000/api/tasks/1/complete \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 🚀 CI/CD

GitHub Actions workflow runs on every push:
- Install dependencies
- Code linting (Pint, ESLint)
- Static analysis (PHPStan)
- Run tests (Pest, Jest)
- Generate coverage reports

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## 📄 License

MIT License

## 👨‍💻 Author

Backend Developer Evaluation Task

---

Made with ❤️ using Laravel 11 and Node.js 20
