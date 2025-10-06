# Mini Wallet Application

A high-performance digital wallet application built with Laravel and Vue.js that allows users to transfer money to each other with real-time updates.

## Features

- **High-Performance Money Transfers**: Handles hundreds of transfers per second with atomic database transactions
- **Real-time Updates**: Instant notifications using Pusher for transaction updates
- **Scalable Balance Management**: Optimized for millions of transaction records
- **Commission System**: Automatic 1.5% commission calculation on transfers
- **Modern UI**: Clean, responsive interface built with Vue.js 3 and Composition API
- **Secure Authentication**: Laravel Sanctum for API authentication

## Technology Stack

### Backend
- **Laravel 12** - PHP framework
- **MySQL** - Database
- **Laravel Sanctum** - API authentication
- **Pusher** - Real-time broadcasting
- **Laravel Broadcasting** - Event system

### Frontend
- **Vue.js 3** - Frontend framework
- **TypeScript** - Type safety
- **Pinia** - State management
- **Vue Router** - Client-side routing
- **Axios** - HTTP client
- **Pusher JS** - Real-time client
- **Tailwind CSS** - Styling

## Project Structure

```
pimono/
├── mini-wallet-backend/          # Laravel API
│   ├── app/
│   │   ├── Http/Controllers/Api/
│   │   ├── Models/
│   │   └── Events/
│   ├── database/migrations/
│   └── routes/api.php
├── mini-wallet-frontend/         # Vue.js Frontend
│   ├── src/
│   │   ├── components/
│   │   ├── views/
│   │   ├── stores/
│   │   └── services/
└── README.md
```

## Installation & Setup

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 20+
- MySQL 8.0+
- Pusher account (for real-time features)

### Backend Setup

1. **Navigate to backend directory:**
   ```bash
   cd mini-wallet-backend
   ```

2. **Install dependencies:**
   ```bash
   composer install
   ```

3. **Configure environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Update database configuration in `.env`:**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=mini_wallet
   DB_USERNAME=root
   DB_PASSWORD=your_password
   ```

5. **Configure Pusher in `.env`:**
   ```env
   BROADCAST_CONNECTION=pusher
   PUSHER_APP_ID=your_app_id
   PUSHER_APP_KEY=your_app_key
   PUSHER_APP_SECRET=your_app_secret
   PUSHER_APP_CLUSTER=mt1
   ```

6. **Run migrations:**
   ```bash
   php artisan migrate
   ```

7. **Start the server:**
   ```bash
   php artisan serve
   ```

The API will be available at `http://localhost:8000`

### High Concurrency Features

- Atomic transfers in a single DB transaction
- Row-level locking on `users` with `lockForUpdate()` and in-transaction balance re-check
- Retry with exponential backoff on transient errors (deadlocks/serialization failures)
- Decimal monetary fields and 1.5% commission calculation
- Idempotency via `Idempotency-Key` headers (stored in `idempotency_keys`)
- Outbox pattern (`outbox_messages`) for reliable event dispatch of `transaction.completed`

#### Database migrations

Run migrations after pulling changes:

```bash
php artisan migrate
```

Creates/updates tables:
- `transactions`, `idempotency_keys`, `outbox_messages`
- Adds DB-level CHECK constraint for non-negative balance when supported

#### Outbox dispatch (local/dev)

Console command:

```bash
php artisan outbox:dispatch --limit=200
```

Dev HTTP endpoint (local env, requires auth):

```http
POST /api/dev/outbox/dispatch
Authorization: Bearer <token>
```

For production, schedule the command with cron/Supervisor to run every minute (or faster as needed).

##### Production scheduling examples

Option A) Use Laravel scheduler (recommended):

1. Ensure your server cron runs the Laravel scheduler every minute:

```cron
* * * * * cd /path/to/mini-wallet-backend && php artisan schedule:run >> /dev/null 2>&1
```

