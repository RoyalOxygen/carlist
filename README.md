# Carlist

Car listing marketplace built with Laravel 9.

## Requirements

- PHP 8.2+
- MySQL 5.7+ / MariaDB 10.3+
- PHP extensions: OpenSSL, PDO, Mbstring, Tokenizer, JSON, cURL, Fileinfo, Exif, Mysqli
- Composer

## Installation

1. Copy the project files to your web server.
2. Point the document root to the `public` directory.
3. Make sure these paths are writable:
   - `storage/framework/`
   - `storage/logs/`
   - `bootstrap/cache/`
4. Open `/install` in your browser.
5. Follow the wizard:
   - Server requirements
   - Folder permissions
   - App name, URL, and database credentials
6. After installation, log in at `/admin`.

Default admin credentials from the seed data:

- Username: `admin`
- Password: `admin`

Change the admin password immediately after the first login.

## Manual setup (optional)

If you prefer not to use the web installer:

```bash
cp .env.example .env
composer install --no-dev --optimize-autoloader
php artisan key:generate
```

Create an empty MySQL database, then import `database/installer/database.sql`.

Mark the app as installed:

```bash
touch storage/installed
```

## Security notes

- Keep `APP_DEBUG=false` in production.
- Do not expose `.env` or `storage/` from the web server.
- Put payment gateway keys in the admin panel or environment variables, never in public files.
- The installer is disabled automatically after `storage/installed` exists.

## License

This project is provided without CodeCanyon / Envato license checks.
