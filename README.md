# Todo API with Laravel

This is a robust Todo List API built with Laravel, featuring JWT authentication, repository pattern, Redis caching, Excel exports, and chart data aggregation.

## Features

### 1. Authentication
- **JWT Authentication**: Secure API using `php-open-source-saver/jwt-auth`.
- **Endpoints**: Login, Register, Logout, Refresh, Me.

### 2. Todo Management
- **CRUD Operations**: Create, Read, Update, Delete Todos.
- **Pagination**: List todos with customizable page size (`?per_page=10`).
- **Validation**: Strict validation rules for data integrity.

### 3. Repository Pattern
- **Decoupled Logic**: Business logic is separated from controllers using `TodoRepositoryInterface` and `TodoRepository`.

### 4. Performance & Caching
- **Redis Caching**: 
  - Implemented for `getAll`, `getPaginated`, and `findById`.
  - Cache invalidation on Create, Update, and Delete using Cache Tags.
- **Data Source Indicator**: API responses include a `source` field (`Database` or `Redis Cache`) to indicate where the data came from.

### 5. Security & Rate Limiting
- **Throttling**: API requests are rate-limited to **60 requests per minute** per user/IP.

### 6. Reporting & Analytics
- **Excel Export**:
  - Endpoint: `GET /api/todos/export`
  - Features: Filter by title, assignee, status, priority, due date range, and time tracked.
  - Includes a summary row with total count and total time tracked.
- **Chart Data**:
  - Endpoint: `GET /api/chart?type={type}`
  - Supported Types: `status`, `priority`, `assignee`.
  - Returns aggregated data for frontend visualization.

## Setup Instructions

1. **Clone the repository**
   ```bash
   git clone <repository_url>
   cd <project_directory>
   ```

2. **Install Dependencies**
   ```bash
   composer install
   ```

3. **Environment Setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   php artisan jwt:secret
   ```

4. **Database Configuration**
   - Update `.env` with your database credentials.
   - Run migrations:
     ```bash
     php artisan migrate
     ```

5. **Redis Configuration**
   - Ensure Redis is installed and running.
   - Update `.env`:
     ```ini
     CACHE_STORE=redis
     REDIS_HOST=127.0.0.1
     REDIS_PASSWORD=null
     REDIS_PORT=6379
     ```

6. **Serve the Application**
   ```bash
   php artisan serve
   ```

## API Documentation

A Postman collection is included in the root directory: `postman_collection.json`. 
Import it into Postman to test all endpoints.

### Key Endpoints

| Method | Endpoint | Description |
| :--- | :--- | :--- |
| POST | `/api/auth/register` | Register a new user |
| POST | `/api/auth/login` | Login and get JWT token |
| GET | `/api/todos` | Get paginated todos |
| POST | `/api/todos` | Create a new todo |
| GET | `/api/todos/{id}` | Get a specific todo |
| PUT | `/api/todos/{id}` | Update a todo |
| DELETE | `/api/todos/{id}` | Delete a todo |
| GET | `/api/todos/export` | Download filtered Excel report |
| GET | `/api/chart` | Get aggregated chart data |
