# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Project Overview

Full-stack e-commerce platform built with **Laravel 12** featuring role-based access control (Admin, Provider, User). Uses **nwidart/laravel-modules** for modular architecture and **Spatie Laravel Permission** for RBAC.

## Development Commands

### Setup
```bash
# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database setup (uses SQLite by default)
php artisan migrate

# Optional: Seed roles and test data
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=PaymentMethodSeeder
```

### Running the Application
```bash
# Run all services (server, queue, logs, vite)
composer dev

# Or run individually:
php artisan serve              # Development server
php artisan queue:listen       # Queue worker
php artisan pail              # Log viewer
npm run dev                    # Vite dev server

# Build for production
npm run build
php artisan optimize
```

### Testing
```bash
# Run all tests (using Pest)
composer test

# Run specific test file
php artisan test --filter=CartMergeTest

# Run tests with coverage
php artisan test --coverage
```

### Code Quality
```bash
# Format code (Laravel Pint)
./vendor/bin/pint

# Static analysis (PHPStan/Larastan)
./vendor/bin/phpstan analyse

# PHP CS Fixer
./vendor/bin/php-cs-fixer fix
```

## Architecture

### Modular Structure

This application uses **nwidart/laravel-modules** package. Each module is self-contained with its own routes, controllers, models, and views.

**Modules:**
- `Admin` - Admin dashboard and user/provider management
- `Provider` - Provider/seller dashboard and functionality
- `Products` - Product and category management (shared by Admin/Provider)
- `Cart` - Shopping cart functionality
- `Orders` - Order management
- `Payments` - Payment processing (Stripe, Razorpay)
- `User` - User-specific features
- `Location` - Country/state/city management

**Module Structure:**
```
Modules/{ModuleName}/
├── app/
│   ├── Http/Controllers/
│   ├── Models/
│   └── Providers/
├── config/
├── database/
├── resources/
│   ├── js/
│   └── views/
├── routes/web.php
├── tests/
└── module.json
```

Module routes are automatically loaded by their service providers - no manual includes needed.

### Authentication & Authorization

**Authentication System:**
- Uses **Laravel Breeze** as base
- Custom portal-based login system: `/login` (users) vs `/admin/login` (admin/provider)
- Email OTP verification system for user registration
- Controllers: `PortalLoginController`, `EmailOtpController`

**Authorization Pattern:**
- **Spatie Permission** package provides `hasRole()` method on User model
- User model has both `role_id` (denormalized) and Spatie roles relationship
- Custom middleware: `EnsureRoleForPath` (aliased as `ensure_role`) handles role-based route protection
- Additional middleware: `SyncRoleSession` keeps role synced in session

**Role Hierarchy:**
1. **Admin** - Full system control, manages users/providers/products
2. **Provider** - Manages own products, views own orders/sales
3. **User** - Customer role, shopping and order placement

**Route Protection Pattern:**
```php
Route::middleware(['auth','ensure_role:admin'])->group(function () {
    // Admin-only routes
});

Route::middleware(['auth','ensure_role:provider'])->group(function () {
    // Provider-only routes  
});
```

### Cart System

**Dual Storage Pattern:**
- **Guest users:** Cart stored in session (`session('cart')`)
- **Authenticated users:** Cart stored in database (`Cart` and `CartItem` models)
- **Merge logic:** Guest cart merges into DB cart on login (see `CartMergeTest`)

**Cart Models:**
- `Cart` - Main cart (has user_id or session_id, discount fields)
- `CartItem` - Individual cart items with product relationships

**Key Controller:** `CartController` handles both session and DB cart logic with conditional branching based on `Auth::check()`

### Product Management

**Ownership Model:**
- Products have a `provider_id` foreign key to users table
- `is_approved` field controls visibility (Admin approval workflow)
- Providers can only manage their own products
- Admins can manage all products and approve/block them

**Models Location:**
- `Modules\Products\Models\Product`
- `Modules\Products\Models\Category`

**Routing Pattern:**
- Provider routes: `/provider/products/*` (via Products module)
- Admin routes: `/admin/products/*` (via Products module)
- Storefront routes: `/shop`, `/shop/{id}` (main routes/web.php)

### Database

**Default Configuration:**
- Uses **SQLite** by default (see `.env.example`)
- Session driver: `database`
- Queue connection: `database`
- Cache store: `database`

**Key Models in app/Models:**
- `User` (with HasRoles trait)
- `Order`, `OrderItem`
- `Cart`, `CartItem`
- `Payment`, `PaymentMethod`, `Transaction`
- `UserAddress`
- `DiscountCode`
- `EmailOtp`
- Location models: `Country`, `State`, `City`

### Frontend Stack

**Technologies:**
- **Vite** for asset bundling
- **Tailwind CSS 3** for styling
- **Alpine.js** for interactivity
- **Axios** for AJAX requests

**Asset Pipeline:**
- Main inputs defined in `vite.config.js`
- Module-specific JS files included (e.g., `Modules/Admin/resources/js/users.js`)
- Blade views use `@vite()` directive

**Admin DataTables:**
- Uses `yajra/laravel-datatables-oracle` for server-side tables
- Pattern: AJAX endpoint returns JSON, view renders DataTable
- Example: Admin users list uses `/admin/users/data` endpoint

## Important Patterns

### Module Controllers vs App Controllers

- **Module controllers:** Located in `Modules/{Name}/app/Http/Controllers/`
- **App controllers:** Located in `app/Http/Controllers/`
- Some functionality (like cart, checkout) lives in app controllers even though modules exist
- Dashboard controllers for admin/provider are in app controllers but views are in modules

### Status Field Pattern

- User model has `status` field for verification ('verified', 'pending', null)
- Status is only used for 'user' role (admin/provider have null status)
- Model boot method enforces this constraint

### Middleware Aliases

Defined in `bootstrap/app.php`:
- `role` - Spatie RoleMiddleware
- `permission` - Spatie PermissionMiddleware  
- `ensure_role` - Custom EnsureRoleForPath (use this for routes)
- `sync_rbac` - SyncRoleSession (auto-applied)

### Dashboard Routing

Central `/dashboard` route redirects by role:
- Admin → `/admin/dashboard`
- Provider → `/provider/dashboard`
- User → `/` (home)

Never hardcode dashboard URLs - let the redirect handle it.

## Testing Notes

- Uses **Pest PHP** (see `tests/Pest.php`)
- Feature tests use `RefreshDatabase` trait
- Test pattern: Guest vs authenticated user scenarios
- Cart merge testing is critical (see `CartMergeTest`)

## Payment Integration

Supported gateways (configured via `PaymentMethod` model):
- Stripe (`stripe/stripe-php`)
- Razorpay (`razorpay/razorpay`)
- Cash on Delivery (COD)

## Common Pitfalls

1. **Don't manually include module routes** - They're auto-loaded by service providers
2. **Always check Auth::check() for cart operations** - Dual storage requires branching
3. **Use `ensure_role` middleware, not `role`** - Custom middleware has better UX (redirects instead of 403)
4. **Product images use accessor** - `Product::image_url` attribute handles placeholder logic
5. **Discount codes affect cart items** - Check both cart-level and item-level discount logic
6. **Seeders are commented out** - Uncomment in DatabaseSeeder if needed for demo data
