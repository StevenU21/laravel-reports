# Installation

You can install the package via Composer:

```bash
composer require deifhelt/laravel-reports
```

## Publishing Configuration

If you wish to customize the package configuration, you can publish the configuration file using:

```bash
php artisan vendor:publish --tag="laravel-reports-config"

```

or

```bash
php artisan vendor:publish --provider="Deifhelt\LaravelReports\LaravelReportsServiceProvider"
```

This will create a `config/reports.php` file in your application where you can adjust available parameters.
