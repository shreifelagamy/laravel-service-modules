# Laravel Service Modules Generator

![Laravel Service Modules](images/laravel-service-modules.png)

A Laravel package to easily generate service modules for your Laravel applications.

## Installation

You can install the package via composer:

```bash
composer require shreifelagamy/laravel-service-modules
```

## Usage

```bash
php artisan service:generate UserService
```

This will create a new service class in the `app/Services` directory.

During the generation process, you will be prompted to:
1. Generate an exception for the service
2. Add methods to the repository

These prompts allow you to customize the service module according to your needs.

The generated service structure will look like this:

```
app/Services/UserService/
├── Providers/
│ └── UserServiceProvider.php
├── Repositories/
│ ├── UserServiceInterface.php
│ └── UserServiceRepository.php
├── Facades/
│ └── UserService.php
└── Exceptions/ (optional)
  └── UserServiceException.php
```

## Configuration

The package comes with a default configuration file that you can publish to customize the behavior:

```bash
php artisan vendor:publish --tag=laravel-service-modules-config
```

This will create a `config/laravel-service-modules.php` file where you can modify the default settings.

## Customization

You can control the directory name for generated services through the config file. This allows you to customize where your service modules are created within your Laravel application.

## License

The Laravel Services Generator is open-sourced software licensed under the MIT license.