2. Then register the command in `app/Console/Kernel.php` (if you use scheduler):

```php
protected function schedule(\Illuminate\Console\Scheduling\Schedule $schedule): void
{
    $schedule->command('outbox:dispatch --limit=500')->everyMinute();
}
```

Option B) Call the command directly via cron (no scheduler):

```cron
* * * * * cd /path/to/mini-wallet-backend && php artisan outbox:dispatch --limit=500 >> storage/logs/outbox.log 2>&1
```

Option C) Supervisor program for continuous dispatch (burstier loads):

`/etc/supervisor/conf.d/outbox.conf`

```
[program:wallet-outbox]
command=php artisan outbox:dispatch --limit=1000
directory=/path/to/mini-wallet-backend
autostart=true
autorestart=true
user=www-data
numprocs=1
stdout_logfile=/path/to/mini-wallet-backend/storage/logs/outbox_supervisor.log
stderr_logfile=/path/to/mini-wallet-backend/storage/logs/outbox_supervisor_error.log
startsecs=0
stopwaitsecs=10
```

Reload supervisor:

```bash
sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl restart wallet-outbox
```

#### Idempotency

Frontend automatically sends a unique `Idempotency-Key` header for `POST /api/transactions`. Backend de-duplicates using `idempotency_keys`.

To manually test idempotency with cURL:

```bash
KEY="test-key-$(date +%s)"
curl -X POST \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: $KEY" \
  -d '{"receiver_id":2, "amount":100.00}' \
  http://localhost:8000/api/transactions

# Replaying the same request will return the same transaction
curl -X POST \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: $KEY" \
  -d '{"receiver_id":2, "amount":100.00}' \
  http://localhost:8000/api/transactions
```

### Frontend Setup

1. **Navigate to frontend directory:**
   ```bash
   cd mini-wallet-frontend
   ```

2. **Install dependencies:**
   ```bash
   npm install
   ```

3. **Configure environment:**
   ```bash
   cp .env.example .env
   ```

4. **Update environment variables in `.env`:**
   ```env
   VITE_API_BASE_URL=http://localhost:8000/api
   VITE_PUSHER_APP_KEY=your_app_key
   VITE_PUSHER_APP_CLUSTER=mt1
   ```

5. **Start the development server:**
   ```bash
   npm run dev
   ```

The frontend will be available at `http://localhost:5173`

## Makefile shortcuts

For convenience on Unix-like systems:

```bash
make setup-backend      # install deps and migrate
make serve-backend      # run php artisan serve
make outbox-dispatch    # run outbox dispatcher once
make setup-frontend     # install frontend deps
make dev-frontend       # vite dev server
make build-frontend     # build frontend
```

## Windows PowerShell scripts

For Windows users without `make`:

```powershell
# Backend setup (install + migrate)
./scripts/backend-setup.ps1

# Frontend setup (npm install)
./scripts/frontend-setup.ps1

# Run outbox dispatcher once (optional limit)
./scripts/outbox-dispatch.ps1 -Limit 500
```

### Queues and Workers for Real-time

For proper real-time functionality (Pusher and Broadcasting), transaction events are queued and a Queue Worker must be running.

1) Required settings in `.env` (Backend):

```env
# Broadcasting (Pusher)
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=mt1

# Queues
QUEUE_CONNECTION=database
QUEUE_FAILED_DRIVER=database-uuids
```

2) Create queue tables (if not already done):

```bash
php artisan migrate
```

3) Run Queue Worker:

- Windows (PowerShell):

```powershell
cd mini-wallet-backend
php artisan queue:work --queue=default --tries=3 --backoff=5
```

- Linux/macOS:

```bash
cd mini-wallet-backend
php artisan queue:work --queue=default --tries=3 --backoff=5
```

