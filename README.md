# Extendable Order and Payment Management API

A Laravel-based RESTful API for managing orders and payments. Payment gateways are easy to extend using the Strategy Pattern.

## Tech Stack

- PHP 8.4 / Laravel 12
- SQLite (default, switchable to MySQL/PostgreSQL)
- JWT Authentication (`php-open-source-saver/jwt-auth`)
- PHPUnit for testing
- GitHub Actions CI/CD

## Installation & Setup

```bash
git clone https://github.com/TAGHREEDAA/extendable-order-payment-api
cd extendable-order-payment-api
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
touch database/database.sqlite
php artisan migrate
php artisan serve
```

The API will be available at `http://localhost:8000/api`

## API Endpoints

### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/register` | Register a new user |
| POST | `/api/auth/login` | Login and get JWT token |
| POST | `/api/auth/logout` | Invalidate token |
| POST | `/api/auth/refresh` | Refresh token |

### Orders (requires authentication)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/orders` | List orders (filter: `?status=0`) |
| POST | `/api/orders` | Create order |
| GET | `/api/orders/{id}` | View order |
| PUT | `/api/orders/{id}` | Update order |
| DELETE | `/api/orders/{id}` | Delete order |

### Payments (requires authentication)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/payments` | List payments (filter: `?order_id=uuid`) |
| POST | `/api/payments` | Process payment |

## API Documentation

Import the Postman collection from `postman_collection.json` in the repo root. It includes examples for all endpoints with success and error cases.

Set the `base_url` variable to `http://localhost:8000/api` and add the JWT token from login to the `Authorization` header as `Bearer <token>`.

## Payment Gateway Extensibility

The system uses the **Strategy Pattern** with a **Factory** to make adding new payment gateways simple.

### Architecture

```
PaymentGatewayInterface  <-- contract
    |
    |-- PaypalGateway    <-- implements interface
    |-- StripeGateway    <-- implements interface
    |-- PayMobGateway    <-- implements interface
    |
PaymentGatewayFactory    <-- resolves gateway by enum
    |
PaymentService           <-- uses factory to process payments
```

### How to Add a New Gateway

**Step 1:** Create a new class in `app/PaymentGateways/` implementing `PaymentGatewayInterface`:

```php
<?php

namespace App\PaymentGateways;

use Illuminate\Support\Str;

class NewGateway implements PaymentGatewayInterface
{
    private string $apiKey;
    private string $apiSecret;

    public function __construct()
    {
        $this->apiKey = env('NEW_GATEWAY_API_KEY', '');
        $this->apiSecret = env('NEW_GATEWAY_API_SECRET', '');
    }

    public function processPayment(float $amount): array
    {
        // Integrate with the gateway SDK
        $success = true;

        return [
            'success' => $success,
            'transaction_id' => $success ? $this->getName() . '-' . Str::uuid() : null,
            'message' => $success ? 'Payment processed' : 'Payment declined',
        ];
    }

    public function getName(): string
    {
        return 'NewGateway';
    }
}
```

**Step 2:** Add a case to the `PaymentGateway` enum in `app/Enums/PaymentGateway.php`:

```php
case NewGateway = 3;
```

**Step 3:** Register it in `PaymentGatewayFactory` (`app/PaymentGateways/PaymentGatewayFactory.php`):

```php
PaymentGateway::NewGateway => app(NewGateway::class),
```

**Step 4:** Add your env variables to `.env`:

```
NEW_GATEWAY_API_KEY=your-key
NEW_GATEWAY_API_SECRET=your-secret
```

That's it. The new gateway is available through the API by passing its enum value in the `gateway` field.

## Architecture Decisions

### Strategy Pattern for Payment Gateways

Instead of using `if/else` or `switch` inside the service to handle different gateways, I used the Strategy Pattern. Each gateway has its own class, so adding a new one doesn't require changing existing code.

I also thought about using Laravel's service container directly, but a Factory class is easier to follow and you can see exactly what maps to what.

### Factory Class for Gateway Resolution

I looked at a few options:

- **Config-driven** — map gateways in a config file. Works well but felt like too much for 3 gateways.
- **Enum-based** — let the enum create the gateway itself. Simple, but the enum shouldn't do two things.
- **Factory class** — a separate class that creates the right gateway.

I picked the Factory because it does one thing, it's easy to test, and adding a new gateway is just one line.

### Order-Payment Relationship (hasMany)

At first it looks like one order = one payment. But what if the payment fails and the user tries again? Both attempts should be saved. That's why I used `hasMany` — it keeps the full history instead of overwriting failed records.

### Security Decisions

- **UUIDs instead of auto-increment IDs** — with sequential IDs like 1, 2, 3 an attacker can guess other resources. UUIDs are random and hard to predict.
- **Global scope for user isolation** — each user can only see their own orders and payments. This is enforced at the model level, not just in controllers.
- **JWT authentication** — stateless tokens that expire and can be refreshed.
- **Rate limiting** — 60 requests per minute to prevent abuse.
- **Input validation** — every request is validated through FormRequest classes before it reaches the service.
- **Gateway credentials in `.env`** — API keys are never hardcoded. They're loaded from environment variables.

## Business Rules

- Payments can only be processed for orders in **confirmed** status
- Orders **cannot be deleted** if they have associated payments
- Payment amount must match the order total
- Users can only access their own orders and payments

## Running Tests

```bash
php artisan test
```

Tests cover authentication, order CRUD, payment processing, validation, authorization, and business rules.

CI/CD is configured via GitHub Actions to run tests on every push.

## Assumptions

- Payment gateways are **simulated** (random success/failure) since this is a test task
- Only **credit card** is assumed as the payment method, the gateway (PayPal, Stripe, PayMob) processes it
- **SQLite** is used by default for simplicity
- UUIDs are used for order and payment IDs
- API rate limiting is set to 60 requests per minute
