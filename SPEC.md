# Cue — Project Specification
**Version:** 1.0  
**Author:** Hassiel Monterrosas  
**App name:** Cue  
**Tagline:** *Your signal for what's coming*  
**Bundle ID:** `com.cueapp.io`  
**App Store name:** `Cue — Cash Flow & Forecast`  
**Domain:** `cueapp.io`  
**API base URL:** `https://api.cueapp.io/api/v1`  

**Date:** March 2026  
**Status:** Ready for implementation

---

## 1. Project Overview

### 1.1 What is Cue?
Cue is a **personal cash flow SaaS** focused on financial projection, not just expense tracking. The core value is letting users see how their money will move across the next 3–12 months — projecting confirmed income, future recurring expenses, fund allocations, and debt installments — so they can make savings and allocation decisions in advance.

### 1.2 Key Differentiators
- **Projection-first:** Every transaction can be projected (future-dated) before it happens, then confirmed when it does.
- **Envelope funds:** Money is mentally separated into named funds (travel, savings goals, etc.) that reduce the operational balance automatically.
- **Debt/MSI tracker:** Multi-installment debts (credit card MSI, loans) are tracked with a countdown of remaining payments and projected impact per month.
- **Multi-currency:** Each workspace has a base currency; transactions in foreign currencies are converted at rate of the day (cached 24h).
- **API-first + Mobile-native:** All business logic is exposed through a versioned REST API. The mobile client is built with NativePHP Mobile v3 (Laravel in a native WebView on iOS/Android).

### 1.3 Business Model
- **Free plan:** 1 workspace, 50 transactions/month limit.
- **Pro plan:** Unlimited workspaces, unlimited transactions, multi-user workspace sharing.
- **Billing:** Polar.sh (Merchant of Record) via `danestves/laravel-polar` package.

---

## 2. Tech Stack

| Layer | Technology | Notes |
|---|---|---|
| Backend | Laravel 12 | API-only, no Blade views for the app |
| Authentication | Laravel Sanctum | Token-based, mobile auth flow |
| Mobile client | NativePHP Mobile v3 | iOS + Android, WebView-native |
| Desktop client | NativePHP Desktop v2 | Optional, future phase |
| Web client | SPA/PWA | Same API, browser-based fallback |
| Database | MySQL 8.0+ | UUID primary keys, soft deletes |
| Cache / Queue | Redis | Exchange rates (24h TTL), job queues |
| Billing | danestves/laravel-polar | Polar.sh MoR, community Laravel package |
| Permissions | spatie/laravel-permission | Role/plan enforcement |
| Feature flags | Laravel Pennant | Plan limit gates |
| Distribution | Bifrost (NativePHP) | App Store + Play Store |
| Testing | Pest PHP | Feature + unit tests |
| Code style | Laravel Pint | PSR-12 enforced |

---

## 3. Database Schema

### 3.1 Naming Conventions
- All primary keys: `uuid` type, named `id`.
- All foreign keys: `uuid` type, named `{table_singular}_id`.
- All tables use `created_at`, `updated_at` timestamps.
- Soft deletes (`deleted_at`) on: `workspaces`, `transactions`, `funds`, `debts`, `recurring_templates`.
- Monetary amounts stored as `decimal(15,4)` — never float.
- All amounts stored in **original currency**; conversion happens at query/display time.

### 3.2 Table: users

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | uuid | PK | |
| name | varchar(255) | NOT NULL | |
| email | varchar(255) | UNIQUE, NOT NULL | |
| password | varchar(255) | NOT NULL | |
| email_verified_at | timestamp | nullable | |
| polar_id | varchar(255) | nullable, index | Polar customer ID |
| created_at | timestamp | | |
| updated_at | timestamp | | |

### 3.3 Table: workspaces

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | uuid | PK | |
| owner_id | uuid | FK → users.id | |
| name | varchar(255) | NOT NULL | e.g. "Mis finanzas 2026" |
| currency | char(3) | NOT NULL | ISO 4217, e.g. "MXN" |
| opening_balance | decimal(15,4) | NOT NULL, default 0 | Initial balance when workspace created |
| projection_months | tinyint | NOT NULL, default 3 | User preference: 3, 6, or 12 |
| deleted_at | timestamp | nullable | |
| created_at | timestamp | | |
| updated_at | timestamp | | |

### 3.4 Table: workspace_users (pivot)

| Column | Type | Constraints | Notes |
|---|---|---|---|
| workspace_id | uuid | FK → workspaces.id | |
| user_id | uuid | FK → users.id | |
| role | enum | 'owner', 'member' | |
| created_at | timestamp | | |

