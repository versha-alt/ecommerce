# Olive Commerce admin

A custom olive-green React admin panel connected to Laravel 13 and MySQL. This implements the admin scope from the latest requirements supplied in chat; those requirements supersede the older NestJS/global-platform architecture documents.

## Open the local project

```powershell
npm run dev
```

- Admin: http://127.0.0.1:5173
- Laravel API: http://127.0.0.1:8000/api/v1
- Admin login: `admin@leekav.com`. Configure a strong `ADMIN_PASSWORD` in the backend `.env`.

The current workspace includes clearly labelled sample business data. It never sends provider requests. Browser verification orders are explicitly labelled and cancelled after testing.

The Windows launch scripts prefer the downloaded, checksum-verified PHP 8.4 and Node 24 runtimes in `.runtime/`. Your XAMPP PHP and system Node remain unchanged. On other machines use PHP 8.3+, Composer, Node 22.12+, and a MySQL 8-compatible database. Laravel 13 requirements: https://laravel.com/docs/13.x/releases.

## Install on another machine

```powershell
npm install
cd apps/backend
composer install
Copy-Item .env.example .env
# Set database credentials, ADMIN_EMAIL and a strong ADMIN_PASSWORD in .env.
php artisan key:generate
php artisan migrate
php artisan db:seed
cd ../..
npm run dev
```

Create the database named in `.env` first. The current local instance uses a dedicated `olive_admin` database on the existing XAMPP MySQL-compatible server. No existing databases were overwritten. An incomplete earlier demo-seed attempt is preserved in the separate `olive_commerce` database.

`DEMO_WORKSPACE=false` gives a blank business workspace plus the first administrator and store settings. Set it to true before first seeding for illustrative records. Reseeding preserves an existing workspace and does not reset passwords. Do not use the local demo password in deployment.

Docker alternative: `npm run services:up` provisions MySQL on localhost:3307 and Redis on localhost:6379. Update the Laravel database port, username and password to match `compose.yaml`. The actual local app currently uses XAMPP on port 3306.

## Implemented admin functionality

- Dashboard: today/week/month filters, paid sales, orders, new customers, average order value, sales chart, top sellers, recent orders, pending-payment attention and low-stock alerts.
- All 12 master modules: brands, three-level categories, attribute definitions, products, inventory, customers, delivery zones, VAT rules, coupons, homepage banners, CMS pages, admin users and roles.
- Operations: orders, payment attempts and evidence, return requests and verified refund records, enquiries, email template editing/preview, store and integration settings.
- Reports: sales grouped daily/weekly/monthly, brand/category sales, top products, orders, payments, customers, stock and coupon usage, with date filters and Excel-compatible CSV exports.
- Search, status filters, sorting, pagination, selection and CSV exports on module lists.
- Image/PDF uploads; uploaded images are resized to a maximum 1600 pixels and converted to WebP.
- Password hashing, expiring hashed API tokens, login throttling and server-enforced Admin/Sales/Store Manager permissions. Restricted module data is excluded from workspace responses.
- Optimistic record versions, soft retirement, before/after audit history, encrypted provider secrets, stock movement reasons, transactional reservations and idempotent order actions.
- Customers, orders and return requests originate in the storefront; admin creation is blocked. Administrators update existing customers, manage order actions and review return requests.
- Product slugs are required and unique through backend validation and a generated database column with a unique index. Selected subcategories expand to all ancestors in both product data and the product_categories relation; category moves synchronize memberships.
- Discounts support amount off products, amount off orders, Buy X get Y and free shipping with server-enforced eligibility, restrictions, minimum purchases, usage limits and dates. One code applies per order; Buy X get Y reward items must be in the cart.
- Payment methods configure online gateways (including PayPal, Razorpay and M-Pesa) and manual/offline methods such as COD. Secrets are encrypted and omitted from API/audit responses. Legacy gateway settings migrate into this module.
- Order and return status transitions have persisted histories. Existing order history is reconstructed from audit events where available.
- Shared checkout service creation calculates KES totals in integer minor units, snapshots product/customer/delivery/VAT details, applies eligible coupons and reserves stock. Dispatch deducts stock; unpaid cancellation releases reservations. Payment/fulfilment/order states stay separate.
- Printable order invoices (these are not KRA fiscal invoices).

