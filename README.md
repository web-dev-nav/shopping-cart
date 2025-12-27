# Simple E-commerce Shopping Cart (Laravel 12 + Breeze React)

A small Laravel 12 project implementing a simple e-commerce shopping cart with:

- Product browsing
- User-owned cart (DB persisted, tied to authenticated user)
- Update quantities / remove items
- Checkout (creates an order and decrements stock)
- **Low Stock Notification** (queued job + email to dummy admin)
- **Daily Sales Report** (scheduled job every evening + email to dummy admin)

**Frontend:** Laravel Breeze (React + Inertia) + Tailwind CSS  
**Backend:** Laravel 12  
**Queue driver:** database (`jobs` table)

---

## Important: which URL to open

This stack uses Laravel + Inertia (server-rendered routes with a React SPA layer).

- **Open the app on Laravel’s server port** (example): `http://127.0.0.1:8000/products`
- **Do not open the app on Vite port 5173**. `http://localhost:5173` is only the asset dev server.

---

## Requirements

- PHP 8.2+
  - SQLite extensions enabled: `pdo_sqlite` and `sqlite3`
- Composer
- Node.js + npm
- (Optional) Git
- (Optional for email inbox) Mailpit or Mailtrap

---

## Quick start (recommended for a demo/video)

From the `shopping-cart/` directory:

### 1) Install
```bash
composer install
copy .env.example .env
php artisan key:generate
npm install
```

### 2) Ensure cache/session/view directories exist (important)
If you see errors like **“Please provide a valid cache path”**, your clone is missing Laravel’s required runtime directories.

This repo keeps the directory structure via placeholder `.gitignore` files under [`storage/framework`](storage/framework/.gitignore:1). If your environment still needs them, create them:

**Linux/macOS (bash):**
```bash
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
chmod -R 775 storage bootstrap/cache
```

**Windows (PowerShell):**
```powershell
New-Item -ItemType Directory -Force -Path `
  storage/framework/cache/data, `
  storage/framework/sessions, `
  storage/framework/views | Out-Null
```

### 3) Create DB + seed demo data
```bash
php artisan migrate:fresh --seed
```

### 3) Run the app (3 terminals is best)

**Terminal A (Laravel web server):**
```bash
php artisan serve
```
Open:
- `http://127.0.0.1:8000/products`
- `http://127.0.0.1:8000/login`
- `http://127.0.0.1:8000/register`

**Terminal B (Vite assets / hot reload):**
```bash
npm run dev
```

**Terminal C (Queue worker – required for low stock emails):**
```bash
php artisan queue:listen
```

**Optional Terminal D (Scheduler – required for daily report scheduling):**
```bash
php artisan schedule:work
```

---

## Demo accounts

Seeded users (password is `password`):

- Dummy admin email recipient: `admin@example.com`
- Test shopper: `test@example.com`

You can also register a new user at:
- `http://127.0.0.1:8000/register`

---

## Main routes

Public:
- `GET /products` – product browsing

Authenticated:
- `GET /cart` – view cart
- `POST /cart/items` – add to cart
- `PATCH /cart/items/{cartItem}` – update quantity
- `DELETE /cart/items/{cartItem}` – remove item
- `POST /checkout` – create order, decrement stock, clear cart

---

## How stock works (expected behavior)

Stock **does not** decrease when you add items to the cart.  
Stock **does** decrease when you **checkout** successfully.

Implementation:
- Cart changes: `App\Http\Controllers\CartController`
- Stock decrement + order creation: `App\Http\Controllers\CheckoutController`

---

## Email testing (Windows-friendly, no Docker required)

You have 3 ways to verify emails.

### Option A (default, easiest): log mailer (no setup)
Emails are written to `storage/logs/laravel.log`.

In `.env`:
```env
MAIL_MAILER=log
```

To watch logs on Windows (PowerShell):
```powershell
Get-Content .\storage\logs\laravel.log -Wait
```

### Option B (best inbox experience, no Docker): Mailtrap (cloud)
1) Create a free Mailtrap account, create an inbox, copy SMTP credentials.
2) Update `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=YOUR_MAILTRAP_USER
MAIL_PASSWORD=YOUR_MAILTRAP_PASS
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="no-reply@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```
3) Trigger the email and check it in the Mailtrap inbox UI.

### Option C (local inbox, no Docker): Mailpit Windows binary
1) Download Mailpit Windows release (exe) from the Mailpit GitHub Releases page.
2) Run the `mailpit.exe` (double-click).
3) Update `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="no-reply@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```
4) Open inbox:
- `http://127.0.0.1:8025`

---

## Key requirement: Low Stock Notification (Queue Job)

### What it does
When checkout causes a product’s stock to drop to or below the threshold, a queued job sends an email to the dummy admin.

- Job: `App\Jobs\SendLowStockNotificationJob`
- Mail: `App\Mail\LowStockNotificationMail`
- View: `resources/views/emails/low-stock.blade.php`
- Triggered in: `App\Http\Controllers\CheckoutController`

### Config
In `.env`:
```env
SHOP_ADMIN_EMAIL=admin@example.com
LOW_STOCK_THRESHOLD=5
QUEUE_CONNECTION=database
```

### How to test quickly (recommended)
The seeded product **Notebook** starts at stock **4**, which is already “low” (<= 5).

Steps:
1) Login as `test@example.com` (password `password`)
2) Go to `/products`
3) Add **Notebook** to cart (qty 1)
4) Go to `/cart`
5) Click **Checkout**
6) Make sure the queue worker is running:
   - `php artisan queue:listen`
7) Verify the email:
   - If `MAIL_MAILER=log`: check `storage/logs/laravel.log`
   - If Mailtrap/Mailpit: check the inbox UI

---

## Key requirement: Daily Sales Report (Scheduled Job)

### What it does
Every evening, the scheduler runs a job that aggregates all products sold that day (from `order_items`) and emails a report to the dummy admin.

- Scheduled in: `routes/console.php`
- Job: `App\Jobs\SendDailySalesReportJob`
- Mail: `App\Mail\DailySalesReportMail`
- View: `resources/views/emails/daily-sales-report.blade.php`

### Config
In `.env`:
```env
DAILY_SALES_REPORT_TIME=20:00
SHOP_ADMIN_EMAIL=admin@example.com
```

### How to test quickly (recommended for recording)
The report now runs inline and self-guards once per day, so it does **not** require a queue worker.

1) Make at least one checkout today (so there are `order_items`)
2) Set the report time to a few minutes from “now”, e.g.:
```env
DAILY_SALES_REPORT_TIME=17:35
```
3) Run the scheduler:
```bash
php artisan schedule:work
```
   - It checks every minute and will fire once past the configured time (cached per day).
4) Wait until the time hits. Then verify the email in logs or inbox.

Production cron example (runs scheduler every minute):
```cron
* * * * * cd /path/to/shopping-cart && php artisan schedule:run >> /dev/null 2>&1
```

## Running tests
```bash
php artisan test
```

---