### 3.5 Table: transactions

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | uuid | PK | |
| workspace_id | uuid | FK → workspaces.id, index | |
| recurring_template_id | uuid | FK → recurring_templates.id, nullable | Set if auto-generated |
| type | enum | 'income', 'expense' | |
| concept | varchar(255) | NOT NULL | e.g. "Quincena", "Renta" |
| amount | decimal(15,4) | NOT NULL | Always positive |
| currency | char(3) | NOT NULL | ISO 4217 |
| exchange_rate | decimal(15,6) | nullable | Rate to workspace base currency at time of transaction |
| category | varchar(100) | nullable | User-defined categories |
| projected_date | date | NOT NULL | The date this is expected to occur |
| confirmed_at | timestamp | nullable | NULL = projected; set = confirmed |
| notes | text | nullable | |
| deleted_at | timestamp | nullable | |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Indexes:** `(workspace_id, projected_date)`, `(workspace_id, confirmed_at)`, `(workspace_id, type)`

### 3.6 Table: funds

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | uuid | PK | |
| workspace_id | uuid | FK → workspaces.id | |
| name | varchar(255) | NOT NULL | e.g. "Viaje Illi", "Ahorro meta" |
| description | text | nullable | |
| target_amount | decimal(15,4) | nullable | Goal amount |
| current_balance | decimal(15,4) | NOT NULL, default 0 | Running balance of this fund |
| currency | char(3) | NOT NULL | |
| target_date | date | nullable | Goal deadline |
| color | varchar(7) | nullable | Hex color for UI |
| deleted_at | timestamp | nullable | |
| created_at | timestamp | | |
| updated_at | timestamp | | |

### 3.7 Table: fund_movements

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | uuid | PK | |
| fund_id | uuid | FK → funds.id | |
| transaction_id | uuid | FK → transactions.id, nullable | Linked transaction if auto-reduced |
| type | enum | 'deposit', 'withdrawal' | |
| amount | decimal(15,4) | NOT NULL | |
| note | varchar(255) | nullable | |
| movement_date | date | NOT NULL | |
| created_at | timestamp | | |
| updated_at | timestamp | | |

### 3.8 Table: debts

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | uuid | PK | |
| workspace_id | uuid | FK → workspaces.id | |
| name | varchar(255) | NOT NULL | e.g. "Boleto de avión MSI", "Préstamo BBVA" |
| total_amount | decimal(15,4) | NOT NULL | Full debt amount |
| installment_amount | decimal(15,4) | NOT NULL | Amount per installment |
| total_installments | smallint | NOT NULL | |
| paid_installments | smallint | NOT NULL, default 0 | |
| currency | char(3) | NOT NULL | |
| start_date | date | NOT NULL | Date of first installment |
| payment_day | tinyint | nullable | Day of month installment is due |
| notes | text | nullable | |
| deleted_at | timestamp | nullable | |
| created_at | timestamp | | |
| updated_at | timestamp | | |

### 3.9 Table: debt_installments

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | uuid | PK | |
| debt_id | uuid | FK → debts.id | |
| installment_number | smallint | NOT NULL | 1-based |
| amount | decimal(15,4) | NOT NULL | May differ from debt.installment_amount for last installment |
| due_date | date | NOT NULL | |
| paid_at | timestamp | nullable | NULL = pending |
| transaction_id | uuid | FK → transactions.id, nullable | Linked expense transaction when paid |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Index:** `(debt_id, due_date)`, `(debt_id, paid_at)`

### 3.10 Table: recurring_templates

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | uuid | PK | |
| workspace_id | uuid | FK → workspaces.id | |
| concept | varchar(255) | NOT NULL | |
| type | enum | 'income', 'expense' | |
| amount | decimal(15,4) | NOT NULL | |
| currency | char(3) | NOT NULL | |
| category | varchar(100) | nullable | |
| frequency | enum | 'weekly', 'biweekly', 'monthly', 'custom' | |
| frequency_day | tinyint | nullable | Day of month (monthly), or day of week (weekly) |
| next_date | date | NOT NULL | Next occurrence to generate |
| generate_ahead_days | smallint | NOT NULL, default 90 | How many days in advance to pre-generate |
| is_active | boolean | NOT NULL, default true | |
| deleted_at | timestamp | nullable | |
| created_at | timestamp | | |
| updated_at | timestamp | | |

### 3.11 Table: subscriptions (managed by laravel-polar)
Auto-generated by `php artisan polar:install`. Do not manually create.

### 3.12 Table: orders (managed by laravel-polar)
Auto-generated by `php artisan polar:install`. Do not manually create.

---

## 4. API Specification

