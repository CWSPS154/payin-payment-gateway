# Payin Payment Gateway Demo

A Laravel 13 demo for merchant offers, customer checkout, payment webhooks, and wallet settlement. It includes admin and merchant sign-in, a seeded demo store, automatic webhook expiry, and a customer-facing checkout simulation.

## Quick setup

Use Bash on Linux, macOS, or WSL. The setup script uses local PHP and Composer when they are ready; if they are unavailable, it can use Docker for the default SQLite setup.

```bash
chmod +x setup.sh
./setup.sh
```

The script installs Composer dependencies, creates `.env` only if it does not exist, generates an application key if needed, creates the SQLite database file, applies pending migrations, and seeds the demo accounts. It is safe to rerun: it does not drop tables or reset wallet balances.

### Run the app

Open two terminals in the project directory.

Terminal 1:

```bash
php artisan serve
```

Terminal 2:

```bash
php artisan schedule:work
```

The scheduler is required to mark payments failed after the webhook timeout. Pending payments expire after 15 minutes by default; configure `DEMO_WEBHOOK_TIMEOUT_MINUTES` in `.env` to change it. The scheduler checks once per minute, so timeout marking can take up to about one minute after the deadline.

Open <http://127.0.0.1:8000>.

### Demo accounts

The default seeded credentials are:

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@example.com` | `password` |
| Merchant | `merchant@example.com` | `password` |

Login pages are prefilled for the local demo. Change the `DEMO_*` values in `.env` before seeding if you want different credentials. The sample merchant is **ABC Store** with **Demo Item** priced at **₹1,000** and a starting wallet balance of **₹20,000**.

## Manual setup

### SQLite with local PHP and Composer

Requirements: PHP 8.3 or later with `pdo_sqlite`, Composer 2, and Bash.

```bash
cp .env.example .env
composer install
php artisan key:generate
mkdir -p database
touch database/database.sqlite
php artisan migrate --seed
```

Then start the app and scheduler using the two commands in [Run the app](#run-the-app).

### MySQL or MariaDB

Requirements: PHP 8.3 or later with `pdo_mysql`, Composer 2, and a MySQL-compatible database.

1. Create an empty database, for example `payment_gateway`.
2. Copy `.env.example` to `.env`.
3. Set `DB_CONNECTION=mysql`, then set `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env`.
4. Install dependencies and initialize the app:

   ```bash
   composer install
   php artisan key:generate
   php artisan migrate --seed
   ```

Use InnoDB for transaction and row-locking support. Then start the app and scheduler in separate terminals.

### Run with Docker

If setup used Docker because PHP or Composer is not installed locally, run Laravel commands through the same Composer image. From the project root:

```bash
docker run --rm --user "$(id -u):$(id -g)" -p 8000:8000 -v "$PWD:/app" -w /app composer:2 php artisan serve --host=0.0.0.0
```

In a second terminal, start the scheduler:

```bash
docker run --rm --user "$(id -u):$(id -g)" -v "$PWD:/app" -w /app composer:2 php artisan schedule:work
```

This Docker fallback uses SQLite. For MySQL with Docker, provide a PHP runtime image that includes `pdo_mysql` and configure the database connection in `.env`.

## Using the demo

- **Admin:** Sign in to view all payments, filter by date, merchant, status, or order ID, and inspect settlement details.
- **Merchant:** Register or sign in to edit the public offer, view wallet and payment summaries, and filter payment activity for that merchant.
- **Customer:** Open **Pay as Customer**, choose an offer, and select a webhook mode at checkout. Manual mode is selected by default: recording a payment outcome leaves it `PENDING` until **Trigger Webhook** is clicked. Automatic mode triggers the webhook immediately.
- If no webhook arrives within the configured timeout, the scheduler marks the payment `FAILED` and stores a timeout reason. A late webhook is rejected.

The simulated checkout does not process real money. Manual webhook outcomes are held in the customer session until triggered or until the payment expires.

## API

### Create a payment

`POST /api/payments`

```json
{
  "merchant_id": 1,
  "order_id": "ORD10001",
  "amount": "10000.00",
  "gateway": "TEST_GATEWAY"
}
```

The response is `201 Created` with a `PENDING` payment and an expiry deadline. The pair `(merchant_id, order_id)` must be unique. Invalid input or a duplicate order returns `422`.

### Send a payment webhook

`POST /api/payment/webhook`

```json
{
  "merchant_id": 1,
  "order_id": "ORD10001",
  "status": "SUCCESS",
  "reference": "GW123456"
}
```

Allowed statuses are `SUCCESS` and `FAILED`. For failed callbacks, an optional `failure_reason` can describe the gateway failure. A successful callback updates the payment, credits the merchant wallet, and records one wallet transaction in a database transaction. Repeated callbacks with the same final status do not create another credit. Conflicting final statuses return `409`; an expired payment also rejects late webhooks with `409`.

## Database scheduler

The schedule is defined in `routes/console.php` and runs `payments:expire-pending` every minute. Local development uses `php artisan schedule:work`. Production should invoke `php artisan schedule:run` from cron once per minute, for example:

```cron
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

If the scheduler is not running, pending payments will not be marked expired automatically.

## Tests

```bash
php artisan test
```

The feature tests use SQLite in memory for fast checks. SQLite does not exercise MySQL row-level locking; verify concurrent settlement against MySQL/InnoDB before relying on it for concurrent production traffic.

## Demo limitations

This is an assignment/demo application. The credentials are intentionally convenient for local review, the checkout is simulated, and the webhook endpoint does not verify a real payment-provider signature. Use production credentials, signature validation, rate limiting, and provider integration before exposing a payment application publicly.