## Operational boundaries

This is a local admin application, not a finished production storefront. The Next.js storefront from the front-store scope is not implemented in this admin task.

Provider fields are configuration storage, not completed live connectors. Daraja STK Push/callback verification, the chosen card gateway, SMTP/SES sending, fiscal invoices, and GA4/Meta storefront activation still require integration work and credentials. Their status stays visibly **Not connected**. Admins cannot create payments or manually mark orders paid. Checkout creates pending transaction attempts when a payment_method_id is supplied to the shared order service. Verified server-side events complete or fail those attempts and persist order payment status. Refund controls record externally verified evidence and reconcile transaction refund status; they do not transfer money. Returned stock is restored only through an explicit inventory receipt after inspection.

Products can record Simple/Variable type, but configurable variant generation and attribute groups/sets are not implemented. Variable parents are not sellable and hold no stock. Product specifications are editable text; attribute definitions are maintained separately. Advanced price schedules, partial shipments, line-level returns, automatic reservation expiry, imports with previews, fine-grained configurable permissions and background email delivery are follow-up work.

The current order policy requires an active tax rule and delivery zone, confirms payment before dispatch, and permits cancellation only before payment/dispatch. Shipping uses discounted merchandise subtotal for free-delivery eligibility; shipping itself is not taxed in this version. Confirm the production policy before launch. Sample VAT data is illustrative configuration, not tax advice.

Record lists are cached for 30 seconds and invalidated on mutations. Local `CACHE_STORE=file` keeps the app working without Redis. Use `CACHE_STORE=redis` and `REDIS_CLIENT=predis` once Redis is available. SSL and Cloudflare/CDN require deployment and domain configuration; CDN and tracking IDs can be stored in settings.

## Validation

```powershell
npm run build
npm test
# Requires the app running and Edge installed:
.runtime/node-v24.13.1-win-x64/node.exe scripts/browser-smoke.mjs
```

Laravel tests use an isolated in-memory test database, never the business database. Browser smoke checks login, navigation across all sections, product search, empty initial category selections and mobile overflow. `scripts/browser-workflows.mjs` checks protected admin creation, persisted order history, all four discount creation flows, online/manual payment configuration, settings navigation, CSV export and mobile layout without changing business records.

## Source layout

- `apps/admin`: React/Vite interface and olive-green design system.
- `apps/backend`: Laravel API, MySQL migrations, permissions, validation, records, audits and seeders.
- `scripts`: launch, build and verification scripts.
- `compose.yaml`: optional local MySQL/Redis infrastructure.
- `docs`: supplied historical specifications, preserved for reference.

Environment files, uploads, runtime binaries and vendor dependencies are ignored by Git. Back up MySQL and uploaded files together before deployment.

Payment history is separate from Payment methods. Admins can review, filter/export and annotate transactions. Financial fields are immutable through the admin API. The internal adapter endpoint `POST /api/v1/payment-events` requires `PAYMENT_EVENTS_SECRET` (32+ characters), an `X-Payment-Timestamp` within five minutes, and `X-Payment-Signature` equal to HMAC-SHA256 of `timestamp.raw_request_body`. Payload: `event_id`, checkout attempt UUID `payment_id`, `method`, `reference`, `amount`, `currency` (KES), and `status` (Successful/Failed). Gateway adapters must verify their provider-specific response before sending a normalized event server-to-server. This endpoint does not accept raw provider webhooks or browser payment claims; it stays disabled until configured. Retries are idempotent and provider transaction references have a database unique index.