### 4.1 Global Conventions
- **Base URL:** `/api/v1/`
- **Authentication:** `Authorization: Bearer {sanctum_token}` on all protected routes.
- **Content-Type:** `application/json` on all requests and responses.
- **Response envelope:**
```json
{
  "data": {},
  "meta": {},
  "message": "optional string"
}
```
- **Error envelope:**
```json
{
  "message": "Human-readable error",
  "errors": { "field": ["validation message"] }
}
```
- **Pagination:** Laravel's default cursor pagination. Query params: `cursor`, `per_page` (default 25, max 100).
- **Date format:** ISO 8601 (`2026-03-15` for dates, `2026-03-15T14:30:00Z` for datetimes).
- **Currency amounts:** Always returned as string decimal `"3800.0000"` to avoid float precision issues in clients.

### 4.2 Auth Endpoints

**POST /api/v1/auth/register**
```
Body: { name, email, password, password_confirmation }
Response 201: { data: { user, token } }
Response 422: validation errors
```
Actions: Creates user, creates default workspace (currency: MXN), returns Sanctum token.

**POST /api/v1/auth/login**
```
Body: { email, password, device_name }
Response 200: { data: { user, token } }
Response 401: { message: "Invalid credentials" }
```
Actions: Validates credentials, creates named Sanctum token (`device_name` for token management), returns token. The `device_name` field is required by Sanctum mobile auth pattern — client should send something like "iPhone 15 Pro" or "Android Pixel 9".

**POST /api/v1/auth/logout**
```
Headers: Authorization: Bearer {token}
Response 200: { message: "Logged out" }
```
Actions: Revokes the current token only.

**GET /api/v1/user**
```
Headers: Authorization: Bearer {token}
Response 200: { data: { user, active_workspace, plan } }
```

**POST /api/v1/auth/forgot-password**
```
Body: { email }
Response 200: { message: "Reset link sent" }
```

**POST /api/v1/auth/reset-password**
```
Body: { token, email, password, password_confirmation }
Response 200: { message: "Password reset" }
```

### 4.3 Workspace Endpoints

**GET /api/v1/workspaces**
```
Response 200: { data: [workspace, ...] }
```
Returns workspaces where user is owner or member.

**POST /api/v1/workspaces**
```
Body: { name, currency, opening_balance }
Response 201: { data: workspace }
Response 402: { message: "Free plan allows 1 workspace" }
```
Middleware: `CheckPlanLimits` — blocks if free plan and already has 1 workspace.

**GET /api/v1/workspaces/{workspace}**
```
Response 200: { data: workspace }
```

**PATCH /api/v1/workspaces/{workspace}**
```
Body: { name?, currency?, opening_balance?, projection_months? }
Response 200: { data: workspace }
```

**DELETE /api/v1/workspaces/{workspace}**
```
Response 200: { message: "Workspace deleted" }
```
Soft delete. Only owner can delete.

**GET /api/v1/workspaces/{workspace}/dashboard**
```
Response 200: {
  data: {
    operational_balance: "15464.5000",
    confirmed_balance: "8791.5000",
    projected_balance: "25045.5000",
    total_funds_allocated: "9000.0000",
    active_debts_count: 2,
    upcoming_payments: [...],
    monthly_summary: { income: "...", expenses: "...", net: "..." }
  }
}
```

**GET /api/v1/workspaces/{workspace}/cashflow**
```
Query params: months (3|6|12, default from workspace.projection_months), from_date (default today)
Response 200: {
  data: {
    periods: [
      {
        month: "2026-04",
        projected_income: "20000.0000",
        projected_expenses: "15000.0000",
        projected_net: "5000.0000",
        projected_balance_end: "20464.5000",
        confirmed_income: "0.0000",
        confirmed_expenses: "0.0000",
        transactions: [...]
      },
      ...
    ]
  }
}
```

### 4.4 Transaction Endpoints

All transaction routes are scoped: `/api/v1/workspaces/{workspace}/transactions`

**GET /api/v1/workspaces/{workspace}/transactions**
```
Query params:
  - month (YYYY-MM, default current month)
  - type (income|expense)
  - confirmed (true|false)
  - category
  - cursor, per_page
Response 200: { data: [transaction, ...], meta: { cursor, ... } }
```

**POST /api/v1/workspaces/{workspace}/transactions**
```
Body: {
  type,
  concept,
  amount,
  currency,
  projected_date,
  category?,
  notes?,
  fund_id?,        // if set, auto-moves amount from/to this fund
  confirm: false   // if true, sets confirmed_at to now
}
Response 201: { data: transaction }
Response 402: free plan limit check
```

**GET /api/v1/workspaces/{workspace}/transactions/{transaction}**
```
Response 200: { data: transaction }
```

**PATCH /api/v1/workspaces/{workspace}/transactions/{transaction}**
```
Body: any transaction fields (partial update)
Response 200: { data: transaction }
```

**DELETE /api/v1/workspaces/{workspace}/transactions/{transaction}**
```
Response 200: { message: "Transaction deleted" }
```
Soft delete.

**PATCH /api/v1/workspaces/{workspace}/transactions/{transaction}/confirm**
```
Body: { confirmed_at?: "2026-03-15T14:30:00Z" } (defaults to now)
Response 200: { data: transaction }
```
Sets `confirmed_at`. This is the key action to mark a projected transaction as real.

