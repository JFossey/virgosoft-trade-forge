# 📦 Install, Setup and Usage

## Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js 18+ and npm
- MySQL 8.0+

## Clone Repository

```bash
git clone <repository-url>
cd virgosoft-trade-forge
```

## Setup Options

1. A quick start docker setup has been provided using the ddev _(recommended)_.
2. Alternatively, instructions for manual local host setup is also provided.

## Quick Setup using DDEV

DDEV provides a containerized development environment with all dependencies pre-configured.

It is a convenient, easy install and use method of doing any `PHP/Node` development.

See [ddev config directory](../.ddev/) for details.

### 1. Install DDEV

Follow the [official DDEV installation guide](https://ddev.readthedocs.io/en/stable/users/install/ddev-installation/) for your operating system.

Once installed most commands can be run in ddev using by using the `ddev exec` command or by opening a shell inside the main container using the `ddev ssh` command.

All instruction are for the `exec` version, to use the `ssh` version just run the command without the `ddev exec` or see the manual setup examples as they are similar.

### 2. Start DDEV Env

```bash
ddev start
```

### 3. Install Dependencies

```bash
ddev composer install
ddev npm install
```

### 4. Configure Environment

```bash
ddev exec cp .env.example .env
ddev exec php artisan key:generate
```

Edit `.env` and configure:
- Pusher credentials for real-time broadcasting
- Database credentials (already configured for DDEV)
- Queue connection sync (already configured for DDEV)

### 5. Run Migrations and Seeders

```bash
ddev exec php artisan migrate --seed
```

### 6. Build Frontend Assets

```bash
# Vite build
ddev npm run build
```

### 7. Access Application

- Frontend: `https://virgosoft-trade-forge.ddev.site`
- API: `https://virgosoft-trade-forge.ddev.site/api`

---

## Manual Setup

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Configure Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and configure the following:

```env
APP_NAME="Trade Forge"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=trade_forge
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_DRIVER=pusher
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=your_cluster
```

### 4. Create Database

Create a new database matching your `DB_DATABASE` value:

```sql
CREATE DATABASE trade_forge CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 5. Run Migrations and Seeders

```bash
php artisan migrate --seed
```

### 6. Start Development Servers

Open two terminal windows:

```bash
# Terminal 1 - Laravel development server
php artisan serve

# Terminal 3 - Vite dev server
npm run dev
```

### 7. Access Application

- Frontend: `http://localhost:8000`
- API: `http://localhost:8000/api`

---

## Usage

### Creating an Account

1. Register a new user account
2. Login with your credentials
3. Your account starts with $0 USD balance

### Funding Your Account

Use the profile page to add USD funds to your account balance.

### Placing Orders

1. Navigate to the trading interface
2. Select a symbol (BTC or ETH)
3. Choose Buy or Sell
4. Enter price and amount
5. Click "Place Order"

### Viewing Orders

- **Open Orders**: Currently active orders waiting to be matched
- **Filled Orders**: Successfully executed trades
- **Cancelled Orders**: Orders you've manually cancelled

### Real-Time Updates

The interface automatically updates when:
- Your order is matched
- Your balance changes
- New orders appear in the orderbook