Note: The `TransactionCompleted` event implements `ShouldBroadcast` and is queued by default; therefore, running a Worker is essential for receiving real-time notifications in the frontend. For immediate broadcasting without queuing, use `ShouldBroadcastNow` instead (not recommended except for debugging/special cases).

4) Stable execution in production (optional):

- Sample Supervisor config for Queue Worker:

```
[program:wallet-queue]
command=php artisan queue:work --queue=default --tries=3 --backoff=5
directory=/path/to/mini-wallet-backend
autostart=true
autorestart=true
user=www-data
numprocs=1
stdout_logfile=/path/to/mini-wallet-backend/storage/logs/queue_supervisor.log
stderr_logfile=/path/to/mini-wallet-backend/storage/logs/queue_supervisor_error.log
startsecs=0
stopwaitsecs=10
```

Note: In addition to the Worker, the `outbox:dispatch` command must also be scheduled/executed for reliable message delivery (Outbox pattern); scheduling examples are provided above in this file.

## API Endpoints

### Authentication
- `POST /api/login` - User login
- `POST /api/register` - User registration
- `GET /api/user` - Get authenticated user

### Transactions
- `GET /api/transactions` - Get transaction history and balance
- `POST /api/transactions` - Create new transfer

### Request/Response Examples

#### Create Transfer
```bash
POST /api/transactions
Authorization: Bearer {token}
Content-Type: application/json

{
  "receiver_id": 2,
  "amount": 100.00
}
```

#### Response
```json
{
  "message": "Transfer completed successfully",
  "transaction": {
    "id": 1,
    "sender_id": 1,
    "receiver_id": 2,
    "amount": 100.00,
    "commission_fee": 1.50,
    "total_amount": 101.50,
    "status": "completed",
    "created_at": "2025-10-04T10:00:00.000000Z",
    "sender": {
      "id": 1,
      "name": "John Doe"
    },
    "receiver": {
      "id": 2,
      "name": "Jane Smith"
    }
  },
  "new_balance": 898.50
}
```

## Key Features Implementation

### High-Performance Balance Management
- Uses database-level balance updates instead of calculating from transaction history
- Implements atomic database transactions to prevent race conditions
- Optimized database indexes for fast queries

### Real-time Updates
- Pusher integration for instant transaction notifications
- Private channels for user-specific updates
- Automatic UI updates without page refresh

### Commission System
- Automatic 1.5% commission calculation
- Commission deducted from sender's balance
- Clear display of fees in UI

### Security Features
- Laravel Sanctum for secure API authentication
- Input validation and sanitization
- CSRF protection
- SQL injection prevention

## Database Schema

### Users Table
```sql
- id (primary key)
- name
- email (unique)
- password (hashed)
- balance (decimal 15,2)
- email_verified_at
- created_at
- updated_at
```

### Transactions Table
```sql
- id (primary key)
- sender_id (foreign key)
- receiver_id (foreign key)
- amount (decimal 15,2)
- commission_fee (decimal 15,2)
- total_amount (decimal 15,2)
- status (enum: pending, completed, failed)
- description (text, nullable)
- created_at
- updated_at
```

## Performance Considerations

- **Database Indexes**: Optimized indexes on frequently queried columns
- **Atomic Transactions**: All balance updates wrapped in database transactions
- **Pagination**: Transaction history paginated for large datasets
- **Real-time Efficiency**: Selective broadcasting to relevant users only

## Testing

### Backend Tests
```bash
cd mini-wallet-backend
php artisan test
```

### Frontend Tests
```bash
cd mini-wallet-frontend
npm run test
```

## Deployment

### Backend Deployment
1. Set up production database
2. Configure environment variables
3. Run migrations: `php artisan migrate`
4. Set up web server (Apache/Nginx)
5. Configure SSL certificate

### Frontend Deployment
1. Build for production: `npm run build`
2. Deploy to CDN or static hosting
3. Configure environment variables

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## License

This project is licensed under the MIT License.

## Support

For support or questions, please contact info@pimono.ae