**POST /api/v1/workspaces/{workspace}/transactions/import**
```
Body: multipart/form-data, file: Excel (.xlsx)
Response 200: { data: { imported: 45, skipped: 2, errors: [...] } }
```
Parses the Excel format matching the user's current spreadsheet structure.

### 4.5 Recurring Templates Endpoints

**GET /api/v1/workspaces/{workspace}/recurring-templates**
```
Response 200: { data: [template, ...] }
```

**POST /api/v1/workspaces/{workspace}/recurring-templates**
```
Body: { concept, type, amount, currency, frequency, frequency_day?, next_date, category? }
Response 201: { data: template }
```
After creation, immediately generates projected transactions up to `generate_ahead_days` days out.

**PATCH /api/v1/workspaces/{workspace}/recurring-templates/{template}**
```
Body: any template fields
Response 200: { data: template }
```
When `amount` or `next_date` changes, regenerates future projected transactions.

**DELETE /api/v1/workspaces/{workspace}/recurring-templates/{template}**
```
Body: { delete_future_transactions: true|false }
Response 200: { message: "Template deleted" }
```

### 4.6 Fund Endpoints

**GET /api/v1/workspaces/{workspace}/funds**
```
Response 200: { data: [fund, ...] }
```
Each fund includes `current_balance`, `target_amount`, `progress_percentage`.

**POST /api/v1/workspaces/{workspace}/funds**
```
Body: { name, description?, target_amount?, currency, target_date?, color? }
Response 201: { data: fund }
```

**PATCH /api/v1/workspaces/{workspace}/funds/{fund}**
```
Body: any fund fields
Response 200: { data: fund }
```

**DELETE /api/v1/workspaces/{workspace}/funds/{fund}**
```
Response 200: { message: "Fund deleted" }
```
Only if `current_balance == 0`. Otherwise return 422 with suggestion to withdraw first.

**POST /api/v1/workspaces/{workspace}/funds/{fund}/deposit**
```
Body: { amount, note?, movement_date? }
Response 200: { data: { fund, movement } }
```
Decreases workspace operational balance by `amount`, increases fund balance by `amount`. Creates a linked expense transaction of category "fund_allocation".

**POST /api/v1/workspaces/{workspace}/funds/{fund}/withdraw**
```
Body: { amount, note?, movement_date? }
Response 200: { data: { fund, movement } }
```
Increases workspace operational balance by `amount`, decreases fund balance by `amount`.

**GET /api/v1/workspaces/{workspace}/funds/{fund}/movements**
```
Response 200: { data: [movement, ...] }
```

### 4.7 Debt Endpoints

**GET /api/v1/workspaces/{workspace}/debts**
```
Response 200: { data: [debt, ...] }
```
Each debt includes `remaining_installments`, `remaining_amount`, `next_installment`.

**POST /api/v1/workspaces/{workspace}/debts**
```
Body: {
  name,
  total_amount,
  installment_amount,
  total_installments,
  currency,
  start_date,
  payment_day?,
  notes?
}
Response 201: { data: debt }
```
Automatically generates all `debt_installments` records and their corresponding projected expense transactions.

**PATCH /api/v1/workspaces/{workspace}/debts/{debt}**
```
Body: any debt fields
Response 200: { data: debt }
```

**DELETE /api/v1/workspaces/{workspace}/debts/{debt}**
```
Response 200: { message: "Debt deleted" }
```
Deletes debt + all unpaid installments + their projected transactions.

**GET /api/v1/workspaces/{workspace}/debts/upcoming**
```
Query params: days (default 30, max 365)
Response 200: { data: [installment with debt info, ...] }
```
Returns all pending installments due within the next `days` days, sorted by `due_date`.

**PATCH /api/v1/workspaces/{workspace}/debts/{debt}/installments/{installment}/pay**
```
Body: { paid_at?: datetime }
Response 200: { data: installment }
```
Marks installment as paid, sets `paid_at`, increments `debt.paid_installments`, confirms the linked transaction.

### 4.8 Reports Endpoints

**GET /api/v1/workspaces/{workspace}/reports/monthly**
```
Query params: year (default current), month (default current)
Response 200: {
  data: {
    income_total, expense_total, net,
    by_category: [{ category, total, percentage }],
    transactions: [...]
  }
}
```

**GET /api/v1/workspaces/{workspace}/reports/projection**
```
Query params: months (3|6|12)
Response 200: { data: { periods: [...] } }
```
Same structure as `/cashflow` but optimized for the report view.

**GET /api/v1/workspaces/{workspace}/reports/export**
```
Query params: from, to, format (csv|xlsx)
Response: file download
```

### 4.9 Billing Endpoints (Polar)

