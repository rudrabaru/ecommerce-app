# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Development Commands

### Environment Setup
```bash
# Copy environment file
cp .env.example .env

# Install PHP dependencies
composer install

# Install Node.js dependencies  
npm install

# Generate application key
php artisan key:generate

# Run database migrations
php artisan migrate
```

### Development Server
```bash
# Start all development services (server, queue, logs, and Vite)
composer dev

# Alternative: Start individual services
php artisan serve              # Laravel development server
php artisan queue:listen --tries=1  # Queue worker
php artisan pail --timeout=0        # Log viewer
npm run dev                    # Vite development server
```

### Asset Management
```bash
# Build assets for development
npm run dev

# Build assets for production
npm run build
```

### Testing
```bash
# Run all tests
composer test
# OR
php artisan test

# Run specific test suite
vendor/bin/pest tests/Feature
vendor/bin/pest tests/Unit

# Run tests with coverage
vendor/bin/pest --coverage
```

### Code Quality
```bash
# Format code with Laravel Pint
vendor/bin/pint

# Check code formatting
vendor/bin/pint --test
```

### Module Management
```bash
# List all modules
php artisan module:list

# Create new module
php artisan module:make ModuleName

# Enable/disable module
php artisan module:enable ModuleName
php artisan module:disable ModuleName

# Generate module components
php artisan module:make-controller ModuleName ControllerName
php artisan module:make-model ModuleName ModelName
php artisan module:make-migration ModuleName create_table_name
```

### Database Operations
```bash
# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Fresh migration with seeding
php artisan migrate:fresh --seed

# Create new migration
php artisan make:migration create_table_name
```

## Architecture Overview

This is a **Laravel 12** ecommerce application built with a **modular architecture** using the `nwidart/laravel-modules` package. The application separates business logic into distinct, self-contained modules.

### Core Technology Stack
- **Framework**: Laravel 12 (PHP 8.2+)
- **Frontend**: Vite + Tailwind CSS + Alpine.js + Laravel Breeze
- **Testing**: Pest PHP
- **Permissions**: Spatie Laravel Permission
- **DataTables**: Yajra Laravel DataTables
- **Modules**: nwidart/laravel-modules

### Modular Structure

The application follows a domain-driven modular approach with these core modules:

```
Modules/
├── Admin/          # Admin panel and management features
├── Cart/           # Shopping cart functionality  
├── Orders/         # Order management and processing
├── Payments/       # Payment processing and gateways
├── Products/       # Product catalog and categories
├── Provider/       # Vendor/supplier management
└── User/           # User management and profiles
```

#### Module Architecture
Each module is self-contained with:
- **Controllers**: HTTP request handling (`app/Http/Controllers/`)
- **Models**: Data layer (`app/Models/`)
- **Views**: Blade templates (`resources/views/`)
- **Routes**: Module-specific routing (`routes/web.php`, `routes/api.php`)
- **Migrations**: Database schema (`database/migrations/`)
- **Service Providers**: Module bootstrapping (`app/Providers/`)
- **Tests**: Module-specific tests (`tests/`)

#### Key Architectural Patterns

**Service Provider Registration**: Each module registers via `ModuleServiceProvider` 
**Route Organization**: Separate web and API routes per module
**View Organization**: Module views can extend layouts or be standalone
**Database Migrations**: Module-specific migrations with automatic discovery
**Configuration**: Per-module config files in `config/config.php`

### Admin Module Specifics
The Admin module is the most comprehensive, featuring:
- **Dashboard**: Analytics and overview components
- **User Management**: CRUD operations with role-based access
- **Media Management**: File upload and organization
- **Settings**: Application configuration panels
- **Demo Components**: UI component showcase and testing

### Frontend Architecture
- **Tailwind CSS**: Utility-first CSS framework
- **Alpine.js**: Lightweight JavaScript framework for interactivity
- **Vite**: Modern frontend build tool
- **Blade Components**: Reusable UI components in `resources/views/components/`

### Important Configuration Files
- `modules_statuses.json`: Controls which modules are active
- `config/modules.php`: Module system configuration
- `composer.json`: Contains custom script commands (`dev`, `test`)
- `vite.config.js`: Asset compilation configuration

### Development Workflow
1. **Module Creation**: Use `php artisan module:make` for new features
2. **Component Development**: Build within module structure
3. **Testing**: Each module has dedicated test directories
4. **Asset Building**: Vite handles CSS/JS compilation
5. **Database**: Module migrations are auto-discovered

### Module Interdependencies
- **Admin** module provides management interfaces for other modules
- **Products** module is foundational for Cart, Orders, and Payments
- **User** module provides authentication base for Admin and Provider
- **Provider** module manages product suppliers

This modular approach enables independent development, testing, and deployment of features while maintaining clean separation of concerns.