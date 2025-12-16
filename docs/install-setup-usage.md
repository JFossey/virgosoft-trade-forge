# 📦 Install & Setup

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

1. A quick start docker setup has been provided using ddev _(recommended)_.
2. Alternatively, instructions for manual local host setup is also provided.

---

## Quick Setup using DDEV

DDEV provides a containerized development environment with all dependencies pre-configured.

It is a convenient, easy install and use method of doing any `PHP/Node` development.

See [ddev config directory](../.ddev/) for details.

### 1. Install DDEV

Follow the [official DDEV installation guide](https://ddev.readthedocs.io/en/stable/users/install/ddev-installation/) for your operating system.

Once installed most commands can be run in ddev using by using the `ddev exec` command or by opening a shell inside the main container using the `ddev ssh` command.

All instruction are for the `exec` version, to use the `ssh` version just run the command without the `ddev exec` or see the manual setup examples as they are similar.

### 2. Start DDEV Environment

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
- **Pusher credentials** for real-time broadcasting (required for real-time features)
- Database credentials (already configured for DDEV)
- Queue connection (already configured to use `sync` for DDEV)

### 5. Run Migrations and Seeders

```bash
ddev exec php artisan migrate --seed
```

This will create test users with pre-funded accounts. See [Usage Guide](usage.md#seed-data--test-accounts) for login credentials.

### 6. Start Development Server

```bash
# For development with hot reloading
ddev npm run dev

# OR for production build
ddev npm run build
```

### 7. Access Application

To view all `ddev` running services and the ports/urls.

```
ddev describe
```

To launch/open the site in the configured URL a browser.

```
ddev launch
```

- Frontend: `https://virgosoft-trade-forge.ddev.site`
- API: `https://virgosoft-trade-forge.ddev.site/api`

---

## Manual Setup

### 1. Install Dependencies

```bash
composer install
npm install
```

### 2. Configure Environment

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
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file

SANCTUM_STATEFUL_DOMAINS=localhost

# Required for real-time features
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=your_cluster
```

**Note:** `QUEUE_CONNECTION=sync` processes orders synchronously within the same request.

### 3. Create Database

Create a new database matching your `DB_DATABASE` value:

```sql
CREATE DATABASE trade_forge CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4. Run Migrations and Seeders

```bash
php artisan migrate --seed
```

This will create test users with pre-funded accounts. See [Usage Guide](usage.md#seed-data--test-accounts) for login credentials.

### 5. Start Development Servers

Open two terminal windows:

```bash
# Terminal 1 - Laravel development server
php artisan serve

# Terminal 2 - Vite dev server
npm run dev
```

### 6. Access Application

- Frontend: `http://localhost:8000`
- API: `http://localhost:8000/api`

---

## Troubleshooting

- If getting login/registration errors and you laravel log has an error `Session store not set on request.` then you need update your `SANCTUM_STATEFUL_DOMAINS` env to match your browser URL.

## Next Steps

Once installation is complete, see the [Usage Guide](usage.md) to learn how to:
- Login with test accounts
- Place buy and sell orders
- View and manage your orders
- Understand the commission structure