**GET /api/v1/billing/plans**
```
Response 200: {
  data: {
    current_plan: "free"|"pro",
    is_subscribed: false,
    subscription: null|{...},
    plans: [{ id, name, price, features }]
  }
}
```

**POST /api/v1/billing/checkout**
```
Body: { product_id }
Response 200: { data: { checkout_url } }
```
Uses `$user->subscribe($product_id)->withSuccessUrl(...)` from laravel-polar.

**GET /api/v1/billing/portal**
```
Response 200: { data: { portal_url } }
```
Returns `$user->customerPortalUrl()` for the client to redirect to.

**POST /polar/webhook** (public, no auth)
```
Polar webhook handler — managed by laravel-polar package automatically.
Must be excluded from CSRF middleware.
```
Listens for: `subscription.created`, `subscription.updated`, `subscription.active`, `subscription.canceled`, `subscription.revoked`, `order.created`.

---

## 5. Core Business Logic

### 5.1 CashFlowService
Location: `app/Services/CashFlowService.php`

**Method: `getOperationalBalance(Workspace $workspace): string`**
```
Formula:
  opening_balance
  + SUM(confirmed income transactions)
  - SUM(confirmed expense transactions)
  - SUM(fund.current_balance for all active funds)
= operational_balance
```
This is the real money available right now, excluding fund allocations.

**Method: `getProjectedBalance(Workspace $workspace, Carbon $targetDate): string`**
```
Formula:
  operational_balance
  + SUM(projected income where projected_date <= targetDate AND confirmed_at IS NULL)
  - SUM(projected expense where projected_date <= targetDate AND confirmed_at IS NULL)
= projected_balance_at_date
```

**Method: `getMonthlyPeriods(Workspace $workspace, int $months): array`**
Returns array of period objects from current month to `$months` ahead, each with:
- `month` (YYYY-MM)
- `projected_income`, `projected_expenses`, `projected_net`
- `confirmed_income`, `confirmed_expenses`
- `balance_start`, `balance_end` (running)
- `deficit` (bool — if `balance_end < 0`)

### 5.2 ProjectionService
Location: `app/Services/ProjectionService.php`

**Method: `generateFromTemplate(RecurringTemplate $template): void`**
Generates projected transaction records from `template.next_date` up to `now() + generate_ahead_days`. Called on template creation and on schedule.

**Method: `regenerateFuture(RecurringTemplate $template): void`**
Deletes all unconfirmed future transactions linked to this template and re-generates. Called when template is edited.

**Scheduled command:** `GenerateProjectedTransactions` — runs daily via `schedule:run`. For each active template where `next_date <= now() + generate_ahead_days`, generates missing projected transactions.

### 5.3 CurrencyService
Location: `app/Services/CurrencyService.php`

**Method: `getRate(string $from, string $to): float`**
Fetches exchange rate from external API (e.g., `frankfurter.app` — free, no key required). Caches result in Redis for 24 hours with key `currency_rate:{from}_{to}`.

**Method: `convertToBase(float $amount, string $fromCurrency, Workspace $workspace): string`**
Converts `$amount` from `$fromCurrency` to `$workspace->currency` using current rate.

**Method: `formatAmount(string $amount, string $currency): string`**
Returns formatted amount with currency symbol for display.

### 5.4 CheckPlanLimits Middleware
Location: `app/Http/Middleware/CheckPlanLimits.php`

Checks on every authenticated API request for limit-reaching actions:
```
If user is NOT subscribed (free plan):
  - On POST /transactions: count transactions in current month for workspace
    → if count >= 50: abort 402 with { message, limit: 50, current: count }
  - On POST /workspaces: count user workspaces
    → if count >= 1: abort 402 with { message, limit: 1, current: count }
```

---

## 6. NativePHP Mobile Integration

### 6.1 Architecture Model
NativePHP Mobile v3 runs the Laravel application embedded inside a native WebView on iOS and Android. The app shell (navigation bar, tab bar, status bar) is native via EDGE Components. The business UI runs as Blade/Alpine views served by the embedded Laravel runtime.

**Important implication:** There are **two Laravel instances**:
1. **Server (API)** — runs on your production server, handles all data, auth, business logic.
2. **Client (NativePHP app)** — runs embedded in the mobile device. This client app calls the server API via HTTP using the Sanctum token.

The NativePHP mobile app is essentially a thin client that:
- Stores the Sanctum token in `SecureStorage` (Keychain on iOS, Keystore on Android).
- Makes all requests to the production API using that token.
- Renders the UI via Blade + Alpine views.
- Uses NativePHP plugins for native device features.

