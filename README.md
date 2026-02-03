# Mailpit API Client for PHP ![Packagist Version](https://img.shields.io/packagist/v/libresign/mailpit-client)

A simple PHP (8.2+) client for [Mailpit][mailpit], the modern replacement for [Mailhog][mailhog].

## Design Goals

- As little dependencies as possible (PHP-HTTP compliant via HTTPlug)
- Simple single client for Mailpit API
- Integration tests on all endpoints, both happy path and failure paths

## Installation

This package does not require any specific HTTP client implementation, but is based on [HTTPlug][httplug], so you can inject your own HTTP client of choice. So you when you install this library make sure you either already have an HTTP client installed, or install one at the same time as installing this library, otherwise installation will fail.

```bash
composer require libresign/mailpit-client <your-http-client-of-choice>
```

For more information please refer to the [HTTPlug documentation for Library Users][httplug-docs].

## Usage

```php
<?php

use LibreSign\Mailpit\MailpitClient;

$client = new MailpitClient(new SomeHttpClient(), new SomeRequestFactory(), 'http://my.mailpit.host:port/');
```

Where `SomeHttpClient` is a class that implements `Http\Client\HttpClient` from HTTPlug and `SomeRequestFactory` is a class that implements `Http\Message\RequestFactory` from HTTPlug, and `my.mailpit.host` is the hostname (or IP) where Mailpit is running, and `port` is the port where the Mailpit API is running (by default 1025).

## Run tests

Make sure you have Mailpit running and run:

```bash
composer cs:check
composer phpmd
composer phpstan
composer test:coverage
composer test:integration
composer test:unit
```

### Running Mailpit for tests

You can either run your own instance of Mailpit or use the provided docker-compose file to run one for you.
To run Mailpit with Docker make sure you have Docker installed and run:

```bash
docker-compose up -d
```

### Mailpit ports for tests

The tests expect Mailpit to listen to SMTP on port 2025 and to HTTP traffic on port 9025.

If you want different ports you can copy `phpunit.xml.dist` to `phpunit.xml` and change the port numbers in the environment variables therein.

[mailpit]: https://mailpit.axllent.org/
[mailhog]: https://github.com/mailhog/MailHog
[httplug]: https://github.com/php-http/httplug
[httplug-docs]: http://docs.php-http.org/en/latest/httplug/users.html
