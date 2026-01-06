## Tech Stack

- **Laravel** ^12.0
- **PHP** 8.2.*

### Core Packages

#### Authentication & Authorization

- **Laravel Passport** ^13.0 - OAuth2 server implementation for API authentication
- **Spatie Laravel Permission** ^6.23 - Role and permission management

#### Media & File Management

- **Spatie Laravel Media Library** ^11.0 - Associate files with Eloquent models

#### API & Query Building

- **Spatie Laravel Query Builder** ^6.3 - Build Eloquent queries from API requests

#### Validation & Utilities

- **Propaganistas Laravel Phone** ^6.0 - Phone number validation and formatting

## Prerequisites

- PHP 8.2.*
- Composer
- Node.js >= 18.x
- NPM or Yarn
- MySQL/MariaDB
- Git

## Installation

### 1. cd to the Repository

```bash
cd ai-services.api
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

```

### 3. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Configure Database

Edit `.env` file with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
```

### 5. Run Migrations & Seeders

```bash
# Run migrations
php artisan migrate

# Seed database with initial data
php artisan db:seed

# Generate the App Key
php artisan key:generate
```

### 6. Generate OAuth2 Keys

```bash
# Generate Passport encryption keys (stored in storage/ directory)
php artisan passport:keys

# Create password grant client
php artisan passport:client --password
```

Copy the **Client ID** and **Client Secret** from the output and add them to your `.env` file:

```env
PASSPORT_PASSWORD_CLIENT_ID=your_client_id
PASSPORT_PASSWORD_CLIENT_SECRET=your_client_secret
```

### 7. Optimize Application

```bash
# Clear All Caches
php artisan optimize:clear
# Clear and cache config
php artisan config:cache

# Optional: Cache routes for production
php artisan route:cache
```

## Running the Application

### Development Mode

```bash
# Start Laravel development server
php artisan serve

# Application will be available at http://127.0.0.1:8000
```

## API Documentation

Once the application is running, you can:

- Import `postman_collection.json` into Postman for API testing
- Access API endpoints at `http://127.0.0.1:8000/api/v1/`
# ai-services-api