### 6.2 Auth Flow (Mobile)
```
1. User opens app (first time)
2. App checks SecureStorage for existing token
3. If no token → show login/register screens
4. User submits credentials
5. App POSTs to https://api.cueapp.io/api/v1/auth/login
   Body: { email, password, device_name: NativePhone::name() }
6. Server responds with { token }
7. App stores token in SecureStorage::set('sanctum_token', token)
8. App fetches /api/v1/user to hydrate user state
9. App navigates to main dashboard

On subsequent opens:
1. App retrieves token from SecureStorage::get('sanctum_token')
2. App fetches /api/v1/user to validate token is still valid
3. If 401 → clear token → show login
4. If 200 → navigate to dashboard
```

### 6.3 Biometric Auth Flow
```
1. After first login, ask user if they want to enable biometrics
2. If yes: store flag in SecureStorage::set('biometrics_enabled', 'true')
3. On subsequent opens:
   a. Retrieve token from SecureStorage
   b. Show biometric prompt (Biometrics::prompt())
   c. On success → proceed with existing token
   d. On failure → show PIN or password fallback
```

### 6.4 EDGE Components Configuration
```php
// Bottom Navigation tabs:
[
  { label: 'Dashboard', icon: 'house', route: '/' },
  { label: 'Ledger', icon: 'list.bullet', route: '/ledger' },
  { label: 'Funds', icon: 'folder', route: '/funds' },
  { label: 'Debts', icon: 'creditcard', route: '/debts' },
  { label: 'Settings', icon: 'gear', route: '/settings' },
]
```

### 6.5 NativePHP Plugins Used

| Plugin | Use |
|---|---|
| SecureStorage | Store/retrieve Sanctum token |
| Biometrics | FaceID/Fingerprint login |
| Firebase | Push notifications (payment reminders, deficit alerts) |
| Network | Detect offline state, queue sync |
| Dialog | Native confirmation dialogs |
| Share | Export reports as PDF |

### 6.6 Environment Configuration
```env
# In NativePHP mobile app config:
NATIVEPHP_APP_ID=com.cueapp.io
NATIVEPHP_APP_NAME="Cue"
NATIVEPHP_APP_VERSION=1.0.0
API_BASE_URL=https://api.cueapp.io/api/v1
```

### 6.7 Distribution
Use **Bifrost** (bifrost.nativephp.com) for build and distribution:
- iOS → App Store via Xcode Cloud / Bifrost
- Android → Google Play via Bifrost
- Bundle ID must be set before first store submission and never changed.

---

## 7. Polar Billing Integration

### 7.1 Installation
```bash
composer require danestves/laravel-polar
php artisan polar:install
```
Publishes: config, migrations (subscriptions, orders tables), views.

### 7.2 Environment Variables
```env
POLAR_ACCESS_TOKEN=your_access_token_from_polar_dashboard
POLAR_WEBHOOK_SECRET=your_webhook_secret_from_polar_dashboard
POLAR_PATH=polar
```

### 7.3 User Model
```php
use Danestves\LaravelPolar\Billable;

class User extends Authenticatable {
    use Billable;
}
```

### 7.4 CSRF Exclusion (bootstrap/app.php)
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: ['polar/*']);
})
```

### 7.5 Products to Create in Polar Dashboard
| Product | Price | Billing |
|---|---|---|
| Cue Pro | $X USD/month | Recurring subscription |
| Cue Pro Yearly | $X USD/year | Recurring subscription (discounted) |

After creating, save the product IDs in `.env`:
```env
POLAR_PRODUCT_PRO_MONTHLY=product_id_here
POLAR_PRODUCT_PRO_YEARLY=product_id_here
```

### 7.6 Key Billing Methods
```php
// Create checkout (returns redirect response)
$user->subscribe(config('polar.products.pro_monthly'))
     ->withSuccessUrl(url('/billing/success'));

// Check subscription status
$user->subscribed();           // bool
$user->subscription()->active(); // bool
$user->subscription()->canceled(); // bool

// Customer portal (manage/cancel subscription)
return $user->redirectToCustomerPortal();
// or get URL for API response:
$url = $user->customerPortalUrl();
```

### 7.7 Webhook Events to Handle
Register these events in the Polar dashboard webhook settings:
- `order.created` — log payment
- `subscription.created` — activate Pro features via Pennant
- `subscription.active` — ensure Pro is active
- `subscription.canceled` — schedule downgrade to Free at period end
- `subscription.revoked` — immediately downgrade to Free

### 7.8 Plan Enforcement with Laravel Pennant
```php
// Define features in AppServiceProvider:
Feature::define('unlimited_transactions', fn(User $user) => $user->subscribed());
Feature::define('multiple_workspaces', fn(User $user) => $user->subscribed());

