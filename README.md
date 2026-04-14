# Calculator

A web-based calculator built with Laravel 13, Livewire 4, Tailwind CSS 4, and SQLite.

## Features

- Basic arithmetic: addition, subtraction, multiplication, division
- Operator precedence (multiplication/division before addition/subtraction)
- Calculation history persisted to SQLite database
- Click a history entry to load its result for continued calculation
- Responsive layout (side-by-side on desktop, stacked on mobile)
- Dark mode support

## Requirements

- PHP 8.3+
- Node.js 22+
- Composer

## Setup

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate

# Build frontend assets
npm run build
```

## Running

```bash
php artisan serve
```

Then visit [http://127.0.0.1:8000](http://127.0.0.1:8000).

## Testing

```bash
php artisan test --compact
```

37 tests covering the expression evaluator (unit) and Livewire component interactions (feature).
