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