// Check in CheckPlanLimits middleware:
if (!Feature::active('unlimited_transactions')) {
    // apply 50 tx/month limit
}
```

---

## 8. Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── V1/
│   │           ├── AuthController.php
│   │           ├── WorkspaceController.php
│   │           ├── TransactionController.php
│   │           ├── RecurringTemplateController.php
│   │           ├── FundController.php
│   │           ├── DebtController.php
│   │           ├── ReportController.php
│   │           └── BillingController.php
│   ├── Middleware/
│   │   └── CheckPlanLimits.php
│   └── Resources/
│       └── Api/
│           ├── UserResource.php
│           ├── WorkspaceResource.php
│           ├── TransactionResource.php
│           ├── FundResource.php
│           ├── FundMovementResource.php
│           ├── DebtResource.php
│           ├── DebtInstallmentResource.php
│           └── RecurringTemplateResource.php
├── Models/
│   ├── User.php
│   ├── Workspace.php
│   ├── Transaction.php
│   ├── Fund.php
│   ├── FundMovement.php
│   ├── Debt.php
│   ├── DebtInstallment.php
│   └── RecurringTemplate.php
├── Services/
│   ├── CashFlowService.php
│   ├── ProjectionService.php
│   └── CurrencyService.php
├── Console/
│   └── Commands/
│       └── GenerateProjectedTransactions.php
└── Observers/
    ├── DebtObserver.php          (auto-generate installments on create)
    └── RecurringTemplateObserver.php (auto-generate transactions on create/update)

routes/
└── api.php                       (all API routes, versioned under /v1/)

tests/
├── Feature/
│   ├── Auth/
│   │   ├── LoginTest.php
│   │   ├── RegisterTest.php
│   │   └── LogoutTest.php
│   ├── Transactions/
│   │   ├── CreateTransactionTest.php
│   │   ├── ConfirmTransactionTest.php
│   │   └── PlanLimitsTest.php
│   ├── Funds/
│   │   └── FundMovementsTest.php
│   ├── Debts/
│   │   └── DebtInstallmentsTest.php
│   └── CashFlow/
│       ├── ProjectionTest.php
│       └── CashFlowServiceTest.php
└── Unit/
    ├── CashFlowServiceTest.php
    └── CurrencyServiceTest.php
```

---

## 9. Routes File (routes/api.php)

```php
Route::prefix('v1')->group(function () {

    // Public routes
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
    });

    // Protected routes
    Route::middleware(['auth:sanctum', 'check.plan.limits'])->group(function () {

        Route::get('user', [AuthController::class, 'user']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        // Workspaces
        Route::apiResource('workspaces', WorkspaceController::class);
        Route::get('workspaces/{workspace}/dashboard', [WorkspaceController::class, 'dashboard']);
        Route::get('workspaces/{workspace}/cashflow', [WorkspaceController::class, 'cashflow']);

        // Workspace-scoped resources
        Route::prefix('workspaces/{workspace}')->group(function () {

            // Transactions
            Route::apiResource('transactions', TransactionController::class);
            Route::patch('transactions/{transaction}/confirm', [TransactionController::class, 'confirm']);
            Route::post('transactions/import', [TransactionController::class, 'import']);

            // Recurring templates
            Route::apiResource('recurring-templates', RecurringTemplateController::class);

            // Funds
            Route::apiResource('funds', FundController::class);
            Route::post('funds/{fund}/deposit', [FundController::class, 'deposit']);
            Route::post('funds/{fund}/withdraw', [FundController::class, 'withdraw']);
            Route::get('funds/{fund}/movements', [FundController::class, 'movements']);

            // Debts
            Route::apiResource('debts', DebtController::class);
            Route::get('debts/upcoming', [DebtController::class, 'upcoming']);
            Route::patch('debts/{debt}/installments/{installment}/pay', [DebtController::class, 'payInstallment']);

            // Reports
            Route::prefix('reports')->group(function () {
                Route::get('monthly', [ReportController::class, 'monthly']);
                Route::get('projection', [ReportController::class, 'projection']);
                Route::get('export', [ReportController::class, 'export']);
            });
        });

        // Billing
        Route::prefix('billing')->group(function () {
            Route::get('plans', [BillingController::class, 'plans']);
            Route::post('checkout', [BillingController::class, 'checkout']);
            Route::get('portal', [BillingController::class, 'portal']);
        });
    });
});
```

---

## 10. Implementation Phases

### Phase 1 — Foundation (Weeks 1–3)
**Goal:** Working API with auth, workspaces, transactions, and basic projection.

1. `laravel new cue-api --pest`
2. Install and configure: `sanctum`, `spatie/laravel-permission`, `laravel/pennant`
3. Create all migrations (Section 3)
4. Create all Eloquent models with relationships, casts, and fillable
5. Implement `AuthController` (register, login, logout, user)
6. Implement `WorkspaceController` with `CheckPlanLimits` middleware
7. Implement `TransactionController` with confirm endpoint
8. Implement `CashFlowService` (operational + projected balance)
9. Implement `ProjectionService` + `RecurringTemplateController`
10. Implement `GenerateProjectedTransactions` scheduled command
11. Implement `CurrencyService` with Frankfurter API + Redis cache
12. Write Pest feature tests for auth and transactions
13. Test all endpoints with a REST client (Insomnia/Postman collection)

**Phase 1 deliverable:** Fully functional API that replicates the Excel ledger behavior — create transactions (projected and confirmed), view running balance, project future months, create recurring templates that auto-generate transactions.

### Phase 2 — Funds + Debts + NativePHP (Weeks 4–5)
**Goal:** Complete the financial model and ship the mobile app shell.

1. Implement `FundController` with deposit/withdraw logic
2. Implement `DebtController` with installment auto-generation (`DebtObserver`)
3. Implement `DebtController@upcoming` and `@payInstallment`
4. Write tests for funds and debts
5. Set up NativePHP Mobile v3 on the same Laravel codebase
6. Configure EDGE Components: Bottom Navigation (5 tabs)
7. Build auth screens (login/register) as Blade + Alpine views
8. Implement SecureStorage token persistence in mobile app
9. Implement Biometrics plugin for FaceID/Fingerprint login
10. Connect mobile UI views to production API endpoints
11. Configure Firebase plugin for push notifications
12. Build basic dashboard view, ledger view, funds view, debts view
13. Test on iOS Simulator and Android Emulator

### Phase 3 — SaaS + Dashboard + Polish (Weeks 6–8)
**Goal:** Production-ready SaaS with billing, dashboard, and app store distribution.

1. Install and configure `danestves/laravel-polar`
2. Create products in Polar sandbox dashboard
3. Implement `BillingController` (plans, checkout, portal)
4. Configure Pennant feature flags tied to subscription status
5. Test full billing flow in Polar sandbox
6. Implement `ReportController` (monthly, projection, export)
7. Build dashboard UI with Chart.js (cash flow bar chart, fund progress, debt countdown)
8. Implement Excel import (`TransactionController@import`)
9. Multi-currency: add currency selector to transaction form, display converted amounts
10. Multi-workspace: workspace switcher in mobile nav
11. Push notifications for: upcoming payment reminders (3 days before), deficit alerts
12. Set up Bifrost for App Store + Google Play submission
13. Switch Polar from sandbox to production
14. Write end-to-end tests for critical paths
15. Deploy API to production server

---

## 11. Environment Variables Reference

```env
# App
APP_NAME="Cue API"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://api.cueapp.io

# Database
DB_CONNECTION=mysql
DB_HOST=
DB_PORT=3306
DB_DATABASE=cue
DB_USERNAME=
DB_PASSWORD=

# Redis
REDIS_HOST=
REDIS_PASSWORD=
REDIS_PORT=6379

# Sanctum
SANCTUM_STATEFUL_DOMAINS=

# Polar Billing
POLAR_ACCESS_TOKEN=
POLAR_WEBHOOK_SECRET=
POLAR_PATH=polar
POLAR_PRODUCT_PRO_MONTHLY=
POLAR_PRODUCT_PRO_YEARLY=

# Currency API (no key required for frankfurter.app)
CURRENCY_CACHE_TTL=86400

# NativePHP (mobile app)
NATIVEPHP_APP_ID=com.cueapp.io
NATIVEPHP_APP_NAME="Cue"
NATIVEPHP_APP_VERSION=1.0.0
API_BASE_URL=https://api.cueapp.io/api/v1
```

---

## 12. Key Rules for Claude Code

When implementing this project, Claude Code must follow these rules without exception:

1. **Never store float amounts** — always `decimal(15,4)` in DB, always `string` in JSON responses.
2. **All monetary math in PHP** — use `bcadd()`, `bcsub()`, `bcmul()`, `bcdiv()` with precision 4. Never use native `+`, `-`, `*`, `/` on monetary values.
3. **UUID everywhere** — no auto-increment IDs. Use `Str::uuid()` for model creation.
4. **All API responses use Resources** — never return raw Model or array from controller.
5. **Scope all workspace queries** — every query inside a workspace context must include `where('workspace_id', $workspace->id)` or use the relationship. Never query globally.
6. **Soft delete always** — never hard delete transactions, funds, debts, or templates.
7. **Confirm vs projected is a timestamp** — `confirmed_at` IS NULL means projected; IS NOT NULL means confirmed. Never use a boolean.
8. **Exchange rates never stored as "converted"** — always store `amount` in original currency + `exchange_rate` at time of transaction. Conversion is computed at read time.
9. **CheckPlanLimits before every write** — apply the middleware to all POST/PATCH routes, not just workspace creation.
10. **Test every endpoint** — every controller action must have a corresponding Pest feature test before being considered done.
11. **No Livewire on the API** — the server is API-only. Livewire is only for future admin panel if needed.
12. **All comments in code in English** — as per project convention.
